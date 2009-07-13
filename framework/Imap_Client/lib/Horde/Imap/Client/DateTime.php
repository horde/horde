<?php
/**
 * Horde_Imap_Client_DateTime:: is a wrapper around PHP's native DateTime
 * class that works around the PHP 5.2.x issue that does not allow DateTime
 * objects to be serialized.  See http://bugs.php.net/bug.php?id=41334
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Horde_Imap_Client
 */
class Horde_Imap_Client_DateTime
{
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
     * Called on serialize().
     */
    public function __sleep()
    {
        return array('_string', '_tz');
    }

    /**
     * Called on a function call.
     */
    public function __call($name, $arguments)
    {
        if (is_null($this->_datetime)) {
            $this->_datetime = is_null($this->_tz)
                ? new DateTime($this->_string)
                : new DateTime($this->_string, $this->_tz);
        }

        call_user_func_array(array($this->_datetime, $name), $arguments);
    }

}
