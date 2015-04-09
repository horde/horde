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
 * PGP element: signatures.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp_Element_Signature
extends Horde_Pgp_Element_Armored
{
    /**
     */
    static protected $_header = 'SIGNATURE';

    /**
     * Return canonical signature element for given PGP data.
     *
     * @param mixed $data PGP data.
     *
     * @return Horde_Pgp_Element_Signature  Signature part.
     */
    static public function findSignature($data)
    {
        $armor = Horde_Pgp_Armor::create($data);

        foreach ($armor as $val) {
            if ($val instanceof Horde_Pgp_Element_Signature) {
                return $val;
            }

            if ($val instanceof Horde_Pgp_Element_SignedMessage) {
                return $val->getSignaturePart();
            }
        }

        return null;
    }

    /**
     * Return the key ID used for the signature.
     *
     * @return string  Key ID.
     */
    public function getSignersKeyId()
    {
        foreach ($this->getMessageOb() as $val) {
            if ($val instanceof OpenPGP_SignaturePacket) {
                return substr($val->issuer(), -8);
            }
        }

        return null;
    }

}
