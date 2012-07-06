<?php
/**
 * Wrap the base class in order to use a single secret key when authenticated
 * to Horde, to reduce complexity and minimze cookie size.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
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
    public function setKey($keyname = 'generic')
    {
        return parent::setKey(self::HORDE_KEYNAME);
    }

    /**
     */
    public function getKey($keyname = 'generic')
    {
        return parent::getKey(self::HORDE_KEYNAME);
    }

    /**
     */
    public function clearKey($keyname = 'generic')
    {
        return parent::clearKey(self::HORDE_KEYNAME);
    }

}
