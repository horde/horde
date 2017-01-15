<?php

class Kronolith_Stub_Registry extends Horde_Test_Stub_Registry
{
    public $admin = false;

    public function isAdmin(array $options = array())
    {
        return $this->admin;
    }

    public function pushApp($app)
    {
        return false;
    }
}