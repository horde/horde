<?php
class Horde_Core_Form_Type_KeyvalMultiEnum extends Horde_Core_Form_Type_MultiEnum
{
    public function getInfo($vars, $var, &$info)
    {
        $value = $vars->get($var->name);
        $info = array();
        foreach ($value as $key) {
            $info[$key] = $this->_values[$key];
        }
    }
}
