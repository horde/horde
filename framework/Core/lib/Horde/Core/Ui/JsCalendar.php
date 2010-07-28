<?php
/**
 * The Horde_Core_Ui_JsCalendar:: class generates the necessary javascript
 * code to allow the javascript calendar widget to be displayed on the page.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Ui_JsCalendar
{
    /**
     * Make sure init() is only run once.
     *
     * @var boolean
     */
    static protected $_initRun = false;

    /**
     * Output the necessary javascript code to allow display of the calendar
     * widget.
     *
     * @param array $params  Configuration parameters for the widget:
     * <pre>
     * 'click_month' - (boolean) If true, the month is clickable.
     *                 DEFAULT: false
     * 'click_week' - (boolean) If true, will display a clickable week.
     *                DEFAULT: false
     * 'click_year' - (boolean) If true, the year is clickable.
     *                DEFAULT: false
     * 'full_weekdays' - (boolean) Add full weekday localized list to
     *                   javascript object.
     *                   DEFAULT: false
     * 'short_weekdays' - (boolean) Display only the first letter of
     *                    weekdays?
     *                    DEFAULT: false
     * </pre>
     */
    static public function init(array $params = array())
    {
        if (self::$_initRun) {
            return;
        }
        self::$_initRun = true;

        $params = array_merge(array(
            'click_month' => false,
            'click_week' => false,
            'click_year' => false,
            'full_weekdays' => false,
            'short_weekdays' => false
        ), $params);

        $weekdays = self::weekdays();
        if ($params['short_weekdays']) {
            foreach ($weekdays as &$day) {
                $day = substr($day, 0, 1);
            }
        }

        Horde::addScriptFile('calendar.js', 'horde');
        Horde::addInlineScript(array(
            'Horde_Calendar.click_month = ' . intval($params['click_month']),
            'Horde_Calendar.click_week = ' . intval($params['click_week']),
            'Horde_Calendar.click_year = ' . intval($params['click_year']),
            'Horde_Calendar.firstDayOfWeek = ' . intval($GLOBALS['prefs']->getValue('first_week_day')),
            'Horde_Calendar.months = ' . Horde_Serialize::serialize(self::months(), Horde_Serialize::JSON, $GLOBALS['registry']->getCharset()),
            'Horde_Calendar.weekdays = ' . Horde_Serialize::serialize($weekdays, Horde_Serialize::JSON, $GLOBALS['registry']->getCharset())
        ));
        if ($params['full_weekdays']) {
            Horde::addInlineScript(array(
                'Horde_Calendar.fullweekdays = ' . Horde_Serialize::serialize(self::fullWeekdays(), Horde_Serialize::JSON, $GLOBALS['registry']->getCharset())
            ));
        }
    }

    /**
     * Return the list of localized abbreviated weekdays.
     *
     * @return array  Abbreviated weekdays.
     */
    static public function weekdays()
    {
        return array(
            _("Su"),
            _("Mo"),
            _("Tu"),
            _("We"),
            _("Th"),
            _("Fr"),
            _("Sa")
        );
    }

    /**
     * Return the list of localized full weekday names.
     *
     * @return array  Full weekday names.
     */
    static public function fullWeekdays()
    {
        return array(
            _("Sunday"),
            _("Monday"),
            _("Tuesday"),
            _("Wednesday"),
            _("Thursday"),
            _("Friday"),
            _("Saturday"),
        );
    }

    /**
     * Return the localized list of months.
     *
     * @return array  Month list.
     */
    static public function months()
    {
        return array(
            _("January"),
            _("February"),
            _("March"),
            _("April"),
            _("May"),
            _("June"),
            _("July"),
            _("August"),
            _("September"),
            _("October"),
            _("November"),
            _("December")
        );
    }

}
