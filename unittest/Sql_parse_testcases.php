<?php
require_once 'DB/DBA/Sql_parse.php';
require_once 'PHPUnit/PHPUnit.php';
require_once 'Var_Dump.php';

class SqlParserTest extends PHPUnit_TestCase {
    // contains the object handle of the parser class
    var $parser;
    //constructor of the test suite
    function SqlParserTest($name) {
        $this->PHPUnit_TestCase($name);
    }

    function setUp() {
        $this->parser = new Sql_parser();
        $this->dumper = new Var_Dump();
    }

    function tearDown() {
        unset($this->parser);
    }

    function runTests($tests) {
        foreach ($tests as $number=>$test) {
            $result = $this->parser->parse($test['sql']);
            $expected = $test['expect'];
            $message = "\nSQL: {$test['sql']}\n";
            $message .= "\nOutput: ".$this->dumper->r_display($result);
            if (PEAR::isError($result)) {
                $message .= 'Message: '.$result->getMessage()."\n";
            }
            $this->assertEquals($expected, $result, $message, $number);
        }
    }

    function testSelect() {
        include 'select.php';
        $this->runTests($tests);
    }

    function testUpdate() {
        include 'update.php';
        $this->runTests($tests);
    }

    function testCreate() {
        include 'create.php';
        $this->runTests($tests);
    }
}
