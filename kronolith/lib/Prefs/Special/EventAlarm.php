<?php
/**
 * Special prefs handling for the 'event_alarms_select' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class Kronolith_Prefs_Special_EventAlarm implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
        Horde_Core_Prefs_Ui_Widgets::alarmInit();
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        return Horde_Core_Prefs_Ui_Widgets::alarm(array(
            'label' => _("Choose how you want to receive reminders for events with alarms:"),
            'pref' => 'event_alarms'
        ));
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        $data = Horde_Core_Prefs_Ui_Widgets::alarmUpdate($ui, array('pref' => 'event_alarms'));
        if (is_null($data)) {
            return false;
        }

        $GLOBALS['prefs']->setValue('event_alarms', serialize($data));
        return true;
    }

}
