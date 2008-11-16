--TEST--
Horde_Crypt_pgp::pgpPacketInformation()
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

require 'pgp.inc';
var_dump($pgp->pgpPacketInformation($pubkey));
var_dump($pgp->pgpPacketInformation($privkey));

?>
--EXPECT--
array(4) {
  ["public_key"]=>
  array(3) {
    ["created"]=>
    string(10) "1155291888"
    ["expires"]=>
    string(1) "0"
    ["size"]=>
    string(4) "1024"
  }
  ["signature"]=>
  array(2) {
    ["id1"]=>
    array(7) {
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
    }
    ["_SIGNATURE"]=>
    array(2) {
      ["micalg"]=>
      string(8) "pgp-sha1"
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
    }
  }
  ["keyid"]=>
  string(16) "7CA74426BADEABD7"
  ["fingerprint"]=>
  string(16) "7CA74426BADEABD7"
}
array(4) {
  ["secret_key"]=>
  array(3) {
    ["created"]=>
    string(10) "1155291888"
    ["expires"]=>
    string(1) "0"
    ["size"]=>
    string(4) "1024"
  }
  ["signature"]=>
  array(2) {
    ["id1"]=>
    array(7) {
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
    }
    ["_SIGNATURE"]=>
    array(2) {
      ["micalg"]=>
      string(8) "pgp-sha1"
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
    }
  }
  ["keyid"]=>
  string(16) "7CA74426BADEABD7"
  ["fingerprint"]=>
  string(16) "7CA74426BADEABD7"
}
