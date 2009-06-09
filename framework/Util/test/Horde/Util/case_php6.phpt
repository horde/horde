--TEST--
Horde_String:: case PHP 6 tests
--SKIPIF--
<?php
if (version_compare(PHP_VERSION, '6.0', '<')) {
   echo 'skip mbstring is broken in PHP < 6.0';
}
?>
--FILE--
<?php

require_once dirname(__FILE__) . '/../../lib/Horde/Util.php';
require_once dirname(__FILE__) . '/../../lib/Horde/String.php';

echo Horde_String::upper('abCDefGHiI', true, 'iso-8859-9') . "\n";
echo Horde_String::lower('abCDefGHiI', true, 'iso-8859-9') . "\n";
echo "\n";
echo Horde_String::ucfirst('integer', true, 'us-ascii') . "\n";
echo Horde_String::ucfirst('integer', true, 'iso-8859-9') . "\n";

?>
--EXPECT--
ABCDEFGHÝI
abcdefghiý

Integer
Ýnteger
