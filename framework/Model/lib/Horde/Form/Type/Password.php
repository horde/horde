<?php
/**
 * Password
 */
class Horde_Form_Type_Password extends Horde_Form_Type {

    public function isValid($var, $vars, $value, &$message)
    {
        $valid = true;

        if ($var->required) {
            $valid = strlen(trim($value)) > 0;

            if (!$valid) {
                $message = _("This field is required.");
            }
        }

        return $valid;
    }

}


/**
 * Password with confirmation
 */
class Horde_Form_Type_passwordConfirm extends Horde_Form_Type {

    public function isValid($var, $vars, $value, &$message)
    {
        if ($var->required && empty($value['original'])) {
            $message = _("This field is required.");
            return false;
        }

        if ($value['original'] != $value['confirm']) {
            $message = _("Passwords must match.");
            return false;
        }

        return true;
    }

    function getInfo($vars, $var, &$info)
    {
        $value = $vars->get($var->name);
        $info = $value['original'];
    }

}
