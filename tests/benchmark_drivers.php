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

ini_set('include_path',ini_get('include_path').':../../');
require_once 'DBA.php';
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

$prefix = './';

function getmicrotime(){ 
    list($usec, $sec) = explode(" ",microtime()); 
    return((float)$usec + (float)$sec); 
} 

foreach (DBA::getDriverList() as $driver) {
    echo "Benchmarking driver: $driver\n";
    $testDB = DBA::create($driver);

    foreach ($maxTestKeys as $maxTestKey) {

        $dat_fp = fopen($prefix."{$driver}_{$maxTestKey}.dat", 'w');
        for ($transactions=$transactionsInterval;
            $transactions <= $maxTransactions;
            $transactions+=$transactionsInterval) {

            $result = $testDB->open($prefix.'benchmark_db_'.$driver, 'n');
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
                            if (!$testDB->exists($testKey)) {
                                ++$actualTransactions;
                                $testDB->insert($testKey, $testData);
                            }
                            break;
                        case 1:
                            if ($testDB->exists($testKey)) {
                                ++$actualTransactions;
                                $testDB->remove($testKey);
                            }
                            break;
                        case 2:
                            $testDB->replace($testKey, $testData);
                            break;
                        case 3:
                            if ($testDB->exists($testKey)) {
                                ++$actualTransactions;
                                $testDB->fetch($testKey);
                            }
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
    $graph_fp = fopen($prefix.$driver.'_graph', 'w');
    fwrite($graph_fp, substr($graph_data, 0, -3));
    fclose($graph_fp);
}
?>
