<?php
    include 'PEAR.php';
	include '../DBA.php';

	$testDataArray = array ('1111',
                            '22222222',
                            '333333333333',
                            '4444444444444444',
                            '55555555555555555555',
                            '666666666666666666666666',
                            '7777777777777777777777777777',
                            '88888888888888888888888888888888',
                            '999999999999999999999999999999999999');
	
	$maxDataIndex = sizeof ($testDataArray)-1;

	$maxTestKeys = array(1600, 3200, 6400, 12800, 25600);
	$maxTestKeys = array(1600, 3200, 6400, 12800, 25600);

    $transactionsInterval = 2000;
    
    $maxTransactions = $transactionsInterval * 8;

    $driver = 'db3';

	$testDB = DBA::create($driver);

    function getmicrotime(){ 
        list($usec, $sec) = explode(" ",microtime()); 
        return ((float)$usec + (float)$sec); 
    } 

    if (!copy('graph_template', "./data/{$driver}_graph")) {
        print ("failed to copy the graph template file\n");
    }
    $graph_fp = fopen("./data/{$driver}_graph", 'a+');
    fwrite($graph_fp, "set out \"$driver.ps\"\n plot \\\n");
    fflush($graph_fp);

    foreach ($maxTestKeys as $maxTestKey) {

        $dat_fp = fopen("./data/{$driver}_{$maxTestKey}.dat", 'w');
        for ($transactions=$transactionsInterval; $transactions <= $maxTransactions;
             $transactions+=$transactionsInterval) {

            $result = $testDB->open('benchmark_db', 'n');
            if (PEAR::isError($result)) {
                echo $result->getMessage()."\n";
            } else {
                $actualTransactions = 0;
                // begin stopwatch
                $start = getmicrotime();
                for ($i=0; $i<$transactions; ++$i) {
                    $testKey = rand (0, $maxTestKey);
                    $testData = $testDataArray[rand(0, $maxDataIndex)];
//                    switch (2) {
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
        fwrite($graph_fp, "\"{$driver}_{$maxTestKey}.dat\" using 1:2 title '$driver, $maxTestKey keys' with lines,\\\n");
        fflush($graph_fp);
    }
    fseek($graph_fp, -3, SEEK_END);
    fwrite($graph_fp, "  \n");
    fclose($graph_fp);

?>
