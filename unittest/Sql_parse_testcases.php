<?php
require_once 'DB/DBA/Sql_parse.php';
require_once 'PHPUnit/PHPUnit.php';
require_once 'Var_Dump.php';

class SqlParserTest extends PHPUnit_TestCase {
    // contains the object handle of the parser class
    var $parser;
    var $dumper;

    //constructor of the test suite
    function SqlParserTest($name) {
        $this->PHPUnit_TestCase($name);
    }

    function setUp() {
        $this->parser = new Sql_parser();
        $this->dumper = new Var_Dump(
            array('displayMode'=> VAR_DUMP_DISPLAY_MODE_TEXT));
    }

    function tearDown() {
        unset($this->parser);
    }

    function runTests($tests) {
        foreach ($tests as $number=>$test) {
            $result = $this->parser->parse($test['sql']);
            $expected = $test['expect'];
            $message = "\nSQL: {$test['sql']}\n";
/*
            ob_start();
            print_r($expected);
            $message .= "\nExpected:\n ".ob_get_contents();
            ob_clean();
            print_r($result);
            $message .= "\nOutput:\n ".ob_get_contents();
            ob_end_clean();
            $message .= "\nExpected:\n ".serialize($expected);
            $message .= "\nOutput:\n ".serialize($result);
*/
            $message .= "\nExpected:\n".$this->dumper->r_display($expected);
            $message .= "\nResult:\n".$this->dumper->r_display($result);
            $this->assertEquals($expected, $result, $message, $number);
        }
    }

    function testSelect() {
        include 'select.php';
        $this->runTests($tests);
    }

/*
    function testUpdate() {
        include 'update.php';
        $this->runTests($tests);
    }
*/

    function testCreate() {
        include 'create.php';
        $this->runTests($tests);
    }
}
