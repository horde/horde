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
     */
    static public function init()
    {
        Horde::addScriptFile('calendar.js', 'horde');
        Horde::addInlineScript(array(
            'Horde_Calendar.firstDayOfWeek = ' . (isset($GLOBALS['prefs']) ? intval($GLOBALS['prefs']->getValue('first_week_day')) : 1),
            'Horde_Calendar.weekdays = ' . Horde_Serialize::serialize(self::weekdays(), Horde_Serialize::JSON, Horde_Nls::getCharset()),
            'Horde_Calendar.months = ' . Horde_Serialize::serialize(self::months(), Horde_Serialize::JSON, Horde_Nls::getCharset()),
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
