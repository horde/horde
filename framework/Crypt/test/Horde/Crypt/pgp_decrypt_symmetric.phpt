--TEST--
Horde_Crypt_pgp::decrypt() message.
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

require 'pgp.inc';
$crypt = file_get_contents(dirname(__FILE__) . '/fixtures/pgp_encrypted_symmetric.txt');

$decrypt = $pgp->decrypt($crypt,
                         array('type' => 'message',
                               'passphrase' => 'Secret'));
if (is_a($decrypt, 'PEAR_Error')) {
    var_dump($decrypt);
} else {
    echo $decrypt->message;
}

?>
--EXPECT--
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
