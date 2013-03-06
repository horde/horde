--TEST--
Test horde_lz4_uncompress() function : basic functionality
--SKIPIF--
<?php
if (!extension_loaded('horde_lz4')) {
    die("skip horde_lz4 extension not loaded");
}
?>
--FILE--
<?php

include(dirname(__FILE__) . '/data.inc');

$compressed = horde_lz4_compress($data);
var_dump(strcmp($data, horde_lz4_uncompress($compressed)));

?>
--EXPECT--
int(0)
