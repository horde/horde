<?php
/**
 * The Horde_Form_Type_nag_start class provides a form field for editing
 * task delayed start dates.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Nag
 */
class Nag_Form_Type_NagStart extends Horde_Form_Type
{
    public function getInfo(&$vars, &$var, &$info)
    {
        $start_type = $vars->get('start_date');
        $start = $vars->get('start');
        if (is_array($start)) {
            $start_day = !empty($start['day']) ? $start['day'] : null;
            $start_month = !empty($start['month']) ? $start['month'] : null;
            $start_year = !empty($start['year']) ? $start['year'] : null;
            $start = (int)strtotime("$start_month/$start_day/$start_year");
        }

        $info = strcasecmp($start_type, 'none') ? $start : 0;
    }

    public function isValid(&$var, &$vars, $value, &$message)
    {
        return true;
    }

    public function getTypeName()
    {
        return 'NagStart';
    }

}
