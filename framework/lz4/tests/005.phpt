--TEST--
Test horde_lz4_uncompress() function : error conditions
--SKIPIF--
<?php
if (!extension_loaded('horde_lz4')) {
    die("skip horde_lz4 extension not loaded");
}
?>
--FILE--
<?php

// Zero arguments
horde_lz4_uncompress();

// Test horde_lz4_uncompress with one more than the expected number of arguments
$data = 'string_val';
$extra_arg = 10;
horde_lz4_uncompress($data, $extra_arg);

// Testing with incorrect arguments
horde_lz4_uncompress(123);

class Tester
{
    function Hello()
    {
        echo "Hello\n";
    }
}

$testclass = new Tester();
horde_lz4_uncompress($testclass);

?>
--EXPECTF--

Warning: horde_lz4_uncompress() expects exactly 1 parameter, 0 given in %s on line %d

Warning: horde_lz4_uncompress() expects exactly 1 parameter, 2 given in %s on line %d

Warning: horde_lz4_uncompress: compressed data must be a string. in %s on line %d

Warning: horde_lz4_uncompress: compressed data must be a string. in %s on line %d
