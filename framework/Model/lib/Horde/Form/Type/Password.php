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
                $message = $this->_dict->t("This field is required.");
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
            $message = $this->_dict->t("This field is required.");
            return false;
        }

        if ($value['original'] != $value['confirm']) {
            $message = $this->_dict->t("Passwords must match.");
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
