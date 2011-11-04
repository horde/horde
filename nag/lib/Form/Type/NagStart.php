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
            if (empty($start['date'])) {
                $start = null;
            } else {
                $start_array = Nag::parseDate($start['date'], false);
                $start_dt = new Horde_Date($start_array);
                $start = $start_dt->timestamp();
            }
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
