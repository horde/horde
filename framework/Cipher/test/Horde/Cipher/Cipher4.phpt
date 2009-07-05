--TEST--
Cast128 Horde_Cipher:: Tests
--FILE--
<?php

require_once dirname(__FILE__) . '/cipher_functions.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Cipher.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/Cipher/Cast128.php';

/* Cast 128 Cipher */
echo "Cast 128:\n";
echo "---------\n\n";

// 128 Bit key test
echo "128-bit Key\n";
$key = "\x01\x23\x45\x67\x12\x34\x56\x78\x23\x45\x67\x89\x34\x56\x78\x9A";
$plaintext = "\x01\x23\x45\x67\x89\xAB\xCD\xEF";
$ciphertext = "\x23\x8B\x4F\xE5\x84\x7E\x44\xB2";
testCipher('cast128', $key, $plaintext, $ciphertext);

// 80 Bit key text
echo "80-bit Key\n";
$key = "\x01\x23\x45\x67\x12\x34\x56\x78\x23\x45";
$plaintext = "\x01\x23\x45\x67\x89\xAB\xCD\xEF";
$ciphertext = "\xEB\x6A\x71\x1A\x2C\x02\x27\x1B";
testCipher('cast128', $key, $plaintext, $ciphertext);

// 40 Bit key text
echo "40-bit Key\n";
$key = "\x01\x23\x45\x67\x12";
$plaintext = "\x01\x23\x45\x67\x89\xAB\xCD\xEF";
$ciphertext = "\x7A\xC8\x16\xD1\x6E\x9B\x30\x2E";
testCipher('cast128', $key, $plaintext, $ciphertext);

?>
--EXPECT--
Cast 128:
---------

128-bit Key
Testing Encryption: Pass
Testing Decryption: Pass

80-bit Key
Testing Encryption: Pass
Testing Decryption: Pass

40-bit Key
Testing Encryption: Pass
Testing Decryption: Pass
