<?php
/**
 * Object representation of a RFC 822 e-mail group.
 *
 * @since 1.1.0
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
 * Object representation of a RFC 822 e-mail address.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @license   http://www.horde.org/licenses/bsd New BSD License
 * @package   Mail
 */
class Horde_Mail_Rfc822_Group implements ArrayAccess
{
    /**
     * List of group e-mail address objects.
     *
     * @var array
     */
    public $addresses = array();

    /**
     * Comments associated with the personal phrase.
     *
     * @var string
     */
    public $groupname = '';

    /**
     * String representation of object.
     *
     * @return string  Returns the full e-mail address.
     */
    public function __toString()
    {
        return $this->writeAddress();
    }

    /**
     * Write a group address given information in this part.
     *
     * @param array $opts  Optional arguments:
     *   - idn: (boolean) See Horde_Mime_Address#writeAddress().
     *
     * @return string  The correctly escaped/quoted address.
     */
    public function writeAddress()
    {
        $addr = array();
        foreach ($this->addresses as $val) {
            $addr[] = $val->writeAddress(array(
                'idn' => (isset($opts['idn']) ? $opts['idn'] : null)
            ));
        }

        return Horde_Mime_Address::writeGroupAddress($ob->groupname, $addr);
    }

    /* ArrayAccess methods. TODO: Here for BC purposes. Remove for 2.0. */

    /**
     */
    public function offsetExists($offset)
    {
        switch ($offset) {
        case 'addresses':
        case 'groupname':
            return true;

        default:
            return false;
        }
    }

    /**
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset)
            ? $this->$offset
            : null;
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        if ($this->offsetExists($offset)) {
            $this->$offset = $value;
        }
    }

    /**
     */
    public function offsetUnset($offset)
    {
        /* Don't allow undsetting of values. */
    }

}
