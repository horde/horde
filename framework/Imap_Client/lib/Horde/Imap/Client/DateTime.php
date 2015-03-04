<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

/**
 * A wrapper around PHP's native DateTime class that handles improperly
 * formatted dates and adds a few features missing from the base object
 * (string representation; doesn't fail on bad date input).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */
class Horde_Imap_Client_DateTime extends DateTime
{
    /**
     */
    public function __construct($time = null, $tz = null)
    {
        /* See https://bugs.php.net/bug.php?id=67118 */
        $bug_67118 = (version_compare(PHP_VERSION, '5.6', '>=')) ||
                     in_array(PHP_VERSION, array('5.4.29', '5.5.13'));
        $tz = new DateTimeZone('UTC');

        try {
            if ($bug_67118) {
                new DateTime($time, $tz);
            }
            parent::__construct($time, $tz);
            return;
        } catch (Exception $e) {}

        /* Bug #5717 - Check for UT vs. UTC. */
        if (substr(rtrim($time), -3) === ' UT') {
            try {
                if ($bug_67118) {
                    new DateTime($time . 'C', $tz);
                }
                parent::__construct($time . 'C', $tz);
                return;
            } catch (Exception $e) {}
        }

        /* Bug #9847 - Catch paranthesized timezone information at end of date
         * string. */
        $date = preg_replace("/\s*\([^\)]+\)\s*$/", '', $time, -1, $i);
        if ($i) {
            try {
                if ($bug_67118) {
                    new DateTime($date, $tz);
                }
                parent::__construct($date, $tz);
                return;
            } catch (Exception $e) {}
        }

        parent::__construct('@-1', $tz);
    }

    /**
     * String representation: UNIX timestamp.
     */
    public function __toString()
    {
        return $this->error()
            ? '0'
            : strval($this->getTimestamp());
    }

    /**
     * Was this an unparseable date?
     *
     * @return boolean  True if unparseable.
     */
    public function error()
    {
        return ($this->getTimestamp() === -1);
    }

}
