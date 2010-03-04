--TEST--
JSON encode/decode tests (invalid UTF-8 input).
--SKIPIF--
<?php
    if (version_compare(phpversion(), '5.3.0') == -1) {
        echo "skip Test requires PHP 5.3+";
    }
?>
--FILE--
<?php

error_reporting(E_ALL);

require dirname(__FILE__) . '/../../../lib/Horde/Serialize.php';
require dirname(__FILE__) . '/../../../../Util/lib/Horde/String.php';
require dirname(__FILE__) . '/../../../../Util/lib/Horde/Util.php';

echo Horde_Serialize::serialize(file_get_contents('./fixtures/badutf8.txt'), Horde_Serialize::JSON);

?>
--EXPECT--
"Note: To play video messages sent to email, QuickTime\u00ae 6.5 or higher is required.\n"
