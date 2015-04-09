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
 */
class Horde_Pgp_Element_SignedMessage
extends Horde_Pgp_Element_Armored
{
    /**
     */
    static protected $_header = 'SIGNED MESSAGE';

    /**
     * Return the signature data for the signed text.
     *
     * @return Horde_Pgp_Element_Signature  Signature part object.
     */
    public function getSignaturePart()
    {
        $pos = $this->_data->pos();
        $armor = new Horde_Pgp_Armor(
            $this->_data->getString($this->_start + 20)
        );
        $this->_resetPos($pos);

        foreach ($armor as $val) {
            if ($val instanceof Horde_Pgp_Element_Signature) {
                return $val;
            }
        }
    }

}
