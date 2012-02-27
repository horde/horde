<?php
/**
 * Object representation of an RFC 822 element.
 *
 * @since 1.2.0
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/bsd New BSD License
 * @package   Mail
 */

/**
 * Object representation of an RFC 822 element.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/bsd New BSD License
 * @package   Mail
 */
class Horde_Mail_Rfc822_Object
{
    /**
     * String representation of object.
     *
     * @return string  Returns the full e-mail address.
     */
    public function __toString()
    {
        return $this->writeAddress();
    }

}
