<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 */

/**
 * Wrap the base class in order to use a single secret key when authenticated
 * to Horde, to reduce complexity and minimze cookie size.
 *
 * Horde_Secret should only be used to encrypt data within the current
 * session. To encrypt data generally, directly use an encryption library
 * since how data is stored in a session may change without warning between
 * versions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 */
class Horde_Core_Secret extends Horde_Secret
{
    const HORDE_KEYNAME = 'horde_secret';

    /**
     */
    public function setKey($keyname = self::DEFAULT_KEY)
    {
        return parent::setKey(self::HORDE_KEYNAME);
    }

    /**
     */
    public function getKey($keyname = self::DEFAULT_KEY)
    {
        return parent::getKey(self::HORDE_KEYNAME);
    }

    /**
     */
    public function clearKey($keyname = self::DEFAULT_KEY)
    {
        return parent::clearKey(self::HORDE_KEYNAME);
    }

}
