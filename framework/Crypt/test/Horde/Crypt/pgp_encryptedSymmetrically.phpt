--TEST--
Horde_Crypt_pgp::encryptedSymmetrically()
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

require 'pgp.inc';
var_dump($pgp->encryptedSymmetrically(file_get_contents(dirname(__FILE__) . '/fixtures/pgp_encrypted.txt')));
var_dump($pgp->encryptedSymmetrically(file_get_contents(dirname(__FILE__) . '/fixtures/pgp_encrypted_symmetric.txt')));

?>
--EXPECT--
bool(false)
bool(true)
