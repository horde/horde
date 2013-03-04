--TEST--
Test horde_lz4_compress() function : error conditions
--SKIPIF--
<?php
if (!extension_loaded('horde_lz4')) {
    die("skip horde_lz4 extension not loaded");
}
?>
--FILE--
<?php

include(dirname(__FILE__) . '/data.inc');

// Zero arguments
var_dump(horde_lz4_compress());

// Test horde_lz4_compress with one more than the expected number of arguments
$data = 'string_val';
$extra_arg = 10;
var_dump(horde_lz4_compress($data, false, false, $extra_arg));

class Tester {
    function Hello() {
        echo "Hello\n";
    }
}

$testclass = new Tester();
var_dump(horde_lz4_compress($testclass));

?>
--EXPECTF--

Warning: horde_lz4_compress() expects at least 1 parameter, 0 given in %s on line %d
bool(false)

Warning: horde_lz4_compress() expects at most 3 parameters, 4 given in %s on line %d
bool(false)

Warning: horde_lz4_compress: expects parameter to be string. in %s on line %d
bool(false)
