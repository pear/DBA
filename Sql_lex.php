<?php
/**
 * A Sql lexigraphical analyser
 * Inspired by the msql lexer
 */

include 'phptype.php';

// {{{ token definitions
define('SQL_END_OF_INPUT',257);
// logical operators
define('SQL_GE',100);
define('SQL_LE',101);
define('SQL_NE',102);
define('SQL_EQ',103);
define('SQL_GT',104);
define('SQL_LT',105);
define('SQL_AND',106);
define('SQL_OR',107);
define('SQL_NOT',108);
// verbs
define('SQL_CREATE',110);
define('SQL_DROP',111);
define('SQL_INSERT',112);
define('SQL_DELETE',113);
define('SQL_SELECT',114);
define('SQL_UPDATE',115);
// conjunctions
define('SQL_BY',120);
define('SQL_AS',121);
define('SQL_ON',122);
define('SQL_BETWEEN',123);
define('SQL_INTO',124);
define('SQL_FROM',125);
define('SQL_WHERE',126);
// modifiers
define('SQL_ASC',131);
define('SQL_DESC',132);
define('SQL_LIKE',133);
define('SQL_RLIKE',134);
define('SQL_CLIKE',135);
define('SQL_SLIKE',136);
define('SQL_STEP',137);
define('SQL_SET',138);
define('SQL_PRIMARY',139);
define('SQL_KEY',140);
define('SQL_UNIQUE',141);
define('SQL_LIMIT',143);
define('SQL_DISTINCT',144);
define('SQL_ORDER',145);
define('SQL_CHECK',146);
define('SQL_VARYING',147);
define('SQL_AUTO_INCREMENT',148);
// nouns
define('SQL_ALL',130);
define('SQL_TABLE',151);
define('SQL_SEQUENCE',152);
define('SQL_VALUE',153);
define('SQL_VALUES',154);
define('SQL_NULL',155);
define('SQL_INDEX',156);
define('SQL_SET_FUNCT',157);
define('SQL_CONSTRAINT',158);
define('SQL_DEFAULT',159);
// variables
define('SQL_IDENT',161);
define('SQL_SYS_VAR',162);
// values
define('SQL_REAL_VAL',162);
define('SQL_TEXT_VAL',163);
define('SQL_INT_VAL',164);
// types
define('SQL_FLOAT',171);
define('SQL_FIXED',171);
define('SQL_INT',172);
define('SQL_UINT',173);
define('SQL_BOOL',174);
define('SQL_CHAR',175);
define('SQL_VARCHAR',176);
define('SQL_TEXT',177);
define('SQL_DATE',178);
define('SQL_MONEY',179);
define('SQL_TIME',180);
define('SQL_IPV4',181);
define('SQL_SET',182);
define('SQL_ENUM',183);
// }}}

class Lexer
{
// {{{ array $symtab
    var $symtab = array(
        '<='=>       SQL_LE,
        '>='=>       SQL_GE,
        '<>'=>       SQL_NE,
        '<'=>        SQL_LT,
        '='=>        SQL_EQ,
        '>'=>        SQL_GT,
        'or'=>       SQL_OR,
        'not'=>      SQL_NOT,
        'and'=>      SQL_AND,
        'insert'=>   SQL_INSERT,
        'select'=>   SQL_SELECT,
        'delete'=>   SQL_DELETE,
        'create'=>   SQL_CREATE,
        'update'=>   SQL_UPDATE,
        'drop'=>     SQL_DROP,
        'as'=>       SQL_AS,
        'into'=>     SQL_INTO,
        'on'=>       SQL_ON,
        'between'=>  SQL_BETWEEN,
        'where'=>    SQL_WHERE,
        'from'=>     SQL_FROM,
        'by'=>       SQL_BY,
        'distinct'=> SQL_DISTINCT,
        'primary'=>  SQL_PRIMARY,
        'like'=>     SQL_LIKE,
        'null'=>     SQL_NULL,
        'asc'=>      SQL_ASC,
        'desc'=>     SQL_DESC,
        'unique'=>   SQL_UNIQUE,
        'table'=>    SQL_TABLE,
        'index'=>    SQL_INDEX,
        'clike'=>    SQL_CLIKE,
        'slike'=>    SQL_SLIKE,
        'rlike'=>    SQL_RLIKE,
        'all'=>      SQL_ALL,
        'key'=>      SQL_KEY,
        'sequence'=> SQL_SEQUENCE,
        'default'=>  SQL_DEFAULT,
        'order'=>    SQL_ORDER,
        'check'=>    SQL_CHECK,
        'set'=>      SQL_SET,
        'step'=>     SQL_STEP,
        'auto_increment'=> SQL_AUTOINCREMENT,
        'value'=>    SQL_VALUE,
        'values'=>   SQL_VALUES,
        'constraint'=> SQL_CONSTRAINT,
        'varying'=>  SQL_VARYING,
        'avg'=>      SQL_SET_FUNCT,
        'count'=>    SQL_SET_FUNCT,
        'max'=>      SQL_SET_FUNCT,
        'min'=>      SQL_SET_FUNCT,
        'sum'=>      SQL_SET_FUNCT,
        'nextval'=>  SQL_SET_FUNCT,
        'limit'=>    SQL_LIMIT,
        'time'=>     SQL_TIME,
        'tinyint'=>  SQL_INT,
        'integer'=>  SQL_INT,
        'bigint'=>   SQL_INT,
        'int'=>      SQL_INT,
        'smallint'=> SQL_INT,
        'uint'=>     SQL_UINT,
        'float'=>    SQL_FLOAT,
        'real'=>     SQL_FLOAT,
        'double'=>   SQL_FLOAT,
        'numeric'=>  SQL_FIXED,
        'decimal'=>  SQL_FIXED,
        'character'=>SQL_CHAR,
        'char'=>     SQL_CHAR,
        'varchar'=>  SQL_VARCHAR,
        'money'=>    SQL_MONEY,
        'date'=>     SQL_DATE,
        'text'=>     SQL_TEXT,
        'ipv4'=>     SQL_IPV4,
        'ipaddr'=>   SQL_IPV4,
        'set'=>      SQL_SET,
        'enum'=>     SQL_ENUM,
    );
// }}}

// {{{ instance variables
    var $tokPtr = 0;
    var $tokStart = 0;
    var $tokLen = 0;
    var $tokText = '';
    var $lineno = 0;
    var $string;
// }}}

// {{{ incidental functions
    function Lexer($string = '')
    {
        $this->string = $string;
    }

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
// }}}

// {{{ lex($this->string)
function lex()
{
    if ($state == 1000) {
        return 0;
    }

    $state = 0;
    while (1) {
        //echo "State: $state, Char: $c\n";
        switch($state) {
            // {{{ State 0 : Start of token
            CASE(0):
                $this->tokPtr = $this->tokStart;
                $this->tokText = '';
                $this->tokLen = 0;
                $c = $this->get();
                while (($c == ' ') || ($c == "\t") || ($c == "\n")) {
                    if ($c == "\n") {
                        ++$this->lineno;
                    }
                    $c = $this->skip();
                    $this->tokLen = 1;
                }
                if (($c == '\'') || ($c == '"')) {
                    $quote = $c;
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
                if ($c == false) {
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
                $this->tokText = substr($this->string, $this->tokStart, 
                                        $this->tokLen);
                $tokval = $this->symtab[strtolower($this->tokText)];
                if ($tokval) {
                    $this->tokStart = $this->tokPtr;
                    return ($tokval);
                } else {
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
                $this->tokText = substr($this->string, $this->tokStart, $this->tokLen);
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
                $this->tokText = substr($this->string,$this->tokStart,$this->tokLen);
                $this->tokStart = $this->tokPtr;
                return (SQL_INT_VAL);
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
                $this->tokText = substr($this->string, $this->tokStart, $this->tokLen);
                $this->tokStart = $this->tokPtr;
                return (SQL_REAL_VAL);
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
                        case '':
                            $this->tokText = '';
                            $bail = true;
                            break;
                        case "\\":
                            if (!$this->get()) {
                                $this->tokText = '';
                                $bail = true;
                            }
                                //$bail = true;
                            break;
                        case $quote:
                            $this->tokText = stripslashes(substr($this->string,
                                       ($this->tokStart+1), ($this->tokLen-2)));
                            $bail = true;
                            break;
                    }
                }
                if ($this->tokText) {
                    $state = 13;
                    break;
                }
                $state = 999;
                break;
            // }}}

            // {{{ State 13: Complete text string
            CASE(13):
                $this->tokStart = $this->tokPtr;
                return (SQL_TEXT_VAL);
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
                $this->tokText = substr($this->tokStart,$this->tokLen);
                $this->tokStart = $this->tokPtr;
                return (SQL_SYS_VAR);
            // }}}

            // {{{ State 999 : Unknown token.  Revert to single char
            CASE(999):
                $this->revert();
                $this->tokText = $this->get();
                $this->tokStart = $this->tokPtr;
                return ($this->tokText);
            // }}}

            // {{{ State 1000 : End Of Input
            CASE(1000):
                $this->tokText = '*end of input*';
                $this->tokStart = $this->tokPtr;
                return (SQL_END_OF_INPUT);
            // }}}

        }
    }
}
// }}}
}
?>
