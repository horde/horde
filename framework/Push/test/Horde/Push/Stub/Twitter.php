<?php

class Horde_Push_Stub_Twitter
extends Horde_Service_Twitter
{
    public $calls;

    public function __construct()
    {
    }

    public function __get($value)
    {
        return $this;
    }

    public function __call($method, $args)
    {
        $this->calls[] = array(
            'method' => $method,
            'args'   => $args
        );
    }
}