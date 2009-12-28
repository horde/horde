<?php

class Horde_Icalendar_Vevent extends Horde_Icalendar_Base
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
            'start' => array('required' => true,
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
        parent::__construct($properties);
    }

    /**
     * Sets the value of a property.
     *
     * @param string $property  The name of the property.
     * @param string $value     The value of the property.
     * @param array $params     Array containing any addition parameters for
     *                          this property.
     * @param boolean $add      Whether to add (instead of replace) the value.
     *
     * @throws InvalidArgumentException
     */
    protected function _setProperty($property, $value, $params = array(), $add = false)
    {
        if ($property == 'startDate') {
            $this->_validate('start', $value);
            $property = 'start';
            $params['value'] = 'date';
        }
        parent::_setProperty($property, $value, $params, $add);
    }

}
