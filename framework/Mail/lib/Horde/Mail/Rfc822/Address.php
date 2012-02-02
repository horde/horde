<?php
/**
 * Object representation of a RFC 822 e-mail address.
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
class Horde_Mail_Rfc822_Address implements ArrayAccess
{
    /**
     * Comments associated with the personal phrase.
     *
     * @var array
     */
    public $comment = array();

    /**
     * Hostname of the address.
     *
     * @var string
     */
    public $host = null;

    /**
     * Local-part of the address.
     *
     * @var string
     */
    public $mailbox = null;

    /**
     * Personal part of the address.
     *
     * @var string
     */
    public $personal = null;

    /**
     * Routing information (obsoleted by RFC 2822 [4.4]).
     *
     * @deprecated
     *
     * @var array
     */
    public $route = array();

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'adl':
            // DEPRECATED
            return empty($route)
                ? ''
                : implode(',', $route);

        case 'full_address':
            // @since 1.1.0
            // Return the full mailbox@host address.
            return is_null($this->host)
                ? $this->mailbox
                : $this->mailbox . '@' . $this->host;

        default:
            return null;
        }
    }

    /**
     * Write an address given information in this part.
     *
     * @since 1.1.0
     *
     * @param array $opts  Optional arguments:
     *   - idn: (boolean) See Horde_Mime_Address#writeAddress().
     *
     * @return string  The correctly escaped/quoted address.
     */
    public function writeAddress(array $opts = array())
    {
        return Horde_Mime_Address::writeAddress(
            $this->mailbox,
            $this->host,
            $this->personal,
            array(
                'idn' => (isset($opts['idn']) ? $opts['idn'] : null)
            )
        );
    }

    /* ArrayAccess methods. TODO: Here for BC purposes. Remove for 2.0. */

    /**
     */
    public function offsetExists($offset)
    {
        return (bool)$this->$offset;
    }

    /**
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        }
    }

    /**
     */
    public function offsetUnset($offset)
    {
        if (property_exists($this, $offset)) {
            switch ($offset) {
            case 'comment':
                $this->comment = array();
                break;

            default:
                $this->$offset = null;
                break;
            }
        }
    }

}
