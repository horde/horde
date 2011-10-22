<?php
class Horde_Core_Form_Type_MultiEnum extends Horde_Form_Type_Enum
{
    public function isValid($var, $vars, $value, &$message)
    {
        if (is_array($value)) {
            foreach ($value as $val) {
                if (!$this->isValid($var, $vars, $val, $message)) {
                    return false;
                }
            }
            return true;
        }

        if (empty($value) && ((string)(int)$value !== $value)) {
            if ($var->required) {
                $message = Horde_Model_Translation::t("This field is required.");
                return false;
            } else {
                return true;
            }
        }

        if (count($this->_values) == 0 || isset($this->_values[$value])) {
            return true;
        }

        $message = Horde_Model_Translation::t("Invalid data.");
        return false;
    }
}
