<?php
/**
 * The Horde_Cipher_Rc4:: class implements the Horde_Cipher interface
 * encryption data using the RC4 encryption algorthim. This class uses the
 * PEAR Crypt_RC4 class to do the encryption.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Horde_Cipher
 */
class Horde_Cipher_Rc4 extends Horde_Cipher
{
    /**
     * Pointer to a PEAR Crypt_RC4 object
     *
     * @var Crypt_RC4
     */
    protected $_cipher;

    /**
     * Constructor.
     */
    public function __construct($params = null)
    {
        $this->_cipher = new Crypt_Rc4();
    }

    /**
     * Set the key to be used for en/decryption.
     *
     * @param string $key  The key to use.
     */
    public function setKey($key)
    {
        $this->_cipher->setKey($key);
    }

    /**
     * Encrypt a block of data.
     *
     * @param string $block  The data to encrypt.
     * @param string $key    The key to use.
     *
     * @return string  The encrypted output.
     */
    public function encryptBlock($block, $key = null)
    {
        if (!is_null($key)) {
            $this->setKey($key);
        }

        // Make a copy of the cipher as it destroys itself during a crypt
        $cipher = $this->_cipher;
        $cipher->crypt($block);

        return $block;
    }

    /**
     * Decrypt a block of data.
     *
     * @param string $block  The data to decrypt.
     * @param string $key    The key to use.
     *
     * @return string  The decrypted output.
     */
    public function decryptBlock($block, $key = null)
    {
        if (!is_null($key)) {
            $this->setKey($key);
        }

        // Make a copy of the cipher as it destroys itself during a
        // crypt.
        $cipher = $this->_cipher;
        $cipher->decrypt($block);

        return $block;
    }

}
