<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2002 Brent Cook                                        |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA|
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'DBA.php';

/**
 * DBA_Driver_Builtin uses the builtin dba functions of PHP as the underlying driver
 * for a DBA class. Depending on the driver, this can be faster or slower than
 * the DBA_Simple class.
 *
 * This class has been tested with DB3 and GDBM. Other drivers may have quirks
 * that this class does not address yet. CDB is known to be unsuitable as a
 * driver due to its lack of writes. DB2 apparently segfaults PHP?
 *
 * @author  Brent Cook <busterb@mail.utexas.edu>
 * @version 0.9
 * @access  public
 * @package DBA
 */
class DBA_Driver_Builtin extends DBA
{

    // {{{ instance variables
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
    var $_driver;

    /**
     * Indicates the ability of the dba driver to replace values
     * @access private
     */
    var $_hasReplace;
    // }}}

    // {{{ DBA_Driver_Builtin($driver = 'gdbm')
    /* Constructor
     *
     * @access public
     * @param   string  $driver dba driver to use
     */
    function DBA_Driver_Builtin($driver = 'gdbm')
    {
        $this->_driver = $driver;
    }
    // }}}

    // {{{ open($dbName='', $mode='r', $driver=NULL)
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
     * @param   boolean $persistent Determines whether to open the database
     *                  peristently. Not supported here.
     * @return  object PEAR_Error on failure
     */
    function open($dbName='', $mode='r', $persistent = false)
    {
        if (is_null($this->_driver)) {
            return $this->raiseError('No dba driver specified');
        }

        if ($this->_driver == 'gdbm') {
            $this->_hasReplace = false;
        } else {
            $this->_hasReplace = true;
        }

        if ($dbName == '') {
            return $this->raiseError('No database name specified');
        } else {
            $this->_dbName = $dbName;
        }

        switch ($mode) {
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
                return $this->raiseError("Invalid file mode: $mode",
                                          E_USER_ERROR);
        }

        // open the index file
        if ($persistent) {
            $this->_dba = dba_popen($dbName, $mode, $this->_driver);
        } else {
            $this->_dba = dba_open($dbName, $mode, $this->_driver);
        }
        if ($this->_dba === false) {
            $this->_writable = false;
            $this->_readable = false;
            return $this->raiseError("Could not open database: $dbName"
                ." with mode $mode");
        }
    }
    // }}}

    // {{{ close()
    /**
     * Closes an open database.
     *
     * @access  public
     * @return  object PEAR_Error on failure
     */
    function close()
    {
        if ($this->isOpen()) {
            $this->_readable = false;
            $this->_writable = false;
            dba_close($this->_dba);
        } else {
            return $this->raiseError('No database was open');
        }
    }
    // }}}

    // {{{ reopen($mode)
    /**
     * Reopens an already open database in read-only or write mode.
     * If the database is already in the requested mode, then this function
     * does nothing.
     *
     * @access  public
     * @param   string  $mode 'r' for read-only, 'w' for read/write
     * @return  object PEAR_Error on failure
     */
    function reopen($mode)
    {
        if ($this->isOpen()) {
            if (($mode == 'r') && $this->isWritable()) {
                // Reopening as read-only
                $this->close();
                return $this->open($this->_dbName, 'r');
            } elseif (($mode == 'w') && (!$this->isWritable)) {
                // Reopening as read-write
                $this->close();
                return $this->open($this->_dbName, 'w');
            }
        } else {
            return $this->raiseError('No database was open');
        }
    }
    // }}}

    // {{{ _DBA_Driver_Builtin()
    /**
     * PEAR emulated destructor calls close on PHP shutdown
     * @access private
     */
    function _DBA_Driver_Builtin()
    {
        $this->close();
    }
    // }}}

    // {{{ isOpen()
    /**
     * Returns the current open status for the database
     *
     * @access  public
     * @return  boolean true if open, false if closed 
     */
    function isOpen()
    {
        return($this->_readable || $this->_writable);
    }
    // }}}

    // {{{ isReadable()
    /**
     * Returns the current read status for the database
     *
     * @access  public
     * @return  boolean true if readable, false if not
     */
    function isReadable()
    {
        return $this->_readable;
    }
    // }}}

    // {{{ isWritable()
    /**
     * Returns the current write status for the database
     *
     * @access  public
     * @return  boolean true if writable, false if not
     */
     function isWritable()
     {
         return $this->_writable;
     }
    // }}}

    // {{{ remove($key)
    /**
     * Removes the value at location $key
     *
     * @access  public
     * @param   string  $key key to delete
     * @return  object PEAR_Error on failure
     */
    function remove($key)
    {
        if ($this->isWritable()) {
            if (!dba_delete($key, $this->_dba)) {
                return $this->raiseError('Cannot delete key: '.$key.
                    ', it does not exist');
            }
        } else {
            return $this->raiseError('Cannot delete key '.
                $key. ', DB not writable');
        }
    }
    // }}}

    // {{{ fetch($key)
    /**
     * Returns the value that is stored at $key.
     *
     * @access  public
     * @param   string $key key to examine
     * @return  mixed the requested value on success, false on failure
     */
    function fetch($key)
    {
        if ($this->isReadable()) {
            if (dba_exists($key, $this->_dba)) {
                return dba_fetch($key, $this->_dba);
            } else {
                return $this->raiseError('Cannot fetch key '.$key.
                    ', it does not exist');
            }
        } else {
            return $this->raiseError('Cannot fetch '.$key.' on '.
                $this->_dbName. ', DB not readable');
        }
    }
    // }}}

    // {{{ firstkey()
    /**
     * Returns the first key in the database
     *
     * @access  public
     * @return  mixed string on success, false on failure
     */
    function firstkey()
    {
        if ($this->isReadable() && ($this->size() > 0)) {
            return dba_firstkey($this->_dba);
        } else {
            return false;
        }
    }
    // }}}

    // {{{ nextkey()
    /**
     * Returns the next key in the database, false if there is a problem
     *
     * @access  public
     * @return  mixed string on success, false on failure
     */
    function nextkey()
    {
        if ($this->isReadable()) {
            return dba_nextkey($this->_dba);
        } else {
            return false;
        }
    }
    // }}}

    // {{{ insert($key, $value)
    /**
     * Inserts a new value at $key. Will not overwrite if the key/value pair
     * already exist
     *
     * @access public
     * @param   string  $key key to insert
     * @param   string  $value value to store
     * @return  object PEAR_Error on failure
     */
    function insert($key, $value)
    {
        if ($this->isWritable()) {

            if ((!$this->_hasReplace && dba_exists($key, $this->_dba)) ||
                (!dba_insert($key, $value, $this->_dba))) {
                return $this->raiseError('Cannot insert on key: '.
                    $key. ', it already exists');
            }
        } else {
            return $this->raiseError('Cannot replace on '.
                $this->_dbName. ', DB not writable');
        }
    }
    // }}}

    // {{{ replace($key, $value)
    /**
     * Inserts a new value at key. If the key/value pair
     * already exist, overwrites the value
     *
     * @access public
     * @param   $key    string the key to insert
     * @param   $val    string the value to store
     * @return  object  PEAR_Error on failure
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
            return $this->raiseError('Cannot replace on '.
                $this->_dbName. ', DB not writable');
        }
    }
    // }}}
    
    // {{{ create($dbName, $driver='gdbm')
    /**
     * Creates a new database file if one does not exist. If it already exists,
     * updates the last-updated timestamp on the database
     *
     * @access  public
     * @param   string  $dbName the database to create
     * @param   string  $driver the dba driver to use
     * @return  object  PEAR_Error on failure
     */
    function create($dbName, $driver='gdbm')
    {
        $db = dba_open($dbName, 'n', $driver);
        if (!(($db !== false) && dba_close($db))) {
            return $this->raiseError('Could not create database: '.$dbName);
        }
    }
    // }}}

    // {{{ db_exists($dbName)
    /**
     * Indicates whether a database with given name exists
     *
     * @access  public
     * @param   string  $dbName the database name to check for existence
     * @return  boolean true if the database exists, false if it doesn't
     */
    function db_exists($dbName)
    {
        return file_exists($dbName);
    }
    // }}}

    // {{{ exists($key)
    /**
     * Check whether key exists
     *
     * @access  public
     * @param   string   $key
     * @return  boolean true if the key exists, false if it doesn't
     */
    function exists($key)
    {
        return($this->isOpen() && dba_exists($key, $this->_dba));
    }
    // }}}

    // {{{ sync()
    /**
     * Synchronizes an open database to disk
     * @access public
     */
    function sync()
    {
        return dba_sync($this->_dba);
    }
    // }}}

    // {{{ optimize()
    /**
     * Optimizes an open database
     * @access public
     */
    function optimize()
    {
        return dba_optimize($this->_dba);
    }
    // }}}
}
?>
