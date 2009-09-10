<?php

class Horde_Icalendar_Vevent extends Horde_Icalendar_Base
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

    public function validate()
    {
        parent::validate();
        if (!isset($this->_properties['start']['value']) &&
            !isset($this->_properties['startDate']['value'])) {
            throw new Horde_Icalendar_Exception('VEVENT components must have a start property set');
        }
    }

}
