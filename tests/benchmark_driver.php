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

ini_set('include_path',ini_get('include_path').':../../');
require_once 'DB_DBA/DBA.php';
require_once 'PEAR.php';

$testDataArray = array ('11111111111111111111',
                        '222222222222222222222222',
                        '3333333333333333333333333333',
                        '44444444444444444444444444444444',
                        '555555555555555555555555555555555555',
                        '6666666666666666666666666666666666666666',
                        '77777777777777777777777777777777777777777777',
                        '888888888888888888888888888888888888888888888888',
                        '9999999999999999999999999999999999999999999999999999');

$maxDataIndex = sizeof ($testDataArray)-1;

$maxTestKeys = array(1600, 3200, 6400, 12800, 25600);

$transactionsInterval = 2000;

$maxTransactions = $transactionsInterval * 8;

$driver = 'db3';

$testDB = DBA::create($driver);

function getmicrotime(){ 
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
} 

foreach ($maxTestKeys as $maxTestKey) {

    $dat_fp = fopen("{$driver}_{$maxTestKey}.dat", 'w');
    for ($transactions=$transactionsInterval; $transactions <= $maxTransactions;
            $transactions+=$transactionsInterval) {

        $result = $testDB->open('benchmark_db', 'n');
        if (PEAR::isError($result)) {
            echo $result->getMessage()."\n";
        } else {
            // only measure successful transactions
            $actualTransactions = 0;

            // begin stopwatch
            $start = getmicrotime();

            for ($i=0; $i<$transactions; ++$i) {
                $testKey = rand (0, $maxTestKey);
                $testData = $testDataArray[rand(0, $maxDataIndex)];
                switch (rand(0, 3)) {
                    case 0:
                        $result = @$testDB->insert($testKey, $testData);
                        break;
                    case 1:
                        $result = @$testDB->delete($testKey);
                        break;
                    case 2:
                        $result = @$testDB->replace($testKey, $testData);
                        break;
                    case 3:
                        $result = @$testDB->fetch($testKey);
                }
                if ($result) {
                    ++$actualTransactions;
                }
            }
            $testDB->close();
        }

        // end stopwatch
        $stop = getmicrotime();
        $line = $actualTransactions.' '.($stop - $start)."\n";
        echo "Keys: $maxTestKey Transactions: $line";
        fwrite($dat_fp, $line);
    }
    fclose($dat_fp);
}

// make a gnuplot data file
$graph_data = <<<EOT
set size 1.0, 1.0
set terminal postscript portrait "Helvetica" 12
set title "driver: $driver"
set xlabel "# of transactions"
set ylabel "time in seconds"
set out "$driver.ps"
plot
EOT;

foreach ($maxTestKeys as $maxTestKey) {
    $graph_data .= " \"{$driver}_{$maxTestKey}.dat\" using 1:2 title '".
                   "$driver, $maxTestKey keys' with lines,\\\n";
}

// write the gnuplot data file, trimming off that last comma :P
$graph_fp = fopen($driver.'_graph', 'w');
fwrite($graph_fp, substr($graph_data, 0, -3));
fclose($graph_fp);

?>
