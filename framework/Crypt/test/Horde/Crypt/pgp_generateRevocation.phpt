--TEST--
Horde_Crypt_pgp::generateRevocation()
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

require 'pgp.inc';
echo $pgp->generateRevocation($privkey, 'me@example.com', 'Secret');

?>
--EXPECTF--
-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: GnuPG v%d.%d.%d (%s)
Comment: A revocation certificate should follow

%s
%s
=%s
-----END PGP PUBLIC KEY BLOCK-----
