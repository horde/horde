<?php
/**
 * The Horde_Form_Type_nag_due class provides a form field for editing
 * task due dates.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Nag
 */
class Nag_Form_Type_NagDue extends Horde_Form_Type
{
    public function getInfo(&$vars, &$var, &$info)
    {
        $due_type = $vars->get('due_type');
        $due = $vars->get('due');
        if (is_array($due)) {
            $due_day = !empty($due['day']) ? $due['day'] : null;
            $due_month = !empty($due['month']) ? $due['month'] : null;
            $due_year = !empty($due['year']) ? $due['year'] : null;
            $due_hour = Horde_Util::getFormData('due_hour');
            $due_minute = Horde_Util::getFormData('due_minute');
            if (!$GLOBALS['prefs']->getValue('twentyFour')) {
                $due_am_pm = Horde_Util::getFormData('due_am_pm');
                if ($due_am_pm == 'pm') {
                    if ($due_hour < 12) {
                        $due_hour = $due_hour + 12;
                    }
                } else {
                    // Adjust 12:xx AM times.
                    if ($due_hour == 12) {
                        $due_hour = 0;
                    }
                }
            }

            $due = (int)strtotime("$due_month/$due_day/$due_year $due_hour:$due_minute");
        }

        $info = strcasecmp($due_type, 'none') ? $due : 0;
    }

    public function isValid(&$var, &$vars, $value, &$message)
    {
        return true;
    }

    public function getTypeName()
    {
        return 'NagDue';
    }

}