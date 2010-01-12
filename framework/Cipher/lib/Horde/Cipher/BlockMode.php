<?php
/**
 * The Horde_Cipher_BlockMode:: class provides a common abstracted
 * interface to various block mode handlers for ciphers.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Horde_Cipher
 */
class Horde_Cipher_BlockMode
{
    /**
     * The initialization vector.
     *
     * @var string
     */
    protected $_iv = "\0\0\0\0\0\0\0\0";

    /**
     * Attempts to return a concrete instance based on $mode.
     *
     * @param string $mode   The type of concrete subclass to return.
     *                       subclass to return.
     * @param array $params  A hash containing any additional parameters a
     *                       subclass might need.
     *
     * @return Horde_Cipher_BlockMode  The newly created concrete instance.
     * @throws Horde_Exception
     */
    static public function factory($driver, $params = null)
    {
        $class = 'Horde_Cipher_BlockMode_' . Horde_String::ucfirst(basename($driver));
        if (!class_exists($class)) {
            throw new Horde_Exception('Driver ' . $driver . ' not found');
        }
        return new $class($params);
    }

    /**
     * Set the IV.
     *
     * @param string $iv  The new IV.
     */
    public function setIV($iv)
    {
        $this->_iv = $iv;
    }

    /**
     * Encrypt a string.
     *
     * @param Horde_Cipher $cipher  Cipher algorithm to use for encryption.
     * @param string $plaintext     The data to encrypt.
     *
     * @return string  The encrypted data.
     */
    public function encrypt($cipher, $plaintext)
    {
        return $plaintext;
    }

    /**
     * Decrypt a string.
     *
     * @param Horde_Cipher $cipher  Cipher algorithm to use for decryption.
     * @param string $ciphertext    The data to decrypt.
     *
     * @return string  The decrypted data.
     */
    public function decrypt($cipher, $ciphertext)
    {
        return $ciphertext;
    }

}
