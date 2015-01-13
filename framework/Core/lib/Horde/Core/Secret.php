<?php
/**
 * Wrap the base class in order to use a single secret key when authenticated
 * to Horde, to reduce complexity and minimze cookie size.
 *
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Core
 */
class Horde_Core_Secret extends Horde_Secret
{
    const HORDE_KEYNAME = 'horde_secret';

    /**
     */
    public function setKey($keyname = self::DEFAULT_KEY)
    {
        return parent::setKey(self::HORDE_KEYNAME);
    }

    /**
     */
    public function getKey($keyname = self::DEFAULT_KEY)
    {
        return parent::getKey(self::HORDE_KEYNAME);
    }

    /**
     */
    public function clearKey($keyname = self::DEFAULT_KEY)
    {
        return parent::clearKey(self::HORDE_KEYNAME);
    }

    /**
     */
    protected function _getCipherOb($key)
    {
        global $conf;

        if (!isset($this->_cipherCache[self::HORDE_KEYNAME])) {
            /* Use more secure CBC mode (rather than ECB). This requires an
             * IV, so use the global 'secret_key'. */
            $this->_cipherCache[self::HORDE_KEYNAME] = new Horde_Crypt_Blowfish(
                substr($key, 0, 56),
                array(
                    'cipher' => 'cbc',
                    'iv' => $conf['secret_key']
                )
            );
        }

        return $this->_cipherCache[self::HORDE_KEYNAME];
    }

}
