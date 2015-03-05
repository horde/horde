<?php
/**
 * @package Horde_Form
 */
class Nag_Form_Type_NagContact extends Horde_Form_Type
{
    public function isValid(&$var, &$vars, $value, &$message)
    {
        if (empty($value)) {
            return true;
        }
        $email = new Horde_Mail_Rfc822_Address($value);
        return $email->valid;
    }

    public function getTypeName()
    {
        return 'NagContact';
    }

    function getInfo(&$vars, &$var, &$info)
    {
        $value = $vars->get($var->getVarName());
        $info = str_replace(',', '', $value);
    }
}