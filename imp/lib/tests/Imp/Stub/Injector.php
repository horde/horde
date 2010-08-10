<?php

class IMP_Stub_Injector
{
    private $_mail;

    public function getInstance($interface)
    {
        switch($interface) {
        case 'IMP_Identity':
            return new IMP_Stub_Identity();
        case 'IMP_Mail':
            if (!isset($this->_mail)) {
                $this->_mail = new Horde_Mail_Transport_Mock();
            }
            return $this->_mail;
        }
    }
}