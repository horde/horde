<?php
/**
 * Openssl driver for blowfish encryption.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt_Blowfish
 */

/**
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt_Blowfish
 */
class Horde_Crypt_Blowfish_Openssl extends Horde_Crypt_Blowfish_Base
{
    /**
     */
    static public function supported()
    {
        return extension_loaded('openssl');
    }

    /**
     */
    public function encrypt($text)
    {
        return openssl_encrypt($text, 'bf-' . $this->cipher, $this->key, true, strval($this->iv));
    }

    /**
     */
    public function decrypt($text)
    {
        return openssl_decrypt($text, 'bf-' . $this->cipher, $this->key, true, strval($this->iv));
    }

}
