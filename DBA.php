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

/**
 * DBA is a set of classes for handling and extending Berkeley DB style
 * databases. It works around some of the quirks in the built-in dba
 * functions in PHP (e.g. gdbm does not support dba_replace), has its own
 * dbm engine for installations where dba support is not included in PHP.
 *
 * @author  Brent Cook <busterb@mail.utexas.edu>
 * @version 0.17
 * @access  public
 * @package DBA
 */
class DBA
{
    /**
    * Creates a new DBA object
    *
    * @static
    * @param   string $driver type of storage object to return
    * @return  object DBA storage object, returned by reference
    */
    function &create($driver = 'simple')
    {
        if (!function_exists('dba_open') || ($driver=='simple')) {
            require_once 'DB/DBA/DBA_Simple.php';
            return new DBA_Simple();
        } else {
            require_once 'DB/DBA/DBA_Builtin.php';
            return new DBA_Builtin($driver);
        }
    }
}
?>
