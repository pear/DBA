<?php
/* Functions: Converted from <ctype.h>.
 * Author: John Millaway
 * 
 * Note: These functions expect a character,
 * such as 'a', or '?', not an integer.
 * If you want to use integers, first convert
 * the integer using the chr() function.
 *
 * Examples:
 * 
 * isalpha('a'); // returns 1
 * isalpha(chr(97)); // same thing
 *
 * isdigit(1); // NO!
 * isdigit('1'); // yes.
*/
function isalnum($c) {
    global $ctype__;
    return (($ctype__[ord($c)] & (01 | 02 | 04)) != 0);
}
function isalpha($c) {
    global $ctype__;
    return (($ctype__[ord($c)] & (01 | 02)) != 0);
}
function isascii($c) {
    return ((ord($c)<=0177) != 0);
}
function iscntrl($c) {
    global $ctype__;
    return (($ctype__[ord($c)] & 040) != 0);
}
function isdigit($c) {
    global $ctype__;
    return (($ctype__[ord($c)] & 04) != 0);
}

function isgraph($c) {
    global $ctype__;
    return (($ctype__[ord($c)] & (020 | 01 | 02 | 04)) != 0);
}

function islower($c) {
    global $ctype__;
    return (($ctype__[ord($c)] & 02) != 0);
}

function isprint($c) {
    global $ctype__;
    return (($ctype__[ord($c)] & (020 | 01 | 02 | 04 | 0200)) != 0);
}

function ispunct($c) {
    global $ctype__;
    return (($ctype__[ord($c)] & 020 ) != 0);
}

function isspace($c) {
    global $ctype__;
    return (($ctype__[ord($c)] & 010 ) != 0);
}

function isupper($c) {
    global $ctype__;
    return (($ctype__[ord($c)] & 01) != 0);
}
function isxdigit($c) {
    global $ctype__;
    return (($ctype__[ord($c)] & (0100 | 04 )) != 0);
}

$ctype__ =
    array(32,32,32,32,32,32,32,32,32,40,40,40,40,40,32,32,32,32,32,32,32,32,  
          32,32,32,32,32,32,32,32,32,32,-120,16,16,16,16,16,16,16,16,16,16,
          16,16,16,16,16,4,4,4,4,4,4,4,4,4,4,16,16,16,16,16,16,16,65,65,65,
          65,65,65,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,16,16,16,16,16,
          16,66,66,66,66,66,66,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,16,
          16,16,16,32,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
          0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
          0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
         0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
?>
