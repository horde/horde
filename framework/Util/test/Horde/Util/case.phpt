--TEST--
Horde_String:: case tests
--SKIPIF--
<?php
if (!setlocale(LC_ALL, 'tr_TR')) echo 'skip No Turkish locale installed.';
?>
--FILE--
<?php

require_once dirname(__FILE__) . '/../../lib/Horde/Util.php';
require_once dirname(__FILE__) . '/../../lib/Horde/String.php';

echo Horde_String::upper('abCDefGHiI', true, 'us-ascii') . "\n";
echo Horde_String::lower('abCDefGHiI', true, 'us-ascii') . "\n";
echo "\n";
echo Horde_String::upper('abCDefGHiI', true, 'Big5') . "\n";
echo Horde_String::lower('abCDefGHiI', true, 'Big5') . "\n";
echo "\n";
setlocale(LC_ALL, 'tr_TR');
echo strtoupper('abCDefGHiI') . "\n";
echo strtolower('abCDefGHiI') . "\n";
echo ucfirst('integer') . "\n";
echo "\n";
echo Horde_String::upper('abCDefGHiI') . "\n";
echo Horde_String::lower('abCDefGHiI') . "\n";
echo Horde_String::ucfirst('integer') . "\n";

?>
--EXPECT--
ABCDEFGHII
abcdefghii

ABCDEFGHII
abcdefghii

ABCDEFGHÝI
abcdefghiý
Ýnteger

ABCDEFGHII
abcdefghii
Integer
