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
 * PGP element: public key.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp_Element_PublicKey
extends Horde_Pgp_Element_Key
{
    /**
     */
    static protected $_header = 'PUBLIC KEY BLOCK';

    /**
     */
    public function getPublicKey()
    {
        return $this;
    }

    /**
     * Generates a Horde_Mime_Part object, in accordance with RFC 3156, that
     * contains a public key.
     *
     * @return Horde_Mime_Part  Object that contains the armored public key.
     */
    public function createMimePart()
    {
        $part = new Horde_Mime_Part();
        $part->setType('application/pgp-keys');
        $part->setHeaderCharset('UTF-8');
        $part->setDescription(Horde_Pgp_Translation::t("PGP Public Key"));
        $part->setContents(strval($this), array('encoding' => '7bit'));

        return $part;
    }

}
