--TEST--
Test horde_lz4_uncompress() function : max size
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
var_dump(strcmp(horde_lz4_uncompress($output, strlen($data)), $data));

?>
--EXPECT--
string(32) "58a645dbce1fcaf21f488b597726efa1"
int(0)
