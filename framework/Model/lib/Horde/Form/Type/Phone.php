<?php
/**
 * Phone number
 */
class Horde_Form_Type_Phone extends Horde_Form_Type {

    public function isValid($var, $vars, $value, &$message)
    {
        $valid = true;

        if ($var->required) {
            $valid = strlen(trim($value)) > 0;
            if (!$valid) {
                $message = $this->_dict->t("This field is required.");
            }
        } else {
            $valid = preg_match('/^\+?[\d()\-\/ ]*$/', $value);
            if (!$valid) {
                $message = $this->_dict->t("You must enter a valid phone number, digits only with an optional '+' for the international dialing prefix.");
            }
        }

        return $valid;
    }

}
