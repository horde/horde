--TEST--
Bug #6896 MIME::rfc822Explode parsing broken
--FILE--
<?php

require dirname(__FILE__) . '/../../../lib/Horde/Mime/Address.php';
var_dump(Horde_Mime_Address::explode('addr1@example.com, addr2@example.com'));

?>
--EXPECT--
array(2) {
  [0]=>
  string(17) "addr1@example.com"
  [1]=>
  string(18) " addr2@example.com"
}
