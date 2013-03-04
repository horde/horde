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
var_dump(strcmp(horde_lz4_uncompress($output), $data));

// Compressing a smaller string
$smallstring = "A small string to compress\n";
$output = horde_lz4_compress($smallstring);
var_dump(strcmp(horde_lz4_uncompress($output), $smallstring));

?>
--EXPECT--
int(0)
int(0)
