--TEST--
Bug #6896 MIME::rfc822Explode parsing broken
--FILE--
<?php

require dirname(__FILE__) . '/../MIME.php';
var_dump(MIME::rfc822Explode('addr1@example.com, addr2@example.com'));

?>
--EXPECT--
array(2) {
  [0]=>
  string(17) "addr1@example.com"
  [1]=>
  string(18) " addr2@example.com"
}
