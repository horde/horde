--TEST--
Test horde_lz4_uncompress() function : bad input (non-lz4 data)
--SKIPIF--
<?php
if (!extension_loaded('horde_lz4')) {
    die("skip horde_lz4 extension not loaded");
}
?>
--FILE--
<?php

include(dirname(__FILE__) . '/data.inc');

// Bad data is missing the Horde-LZ4 header and is not LZ4 data.
$bad_data = "12345678";
var_dump(horde_lz4_uncompress($bad_data));

?>
--EXPECT--
bool(false)
