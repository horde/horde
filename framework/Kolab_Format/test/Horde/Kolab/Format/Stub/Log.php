<?php

class Horde_Kolab_Format_Stub_Log
{
    public $log = array();

    public function debug($message)
    {
        $this->log[] = $message;
    }
}