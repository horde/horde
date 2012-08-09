<?php
/**
 */
class Nag_Form_Type_NagTags extends Horde_Form_Type
{
    public function getInfo(&$vars, &$var, &$info)
    {
        $info = $var->getValue($vars);
    }

    public function isValid(&$var, &$vars, $value, &$message)
    {
        return true;
    }

    public function getTypeName()
    {
        return 'NagTags';
    }

}