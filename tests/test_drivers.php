<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2003 Brent Cook                                        |
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

// test functionality of the file-based dba driver

ini_set('include_path',ini_get('include_path').':../../');
include 'PEAR.php';
include 'DBA.php';

$testDataArray = array ('1', '22', '333', '4444', '55555', '666666',
                        '7777777', '88888888', '999999999');

$maxDataIndex = sizeof($testDataArray)-1;

foreach (DBA::getDriverList() as $driver) {
echo "Testing $driver\n\n";
$testDB =& DBA::create($driver);

if (PEAR::isError($error=$testDB->open('file_test', 'c'))) {
    echo $error->getMessage()."\n";
    exit;
}

// main testing loop
for ($i=0; $i<5000; ++$i) {
    $testKey = rand (0, 200);
    $testData = $testDataArray[rand(0, 8)];
    switch (rand(0, 3)) {
        case 0:
            if (!$testDB->exists($testKey)) {
                $result = $testDB->insert($testKey, $testData);
            }
            break;
        case 1:
            if ($testDB->exists($testKey)) {
                $result = $testDB->remove($testKey);
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
$result = $testDB->close();
if (PEAR::isError($result)) {
    echo $result->getMessage();
    exit;
}

$testDB->open('file_test', 'r');
$key = $testDB->firstkey();
while ($key !== FALSE) {
    echo "$key = ".$testDB->fetch($key)."\n";
    $key = $testDB->nextkey($key);
}

$result = $testDB->close();
if (PEAR::isError($result)) {
    echo $result->getMessage();
    exit;
}
$result = $testDB->drop();
if (PEAR::isError($result)) {
    echo $result->getMessage();
    exit;
}
}

?>
