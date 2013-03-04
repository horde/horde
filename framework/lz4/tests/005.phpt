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
var_dump(horde_lz4_uncompress());

// Test horde_lz4_uncompress with one more than the expected number of arguments
$data = 'string_val';
$extra_arg = 10;
var_dump(horde_lz4_uncompress($data, -1, -1, $extra_arg));

// Testing with incorrect arguments
var_dump(horde_lz4_uncompress(123));

class Tester
{
    function Hello()
    {
        echo "Hello\n";
    }
}

$testclass = new Tester();
var_dump(horde_lz4_uncompress($testclass));

?>
--EXPECTF--

Warning: horde_lz4_uncompress() expects at least 1 parameter, 0 given in %s on line %d
bool(false)

Warning: horde_lz4_uncompress() expects at most 3 parameters, 4 given in %s on line %d
bool(false)

Warning: horde_lz4_uncompress: expects parameter to be string. in %s on line %d
bool(false)

Warning: horde_lz4_uncompress: expects parameter to be string. in %s on line %d
bool(false)
