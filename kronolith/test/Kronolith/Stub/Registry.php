<?php

class Kronolith_Stub_Registry
{
    public $admin = false;

    public function getAuth()
    {
        return 'test';
    }

    public function isAdmin()
    {
        return $this->admin;
    }

    public function get()
    {
        return '';
    }

    public function getApp()
    {
        return 'kronolith';
    }
}