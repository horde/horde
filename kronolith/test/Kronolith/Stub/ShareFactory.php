<?php

class Kronolith_Stub_ShareFactory
{
    private $_shares;

    public function __construct($shares)
    {
        $this->shares = $shares;
    }

    public function create()
    {
        return $this->_shares;
    }
}