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
 * @author Brent Cook <busterb@mail.utexas.edu>
 * @version 0.1
 * @see PHP dba Documentation
 */

require_once ('DBA_simple.php');

if (!function_exists('dba_open')) {

	function dba_close(&$dba)
	{
		$result = $dba->close();
        unset($dba);
        return $result;
	}

	function dba_delete($key, &$dba)
	{
		return $dba->delete($key);
	}

	function dba_exists($key, &$dba)
	{
		return $dba->exists($key);
	}

	function dba_fetch($key, &$dba)
	{
        return $dba->fetch($key);
	}

	function dba_firstkey(&$dba)
	{
		return $dba->firstkey();
	}

	function dba_insert($key, $value, &$dba)
	{
		return $dba->insert($key, $value);
	}

	function dba_nextkey(&$dba)
	{
		return $dba->nextkey($key);
	}

	function dba_open($filename, $mode, $handler)
	{
		$dba = new DBA_Simple();
		$dba->open($filename, $mode);
		return $dba;
	}

    function dba_optimize()
    {
        return $dba->optimize();
    }

    function dba_popen()
    {
        return FALSE;
    }

	function dba_replace($key, $value, &$dba)
	{
		return $dba->replace($key, $value);
	}

    function dba_sync()
    {
        return $dba->sync();
    }
}
?>
