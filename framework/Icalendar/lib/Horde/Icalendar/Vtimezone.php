<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */

/**
 * This class represents a VTIMEZONE component.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */
class Horde_Icalendar_Vtimezone extends Horde_Icalendar_Base
{
    /**
     * Constructor.
     *
     * @param array $properties  A hash of properties and values to populate
     *                           this object with.
     *
     * @throws InvalidArgumentException
     * @throws Horde_Icalendar_Exception
     */
    public function __construct(array $properties = array())
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
        parent::__construct($properties);
    }
}
