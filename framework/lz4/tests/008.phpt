--TEST--
Test horde_lz4_compress() / horde_lz4_uncompress() function : extras and offset
--SKIPIF--
<?php
if (!extension_loaded('horde_lz4')) {
    die("skip horde_lz4 extension not loaded");
}
?>
--FILE--
<?php

include(dirname(__FILE__) . '/data.inc');

$extras = 'TEST';

$output = horde_lz4_compress($data, $extras);
var_dump(md5($output));
var_dump(strcmp(horde_lz4_uncompress($output, strlen($data), strlen($extras)), $data));

?>
--EXPECT--
string(32) "5930843ebc3b37585a35c8b8b0172a89"
int(0)
