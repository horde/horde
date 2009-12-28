<?php

class Horde_Icalendar_Vcalendar extends Horde_Icalendar_Base
{
    public function __construct($properties = array())
    {
        $this->_properties += array(
            // RFC 2445 Section 4.7.1
            'scale' => array('required' => false,
                             'multiple' => false,
                             'type' => 'string'),
            // RFC 2445 Section 4.7.2
            'method' => array('required' => false,
                              'multiple' => false,
                              'type' => 'string'),
            // RFC 2445 Section 4.7.3
            'product' => array('required' => true,
                               'multiple' => false,
                               'type' => 'string'),
            // RFC 2445 Section 4.7.4
            'version' => array('required' => true,
                               'multiple' => false,
                               'type' => 'string'),
        );

        $properties = array_merge(array('version' => '2.0',
                                        'product' => '-//The Horde Project//Horde_Icalendar Library//EN'),
                                  $properties);
        parent::__construct($properties);
    }

    /**
     * Getter.
     *
     * @throws InvalidArgumentException
     */
    public function __get($property)
    {
        $value = parent::__get($property);
        if ($property == 'scale' && is_null($value)) {
            $value = 'GREGORIAN';
        }
        return $value;
    }

}
