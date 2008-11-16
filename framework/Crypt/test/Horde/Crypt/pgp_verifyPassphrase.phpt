--TEST--
Horde_Crypt_pgp::verifyPassphrase().
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

require 'pgp.inc';

var_dump($pgp->verifyPassphrase($pubkey, $privkey, 'Secret'));
var_dump($pgp->verifyPassphrase($pubkey, $privkey, 'Wrong'));

?>
--EXPECT--
bool(true)
bool(false)
