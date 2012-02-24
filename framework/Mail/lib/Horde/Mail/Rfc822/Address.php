<?php
/**
 * Object representation of a RFC 822 e-mail address.
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
class Horde_Mail_Rfc822_Address extends Horde_Mail_Rfc822_Object implements ArrayAccess
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
     * Constructor.
     *
     * @param string $addresses  If set, address is parsed and used as the
     *                           object address (since 1.2.0). Address is not
     *                           validated; first e-mail address parsed is
     *                           used.
     */
    public function __construct($address = null)
    {
        if (!is_null($address)) {
            $rfc822 = new Horde_Mail_Rfc822();
            $addr = $rfc822->parseAddressList($address, array(
                'nest_groups' => false,
                'validate' => false
            ));
            if (count($addr)) {
                foreach ($addr[0] as $key => $val) {
                    $this->$key = $val;
                }
            }
        }
    }

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
            // Return the full mailbox@host address.
            return is_null($this->host)
                ? $this->mailbox
                : $this->mailbox . '@' . $this->host;

        case 'personal_decoded':
            // DEPRECATED
            return Horde_Mime::decode($this->personal, 'UTF-8');

        case 'personal_encoded':
            return Horde_Mime::encode($this->personal, 'UTF-8');

        default:
            return null;
        }
    }

    /**
     * Write an address given information in this part.
     *
     * @param array $opts  Optional arguments:
     *   - encode: (boolean) MIME encode the personal part?
     *   - idn: (boolean) If true, decode IDN domain names (Punycode/RFC 3490).
     *          If false, convert domain names into IDN if necessary (@since
     *          1.5.0).
     *          If null, does no conversion.
     *          Requires the idn or intl PHP module.
     *          DEFAULT: true
     *
     * @return string  The correctly escaped/quoted address.
     */
    public function writeAddress(array $opts = array())
    {
        $host = ltrim($this->host, '@');
        if (isset($opts['idn'])) {
            switch ($opts['idn']) {
            case true:
                if (function_exists('idn_to_utf8')) {
                    $host = idn_to_utf8($host);
                }
                break;

            case false:
                if (function_exists('idn_to_ascii')) {
                    $host = idn_to_ascii($host);
                }
                break;
            }
        }

        $rfc822 = new Horde_Mail_Rfc822();
        $address = $rfc822->encode($this->mailbox, 'address') . '@' . $host;
        $personal = empty($opts['encode'])
             ? $this->personal
             : $this->personal_encoded;

        return (strlen($personal) && ($personal != $address))
            ? $rfc822->encode($personal, 'personal') . ' <' . $address . '>'
            : $address;
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
