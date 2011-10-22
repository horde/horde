<?php
/**
 * Password with confirmation
 */
class Horde_Core_Form_Type_PasswordConfirm extends Horde_Core_Form_Type
{
    public function isValid($var, $vars, $value, &$message)
    {
        if ($var->required && empty($value['original'])) {
            $message = Horde_Model_Translation::t("This field is required.");
            return false;
        }

        if ($value['original'] != $value['confirm']) {
            $message = Horde_Model_Translation::t("Passwords must match.");
            return false;
        }

        return true;
    }

    public function getInfo($vars, $var, &$info)
    {
        $value = $vars->get($var->name);
        $info = $value['original'];
    }
}
