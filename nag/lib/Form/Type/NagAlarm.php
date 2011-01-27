<?php
/**
 * The Horde_Form_Type_nag_alarm class provides a form field for editing task
 * alarms.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Nag
 */
class Nag_Form_Type_NagAlarm extends Horde_Form_Type
{
    public function getInfo(&$vars, &$var, &$info)
    {
        $info = $var->getValue($vars);
        if (!$info['on']) {
            $info = 0;
        } else {
            $value = $info['value'];
            $unit = $info['unit'];
            if ($value == 0) {
                $value = $unit = 1;
            }
            $info = $value * $unit;
        }
    }

    public function isValid(&$var, &$vars, $value, &$message)
    {
        if ($value['on']) {
            if ($vars->get('due_type') == 'none') {
                $message = _("A due date must be set to enable alarms.");
                return false;
            }
        }

        return true;
    }

    public function getTypeName()
    {
        return 'NagAlarm';
    }

}