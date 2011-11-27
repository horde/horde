<?php
/**
 * This file contains the Horde_Service_Weather_Current_Base class for
 * abstracting access to current observations.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Current_Base class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Current_Base
 {
    /**
     * Local properties cache.
     *
     * @var array
     */
    protected $_properties = array();

    /**
     * Parent weather object.
     *
     * @var Horde_Service_Weather_Base
     */
    protected $_weather;

    /**
     * Units to return data in
     *
     * @var integer
     */
    public $units = Horde_Service_Weather::UNITS_STANDARD;

    /**
     * Const'r
     *
     * @param mixed $properties  Current properties, in driver keys.
     *
     * @return Horde_Service_Weather_Current_Base
     */
    public function __construct($properties, Horde_Service_Weather_Base $weather)
    {
        $this->_properties = $properties;
        $this->_weather = $weather;
        $this->units = $weather->units;
    }

    public function __get($property)
    {
        if (isset($this->_properties[$property])) {
            return $this->_properties[$property];
        }

        throw new Horde_Service_Weather_Exception_InvalidProperty('This station does not support that property');
    }

    public function __set($property, $value)
    {
        $this->_properties[$property] = $value;
    }

 }