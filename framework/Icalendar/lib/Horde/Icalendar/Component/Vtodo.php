<?php

class Horde_Icalendar_Component_Vtodo extends Horde_Icalendar_Component_Base
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_properties += array(
            'uid' => array('required' => true,
                           'multiple' => false,
                           'type' => 'string'),
            'start' => array('required' => false,
                             'multiple' => false,
                             'class' => 'Horde_Date'),
            'startDate' => array('required' => false,
                                 'multiple' => false,
                                 'class' => 'Horde_Date'),
            'stamp' => array('required' => true,
                             'multiple' => false,
                             'class' => 'Horde_Date'),
            'summary' => array('required' => false,
                               'multiple' => false,
                               'type' => 'string'),
            'description' => array('required' => false,
                                   'multiple' => false,
                                   'type' => 'string'));
    }

}
