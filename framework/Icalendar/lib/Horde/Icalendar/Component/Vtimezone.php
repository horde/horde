<?php

class Horde_Icalendar_Component_Vtimezone extends Horde_Icalendar_Component_Base
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_properties += array(
            /*
   Within the "VTIMEZONE" calendar component, this property defines the
   effective start date and time for a time zone specification. This
   property is REQUIRED within each STANDARD and DAYLIGHT part included
   in "VTIMEZONE" calendar components and MUST be specified as a local
   DATE-TIME without the "TZID" property parameter.
            */
            'start' => array('required' => true,
                             'multiple' => false,
                             'class' => 'Horde_Date'),
        );
    }

}
