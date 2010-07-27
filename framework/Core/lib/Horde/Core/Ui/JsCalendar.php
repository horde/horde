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
            'Horde_Calendar.firstDayOfWeek = ' . (isset($GLOBALS['prefs']) ? intval($GLOBALS['prefs']->getValue('first_week_day')) : 1),
            'Horde_Calendar.weekdays = ' . Horde_Serialize::serialize($weekdays, Horde_Serialize::JSON, $GLOBALS['registry']->getCharset()),
            'Horde_Calendar.months = ' . Horde_Serialize::serialize(self::months(), Horde_Serialize::JSON, $GLOBALS['registry']->getCharset())
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
