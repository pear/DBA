<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Brent Cook <busterb@mail.utexas.edu>                         |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'PEAR.php';

/**
 * DBA_Builtin uses the builtin dba functions of PHP as the underlying driver
 * for a DBA class. Depending on the driver, this can be faster or slower than
 * the DBA_Simple class.
 *
 * This class has been tested with DB3 and GDBM. Other drivers may have quirks
 * that this class does not address yet.
 *
 * @author  Brent Cook
 * @version 0.0.11
 * @access  public
 * @package DBA
 */
class DBA_Builtin extends PEAR{

    /**
     * Name of the database
     * @access private
     */
    var $_dbName;

    /**
     * Indicates the current ability for read/write operations
     * @access private
     */
    var $_writable;

    /**
     * Indicates the current ability for read operations
     * @access private
     */
    var $_readable;

    /**
     * Name of the builtin dba driver to use
     * @access private
     */
    var $_driver = NULL;

    /**
     * Indicates the ability of the dba driver to replace values
     * @access private
     */
    var $_hasReplace;

    /* Constructor
     *
     * @access public
     * @param   string  $driver dba driver to use
     */
    function DBA_Builtin ($driver = 'gdbm')
    {
        $this->_driver = $driver;
    }

    /**
     * Opens a database.
     *
     * @access public
     * @param   string  $dbName The name of a database
     * @param   string  $mode The mode in which to open a database.
     *                   'r' opens read-only.
     *                   'w' opens read-write.
     *                   'n' creates a new database and opens read-write.
     *                   'c' creates a new database if the database does not
     *                      exist and opens read-write.
     * @param   string  $driver dba driver to use
     * @returns object PEAR_Error on failure
     */
    function open ($dbName='', $mode='r', $driver=NULL)
    {
        if (!is_null($driver)) {
            $this->_driver = $driver;
        }

        if (is_null($this->_driver)) {
            return $this->raiseError('DBA: No dba driver specified');
        }

        if ($this->_driver == 'gdbm') {
            $this->_hasReplace = false;
        } else {
            $this->_hasReplace = true;
        }

        if ($dbName == '') {
            return $this->raiseError('DBA: No database name specified');
        } else {
            $this->_dbName = $dbName;
        }

        switch ($mode)
        {
            case 'r':
                    // open for reading
                    $this->_writable = false;
                    $this->_readable = true;
                    break;
            case 'n':
            case 'c':
            case 'w':
                    $this->_writable = true;
                    $this->_readable = true;
                    break;
            default:
                return $this->raiseError("DBA: Invalid file mode: $mode", E_USER_ERROR);
        }

        // open the index file
        $this->_dba = dba_open($dbName, $mode, $this->_driver);
        if ($this->_dba === false) {
            $this->_writable = false;
            $this->_readable = false;
            return $this->raiseError("DBA: Could not open database: $dbName"
                          ." with mode $mode");
        }
    }

    /**
     * Closes an open database.
     *
     * @access public
     * @returns object PEAR_Error on failure
     */
    function close ()
    {
        if ($this->isOpen())
        {
            $this->_readable = false;
            $this->_writable = false;
            dba_close($this->_dba);
        } else {
            return $this->raiseError('DBA: No database was open');
        }
    }

    /**
     * Reopens an already open database in read-only or write mode.
     * If the database is already in the requested mode, then this function
     * does nothing.
     *
     * @access public
     * @param   string  $mode 'r' for read-only, 'w' for read/write
     * @returns object PEAR_Error on failure
     */
    function reopen ($mode)
    {
        if ($this->isOpen())
        {
            if (($mode == 'r') && $this->isWritable())
            {
                // Reopening as read-only
                $this->close();
                return $this->open($this->_dbName, 'r');
            } elseif (($mode == 'w') && (!$this->isWritable)) {
                // Reopening as read-write
                $this->close();
                return $this->open($this->_dbName, 'w');
            }
        } else {
            return $this->raiseError('DBA: No database was open');
        }
    }

    /**
     * PEAR emulated destructor calls close on PHP shutdown
     * @access private
     */
    function _DBA_Builtin()
    {
        $this->close();
    }

    /**
     * Returns the current read status for the database
     *
     * @access public
     * @returns boolean
     */
    function isOpen()
    {
        return ($this->_readable || $this->_writable);
    }

    /**
     * Returns the current read status for the database
     *
     * @access public
     * @returns boolean
     */
    function isReadable()
    {
        return $this->_readable;
    }

    /**
     * Returns the current write status for the database
     *
     * @access public
     * @returns boolean
     */
     function isWritable()
     {
         return $this->_writable;
     }

    /**
     * Deletes the value at location $key
     *
     * @access public
     * @param   string  $key key to delete
     * @returns object PEAR_Error on failure
     */
    function delete($key)
    {
        if ($this->isWritable())
        {
            if (!dba_delete($key, $this->_dba)) {
                return $this->raiseError('DBA: cannot delete key: '.
                               $key. ', it does not exist');
            }
        } else {
            return $this->raiseError('DBA: cannot delete key '.
                           $key. ', DB not writable');
        }
    }

    /**
     * Returns the value that is stored at $key.
     *
     * @access public
     * @param   string $key key to examine
     * @returns mixed  the requested value on success, false on failure
     */
    function fetch($key)
    {
        if ($this->isReadable())
        {
            if (dba_exists($key, $this->_dba)) {
                return dba_fetch($key, $this->_dba);
            } else {
                return $this->raiseError('DBA: cannot fetch key '.$key.
                              ', it does not exist');
            }
        } else {
            return $this->raiseError('DBA: cannot fetch '.$key.' on '.
                          $this->_dbName. ', DB not readable');
        }
    }

    /**
     * Returns the first key in the database
     *
     * @access public
     * @returns mixed string on success, false on failure
     */
    function firstkey()
    {
        if ($this->isReadable() && ($this->size() > 0))
        {
            return dba_firstkey($this->_dba);
        } else {
            return false;
        }
    }

    /**
     * Returns the next key in the database, false if there is a problem
     *
     * @access public
     * @returns mixed string on success, false on failure
     */
    function nextkey()
    {
        if ($this->isReadable())
        {
            return dba_nextkey($this->_dba);
        } else {
            return false;
        }
    }

    /**
     * Inserts a new value at $key. Will not overwrite if the key/value pair
     * already exist
     *
     * @access public
     * @param   string  $key key to insert
     * @param   string  $value value to store
     * @returns object PEAR_Error on failure
     */
    function insert($key, $value)
    {
        if ($this->isWritable()) {

            if ((!$this->_hasReplace && dba_exists($key, $this->_dba)) ||
                (!dba_insert($key, $value, $this->_dba))) {
                return $this->raiseError('DBA: cannot insert on key: '.
                               $key. ', it already exists');
            }
        } else {
            return $this->raiseError('DBA: cannot replace on '.
                          $this->_dbName. ', DB not writable');
        }
    }

    /**
     * Inserts a new value at key. If the key/value pair
     * already exist, overwrites the value
     *
     * @access public
     * @param   $key    string the key to insert
     * @param   $val    string the value to store
     * @returns object PEAR_Error on failure
     */
    function replace($key, $value)
    {
        if ($this->isWritable()) {

            if ($this->_hasReplace) {
                return dba_replace($key, $value, $this->_dba);
            } else {
                $r = true;
                if (dba_exists($key, $this->_dba)) {
                    $r = dba_delete($key, $this->_dba);
                }
                return $r && dba_insert($key, $value, $this->_dba);
            }

        } else {
            return $this->raiseError('DBA: cannot replace on '.
                          $this->_dbName. ', DB not writable');
        }
    }
    
    /**
     * Creates a new database file if one does not exist. If it already exists,
     * updates the last-updated timestamp on the database
     *
     * @access public
     * @param   string  $dbName the database to create
     * @param   string  $driver the dba driver to use
     * @returns object PEAR_Error on failure
     */
    function create($dbName, $driver='gdbm')
    {
        $db = dba_open($dbName, 'n', $driver);
        if (!(($db !== false) && dba_close($db))) {
            return $this->raiseError('DBA: Could not create database: '.$dbName);
        }
    }

    /**
     * Indicates whether a database with given name exists
     *
     * @access public
     * @param   string  $dbName the database name to check for existence
     * @returns boolean
     */
    function db_exists($dbName)
    {
        return file_exists($dbName);
    }

    /**
     * Check whether key exists
     *
     * @access public
     * @param   string   $key
     * @returns boolean
     */
    function exists($key)
    {
        return ($this->isOpen() && dba_exists($key, $this->_dba));
    }

    /**
     * Synchronizes an open database to disk
     * @access public
     */
    function sync()
    {
        return dba_sync($this->_dba);
    }

    /**
     * Optimizes an open database
     * @access public
     */
    function optimize()
    {
        return dba_optimize($this->_dba);
    }
}
?>