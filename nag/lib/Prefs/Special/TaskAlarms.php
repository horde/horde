<?php
/**
 * Special prefs handling for the 'task_alarms_select' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class Nag_Prefs_Special_TaskAlarms implements Horde_Core_Prefs_Ui_Special
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
            'label' => _("Choose how you want to receive reminders for tasks with alarms:"),
            'pref' => 'task_alarms'
        ));
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        $data = Horde_Core_Prefs_Ui_Widgets::alarmUpdate($ui, array('pref' => 'task_alarms'));
        if (is_null($data)) {
            return false;
        }

        $GLOBALS['prefs']->setValue('task_alarms', serialize($data));
        return true;
    }

}
