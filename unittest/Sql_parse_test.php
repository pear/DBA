<?php
require_once 'Sql_parse_testcases.php';
require_once 'PHPUnit/PHPUnit.php';

$suite = new PHPUnit_TestSuite('SqlParserTest');
$result = PHPUnit::run($suite);

echo $result->toString();
?>
