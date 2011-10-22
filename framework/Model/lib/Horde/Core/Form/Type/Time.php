<?php
/**
 * Time
 */
class Horde_Form_Type_Time extends Horde_Form_Type {

    public function isValid($var, $vars, $value, &$message)
    {
        if ($var->required && empty($value) && ((string)(double)$value !== $value)) {
            $message = Horde_Model_Translation::t("This field is required.");
            return false;
        }

        if (empty($value) || preg_match('/^[0-2]?[0-9]:[0-5][0-9]$/', $value)) {
            return true;
        }

        $message = Horde_Model_Translation::t("This field may only contain numbers and the colon.");
        return false;
    }

}
