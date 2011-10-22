<?php
/**
 * Password
 */
class Horde_Core_Form_Type_Password extends Horde_Core_Form_Type
{
    public function isValid($var, $vars, $value, &$message)
    {
        $valid = true;

        if ($var->required) {
            $valid = strlen(trim($value)) > 0;

            if (!$valid) {
                $message = Horde_Model_Translation::t("This field is required.");
            }
        }

        return $valid;
    }
}
