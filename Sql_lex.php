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
//
// $Id$
//

include 'DB/DBA/phptype.php';

// {{{ token definitions
// variables
define('TOK_IDENT',1);
define('TOK_SYS_VAR',2);
// values
define('TOK_REAL_VAL',3);
define('TOK_TEXT_VAL',4);
define('TOK_INT_VAL',5);
define('TOK_END_OF_INPUT',6);
// }}}

/**
 * A lexigraphical analyser inspired by the msql lexer
 *
 * @author  Brent Cook <busterb@mail.utexas.edu>
 * @version 0.18
 * @access  public
 * @package DBA
 */
class Lexer
{
    // array of valid tokens for the lexer to recognize
    // format is 'token literal'=>TOKEN_VALUE
    var $symtab = array();

// {{{ instance variables
    var $tokPtr = 0;
    var $tokStart = 0;
    var $tokLen = 0;
    var $tokText = '';
    var $lineNo = 0;
    var $lineBegin = 0;
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
                        ++$this->lineNo;
                        $this->lineBegin = $this->tokPtr;
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
                if (isAlnum($c) || ($c == '_')) {
                    $state = 1;
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
                    return (TOK_IDENT);
                }
                break;
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
                return (TOK_INT_VAL);
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
                return (TOK_REAL_VAL);
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
                return (TOK_TEXT_VAL);
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
                return (TOK_SYS_VAR);
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
                return (TOK_END_OF_INPUT);
            // }}}

        }
    }
}
// }}}
}
?>
