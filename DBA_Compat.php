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
 * dba compatibility layer
 * This works in reverse of the rest of the DBA classes. If you have code
 * that requires the PHP dba functions, but are using a system where they
 * are not available, including this file will define a set for you.
 * See the PHP documentation on dba for explanation of how these functions
 * work.
 *
 * @author Brent Cook <busterb@mail.utexas.edu>
 * @version 0.0.12
 * @see PHP dba Documentation
 */

if (!function_exists('dba_open')) {

    require_once 'PEAR.php';
    require_once 'DB_DBA/DBA_Simple.php';

	function dba_close(&$dba)
	{
		$result = !PEAR::isError($dba->close());
        unset($dba);
        return $result;
	}

	function dba_delete($key, &$dba)
	{
		return !PEAR::isError($dba->delete($key));
	}

	function dba_exists($key, &$dba)
	{
		return !PEAR::isError($dba->exists($key));
	}

	function dba_fetch($key, &$dba)
	{
        return !PEAR::isError($dba->fetch($key));
	}

	function dba_firstkey(&$dba)
	{
		return $dba->firstkey();
	}

	function dba_insert($key, $value, &$dba)
	{
		return !PEAR::isError($dba->insert($key, $value));
	}

	function dba_nextkey(&$dba)
	{
		return $dba->nextkey($key);
	}

	function dba_open($filename, $mode, $handler)
	{
		$dba = new DBA_Simple();
		$dba->open($filename, $mode);
        if (PEAR::isError($dba)) {
            return false;
        } else {
            return $dba;
        }
	}

    function dba_popen(&$dba)
    {
        return FALSE;
    }

	function dba_replace($key, $value, &$dba)
	{
		return !PEAR::isError($dba->replace($key, $value));
	}

    function dba_sync(&$dba)
    {
        $dba->sync();
    }

    function dba_optimize(&$dba)
    {
        $dba->optimize();
    }
}
?>
