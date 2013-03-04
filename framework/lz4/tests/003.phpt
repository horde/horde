--TEST--
Test horde_lz4_compress() function : variation
--SKIPIF--
<?php
if (!extension_loaded('horde_lz4')) {
    die("skip horde_lz4 extension not loaded");
}
?>
--FILE--
<?php

include(dirname(__FILE__) . '/data.inc');

$output = horde_lz4_compress($data);
var_dump(md5($output) != md5(horde_lz4_compress($output)));

?>
--EXPECT--
bool(true)
