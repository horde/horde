<?php
/**
 * This file contains the Horde_Service_Weather_Location_Base class for
 * access to various weather providers' location parsing.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Location_Base class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Location_Base
{
    protected $_location;

    protected $_params;

    /**
     * Const'r
     *
     * @param  string $location  The textual location.
     * @param  array $params Additional parameters.
     *
     * @return Horde_Service_Weather_Location_Base
     */
    public function __construct($location, array $params = array())
    {
        $this->_location = $location;
    }

    /**
     * Fetch a proper location code from the backend.
     *
     * @throws Horde_Service_Weather_Exception_NotFound
     */
    public function getLocationCode()
    {
        // Default implementation just returns the provided location string.
        return $this->_location;
    }

}