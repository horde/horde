<?php
/**
 * An on/off value
 */
class Horde_Core_Form_Type_Boolean extends Horde_Core_Form_Type
{
    public function isValid($var, $vars, $value, &$message)
    {
        return true;
    }

    public function getInfo($vars, $var, &$info)
    {
        $info = Horde_String::lower($vars->get($var->name)) == 'on';
    }
}
