<?php

    // test functionality of the simple dba layer

    include 'PEAR.php';
	include '../DBA_Simple.php';

	$testDataArray = array ('1', '22', '333', '4444', '55555', '6666666', '7777777', '88888888', '999999999');
	
	$maxDataIndex = sizeof ($testDataArray)-1;

	$testDB = new DBA_Simple();

    if (PEAR::isError($error=$testDB->open('test', 'c')))
    {
        echo $error->getMessage()."\n";
    } else {
        for ($i=0; $i<1000; ++$i) {
            $testKey = rand (0, 99);
            $testData = $testDataArray[rand(0, 3)];
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
    $testDB->close();
?>
