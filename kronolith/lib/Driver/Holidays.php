<?php
/**
 * The Kronolith_Driver_Holidays implements support for the PEAR package
 * Date_Holidays.
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @see     http://pear.php.net/packages/Date_Holidays
 * @author  Stephan Hohmann <webmaster@dasourcerer.net>
 * @package Kronolith
 */
class Kronolith_Driver_Holidays extends Kronolith_Driver
{

    public function listAlarms($date, $fullevent = false)
    {
        return array();
    }

    /**
     * Returns a list of all holidays occuring between <code>$startDate</code>
     * and <code>$endDate</code>.
     *
     * @param int|Horde_Date $startDate  The start of the datespan to be
     *                                   checked. Defaults to the current date.
     * @param int|Horde_Date $endDate    The end of the datespan. Defaults to
     *                                   the current date.
     * @param bool $hasAlarm             Left in for compatibility reasons and
     *                                   has no effect on this function.
     *                                   Defaults to <code>false</code>
     *
     * @return array  An array of all holidays within the given datespan.
     */
    public function listEvents($startDate = null, $endDate = null, $hasAlarm = false)
    {
        global $language;

        $events = array();

        if (is_null($startDate)) {
            $startDate = new Horde_Date($_SERVER['REQUEST_TIME']);
        }

        if (is_null($endDate)) {
            $endDate = new Horde_Date($_SERVER['REQUEST_TIME']);
        }

        Date_Holidays::staticSetProperty('DIE_ON_MISSING_LOCALE', false);
        foreach (unserialize($GLOBALS['prefs']->getValue('holiday_drivers')) as $driver) {
            for ($year = $startDate->year; $year <= $endDate->year; $year++) {
                $dh = Date_Holidays::factory($driver, $year, $language);
                if (Date_Holidays::isError($dh)) {
                    Horde::logMessage(sprintf('Factory was unable to produce driver object for driver %s in year %s with locale %s',
                                              $driver, $year, $language),
                                      __FILE__, __LINE__, PEAR_LOG_ERR);
                    continue;
                }
                $dh->addTranslation($language);
                $events = array_merge($events, $this->_getEvents($dh, $startDate, $endDate));
            }
        }

        return $events;
    }

    private function _getEvents($dh, $startDate, $endDate)
    {
        $events = array();
        for ($date = new Horde_Date($startDate);
             $date->compareDate($endDate) <= 0;
             $date->mday++) {
            $holidays = $dh->getHolidayForDate($date->format('Y-m-d'), null, true);
            if (Date_Holidays::isError($holidays)) {
                Horde::logMessage(sprintf('Unable to retrieve list of holidays from %s to %s',
                                          (string)$startDate, (string)$endDate), __FILE__, __LINE__);
                continue;
            }

            if (is_null($holidays)) {
                continue;
            }

            foreach ($holidays as $holiday) {
                $event = new Kronolith_Event_Holidays($this);
                $event->fromDriver($holiday);
                $events[] = $event;
            }
        }
        return $events;
    }

    private function _getTranslationFile($driver)
    {
        static $data_dir;
        if (!isset($data_dir)) {
            include_once 'PEAR/Config.php';
            $pear_config = new PEAR_Config();
            $data_dir = $pear_config->get('data_dir');
        }
        if (empty($data_dir)) {
            return;
        }

        foreach (array('', '_' . $driver) as $pkg_ext) {
            foreach (array('ser', 'xml') as $format) {
                $location = $data_dir . '/Date_Holidays' . $pkg_ext . '/lang/'
                    . $driver . '/' . $GLOBALS['language'] . '.' . $format;
                if (file_exists($location)) {
                    return array($format, $location);
                }
            }
        }

        return array(null, null);
    }

}
