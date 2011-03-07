<?php
/**
 * @package Form
 */
class Horde_Form_Type_tableset extends Horde_Form_Type {

    var $_values;
    var $_header;

    function init($values, $header)
    {
        $this->_values = $values;
        $this->_header = $header;
    }

    function isValid(&$var, &$vars, $value, &$message)
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

        $message = Horde_Form_Translation::t("Invalid data submitted.");
        return false;
    }

    function getHeader()
    {
        return $this->_header;
    }

    function getValues()
    {
        return $this->_values;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => Horde_Form_Translation::t("Table Set"),
            'params' => array(
                'values' => array('label' => Horde_Form_Translation::t("Values"),
                                  'type'  => 'stringlist'),
                'header' => array('label' => Horde_Form_Translation::t("Headers"),
                                  'type'  => 'stringlist')),
            );
    }

}
