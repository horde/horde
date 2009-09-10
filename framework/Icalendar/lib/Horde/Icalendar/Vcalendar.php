<?php

class Horde_Icalendar_Vcalendar extends Horde_Icalendar_Base
{
    public function __construct($params = array())
    {
        $params = array_merge(array('version' => '2.0'), $params);
        parent::__construct($params);
    }

}
