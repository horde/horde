--TEST--
Bug #338 (fileinfo returning charset)
--SKIPIF--
<?php if (!extension_loaded('fileinfo')) echo 'skip No fileinfo extension installed'; ?>
--FILE--
<?php
require_once 'Horde/Util.php';
require dirname(__FILE__) . '/../../../lib/Horde/Mime/Magic.php';
echo Horde_Mime_Magic::analyzeFile(dirname(__FILE__) . '/bug_325.txt');
?>
--EXPECT--
text/plain
