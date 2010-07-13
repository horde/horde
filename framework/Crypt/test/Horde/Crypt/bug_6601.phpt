--TEST--
Bug #6601
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

@date_default_timezone_set('GMT');

require 'pgp.inc';

echo $pgp->pgpPrettyKey(file_get_contents(dirname(__FILE__) . '/fixtures/bug_6601.asc'));
?>
--EXPECT--
Name:             Richard Selsky
Key Type:         Public Key
Key Creation:     04/11/08
Expiration Date:  [Never]
Key Length:       1024 Bytes
Comment:          [None]
E-Mail:           rselsky@bu.edu
Hash-Algorithm:   pgp-sha1
Key ID:           0xF3C01D42
Key Fingerprint:  5912 D91D 4C79 C670 1FFF  1486 04A6 7B37 F3C0 1D42

