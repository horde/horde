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
var_dump(md5($output));
var_dump(md5(horde_lz4_compress($output)));

?>
--EXPECTF--
string(32) "58a645dbce1fcaf21f488b597726efa1"
string(32) "91336cf7e1da47b49f03cd666d586450"
