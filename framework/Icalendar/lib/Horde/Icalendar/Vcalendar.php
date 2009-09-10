<?php

class Horde_Icalendar_Vcalendar extends Horde_Icalendar_Base
{
    public function __construct($properties = array())
    {
        $this->_properties += array(
            'version' => array('required' => true,
                               'multiple' => false,
                               'type' => 'string'),
            'product' => array('required' => true,
                               'multiple' => false,
                               'type' => 'string')
        );

        $properties = array_merge(array('version' => '2.0',
                                        'product' => '-//The Horde Project//Horde_Icalendar Library//EN'),
                                  $properties);
        parent::__construct($properties);
    }

}
