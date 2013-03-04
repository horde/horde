--TEST--
Test horde_lz4_compress() function : basic functionality
--SKIPIF--
<?php
if (!extension_loaded('horde_lz4')) {
    die("skip horde_lz4 extension not loaded");
}
?>
--FILE--
<?php

include(dirname(__FILE__) . '/data.inc');

// Compressing a big string
$output = horde_lz4_compress($data);
var_dump(md5($output));
var_dump(strcmp(horde_lz4_uncompress($output), $data));

// Compressing a smaller string
$smallstring = "A small string to compress\n";
$output = horde_lz4_compress($smallstring);
var_dump(bin2hex($output));
var_dump(strcmp(horde_lz4_uncompress($output), $smallstring));

?>
--EXPECT--
string(32) "58a645dbce1fcaf21f488b597726efa1"
int(0)
string(66) "1b000000f00c4120736d616c6c20737472696e6720746f20636f6d70726573730a"
int(0)
