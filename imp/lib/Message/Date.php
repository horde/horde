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
    static private $_cache = array();

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
        global $prefs, $registry;

        $udate = null;
        if ($this->_date && !$this->_date->error()) {
            try {
                $udate = $this->_date->format('U');
                $time_str = strftime($prefs->getValue('time_format'), $udate);

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
                /* Not today, use the date. */
                return sprintf(
                    '%s (%s %s)',
                    strftime($prefs->getValue('date_format'), $udate),
                    $time_str,
                    $tz
                );
            }

            /* Else, it's today, use the time only. */
            return sprintf(_("Today, %s %s"), $time_str, $tz);
        }

        if (is_null($udate)) {
            return _("Unknown Date");
        }

        if ($format === self::DATE_FORCE) {
            return strftime($prefs->getValue('date_format'), $udate) . ' [' . $time_str . ']';
        }

        $this->_buildCache();

        if (($udate < self::$_cache['today_start']) ||
            ($udate > self::$_cache['today_end'])) {
            /* Not today, use the date. */
            return ($format === self::DATE_FULL)
                ? strftime($prefs->getValue('date_format'), $udate) . ' [' . $time_str . ']'
                : strftime($prefs->getValue('date_format_mini'), $udate);
        }

        /* It's today, use the time. */
        return $time_str;
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
        }
    }

}
