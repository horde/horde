<?php
/**
 * This file contains the Horde_Service_Weather_Period_Base class for
 * abstracting access to a single forecast period.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Period_Base class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Period_Base
{
    /**
     * Properties for this single peridd, as returned from the forecast request.
     *
     * @var array
     */
    protected $_properties;

    /**
     * The units to return for things like degrees and wind speed.
     *
     * @var integer
     */
    public $units = Horde_Service_Weather::UNITS_STANDARD;

    /**
     * Const'r
     *
     * @param  array $properties  Current properties, in driver keys.
     *
     * @return Horde_Service_Weather_Current
     */
    public function __construct(array $properties = array())
    {
        $this->_properties = $properties;
    }

    /**
     * Default implementation - just return the value set.
     *
     * @param string $property  The requested property.
     *
     * @return mixed  The property value.
     * @throws  Horde_Service_Weather_Exception_InvalidProperty
     */
    public function __get($property)
    {
        if (isset($this->_properties[$property])) {
            return $this->_properties[$property];
        }

        throw new Horde_Service_Weather_Exception_InvalidProperty('This provider does not support that property');
    }

    public function __set($property, $value)
    {
        $this->_properties[$property] = $value;
    }

 }