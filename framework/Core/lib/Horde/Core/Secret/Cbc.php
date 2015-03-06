<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 */

/**
 * Horde_Secret, using single session key, with CBC based Blowfish encryption.
 *
 * This is much more secure than the default Horde_Secret algorithm. It should
 * be used for all Horde_Secret/session encryption, but for BC purposes it
 * needs to live in a separate class for now.
 *
 * Uses the additional parameter 'iv' - the IV used to seed the CBC cipher.
 *
 * @todo  Merge this class with Horde_Core_Secret.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 * @since     2.20.0
 */
class Horde_Core_Secret_Cbc extends Horde_Core_Secret
{
    /**
     */
    protected function _getCipherOb($key)
    {
        global $conf;

        if (!isset($this->_cipherCache[self::HORDE_KEYNAME])) {
            /* Use more secure CBC mode (rather than ECB). */
            $this->_cipherCache[self::HORDE_KEYNAME] = new Horde_Crypt_Blowfish(
                substr($key, 0, 56),
                array(
                    'cipher' => 'cbc',
                    'iv' => $this->_params['iv']
                )
            );
        }

        return $this->_cipherCache[self::HORDE_KEYNAME];
    }

}
