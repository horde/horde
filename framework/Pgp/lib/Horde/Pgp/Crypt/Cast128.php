<?php
/**
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */

/**
 * CAST5 (RFC 2144) implementation, using OpenSSL, sufficient to do
 * encryption/decryption for purposes of OpenPGP_Crypt_Symmetric.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2017 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp_Crypt_Cast128
{
    /**
     */
    public $block_size = 8;

    /**
     */
    public $key_size = 16;

    /**
     */
    private $_key;

    /**
     */
    private $_iv = "\0\0\0\0\0\0\0\0";

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!Horde_Util::extensionExists('openssl')) {
            throw new RuntimeException();
        }
    }

    /**
     */
    public function setKey($key)
    {
        $this->_key = $key;
    }

    /**
     */
    public function setIV($iv)
    {
        $this->_iv = $iv;
    }

    /**
     */
    public function encrypt($data)
    {
        return openssl_encrypt(
            $data,
            /* In OpenSSL, cast5-cfb == CAST128 w/NCFB encoding. */
            'cast5-cfb',
            $this->_key,
            OPENSSL_RAW_DATA,
            $this->_iv
        );
    }

    /**
     */
    public function decrypt($data)
    {
        return openssl_decrypt(
            $data,
            /* In OpenSSL, cast5-cfb == CAST128 w/NCFB encoding. */
            'cast5-cfb',
            $this->_key,
            OPENSSL_RAW_DATA,
            $this->_iv
        );
    }

}
