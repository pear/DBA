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
//

require_once('PEAR.php');

/**
 * DBA is a set of classes for handling and extending Berkeley DB style
 * databases. It works around some of the quirks in the built-in dba
 * functions in PHP (e.g. gdbm does not support dba_replace), has a file-based
 * dbm engine for installations where dba support is not included in PHP.
 *
 * @author  Brent Cook <busterb@mail.utexas.edu>
 * @version 0.9.4
 * @access  public
 * @package DBA
 */
class DBA extends PEAR
{
    /**
     * Default constructor
     */
    function DBA()
    {
        // call the base constructor
        $this->PEAR();
    }

    /**
     * Creates a new DBA object
     *
     * @static
     * @param   string $driver type of storage object to return
     * @return  object DBA storage object, returned by reference
     */
    function &create($driver = 'file')
    {
        if (!function_exists('dba_open') || ($driver=='file')) {
            require_once 'DBA/Driver/File.php';
            return new DBA_Driver_File();
        } elseif (($driver == 'db3') || ($driver == 'gdbm')) {
            require_once 'DBA/Driver/Builtin.php';
            return new DBA_Driver_Builtin($driver);
        } else {
            return PEAR::raiseError('Unknown DBA driver, '.$driver);
        }
    }

    function raiseError($message) {
        return PEAR::raiseError('DBA: '.$message);
    }

    /**
     * Returns whether a database exists
     *
     * @param  string $name name of the database to find
     * @param  string @driver driver to test for
     * @return boolean true if the database exists
     */
    function db_exists($name, $driver = 'file')
    {
        if (!function_exists('dba_open') || ($driver=='file')) {
            return (file_exists($name.'.dat') && file_exists($name.'.idx'));
        } elseif (($driver == 'db3') || ($driver == 'gdbm')){
            return file_exists($name);
        } else {
            return PEAR::raiseError('Unknown DBA driver, '.$driver);
        }
    }

    /**
     * Returns an array of the currently supported drivers
     *
     * @return array
     */
    function getDriverList()
    {
        return array('gdbm', 'db3', 'file');
    }
}
?>
