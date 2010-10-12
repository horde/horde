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
     * Translation provider.
     *
     * @var Horde_Translation
     */
    static protected $_coreDict;

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

        self::$_coreDict = new Horde_Translation_Gettext('Horde_Core', dirname(__FILE__) . '/../../../../locale');

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

        $js = array(
            '-Horde_Calendar.click_month' => intval($params['click_month']),
            '-Horde_Calendar.click_week' => intval($params['click_week']),
            '-Horde_Calendar.click_year' => intval($params['click_year']),
            '-Horde_Calendar.firstDayOfWeek' => intval($GLOBALS['prefs']->getValue('first_week_day')),
            'Horde_Calendar.months' => self::months(),
            'Horde_Calendar.weekdays' => $weekdays
        );
        if ($params['full_weekdays']) {
            $js['Horde_Calendar.fullweekdays'] = self::fullWeekdays();
        }

        Horde::addScriptFile('calendar.js', 'horde');
        Horde::addInlineJsVars($js);
    }

    /**
     * Return the list of localized abbreviated weekdays.
     *
     * @return array  Abbreviated weekdays.
     */
    static public function weekdays()
    {
        return array(
            self::$_coreDict->t("Su"),
            self::$_coreDict->t("Mo"),
            self::$_coreDict->t("Tu"),
            self::$_coreDict->t("We"),
            self::$_coreDict->t("Th"),
            self::$_coreDict->t("Fr"),
            self::$_coreDict->t("Sa")
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
            self::$_coreDict->t("Sunday"),
            self::$_coreDict->t("Monday"),
            self::$_coreDict->t("Tuesday"),
            self::$_coreDict->t("Wednesday"),
            self::$_coreDict->t("Thursday"),
            self::$_coreDict->t("Friday"),
            self::$_coreDict->t("Saturday"),
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
            self::$_coreDict->t("January"),
            self::$_coreDict->t("February"),
            self::$_coreDict->t("March"),
            self::$_coreDict->t("April"),
            self::$_coreDict->t("May"),
            self::$_coreDict->t("June"),
            self::$_coreDict->t("July"),
            self::$_coreDict->t("August"),
            self::$_coreDict->t("September"),
            self::$_coreDict->t("October"),
            self::$_coreDict->t("November"),
            self::$_coreDict->t("December")
        );
    }

}
