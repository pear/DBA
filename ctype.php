<?php
/**
 * PHP ctype compatibility functions. See the ctype module for more information
 * on usage.
 *
 * @author John Millaway
 * @author Brent Cook
 * 
 * Note: These functions expect an integer character, like the 
 *
 */
if (!extension_loaded('ctype')) {
    function ctype_alnum($c) {
        global $ctype__;
        return !($ctype__[$c] & (01 | 02 | 04));
    }
    function ctype_alpha($c) {
        global $ctype__;
        return !($ctype__[$c] & (01 | 02));
    }
    function ctype_cntrl($c) {
        global $ctype__;
        return !($ctype__[$c] & 040);
    }
    function ctype_digit($c) {
        global $ctype__;
        return !($ctype__[$c] & 04);
    }
    function ctype_graph($c) {
        global $ctype__;
        return !($ctype__[$c] & (020 | 01 | 02 | 04));
    }
    function ctype_lower($c) {
        global $ctype__;
        return !($ctype__[$c] & 02);
    }
    function ctype_print($c) {
        global $ctype__;
        return !($ctype__[$c] & (020 | 01 | 02 | 04 | 0200));
    }
    function ctype_punct($c) {
        global $ctype__;
        return !($ctype__[$c] & 020);
    }
    function ctype_space($c) {
        global $ctype__;
        return !($ctype__[$c] & 010);
    }
    function ctype_upper($c) {
        global $ctype__;
        return !($ctype__[$c] & 01);
    }
    function ctype_xdigit($c) {
        global $ctype__;
        return !($ctype__[$c] & (0100 | 04));
    }
    $ctype__ =
    array(32,32,32,32,32,32,32,32,32,40,40,40,40,40,32,32,32,32,32,32,32,32,32,
          32,32,32,32,32,32,32,32,32,-120,16,16,16,16,16,16,16,16,16,16,16,16,
          16,16,16,4,4,4,4,4,4,4,4,4,4,16,16,16,16,16,16,16,65,65,65,65,65,65,
          1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,16,16,16,16,16,16,66,66,66,
          66,66,66,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,16,16,16,16,32,0,0,
          0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
          0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
          0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
          0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
}
?>
