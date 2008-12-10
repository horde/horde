--TEST--
Horde_Crypt_pgp::encrypt() signature.
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

require 'pgp.inc';
$clear = file_get_contents(dirname(__FILE__) . '/fixtures/clear.txt');

echo $pgp->encrypt($clear,
                   array('type' => 'signature',
                         'pubkey' => $pubkey,
                         'privkey' => $privkey,
                         'passphrase' => 'Secret'));
echo $pgp->encrypt($clear,
                   array('type' => 'signature',
                         'pubkey' => $pubkey,
                         'privkey' => $privkey,
                         'passphrase' => 'Secret',
                         'sigtype' => 'cleartext'));

?>
--EXPECTF--
-----BEGIN PGP SIGNATURE-----
Version: GnuPG v%d.%d.%d (%s)

%s
%s
=%s
-----END PGP SIGNATURE-----
-----BEGIN PGP SIGNED MESSAGE-----
Hash: SHA1

0123456789012345678901234567890123456789
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
0123456789012345678901234567890123456789
!"$§%&()=?^´°`+#-.,*'_:;<>|~\{[]}
-----BEGIN PGP SIGNATURE-----
Version: GnuPG v%d.%d.%d (%s)

%s
%s
=%s
-----END PGP SIGNATURE-----
