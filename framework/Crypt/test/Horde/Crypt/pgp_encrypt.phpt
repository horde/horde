--TEST--
Horde_Crypt_pgp::encrypt() message.
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

require 'pgp.inc';
$clear = file_get_contents(dirname(__FILE__) . '/fixtures/clear.txt');

echo $pgp->encrypt($clear,
                   array('type' => 'message',
                         'recips' => array('me@example.com' => $pubkey)));

?>
--EXPECTF--
-----BEGIN PGP MESSAGE-----
Version: GnuPG v%d.%d.%d (%s)

%s
%s
%s
%s
%s
%s
%s
%s
%s
%s
=%s
-----END PGP MESSAGE-----
