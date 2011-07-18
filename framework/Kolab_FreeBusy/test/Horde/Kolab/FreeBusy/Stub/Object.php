<?php

class Horde_Kolab_FreeBusy_Stub_Object
{
    private $_data;

    public function __construct($data)
    {
        $this->_data = $data;
    }

    public function getSingle($name)
    {
        return $this->_data[$name];
    }
}