<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 */

/**
 * PGP backend that uses the PECL gnupg extension.
 *
 * NOTE: This class is NOT intended to be accessed outside of this package.
 * There is NO guarantees that the API of this class will not change across
 * versions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 */
class Horde_Crypt_Pgp_Backend_Pecl
extends Horde_Crypt_Pgp_Backend
{
    /**
     */
    static public function supported()
    {
        return Horde_Util::extensionExists('gnupg');
    }

    /**
     */
    public function getSignersKeyId($text)
    {
        $gpg = new gnupg();

        if (($info = $gpg->verify($text, false)) === false) {
            throw new RuntimeException();
        }

        if (count($info)) {
            $data = reset($info);
            if (isset($data['fingerprint'])) {
                return substr($data['fingerprint'], -8);
            }
        }

        return null;
    }

    /**
     */
    public function getFingerprintsFromKey($pgpdata)
    {
        $gpg = new gnupg();

        if (($info = $gpg->import($pgpdata)) === false) {
            throw new RuntimeException();
        }

        $out = array();

        if (strlen($info['fingerprint'])) {
            $out['0x' . substr($info['fingerprint'], -8)] = $info['fingerprint'];
        }

        return $out;
    }

}
