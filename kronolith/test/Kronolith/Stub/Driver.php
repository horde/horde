<?php

class Kronolith_Stub_Driver extends Kronolith_Driver
{
    public $calendar = 'foo';

    public function __construct($calendar = 'foo')
    {
        $this->calendar = $calendar;
    }
}