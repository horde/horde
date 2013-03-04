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
horde_lz4_compress();

// Test horde_lz4_compress with one more than the expected number of arguments
$data = 'string_val';
$extra_arg = 10;
horde_lz4_compress($data, false, $extra_arg);

class Tester {
    function Hello() {
        echo "Hello\n";
    }
}

$testclass = new Tester();
horde_lz4_compress($testclass);

?>
--EXPECTF--

Warning: horde_lz4_compress() expects at least 1 parameter, 0 given in %s on line %d

Warning: horde_lz4_compress() expects at most 2 parameters, 3 given in %s on line %d

Warning: horde_lz4_compress: uncompressed data must be a string. in %s on line %d
