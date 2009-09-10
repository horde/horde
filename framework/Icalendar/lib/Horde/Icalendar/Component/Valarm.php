<?php

class Horde_Icalendar_Component_Valarm extends Horde_Icalendar_Component_Base
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_properties += array(
            'summary' => array('required' => false,
                               'multiple' => false,
                               'type' => 'string'),
            'description' => array('required' => false,
                                   'multiple' => false,
                                   'type' => 'string'));
    }

}
