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
// | Authors: Brent Cook <busterb@mail.utexas.edu>                        |
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
    * @version 0.0.12
    * @param  string $driver  Type of storage object to return
    * @return object DBA storage object by reference
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
