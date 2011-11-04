<?php
/**
 * This file contains the Horde_Service_Weather_Location class for
 * access to weather underground.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Location_WeatherUnderground class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Location_WeatherUnderground extends Horde_Service_Weather_Location_Base
{

    /**
     * @var Horde_Service_Weather_WeatherUnderground
     */
    protected $_client;

    public function __construct($location, array $params = array())
    {
        parent::__construct($location, $params);
    }

    public function setClient($client)
    {
        $this->_client = $client;
    }

    /**
     * Fetch a proper location code from the backend.
     *
     * @throws Horde_Service_Weather_Exception_NotFound
     */
    public function getLocationCode()
    {
        // See what kind of data we have.
        if (!empty($this->_params['ip'])) {
            // We have an IP address, use it for location.
            $location = $this->_client->getLocationByIp($this->_params['ip']);
        }
    }

}