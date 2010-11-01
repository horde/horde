--TEST--
Horde_Crypt_pgp::decrypt() signature.
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

date_default_timezone_set('GMT');

require 'pgp.inc';

echo $pgp->decrypt(file_get_contents(dirname(__FILE__) . '/fixtures/clear.txt'),
                   array('type' => 'detached-signature',
                         'pubkey' => $pubkey,
                         'signature' => file_get_contents(dirname(__FILE__) . '/fixtures/pgp_signature.txt')));

echo $pgp->decrypt(file_get_contents(dirname(__FILE__) . '/fixtures/pgp_signed.txt'),
                   array('type' => 'signature',
                         'pubkey' => $pubkey));

echo $pgp->decrypt(file_get_contents(dirname(__FILE__) . '/fixtures/pgp_signed2.txt'),
                   array('type' => 'signature',
                         'pubkey' => $pubkey));

?>
--EXPECT--
gpg: Signature made Fri Aug 11 14:42:54 2006 GMT using DSA key ID BADEABD7
gpg: Good signature from "My Name (My Comment) <me@example.com>"
gpg: Signature made Fri Aug 11 14:25:36 2006 GMT using DSA key ID BADEABD7
gpg: Good signature from "My Name (My Comment) <me@example.com>"
gpg: Signature made Fri Aug 11 14:28:48 2006 GMT using DSA key ID BADEABD7
gpg: Good signature from "My Name (My Comment) <me@example.com>"
