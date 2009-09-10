<?php

class Horde_Icalendar_Vjournal extends Horde_Icalendar_Base
{
    /**
     * Constructor.
     */
    public function __construct($properties = array())
    {
        $this->_properties += array(
            'uid' => array('required' => true,
                           'multiple' => false,
                           'type' => 'string'),
            'stamp' => array('required' => true,
                             'multiple' => false,
                             'class' => 'Horde_Date'),
            'summary' => array('required' => false,
                               'multiple' => false,
                               'type' => 'string'),
            'description' => array('required' => false,
                                   'multiple' => false,
                                   'type' => 'string'));
        parent::__construct($properties);
    }

}
