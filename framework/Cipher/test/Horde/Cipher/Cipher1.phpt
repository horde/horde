--TEST--
RC4 Horde_Cipher:: Tests
--SKIPIF--
<?php
    $rc4 = @include_once 'Crypt/Rc4.php';
    if (!$rc4) echo 'skip Crypt_Rc4 pear module not available';
?>
--FILE--
<?php

require_once dirname(__FILE__) . '/cipher_functions.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Cipher.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Cipher/Rc4.php';

/* RC4 Cipher */
echo "RC4:\n";
echo "----\n\n";

// 64 Bit key test
echo "64-bit Key\n";
$key = "\x01\x23\x45\x67\x89\xab\xcd\xef";
$plaintext = "\x01\x23\x45\x67\x89\xab\xcd\xef";
$ciphertext = "\x75\xb7\x87\x80\x99\xe0\xc5\x96";
testCipher('rc4', $key, $plaintext, $ciphertext);

// 64 Bit key test
echo "64-bit Key\n";
$key = "\x01\x23\x45\x67\x89\xab\xcd\xef";
$plaintext = "\x00\x00\x00\x00\x00\x00\x00\x00";
$ciphertext = "\x74\x94\xc2\xe7\x10\x4b\x08\x79";
testCipher('rc4', $key, $plaintext, $ciphertext);

// 64 Bit key test
echo "64-bit Key\n";
$key = "\x00\x00\x00\x00\x00\x00\x00\x00";
$plaintext = "\x00\x00\x00\x00\x00\x00\x00\x00";
$ciphertext = "\xde\x18\x89\x41\xa3\x37\x5d\x3a";
testCipher('rc4', $key, $plaintext, $ciphertext);

// 32 Bit key test
echo "32-bit Key\n";
$key = "\xef\x01\x23\x45";
$plaintext = "\x00\x00\x00\x00\x00\x00\x00\x00";
$ciphertext = "\xd6\xa1\x41\xa7\xec\x3c\x38\xdf";
testCipher('rc4', $key, $plaintext, $ciphertext);

?>
--EXPECT--
RC4:
----

64-bit Key
Testing Encryption: Pass
Testing Decryption: Pass

64-bit Key
Testing Encryption: Pass
Testing Decryption: Pass

64-bit Key
Testing Encryption: Pass
Testing Decryption: Pass

32-bit Key
Testing Encryption: Pass
Testing Decryption: Pass
