<?php
/**
 * Copyright 2006-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2006-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common code dealing with date formatting for messages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2006-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Message_Date
{
    const DATE_FORCE = 1;
    const DATE_FULL = 2;
    const DATE_LOCAL = 3;

    /**
     * Shared cache.
     *
     * @var array
     */
    private static $_cache = array();

    /**
     * The date object.
     *
     * @var Horde_Imap_Client_DateTime
     */
    private $_date;

    /**
     * Constructor.
     *
     * @param Horde_Imap_Client_DateTime $date  The date object.
     */
    public function __construct($date = null)
    {
        if (!is_object($date) && !is_null($date)) {
            $date = new Horde_Imap_Client_DateTime($date);
        }

        $this->_date = $date;
    }

    /**
     */
    public function __toString()
    {
        return $this->format();
    }

    /**
     * Formats the date.
     *
     * @param integer $format  Formatting options:
     *   - DATE_FORCE - Force use of date formatting, instead of time
     *                  formatting, for all dates.
     *   - DATE_FULL - Use full representation of date, including time
     *                 information.
     *   - DATE_LOCAL - Display localized formatting (with timezone
     *                  information). Displays "Today" for the current date.
     *
     * @return string  The formatted date string.
     */
    public function format($format = 0)
    {
        global $registry;

        $udate = null;
        if ($this->_date && !$this->_date->error()) {
            try {
                $udate = $this->_date->format('U');

                if (empty(self::$_cache['tz'])) {
                    $registry->setTimeZone();
                    self::$_cache['tz'] = true;
                }
            } catch (Exception $e) {}
        }

        switch ($format) {
        case self::DATE_LOCAL:
            if (is_null($udate)) {
                return '';
            }

            $this->_buildCache();
            $tz = strftime('%Z');

            if (($udate < self::$_cache['today_start']) ||
                ($udate > self::$_cache['today_end'])) {
                if ($udate > self::$_cache['yesterday_start']) {
                    /* Yesterday. */
                    return sprintf(
                        _("Yesterday, %s %s"),
                        $this->_format('time_format', $udate),
                        $tz
                    );
                }

                /* Not today, use the date. */
                return sprintf(
                    '%s (%s %s)',
                    $this->_format('date_format', $udate),
                    $this->_format('time_format', $udate),
                    $tz
                );
            }

            /* Else, it's today, use the time only. */
            return sprintf(
                _("Today, %s %s"),
                $this->_format('time_format', $udate),
                $tz
            );
        }

        if (is_null($udate)) {
            return _("Unknown Date");
        }

        if ($format === self::DATE_FORCE) {
            return $this->_format('date_format', $udate) . ' [' .
                $this->_format('time_format', $udate) . ' ' . strftime('%Z') .
                ']';
        }

        $this->_buildCache();

        if (($udate < self::$_cache['today_start']) ||
            ($udate > self::$_cache['today_end'])) {
            if ($udate > self::$_cache['yesterday_start']) {
                /* Yesterday. */
                return sprintf(
                    _("Yesterday, %s"),
                    $this->_format('time_format_mini', $udate)
                );
            }

            /* Not today, use the date. */
            return ($format === self::DATE_FULL)
                ? $this->_format('date_format', $udate) . ' [' . $this->_format('time_format', $udate) . ']'
                : $this->_format('date_format_mini', $udate);
        }

        /* It's today, use the time. */
        return $this->_format('time_format_mini', $udate);
    }

    /**
     * Build the date cache.
     */
    private function _buildCache()
    {
        if (!isset(self::$_cache['today_start'])) {
            $date = new DateTime('today');
            self::$_cache['today_start'] = $date->format('U');

            $date = new DateTime('today + 1 day');
            self::$_cache['today_end'] = $date->format('U');

            $date = new DateTime('today - 1 day');
            self::$_cache['yesterday_start'] = $date->format('U');
        }
    }

    /**
     * Format the date/time.
     *
     * @param string $type    The date/time preference name.
     * @param integer $udate  The UNIX timestamp.
     *
     * @return string  Formatted date/time string.
     */
    private function _format($type, $udate)
    {
        return ltrim(strftime($GLOBALS['prefs']->getValue($type), $udate));
    }

}
