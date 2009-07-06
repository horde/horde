<?php
/**
 * This script contains functions used for the cipher tests.
 *
 * @package Horde_Cipher
 */

require_once 'Horde/String.php';

function testCipher($cipher, $key,  $plaintext, $ciphertext)
{
    $cipher = &Horde_Cipher::factory($cipher);
    $cipher->setKey($key);

    echo "Testing Encryption: ";
    $res = $cipher->encryptBlock($plaintext);
    if ($res == $ciphertext) {
        echo "Pass\n";
    } else {
        echo "Fail\n";
        echo "Returned: ";
        for ($i = 0; $i < strlen($res); $i++) {
            echo str_pad(dechex(ord(substr($res, $i, 1))), 2, '0', STR_PAD_LEFT) . " ";
        } echo "\n";
        echo "Expected: ";
        for ($i = 0; $i < strlen($ciphertext); $i++) {
            echo str_pad(dechex(ord(substr($ciphertext, $i, 1))), 2, '0', STR_PAD_LEFT)  . " ";
        } echo "\n";

    }
    echo "Testing Decryption: ";
    $res = $cipher->decryptBlock($ciphertext);
    if ($res == $plaintext) {
        echo "Pass\n";
    } else {
        echo "Fail\n";
        echo "Returned: ";
        for ($i = 0; $i < strlen($res); $i++) {
            echo str_pad(dechex(ord(substr($res, $i, 1))), 2, '0', STR_PAD_LEFT) . " ";
        } echo "\n";
        echo "Expected: ";
        for ($i = 0; $i < strlen($plaintext); $i++) {
            echo str_pad(dechex(ord(substr($plaintext, $i, 1))), 2, '0', STR_PAD_LEFT)  . " ";
        } echo "\n";
    }
    echo "\n";
    flush();
}
