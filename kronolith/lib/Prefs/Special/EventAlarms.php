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
class Kronolith_Prefs_Special_EventAlarms implements Horde_Core_Prefs_Ui_Special
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
        global $injector, $prefs, $registry;

        $data = Horde_Core_Prefs_Ui_Widgets::alarmUpdate($ui, array('pref' => 'event_alarms'));
        if (is_null($data)) {
            return false;
        }

        $prefs->setValue('event_alarms', serialize($data));

        try {
            $alarms = $registry->callAppMethod('kronolith', 'listAlarms', array('args' => array($_SERVER['REQUEST_TIME'])));
            if (!empty($alarms)) {
                $horde_alarm = $injector->getInstance('Horde_Alarm');
                foreach ($alarms as $alarm) {
                    $alarm['start'] = new Horde_Date($alarm['start']);
                    $alarm['end'] = new Horde_Date($alarm['end']);
                    $horde_alarm->set($alarm);
                }
            }
        } catch (Exception $e) {}

        return true;
    }

}
