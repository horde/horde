<?php

class Horde_Kolab_Session_Stub_ImapFactory
extends Horde_Kolab_Session_Factory_Imap
{
    private $_result;

    public function __construct($result = null)
    {
        $this->_result = $result;
    }

    public function create($params)
    {
        return new Horde_Kolab_Session_Stub_Imap($this->_result);
    }
}