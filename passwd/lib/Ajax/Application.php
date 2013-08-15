<?php
class Passwd_Ajax_Application extends Horde_Core_Ajax_Application
{
    protected function _init() {
        $this->addHandler('Passwd_Ajax_Application_Handler');
    }
}