<?php
/**
 * The Horde_Ui_JsCalendar:: class generates the necessary javascript code
 * to allow the javascript calendar widget to be displayed on the page.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Ui
 */
class Horde_Ui_JsCalendar
{
    /**
     * Output the necessary javascript code to allow display of the calendar
     * widget.
     *
     * @param array $params  Configuration parameters for the widget:
     *                       - short_weekdays: display only the first letter of
     *                         weekdays?
     */
    static public function init($params = array())
    {
        $params += array('short_weekdays' => false);
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
            'Horde_Calendar.months = ' . Horde_Serialize::serialize(self::months(), Horde_Serialize::JSON, $GLOBALS['registry']->getCharset()),
        ));
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
