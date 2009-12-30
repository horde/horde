<?php

class Horde_Icalendar_Valarm extends Horde_Icalendar_Base
{
    /**
     * Constructor.
     */
    public function __construct($properties = array())
    {
        $this->_properties += array(
            'summary' => array('required' => false,
                               'multiple' => false,
                               'type' => 'string'),
            'description' => array('required' => false,
                                   'multiple' => false,
                                   'type' => 'string'));
        parent::__construct($properties);
    }

}
