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
// | Author: Brent Cook <busterb@mail.utexas.edu>                         |
// +----------------------------------------------------------------------+
//
// $Id$
//
class DBA
{
    /**
    * Return a DBA object
    *
    * @static
    * @version 0.0.14
    * @param  string $driver  Type of storage object to return
    * @return object          DBA storage object, returned by reference
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
