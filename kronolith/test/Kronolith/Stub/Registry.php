<?php

class Kronolith_Stub_Registry
{
    public function getAuth()
    {
        return 'test';
    }

    public function isAdmin()
    {
        return false;
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