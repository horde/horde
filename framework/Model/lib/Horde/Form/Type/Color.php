<?php
/**
 * Color
 */
class Horde_Form_Type_Color extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->required && empty($value)) {
            $message = $this->_dict->t("This field is required.");
            return false;
        }

        if (empty($value) || preg_match('/^#([0-9a-z]){6}$/i', $value)) {
            return true;
        }

        $message = $this->_dict->t("This field must contain a color code in the RGB Hex format, for example '#1234af'.");
        return false;
    }

}
