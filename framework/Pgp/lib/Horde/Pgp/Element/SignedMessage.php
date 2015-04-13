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
 * PGP armor part: signed message.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 *
 * @property-read Horde_Pgp_Element_Signature $signature  Signature element.
 * @property-read Horde_Pgp_Element_Text $text  Literal text element.
 */
class Horde_Pgp_Element_SignedMessage
extends Horde_Pgp_Element
{
    /**
     */
    protected $_armor = 'SIGNED MESSAGE';

    /**
     */
    public function __construct($data, array $headers = array())
    {
        if (!($data instanceof OpenPGP_Message)) {
            Horde_Pgp_Backend_Openpgp::autoload();
            $msg = new OpenPGP_Message();

            $pos = strpos($data, '-----BEGIN PGP SIGNATURE-----');
            $msg[] = new OpenPGP_LiteralDataPacket(
                substr($data, 0, $pos),
                array('format' => 'u')
            );
            $msg[] = Horde_Pgp_Element_Signature::create(
                substr($data, $pos) .  "-----END PGP SIGNATURE-----\n"
            )->message[0];
        } else {
            $msg = $data;
        }

        parent::__construct($msg, $headers);
    }

    /**
     */
    public function __toString()
    {
        $out = parent::__toString();

        /* Remove trailing END SIGNED MESSAGE armor. */
        return substr($out, 0, strrpos($out, '-----END'));
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'signature':
            return new Horde_Pgp_Element_Signature(
                new OpenPGP_Message(array($this->message[1]))
            );

        case 'text':
            return new Horde_Pgp_Element_Text(
                new OpenPGP_Message(array($this->message[0]))
            );
        }
    }

}
