<?php
include 'phptype.php';

// {{{ token definitions
define('END_OF_INPUT',257);
define('GE',258);
define('LE',259);
define('NE',260);
define('EQ',261);
define('GT',262);
define('LT',263);
define('BETWEEN',264);
define('SLQ_CREATE',265);
define('SLQ_DROP',266);
define('SLQ_INSERT',267);
define('SLQ_DELETE',268);
define('SLQ_SELECT',269);
define('SLQ_UPDATE',270);
define('ALL',271);
define('DISTINCT',272);
define('SQL_AS',273);
define('WHERE',274);
define('ORDER',275);
define('FROM',276);
define('INTO',277);
define('TABLE',278);
define('BY',279);
define('ASC',280);
define('DESC',281);
define('LIKE',282);
define('RLIKE',283);
define('CLIKE',284);
define('SLIKE',285);
define('SQL_AND',286);
define('SQL_OR',287);
define('VALUES',288);
define('SET',289);
define('NOT',290);
define('NULLSYM',291);
define('PRIMARY',292);
define('SQL_KEY',293);
define('INDEX',294);
define('UNIQUE',295);
define('ON',296);
define('IDENT',297);
define('SET_FUNCT',298);
define('SYS_VAR',299);
define('NUM',300);
define('REAL_NUM',301);
define('SLQ_INT',302);
define('SLQ_INT8',303);
define('SLQ_INT16',304);
define('SLQ_INT32',305);
define('SLQ_INT64',306);
define('SLQ_UINT',307);
define('SLQ_UINT8',308);
define('SLQ_UINT16',309);
define('SLQ_UINT32',310);
define('SLQ_UINT64',311);
define('SLQ_BOOL',312);
define('SLQ_CHAR',313);
define('SLQ_TEXT',314);
define('SLQ_REAL',315);
define('SLQ_DATE',316);
define('SLQ_MONEY',317);
define('SLQ_TIME',318);
define('SLQ_IPV4',319);
define('LIMIT',320);
define('CREATE_TABLE',321);
define('CREATE_INDEX',322);
define('CREATE_SEQUENCE',323);
define('DROP_TABLE',324);
define('DROP_INDEX',325);
define('DROP_SEQUENCE',326);
define('SEQUENCE',327);
define('VALUE',328);
define('STEP',329);
define('AVL_INDEX',330);
// }}}

// {{{ array $symtab
$symtab = array(
    'select'=>  SQL_SELECT,
    'values'=>  VALUES,
    'uint'=>    SQL_UINT,
    'int32'=>   SQL_INT,
    'or'=>      SQL_OR,
    'not'=>     NOT,
    'distinct'=>DISTINCT,
    'int16'=>   SQL_INT16,
    'and'=>     SQL_AND,
    'delete'=>  SQL_DELETE,
    'update'=>  SQL_UPDATE,
    'avl'=>     AVL_INDEX,
    'ipv4'=>    SQL_IPV4,
    'int8'=>    SQL_INT8,
    'from'=>    FROM,
    'create'=>  SQL_CREATE,
    'primary'=> PRIMARY,
    'smallint'=>SQL_INT,
    'real'=>    SQL_REAL,
    'as'=>      SQL_AS,
    'min'=>     SET_FUNCT,
    'ipaddr'=>  SQL_IPV4,
    'drop'=>    SQL_DROP,
    'insert'=>  SQL_INSERT,
    'like'=>    LIKE,
    'text'=>    SQL_TEXT,
    'sum'=>     SET_FUNCT,
    'int64'=>   SQL_INT64,
    'uint32'=>  SQL_UINT,
    'NULL'=>    NULLSYM,
    'max'=>     SET_FUNCT,
    'float'=>   SQL_REAL,
    'asc'=>     ASC,
    'unique'=>  UNIQUE,
    'rlike'=>   RLIKE,
    'uint16'=>  SQL_UINT16,
    'table'=>   TABLE,
    'index'=>   INDEX,
    'clike'=>   CLIKE,
    'money'=>   SQL_MONEY,
    'slike'=>   SLIKE,
    'uint8'=>   SQL_UINT8,
    '<='=>      LE,
    'all'=>     ALL,
    'key'=>     SQL_KEY,
    'count'=>   SET_FUNCT,
    'sequence'=>SEQUENCE,
    '<>'=>      NE,
    'into'=>    INTO,
    'between'=> BETWEEN,
    'uint64'=>  SQL_UINT64,
    'where'=>   WHERE,
    '>='=>      GE,
    'by'=>      BY,
    'null'=>    NULLSYM,
    'int'=>     SQL_INT,
    'double'=>  SQL_REAL,
    '<'=>       LT,
    'order'=>   ORDER,
    'set'=>     SET,
    'step'=>    STEP,
    '='=>       EQ,
    'on'=>      ON,
    'value'=>   VALUE,
    'character'=>SQL_CHAR,
    'bigint'=>  SQL_INT,
    '>'=>       GT,
    'integer'=> SQL_INT,
    'char'=>    SQL_CHAR,
    'avg'=>     SET_FUNCT,
    'date'=>    SQL_DATE,
    'float8'=>  SQL_REAL,
    'desc'=>    DESC,
    'limit'=>   LIMIT,
    'time'=>    SQL_TIME,
    'tinyint'=> SQL_INT,
    'int4'=>    SQL_INT,
);
// }}}

$tokPtr = 0;
$tokStart = 0;
$prev = null;
$rep = null;
$text = null;
$state = 0;
$lineno = 0;

// {{{ lex($string)
function lex($string)
{
    global $tokPtr, $tokStart, $prev, $rep, $text, $state, $lineno, $symtab;

    function get() {
        global $string, $tokPtr, $tokLen;
        ++$tokLen;
        return $string[$tokPtr++];
    }

    function unget() {
        global $tokPtr, $tokLen;
        --$tokPtr;
        --$tokLen;
    }

    function skip() {
        global $string, $tokPtr, $tokStart;
        ++$tokStart;
        return $string[$tokPtr++];
    }

    function revert() {
        global $tokPtr, $tokStart, $tokLen;
        $tokPtr = $tokStart;
        $tokLen = 0;
    }

    function isCompop($c) {
        return ($c == '<' || $c == '>' || $c == '=');
    }

    $prev = $rep;
    if ($state == 1000) {
        $prev = null;
        return 0;
    }

    $state = 0;
    while (1) {
        usleep(250000);
        echo "State $state, $string[$tokPtr]\n";
        switch($state) {
            // {{{ State 0 : Start of token
            CASE(0):
                $tokPtr = $tokStart;
                $text = NULL;
                $tokLen = 0;
                $c = get();
                while ($c == ' ' || $c == '\t' || $c == '\n') {
                    if ($c == '\n') {
                        ++$lineno;
                    }
                    $c = skip();
                    $tokLen = 1;
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
                    $t = get();
                    if (isDigit($t)) {
                        unget();
                        $state = 7;
                        break;
                    } else {
                        unget();
                    }
                }
                if ($c == '-' || $c == '+') {
                    $state = 9;
                    break;
                }
                if (isCompop($c)) {
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
                $c = get();
                if (isAlnum($c)) {
                    echo "'$c' is Alnum\n";
                    $state = 1;
                    break;
                }
                if ($c == ' ') {
                    echo "'$c' is a space\n";
                    $state = 3;
                    break;
                }
                $state = 2;
                break;
            // }}}

            /* {{{ State 2 : Complete keyword or ident */
            CASE(2):
                unget();
                $tokval = $symtab[substr($string,$tokStart,$tokLen)];
//                $tokval = _findKeyword($tokStart,$tokLen);
                if ($tokval) {
                    $tokStart = $tokPtr;
                    return ($tokval);
                } else {
                    $text = substr($string, $tokStart, $tokLen);
                    $lval = $text;
                    $tokStart = $tokPtr;
                    return (IDENT);
                }
                break; 
            // }}}

            // {{{ State 3 : Incomplete ident
            CASE(3):
                $c = get();
                if (isAlnum($c) || $c == ' ')
                {
                    $state = 3;
                    break;
                }
                $state = 4;
                break;
            // }}}

            // {{{ State 4: Complete ident
            CASE(4):
                unget();
                $text = substr($string, $tokStart, $tokLen);
                $lval = $text;
                $tokStart = $tokPtr;
                return (IDENT);
            // }}}

            // {{{ State 5: Incomplete real or int number
            CASE(5):
                $c = get();
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
                unget();
                $text = substr($string,$tokStart,$tokLen);
                $lval = $text;
                $tokStart = $tokPtr;
                return (NUM);
                break;
            // }}}

            // {{{ State 7: Incomplete real number
            CASE(7):
                $c = get();

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
                unget();
                $text = substr($string, $tokStart, $tokLen);
                $lval = $text;
                $tokStart = $tokPtr;
                return (REAL_NUM);
            // }}}

            // {{{ State 9: Incomplete signed number
            CASE(9):
                $c = get();
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
                $c = get();
                if (isCompop($c))
                {
                    $state = 10;
                    break;
                }
                $state = 11;
                break;
            // }}}

            // {{{ State 11: Complete comparison operator
            CASE(11):
                unget();
                $tokval = $symtab[substr($string,$tokStart,$tokLen)];
//                $tokval = _findKeyword(tokStart,$tokLen);
                if ($tokval)
                {
                    $tokStart = $tokPtr;
                    return ($tokval);
                }
                $state = 999;
                break;
            // }}}

            // {{{ State 12: Incomplete text string
            CASE(12):
                $bail = false;
                while (!$bail) {
                    switch (get()) {
                        case null:
                            $text = false;
                            $bail = true;
                            break;
                        case '\\':
                            if (get()) {
                                $text = false;
                                $bail = true;
                            }
                            break;
                        case '\'':
                            $text = substr($string,$tok, $tokLen);
                            $bail = true;
                            break;
                    }
                }
//                $text = _readTextLiteral($tokStart);
                $lval = $text;
                if ($text) {
                    $state = 13;
                    break;
                }
                $state = 999;
                break;
            // }}}

            // {{{ State 13: Complete text string
            CASE(13):
                $tokStart = $tokPtr;
                return (MSQL_TEXT);
                break;
            // }}}

            // {{{ State 14: Comment
            CASE(14):
                $c = skip();
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
                    $c = get();
                    if($c == '-' || $c == '+') {
                            $state = 16;
                            break;
                    }
                    $state = 999;
                    break;
            // }}}

            // {{{ state 16: Exponent Value-first digit in Scientific Notation
            CASE(16):
                    $c = get();
                    if (isDigit($c)) {
                            $state = 17;
                            break;
                    }
                    $state = 999;  // if no digit, then token is unknown
                    break;
            // }}}

            // {{{ State 17: Exponent Value in Scientific Notation
            CASE(17):
                    $c = get();
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
                $c = get();
                if (isalnum($c) || $c == ' ') {
                    $state = 18;
                    break;
                }
                $state = 19;
                break;
            // }}}

            // {{{ State 19: Complete Sys Var
            CASE(19):
                unget();
                $text = substr($tokStart,$tokLen);
                $lval = $text;
                $tokStart = $tokPtr;
                return (SYS_VAR);
            // }}}

            // {{{ State 999 : Unknown token.  Revert to single char
            CASE(999):
                revert();
                $c = get();
                $text = $c;
                $lval = $text;
                $tokStart = $tokPtr;
                return ($text[0]);
            // }}}

            // {{{ State 1000 : End Of Input
            CASE(1000):
                $text = $lval = '*end of input*';
                $tokStart = $tokPtr;
                return (END_OF_INPUT);
            // }}}

        }
    }
// }}}

}
$string = "create";
echo lex($string);
?>
