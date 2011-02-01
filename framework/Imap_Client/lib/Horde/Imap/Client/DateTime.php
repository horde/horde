<?php
/**
 * Horde_Imap_Client_DateTime:: is a wrapper around PHP's native DateTime
 * class that works around the PHP 5.2.x issue that does not allow DateTime
 * objects to be serialized.  See http://bugs.php.net/bug.php?id=41334
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 */
class Horde_Imap_Client_DateTime implements Serializable
{
    /* Serialized version. */
    const VERSION = 1;

    /**
     * The datetime string.
     *
     * @var string
     */
    private $_string;

    /**
     * The timezone string.
     *
     * @var string
     */
    private $_tz = null;

    /**
     * The DateTime object to use for function calls.
     *
     * @var DateTime
     */
    private $_datetime = null;

    /**
     * Constructor.
     *
     * @param string $time      String in a format accepted by strtotime().
     * @param DateTimeZone $tz  Time zone of the time.
     */
    public function __construct($time, $tz = null)
    {
        $this->_string = $time;
        if (!is_null($tz)) {
            $this->_tz = $tz->getName();
        }
    }

    /**
     * String representation: UNIX timestamp.
     */
    public function __toString()
    {
        return $this->format('U');
    }

    /**
     * Serialize.
     *
     * @return string  Serialized representation of this object.
     */
    public function serialize()
    {
        return serialize(array(
            self::VERSION,
            $this->_string,
            $this->_tz
        ));
    }

    /**
     * Unserialize.
     *
     * @param string $data  Serialized data.
     *
     * @throws Exception
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_string = $data[1];
        $this->_tz = $data[2];
    }

    /**
     * Called on a function call.
     *
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        if (is_null($this->_datetime)) {
            try {
                $this->_datetime = new DateTime($this->_string, $this->_tz);
            } catch (Exception $e) {
                /* Bug #5717 - Check for UT vs. UTC. */
                if (substr(rtrim($date), -3) != ' UT') {
                    throw $e;
                }

                $this->_datetime = new DateTime($this->_string . 'C', $this->_tz);
            }
        }

        return call_user_func_array(array($this->_datetime, $name), $arguments);
    }

}
