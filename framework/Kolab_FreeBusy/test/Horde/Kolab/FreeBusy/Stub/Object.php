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

    public function getServer($name)
    {
        if (isset($this->_data['server'][$name])) {
            return $this->_data['server'][$name];
        } else {
            switch ($name) {
            case 'freebusy':
                return 'https://localhost/export';
            default:
                return null;
            }
        }
    }
}