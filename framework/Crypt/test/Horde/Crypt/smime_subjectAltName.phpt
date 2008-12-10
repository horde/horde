--TEST--
Horde_Crypt_smime::getEmailFromKey() with subjectAltName
--SKIPIF--
<?php
echo "skip openssl_x509_parse() doesn't return subjectAltName of the example cert.";
require 'smime_skipif.inc';
?>
--FILE--
<?php

require 'smime.inc';
$key = file_get_contents(dirname(__FILE__) . '/fixtures/smime_subjectAltName.pem');
echo $smime->getEmailFromKey($key);

?>
--EXPECT--
test1@example.com
