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

// test functionality of the simple dba layer

ini_set('include_path',ini_get('include_path').':../../');
include 'PEAR.php';
include 'DB/DBA/DBA_Simple.php';

$testDataArray = array ('1', '22', '333', '4444', '55555', '6666666', '7777777', '88888888', '999999999');

$maxDataIndex = sizeof ($testDataArray)-1;

$testDB =& new DBA_Simple();

if (PEAR::isError($error=$testDB->open('test', 'c')))
{
    echo $error->getMessage()."\n";
} else {
    for ($i=0; $i<5000; ++$i) {
        $testKey = rand (0, 99);
        $testData = $testDataArray[rand(0, 8)];
        switch (rand(0, 3)) {
            case 0:
                if (!$testDB->exists($testKey)) {
                    $result = $testDB->insert($testKey, $testData);
                }
                break;
            case 1:
                if ($testDB->exists($testKey)) {
                    $result = $testDB->delete($testKey);
                }
                break;
            case 2:
                $result = $testDB->replace($testKey, $testData);
                break;
            case 3:
                if ($testDB->exists($testKey)) {
                    $result = $testDB->fetch($testKey);
                }
        }
        if (PEAR::isError($result)) {
            echo $result->getMessage()."\n";
        }
    }
    $testDB->close();
}

$testDB->open('test', 'r');
$key = $testDB->firstkey();
while ($key !== FALSE) {
    echo "$key = ".$testDB->fetch($key)."\n";
    $key = $testDB->nextkey($key);
}

//$testDB->close();

?>
