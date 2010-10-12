<?php
/**
 * Set of values
 */
class Horde_Form_Type_Set extends Horde_Form_Type {

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

        $message = $this->_dict->t("Invalid data.");
        return false;
    }

}

class Horde_Form_Type_multienum extends Horde_Form_Type_enum {

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
                $message = $this->_dict->t("This field is required.");
                return false;
            } else {
                return true;
            }
        }

        if (count($this->_values) == 0 || isset($this->_values[$value])) {
            return true;
        }

        $message = $this->_dict->t("Invalid data.");
        return false;
    }

}

class Horde_Form_Type_keyval_multienum extends Horde_Form_Type_multienum {

    function getInfo($vars, $var, &$info)
    {
        $value = $vars->get($var->name);
        $info = array();
        foreach ($value as $key) {
            $info[$key] = $this->_values[$key];
        }
    }

}
