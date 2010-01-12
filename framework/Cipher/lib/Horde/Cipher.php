<?php
/**
 * The Horde_Cipher:: class provides a common abstracted interface to
 * various Ciphers for encryption of arbitrary length pieces of data.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Horde_Cipher
 */
class Horde_Cipher
{
    /**
     * The block mode for the cipher chaining
     *
     * @var string
     */
    protected $_blockMode = 'cbc';

    /**
     * The block size.
     *
     * @var integer
     */
    protected $_blockSize = 8;

    /**
     * The initialization vector
     *
     * @var string
     */
    protected $_iv = null;

    /**
     * Attempts to return a concrete Horde_Cipher instance.
     *
     * @param string $cipher  The type of concrete Horde_Cipher subclass to
     *                        return.
     * @param array $params   A hash containing any additional parameters a
     *                        subclass might need.
     *
     * @return Horde_Cipher  The newly created concrete Horde_Cipher instance.
     * @throws Horde_Exception
     */
    static public function factory($driver, $params = null)
    {
        $class = 'Horde_Cipher_' . Horde_String::ucfirst(basename($driver));
        if (!class_exists($class)) {
            throw new Horde_Exception('Driver ' . $driver . ' not found');
        }
        return new $class($params);
    }

    /**
     * Set the block mode for cipher chaining.
     *
     * @param string $blockMode  The new blockmode.
     */
    public function setBlockMode($blockMode)
    {
        $this->_blockMode = $blockMode;
    }

    /**
     * Return the size of the blocks that this cipher needs.
     *
     * @return integer  The number of characters per block.
     */
    public function getBlockSize()
    {
        return $this->_blockSize;
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
     * @param string $plaintext  The data to encrypt.
     *
     * @return string  The encrypted data.
     */
    public function encrypt($plaintext)
    {
        $blockMode = Horde_Cipher_BlockMode::factory($this->_blockMode);

        if (!is_null($this->_iv)) {
            $blockMode->setIV($this->_iv);
        }

        return $blockMode->encrypt($this, $plaintext);
    }

    /**
     * Decrypt a string.
     *
     * @param string $ciphertext  The data to decrypt.
     *
     * @return string  The decrypted data.
     */
    public function decrypt($ciphertext)
    {
        $blockMode = Horde_Cipher_BlockMode::factory($this->_blockMode);

        if (!is_null($this->_iv)) {
            $blockMode->setIV($this->_iv);
        }

        return $blockMode->decrypt($this, $ciphertext);
    }

}
