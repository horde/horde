--TEST--
Horde_Crypt_pgp::pgpPrettyKey()
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

require 'pgp.inc';
echo $pgp->pgpPrettyKey($pubkey);
echo $pgp->pgpPrettyKey($privkey);

?>
--EXPECT--
Name:             My Name
Key Type:         Public Key
Key Creation:     08/11/06
Expiration Date:  [Never]
Key Length:       1024 Bytes
Comment:          My Comment
E-Mail:           me@example.com
Hash-Algorithm:   pgp-sha1
Key ID:           0xBADEABD7
Key Fingerprint:  966F 4BA9 569D E6F6 5E82  5397 7CA7 4426 BADE ABD7

Name:             My Name
Key Type:         Private Key
Key Creation:     08/11/06
Expiration Date:  [Never]
Key Length:       1024 Bytes
Comment:          My Comment
E-Mail:           me@example.com
Hash-Algorithm:   pgp-sha1
Key ID:           0xBADEABD7
Key Fingerprint:  966F 4BA9 569D E6F6 5E82  5397 7CA7 4426 BADE ABD7
