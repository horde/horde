<?php

class Horde_Kolab_Session_Stub_Imap
{
    private $_result;

    public function __construct($result = null)
    {
        $this->_result = $result;
    }

    public function login()
    {
        if ($this->_result !== null) {
            throw new Horde_Imap_Client_Exception(
                'Login failed', $this->_result
            );
        }
    }
}