--TEST--
Horde_Crypt_pgp::pgpPacketSignatureByUidIndex()
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

require 'pgp.inc';
var_dump($pgp->pgpPacketSignatureByUidIndex($pubkey, 'id1'));
var_dump($pgp->pgpPacketSignatureByUidIndex($privkey, 'id1'));
var_dump($pgp->pgpPacketSignatureByUidIndex($privkey, 'id2'));

?>
--EXPECT--
array(11) {
  ["name"]=>
  string(7) "My Name"
  ["comment"]=>
  string(10) "My Comment"
  ["email"]=>
  string(14) "me@example.com"
  ["keyid"]=>
  string(16) "7CA74426BADEABD7"
  ["fingerprint"]=>
  string(16) "7CA74426BADEABD7"
  ["sig_7CA74426BADEABD7"]=>
  array(4) {
    ["keyid"]=>
    string(16) "7CA74426BADEABD7"
    ["fingerprint"]=>
    string(16) "7CA74426BADEABD7"
    ["created"]=>
    string(10) "1155291888"
    ["micalg"]=>
    string(8) "pgp-sha1"
  }
  ["micalg"]=>
  string(8) "pgp-sha1"
  ["key_type"]=>
  string(10) "public_key"
  ["key_created"]=>
  string(10) "1155291888"
  ["key_expires"]=>
  string(1) "0"
  ["key_size"]=>
  string(4) "1024"
}
array(11) {
  ["name"]=>
  string(7) "My Name"
  ["comment"]=>
  string(10) "My Comment"
  ["email"]=>
  string(14) "me@example.com"
  ["keyid"]=>
  string(16) "7CA74426BADEABD7"
  ["fingerprint"]=>
  string(16) "7CA74426BADEABD7"
  ["sig_7CA74426BADEABD7"]=>
  array(4) {
    ["keyid"]=>
    string(16) "7CA74426BADEABD7"
    ["fingerprint"]=>
    string(16) "7CA74426BADEABD7"
    ["created"]=>
    string(10) "1155291888"
    ["micalg"]=>
    string(8) "pgp-sha1"
  }
  ["micalg"]=>
  string(8) "pgp-sha1"
  ["key_type"]=>
  string(10) "secret_key"
  ["key_created"]=>
  string(10) "1155291888"
  ["key_expires"]=>
  string(1) "0"
  ["key_size"]=>
  string(4) "1024"
}
array(0) {
}
