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
class Horde_Mail_Rfc822_Group extends Horde_Mail_Rfc822_Object implements ArrayAccess
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
     * Constructor.
     *
     * @param string $groupname  If set, used as the group name. (Since 1.2.0)
     * @param string $addresses  If set, addresses is parsed and used as the
     *                           list of group addresses. (Since 1.2.0)
     *                           Addresses are not verified; sub-groups are
     *                           eliminated.
     */
    public function __construct($groupname = null, $addresses = null)
    {
        if (!is_null($groupname)) {
            $this->groupname = $groupname;
        }

        if (!is_null($addresses)) {
            $rfc822 = new Horde_Mail_Rfc822();
            $this->addresses = $rfc822->parseAddressList($addresses, array(
                'nest_groups' => false,
                'validate' => false
            ));
        }
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'groupname_decoded':
            // DEPRECATED
            return Horde_Mime::decode($this->groupname, 'UTF-8');

        case 'groupname_encoded':
            return Horde_Mime::encode($this->groupname, 'UTF-8');

        default:
            return null;
        }
    }

    /**
     * Write a group address given information in this part.
     *
     * @param array $opts  Optional arguments:
     *   - encode: (boolean) MIME encode the groupname/personal parts?
     *   - idn: (boolean) See Horde_Mime_Address#writeAddress().
     *
     * @return string  The correctly escaped/quoted address.
     */
    public function writeAddress()
    {
        $addr = array();
        foreach ($this->addresses as $val) {
            $addr[] = $val->writeAddress(array(
                'encode' => !empty($opts['encode']),
                'idn' => (isset($opts['idn']) ? $opts['idn'] : null)
            ));
        }

        return Horde_Mime_Address::writeGroupAddress(empty($opts['encode']) ? $ob->groupname : Horde_Mime::encode($ob->groupname, 'UTF-8'), $addr);
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
        /* Don't allow unsetting of values. */
    }

}
