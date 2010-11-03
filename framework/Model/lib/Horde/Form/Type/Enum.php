<?php
/**
 * Choose one from a list of values
 */
class Horde_Form_Type_Enum extends Horde_Form_Type {

    /**
     * List of values to choose from
     *
     * @type stringlist
     * @var array
     */
    protected $_values = array();

    /**
     * Initial prompt value, if any
     *
     * @type text
     * @var string
     */
    protected $_prompt;

    /**
     */
    public function isValid($var, $vars, $value, &$message)
    {
        if ($var->required && $value == '' && !isset($this->_values[$value])) {
            $message = Horde_Model_Translation::t("This field is required.");
            return false;
        }

        if (count($this->_values) == 0 || isset($this->_values[$value]) ||
            ($this->_prompt && empty($value))) {
            return true;
        }

        $message = Horde_Model_Translation::t("Invalid data.");
        return false;
    }

}
