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
 * This class represents a VFREEBUSY component.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */
class Horde_Icalendar_Vfreebusy extends Horde_Icalendar_Base
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
            'uid' => array('required' => true,
                           'multiple' => false,
                           'type' => 'string'),
            'start' => array('required' => true,
                             'multiple' => false,
                             'class' => 'Horde_Date'),
            'stamp' => array('required' => true,
                             'multiple' => false,
                             'class' => 'Horde_Date'),
            // @todo: check MUST/MUST NOT re 4.8.4.3 and CAL-ADDRESS type.
            'organizer' => array('required' => false,
                                 'multiple' => false,
                                 'type' => 'string'),
        );
        parent::__construct($properties);
    }

    /**
     * Validates a property-value-pair.
     *
     * Values and parameters might be manipulated by this method.
     *
     * @param string $property  A property name.
     * @param mixed $value      A property value.
     * @param array $params     Property parameters.
     *
     * @throws InvalidArgumentException
     */
    protected function _validate($property, &$value, array &$params = array())
    {
        parent::_validate($property, $value);
        if ($property == 'start') {
            $value->setTimezone('UTC');
        }
    }
}
