<?php

class Stub_Log
{
    public $log = array();

    public function debug($message)
    {
        $this->log[] = $message;
    }
}