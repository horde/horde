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
extends Horde_Pgp_Element
{
    /**
     */
    protected $_armor = 'SIGNATURE';

    /**
     * Return the key ID used for the signature.
     *
     * @return string  Key ID.
     */
    public function getSignersKeyId()
    {
        foreach ($this->message as $val) {
            if ($val instanceof OpenPGP_SignaturePacket) {
                return substr($val->issuer(), -8);
            }
        }

        return null;
    }

}
