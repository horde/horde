<?php

abstract class Horde_Icalendar_Base
{
    /**
     * @var array
     */
    protected $_components = array();

    protected $_params;

    public function __construct($params)
    {
        $this->_params = $params;
    }

    public function addComponent(Horde_Icalendar_Component_Base $component)
    {
        $this->_components[] = $component;
    }

    /**
     * @todo Use LSB (static::__CLASS__) once we require PHP 5.3
     */
    public function export()
    {
        $writer = Horde_Icalendar_Writer::factory(
            str_replace('Horde_Icalendar_', '', get_class($this)),
            str_replace('.', '', $this->_params['version']));
    }

}
