<?php
/**
 * This file contains the Horde_Service_Weather_Station class for abstracting
 * access to station descriptors.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Station class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Station
{
    protected $_properties = array();

    public function __construct($properties = array())
    {
        $this->_properties = $properties;
    }

    public function __get($property)
    {
        if (isset($this->_properties[$property])) {
            return $this->_properties[$property];
        }
        return '';
    }

    public function __set($property, $value)
    {
        $this->_properties[$property] = $value;
    }

}