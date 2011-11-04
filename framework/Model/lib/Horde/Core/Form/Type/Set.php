<?php
/**
 * Set of values
 */
class Horde_Core_Form_Type_Set extends Horde_Core_Form_Type
{
    /**
     * Values
     *
     * @type stringlist
     * @var string
     */
    protected $_values;

    public function isValid($var, $vars, $value, &$message)
    {
        if (count($this->_values) == 0 || count($value) == 0) {
            return true;
        }
        foreach ($value as $item) {
            if (!isset($this->_values[$item])) {
                $error = true;
                break;
            }
        }
        if (!isset($error)) {
            return true;
        }

        $message = Horde_Model_Translation::t("Invalid data.");
        return false;
    }
}
