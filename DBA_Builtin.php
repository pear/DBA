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

/**
 * DBA_Builtin uses the builtin dba functions of PHP as the underlying driver
 * for a DBA class. Depending on the driver, this can be faster or slower than
 * the DBA_Simple class.
 *
 * @author  Brent Cook
 * @version 0.0.9
 * @access  public
 * @package DBA
 */
class DBA_Builtin {

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
     * @param   string  $driver dba driver to use
     */
    function DBA_Builtin ($driver = 'gdbm')
    {
        $this->_driver = $driver;
    }

    /**
     * Opens a database.
     *
     * @param   string  $dbName The name of a database
     * @param   string  $mode The mode in which to open a database.
     *                   'r' opens read-only.
     *                   'w' opens read-write.
     *                   'n' creates a new database and opens read-write.
     *                   'c' creates a new database if the database does not
     *                      exist and opens read-write.
     * @param   string  $driver dba driver to use
     * @returns boolean true on success, false on failure
     */
    function open ($dbName='', $mode='r', $driver=NULL)
    {
        if (!is_null($driver)) {
            $this->_driver = $driver;
        }

        if (is_null($this->_driver)) {
            trigger_error('DBA: No dba driver specified');
            return false;
        }

        if ($this->_driver == 'gdbm') {
            $this->_hasReplace = false;
        } else {
            $this->_hasReplace = true;
        }

        if ($dbName == '') {
            trigger_error('DBA: No database name specified', E_USER_WARNING);
            return false;
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
                trigger_error("DBA: Invalid file mode: $mode", E_USER_ERROR);
                return false;
        }

        // open the index file
        $this->_dba = dba_open($dbName, $mode, $this->_driver);
        if ($this->_dba === false) {
            $this->_writable = false;
            $this->_readable = false;
            trigger_error("DBA: Could not open database: $dbName"
                          ." with mode $mode");
            return false;
        }
        return true; // everything worked out
    }

    /**
     * Closes an open database.
     *
     * @returns boolean true on success, false on failure
     */
    function close ()
    {
        if ($this->isOpen())
        {
            $this->_readable = false;
            $this->_writable = false;
            dba_close($this->_dba);
            return true;
        } else {
            return trigger_error('DBA: No database was open', E_USER_WARNING);
            return false;
        }
    }

    /**
     * Reopens an already open database in read-only or write mode.
     * If the database is already in the requested mode, then this function
     * does nothing.
     *
     * @param   string  $mode 'r' for read-only, 'w' for read/write
     * @returns boolean true on success, false on failure
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
            } else {
                return true;
            }
        } else {
            trigger_error('DBA: No database was open', E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns the current read status for the database
     *
     * @returns boolean
     */
    function isOpen()
    {
        return ($this->_readable || $this->_writable);
    }

    /**
     * Returns the current read status for the database
     *
     * @returns boolean
     */
    function isReadable()
    {
        return $this->_readable;
    }

    /**
     * Returns the current write status for the database
     *
     * @returns boolean
     */
     function isWritable()
     {
         return $this->_writable;
     }

    /**
     * Deletes the value at location $key
     *
     * @param   string  $key key to delete
     * @returns boolean true on success, false on failure
     */
    function delete($key)
    {
        if ($this->isWritable())
        {
            if (!dba_delete($key, $this->_dba)) {
                trigger_error('DBA: cannot delete key: '.
                               $key. ', it does not exist', E_USER_WARNING);
                return false;
            }
        } else {
            trigger_error('DBA: cannot delete key '.
                           $key. ', DB not writable', E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns the value that is stored at $key.
     *
     * @param   string $key key to examine
     * @returns mixed  the requested value on success, false on failure
     */
    function fetch($key)
    {
        if ($this->isReadable())
        {
            if (dba_exists($key)) {
                return dba_fetch($key, $this->_dba);
            } else {
                trigger_error('DBA: cannot fetch key '.$key.
                              ', it does not exist', E_USER_WARNING);
                return false;
            }
        } else {
            trigger_error('DBA: cannot fetch '.$key.' on '.
                          $this->_dbName. ', DB not readable', E_USER_WARNING);
            return false;
        }
    }

    /**
     * Returns the first key in the database
     *
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
     * @param   string  $key key to insert
     * @param   string  $value value to store
     * @returns boolean true on success, false on failure
     */
    function insert($key, $value)
    {
        if ($this->isWritable()) {

            if ((!$this->_hasReplace && dba_exists($key, $this->_dba)) ||
                (!dba_insert($key, $value, $this->_dba))) {
                trigger_error('DBA: cannot insert on key: '.
                               $key. ', it already exists', E_USER_WARNING);
                return false;
            } else {
                return true;
            }
        } else {
            trigger_error('DBA: cannot replace on '.
                          $this->_dbName. ', DB not writable', E_USER_WARNING);
            return false;
        }
    }

    /**
     * Inserts a new value at key. If the key/value pair
     * already exist, overwrites the value
     *
     * @param   $key    string the key to insert
     * @param   $val    string the value to store
     * @returns boolean true on success, false on failure
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
            trigger_error('DBA: cannot replace on '.
                          $this->_dbName. ', DB not writable', E_USER_WARNING);
            return false;
        }
    }
    
    /**
     * Creates a new database file if one does not exist. If it already exists,
     * updates the last-updated timestamp on the database
     *
     * @param   string  $dbName the database to create
     * @param   string  $driver the dba driver to use
     * @returns boolean true on success, false on failure
     */
    function create($dbName, $driver='gdbm')
    {
        $db = dba_open($dbName, 'n', $driver);
        if (($db !== false) && dba_close($db)) {
            return true;
        } else {
            trigger_error('DBA: Could not create database: '.$dbName);
            return false;
        }
    }

    /**
     * Indicates whether a database with given name exists
     *
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
     * @param   string   $key
     * @returns boolean
     */
    function exists($key)
    {
        return ($this->isOpen() && dba_exists($key, $this->_dba));
    }

    /**
     * Synchronizes an open database to disk
     */
    function sync()
    {
        return dba_sync($this->_dba);
    }

    /**
     * Optimizes an open database
     */
    function optimize()
    {
        return dba_optimize($this->_dba);
    }
}
?>
