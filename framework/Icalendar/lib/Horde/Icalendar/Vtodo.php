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
 * This class represents a VTODO component.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */
class Horde_Icalendar_Vtodo extends Horde_Icalendar_Base
{
    /**
     * Whether this is a group scheduled entity.
     *
     * @var boolean
     */
    protected $_groupScheduled = false;

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
                                   'type' => 'string'),
            // @todo: check MUST/MUST NOT re 4.8.4.3 and CAL-ADDRESS type.
            'organizer' => array('required' => false,
                                 'multiple' => false,
                                 'type' => 'string'),
        );
        parent::__construct($properties);
    }

    /**
     * Sets the value of a property.
     *
     * @param string $property  A property name.
     * @param mixed $value      A property value.
     * @param array $params     Property parameters.
     * @param boolean $add      Whether to add (instead of replace) the value.
     */
    protected function _setProperty($property, $value, array $params = array(),
                                    $add = false)
    {
        if ($property == 'startDate') {
            $this->_validate('start', $value);
            $property = 'start';
            $params['value'] = 'DATE';
        }
        parent::_setProperty($property, $value, $params, $add);
    }
}
