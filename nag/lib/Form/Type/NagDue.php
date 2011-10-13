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
            $due_date = !empty($due['date']) ? $due['date'] : null;
            $due_time = !empty($due['time']) ? $due['time'] : null;
            $due = (int)strtotime("$due_date $due_time");
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
