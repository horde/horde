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
 * @package   Pgp
 */

/**
 * PGP element: private key.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp_Element_PrivateKey
extends Horde_Pgp_Element_Key
{
    /**
     */
    static protected $_header = 'PRIVATE KEY BLOCK';

    /**
     */
    public function getPublicKey()
    {
        $parse = $this->getMessageOb();
        $pubkey = clone $parse;

        foreach ($parse as $key => $val) {
            if ($val instanceof OpenPGP_SecretKeyPacket) {
                $ob = ($val instanceof OpenPGP_SecretSubkeyPacket)
                    ? new OpenPGP_PublicSubkeyPacket()
                    : new OpenPGP_PublicKeyPacket();
                foreach (array_keys(get_object_vars($ob)) as $key2) {
                    if ($key2 !== 'tag') {
                        $ob->$key2 = $val->$key2;
                    }
                }
                $pubkey[$key] = $ob;
            }
        }

        return Horde_Pgp_Element_PublicKey::createFromRawData(
            $pubkey->to_bytes()
        );
    }

}
