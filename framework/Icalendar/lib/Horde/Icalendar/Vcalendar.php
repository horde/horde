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
 * This class represents a VCALENDAR component, by default representing the
 * iCalendar 2.0 standard as defined in RFC 2445.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */
class Horde_Icalendar_Vcalendar extends Horde_Icalendar_Base
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

        $properties += array('version' => '2.0',
                             'product' => '-//The Horde Project//Horde_Icalendar Library//EN');
        parent::__construct($properties);
    }

    /**
     * Returns the value(s) of a property.
     *
     * @param string $property  A property name.
     *
     * @return mixed  The property value, or an array of values if the property
     *                is allowed to have multiple values.
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
