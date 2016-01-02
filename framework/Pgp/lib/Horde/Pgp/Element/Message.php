<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */

/**
 * PGP element: signed, encrypted, or compressed file.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp_Element_Message
extends Horde_Pgp_Element
{
    /**
     */
    protected $_armor = 'MESSAGE';

    /**
     */
    public function isEncryptedSymmetrically()
    {
        foreach ($this->message as $val) {
            if ($val instanceof OpenPGP_SymmetricSessionKeyPacket) {
                return true;
            }
        }

        return false;
    }

}
