--TEST--
Horde_Mime RFC 2231 & workaround for broken MUA's
--FILE--
<?php

require dirname(__FILE__) . '/../../../lib/Horde/Mime.php';
require_once 'Horde/String.php';
require_once 'Horde/Util.php';

Horde_Mime::$brokenRFC2231 = true;
var_dump(Horde_Mime::encodeParam('test', str_repeat('a', 100) . '.txt', 'UTF-8'));

?>
--EXPECT--
array(3) {
  ["test"]=>
  string(104) "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.txt"
  ["test*0"]=>
  string(68) "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"
  ["test*1"]=>
  string(36) "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.txt"
}
