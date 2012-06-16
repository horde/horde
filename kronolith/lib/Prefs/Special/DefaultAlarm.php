<?php
/**
 * Special prefs handling for the 'default_alarm_management' preference.
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
class Kronolith_Prefs_Special_DefaultAlarm implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $prefs;

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        if ($alarm_value = $prefs->getValue('default_alarm')) {
            if ($alarm_value % 10080 == 0) {
                $alarm_value /= 10080;
                $t->set('week', true);
            } elseif ($alarm_value % 1440 == 0) {
                $alarm_value /= 1440;
                $t->set('day', true);
            } elseif ($alarm_value % 60 == 0) {
                $alarm_value /= 60;
                $t->set('hour', true);
            } else {
                $t->set('minute', true);
            }
        } else {
            $t->set('minute', true);
        }

        $t->set('alarm_value', intval($alarm_value));

        return $t->fetch(KRONOLITH_TEMPLATES . '/prefs/defaultalarm.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        $GLOBALS['prefs']->setValue('default_alarm', intval($ui->vars->alarm_value) * intval($ui->vars->alarm_unit));
        return true;
    }

}
