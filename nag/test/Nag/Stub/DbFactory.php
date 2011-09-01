<?php

class Nag_Stub_DbFactory
{
    private $_db;

    public function __construct($db)
    {
        $this->_db = $db;
    }

    public function create($app, $type)
    {
        return $this->_db;
    }
}