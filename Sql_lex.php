<?php
include 'phptype.php';

// {{{ token definitions
define('SQL_END_OF_INPUT',257);
define('SQL_GE',258);
define('SQL_LE',259);
define('SQL_NE',260);
define('SQL_EQ',261);
define('SQL_GT',262);
define('SQL_LT',263);
define('SQL_BETWEEN',264);
define('SQL_CREATE',265);
define('SQL_DROP',266);
define('SQL_INSERT',267);
define('SQL_DELETE',268);
define('SQL_SELECT',269);
define('SQL_UPDATE',270);
define('SQL_ALL',271);
define('SQL_DISTINCT',272);
define('SQL_AS',273);
define('SQL_WHERE',274);
define('SQL_ORDER',275);
define('SQL_FROM',276);
define('SQL_INTO',277);
define('SQL_TABLE',278);
define('SQL_BY',279);
define('SQL_ASC',280);
define('SQL_DESC',281);
define('SQL_LIKE',282);
define('SQL_RLIKE',283);
define('SQL_CLIKE',284);
define('SQL_SLIKE',285);
define('SQL_AND',286);
define('SQL_OR',287);
define('SQL_VALUES',288);
define('SQL_SET',289);
define('SQL_NOT',290);
define('SQL_NULLSYM',291);
define('SQL_PRIMARY',292);
define('SQL_KEY',293);
define('SQL_INDEX',294);
define('SQL_UNIQUE',295);
define('SQL_ON',296);
define('SQL_IDENT',297);
define('SQL_SET_FUNCT',298);
define('SQL_SYS_VAR',299);
define('SQL_NUM',300);
define('SQL_REAL_NUM',301);
define('SQL_INT',302);
define('SQL_INT8',303);
define('SQL_INT16',304);
define('SQL_INT32',305);
define('SQL_INT64',306);
define('SQL_UINT',307);
define('SQL_UINT8',308);
define('SQL_UINT16',309);
define('SQL_UINT32',310);
define('SQL_UINT64',311);
define('SQL_BOOL',312);
define('SQL_CHAR',313);
define('SQL_TEXT',314);
define('SQL_REAL',315);
define('SQL_DATE',316);
define('SQL_MONEY',317);
define('SQL_TIME',318);
define('SQL_IPV4',319);
define('SQL_LIMIT',320);
define('SQL_CREATE_TABLE',321);
define('SQL_CREATE_INDEX',322);
define('SQL_CREATE_SEQUENCE',323);
define('SQL_DROP_TABLE',324);
define('SQL_DROP_INDEX',325);
define('SQL_DROP_SEQUENCE',326);
define('SQL_SEQUENCE',327);
define('SQL_VALUE',328);
define('SQL_STEP',329);
define('SQL_AVL_INDEX',330);
// }}}

class Lexer
{
// {{{ array $symtab
    var $symtab = array(
        'select'=>  SQL_SELECT,
        'values'=>  SQL_VALUES,
        'uint'=>    SQL_UINT,
        'int32'=>   SQL_INT,
        'or'=>      SQL_OR,
        'not'=>     SQL_NOT,
        'distinct'=>SQL_DISTINCT,
        'int16'=>   SQL_INT16,
        'and'=>     SQL_AND,
        'delete'=>  SQL_DELETE,
        'update'=>  SQL_UPDATE,
        'avl'=>     SQL_AVL_INDEX,
        'ipv4'=>    SQL_IPV4,
        'int8'=>    SQL_INT8,
        'from'=>    SQL_FROM,
        'create'=>  SQL_CREATE,
        'primary'=> SQL_PRIMARY,
        'smallint'=>SQL_INT,
        'real'=>    SQL_REAL,
        'as'=>      SQL_AS,
        'min'=>     SQL_SET_FUNCT,
        'ipaddr'=>  SQL_IPV4,
        'drop'=>    SQL_DROP,
        'insert'=>  SQL_INSERT,
        'like'=>    SQL_LIKE,
        'text'=>    SQL_TEXT,
        'sum'=>     SQL_SET_FUNCT,
        'int64'=>   SQL_INT64,
        'uint32'=>  SQL_UINT,
        'NULL'=>    SQL_NULLSYM,
        'max'=>     SQL_SET_FUNCT,
        'float'=>   SQL_REAL,
        'asc'=>     SQL_ASC,
        'unique'=>  SQL_UNIQUE,
        'rlike'=>   SQL_RLIKE,
        'uint16'=>  SQL_UINT16,
        'table'=>   SQL_TABLE,
        'index'=>   SQL_INDEX,
        'clike'=>   SQL_CLIKE,
        'money'=>   SQL_MONEY,
        'slike'=>   SQL_SLIKE,
        'uint8'=>   SQL_UINT8,
        '<='=>      SQL_LE,
        'all'=>     SQL_ALL,
        'key'=>     SQL_KEY,
        'count'=>   SQL_SET_FUNCT,
        'sequence'=>SQL_SEQUENCE,
        '<>'=>      SQL_NE,
        'into'=>    SQL_INTO,
        'between'=> SQL_BETWEEN,
        'uint64'=>  SQL_UINT64,
        'where'=>   SQL_WHERE,
        '>='=>      SQL_GE,
        'by'=>      SQL_BY,
        'null'=>    SQL_NULLSYM,
        'int'=>     SQL_INT,
        'double'=>  SQL_REAL,
        '<'=>       SQL_LT,
        'order'=>   SQL_ORDER,
        'set'=>     SQL_SET,
        'step'=>    SQL_STEP,
        '='=>       SQL_EQ,
        'on'=>      SQL_ON,
        'value'=>   SQL_VALUE,
        'character'=>SQL_CHAR,
        'bigint'=>  SQL_INT,
        '>'=>       SQL_GT,
        'integer'=> SQL_INT,
        'char'=>    SQL_CHAR,
        'avg'=>     SQL_SET_FUNCT,
        'date'=>    SQL_DATE,
        'float8'=>  SQL_REAL,
        'desc'=>    SQL_DESC,
        'limit'=>   SQL_LIMIT,
        'time'=>    SQL_TIME,
        'tinyint'=> SQL_INT,
        'int4'=>    SQL_INT,
    );
// }}}

    var $tokPtr = 0;
    var $tokStart = 0;
    var $prev = null;
    var $rep = null;
    var $text = null;
    var $tokLen = 0;
    var $lineno = 0;
    var $string;

    function get() {
        ++$this->tokLen;
        return $this->string[$this->tokPtr++];
    }

    function unget() {
        --$this->tokPtr;
        --$this->tokLen;
    }

    function skip() {
        ++$this->tokStart;
        return $this->string[$this->tokPtr++];
    }

    function revert() {
        $this->tokPtr = $this->tokStart;
        $this->tokLen = 0;
    }

    function isCompop($c) {
        return ($c == '<' || $c == '>' || $c == '=');
    }

// {{{ lex($this->string)
function lex()
{
    $this->prev = $this->rep;
    if ($state == 1000) {
        $this->prev = null;
        return 0;
    }

    $state = 0;
    while (1) {
        //echo 'State '.$state.', '.$this->string[$this->tokPtr]."\n";
        switch($state) {
            // {{{ State 0 : Start of token
            CASE(0):
                $this->tokPtr = $this->tokStart;
                $this->text = NULL;
                $this->tokLen = 0;
                $c = $this->get();
                while ($c == ' ' || $c == '\t' || $c == '\n') {
                    if ($c == '\n') {
                        ++$this->lineno;
                    }
                    $c = $this->skip();
                    $this->tokLen = 1;
                }
                if ($c == '\'') {
                    $state = 12;
                    break;
                }
                if ($c == '_') {
                    $state = 18;
                    break;
                }
                if (isAlpha($c)) {
                    $state = 1;
                    break;
                }
                if (isDigit($c)) {
                    $state = 5;
                    break;
                }
                if ($c == '.') {
                    $t = $this->get();
                    if (isDigit($t)) {
                        $this->unget();
                        $state = 7;
                        break;
                    } else {
                        $this->unget();
                    }
                }
                if ($c == '-' || $c == '+') {
                    $state = 9;
                    break;
                }
                if ($this->isCompop($c)) {
                    $state = 10;
                    break;
                }
                if ($c == '#') {
                    $state = 14;
                    break;
                }
                if ($c == 0) {
                    $state = 1000;
                    break;
                }
                $state = 999;
                break;
            // }}}

            // {{{ State 1 : Incomplete keyword or ident
            CASE(1):
                $c = $this->get();
                if (isAlnum($c)) {
                    $state = 1;
                    break;
                }
                if ($c == '_') {
                    $state = 3;
                    break;
                }
                $state = 2;
                break;
            // }}}

            /* {{{ State 2 : Complete keyword or ident */
            CASE(2):
                $this->unget();
                //echo "$this->tokStart, $this->tokLen\n";
                //echo substr($this->string,$this->tokStart,$this->tokLen)."\n";
                $tokval = $this->symtab[
                        substr($this->string,$this->tokStart,$this->tokLen)];
//                $tokval = _findKeyword($this->tokStart,$this->tokLen);
                if ($tokval) {
                    $this->tokStart = $this->tokPtr;
                    return ($tokval);
                } else {
                    $this->text = substr($this->string, $this->tokStart, $this->tokLen);
                    $lval = $this->text;
                    $this->tokStart = $this->tokPtr;
                    return (SQL_IDENT);
                }
                break; 
            // }}}

            // {{{ State 3 : Incomplete ident
            CASE(3):
                $c = $this->get();
                if (isAlnum($c) || $c == '_')
                {
                    $state = 3;
                    break;
                }
                $state = 4;
                break;
            // }}}

            // {{{ State 4: Complete ident
            CASE(4):
                $this->unget();
                $this->text = substr($this->string, $this->tokStart, $this->tokLen);
                $lval = $this->text;
                $this->tokStart = $this->tokPtr;
                return (SQL_IDENT);
            // }}}

            // {{{ State 5: Incomplete real or int number
            CASE(5):
                $c = $this->get();
                if (isDigit($c)) {
                    $state = 5;
                    break;
                }
                if ($c == '.') {
                    $state = 7;
                    break;
                }
                $state = 6;
                break;
            // }}}

            // {{{ State 6: Complete integer number
            CASE(6):
                $this->unget();
                $this->text = substr($this->string,$this->tokStart,$this->tokLen);
                $lval = $this->text;
                $this->tokStart = $this->tokPtr;
                return (SQL_NUM);
                break;
            // }}}

            // {{{ State 7: Incomplete real number
            CASE(7):
                $c = $this->get();

                /* Analogy Start */
                if ($c == 'e' || $c == 'E') {
                        $state = 15;
                        break;
                }
                /* Analogy End   */

                if (isDigit($c)) {
                    $state = 7;
                    break;
                }
                $state = 8;
                break;
            // }}}

            // {{{ State 8: Complete real number */
            CASE(8):
                $this->unget();
                $this->text = substr($this->string, $this->tokStart, $this->tokLen);
                $lval = $this->text;
                $this->tokStart = $this->tokPtr;
                return (SQL_REAL_NUM);
            // }}}

            // {{{ State 9: Incomplete signed number
            CASE(9):
                $c = $this->get();
                if (isDigit($c)) {
                    $state = 5;
                    break;
                }
                if ($c == '.') {
                    $state = 7;
                    break;
                }
                $state = 999;
                break;
            // }}}
 
            // {{{ State 10: Incomplete comparison operator
            CASE(10):
                $c = $this->get();
                if ($this->isCompop($c))
                {
                    $state = 10;
                    break;
                }
                $state = 11;
                break;
            // }}}

            // {{{ State 11: Complete comparison operator
            CASE(11):
                $this->unget();
                $tokval = $this->symtab[
                        substr($this->string,$this->tokStart,$this->tokLen)];
//                $tokval = _findKeyword(tokStart,$this->tokLen);
                if ($tokval)
                {
                    $this->tokStart = $this->tokPtr;
                    return ($tokval);
                }
                $state = 999;
                break;
            // }}}

            // {{{ State 12: Incomplete text string
            CASE(12):
                $bail = false;
                while (!$bail) {
                    switch ($this->get()) {
                        case null:
                            $this->text = false;
                            $bail = true;
                            break;
                        case '\\':
                            if ($this->get()) {
                                $this->text = false;
                                $bail = true;
                            }
                            break;
                        case '\'':
                            $this->text = substr($this->string,$tok, $this->tokLen);
                            $bail = true;
                            break;
                    }
                }
//                $this->text = _readTextLiteral($this->tokStart);
                $lval = $this->text;
                if ($this->text) {
                    $state = 13;
                    break;
                }
                $state = 999;
                break;
            // }}}

            // {{{ State 13: Complete text string
            CASE(13):
                $this->tokStart = $this->tokPtr;
                return (SQL_TEXT);
                break;
            // }}}

            // {{{ State 14: Comment
            CASE(14):
                $c = $this->skip();
                if ($c == '\n') {
                    $state = 0;
                } else {
                    $state = 14;
                }
                break;
            // }}}

    // Analogy Start
            // {{{ State 15: Exponent Sign in Scientific Notation
            CASE(15):
                    $c = $this->get();
                    if($c == '-' || $c == '+') {
                            $state = 16;
                            break;
                    }
                    $state = 999;
                    break;
            // }}}

            // {{{ state 16: Exponent Value-first digit in Scientific Notation
            CASE(16):
                    $c = $this->get();
                    if (isDigit($c)) {
                            $state = 17;
                            break;
                    }
                    $state = 999;  // if no digit, then token is unknown
                    break;
            // }}}

            // {{{ State 17: Exponent Value in Scientific Notation
            CASE(17):
                    $c = $this->get();
                    if (isDigit($c)) {
                            $state = 17;
                            break;
                    }
                    $state = 8;  // At least 1 exponent digit was required
                    break;
            // }}}
    // Analogy End

            // {{{ State 18 : Incomplete System Variable
            CASE(18):
                $c = $this->get();
                if (isalnum($c) || $c == '_') {
                    $state = 18;
                    break;
                }
                $state = 19;
                break;
            // }}}

            // {{{ State 19: Complete Sys Var
            CASE(19):
                $this->unget();
                $this->text = substr($this->tokStart,$this->tokLen);
                $lval = $this->text;
                $this->tokStart = $this->tokPtr;
                return (SQL_SYS_VAR);
            // }}}

            // {{{ State 999 : Unknown token.  Revert to single char
            CASE(999):
                $this->revert();
                $c = $this->get();
                $this->text = $c;
                $lval = $this->text;
                $this->tokStart = $this->tokPtr;
                return ($this->text[0]);
            // }}}

            // {{{ State 1000 : End Of Input
            CASE(1000):
                $this->text = $lval = '*end of input*';
                $this->tokStart = $this->tokPtr;
                return (SQL_END_OF_INPUT);
            // }}}

        }
    }
// }}}

}
}

$lexer = new Lexer();
$lexer->string = "create table dogfood int 23.4 99";
$token = $lexer->lex();
while ($token != SQL_END_OF_INPUT) {
    echo $token."\n";
    $token = $lexer->lex();
}
?>
