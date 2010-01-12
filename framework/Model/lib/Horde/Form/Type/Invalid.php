<?php
class Horde_Form_Type_invalid extends Horde_Form_Type {

    var $message;

    function init($message)
    {
        $this->message = $message;
    }

    function isValid($var, $vars, $value, &$message)
    {
        return false;
    }

}
