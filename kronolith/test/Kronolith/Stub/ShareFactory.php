<?php

class Kronolith_Stub_ShareFactory
{
    private $_shares;

    public function __construct($shares)
    {
        $this->_shares = $shares;
        $this->_shares->setShareCallback(array($this, 'create'));
    }

    public function create()
    {
        return $this->_shares;
    }

    public function __sleep()
    {
        return array();
    }
}