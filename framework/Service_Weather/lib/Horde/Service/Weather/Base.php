<?php
/**
 * This file contains the Horde_Service_Weather_Base class for abstracting
 * access to various weather providers.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Base class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 abstract class Horde_Service_Weather_Base
 {

    protected $_params;

    protected $_location;

    /**
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Constructor
     *
     * @param Horde_Service_Weather_Location_Base $location  The location object.
     * @param array $params                                  Parameters.
     *<pre>
     * 'cache' optional Horde_Cache object
     *</pre>
     *
     * @return Horde_Service_Weather_Base
     */
    public function __construct(
        Horde_Service_Weather_Location_Base $location,
        array $params = array())
    {
        $this->_location = $location;
        $this->_params = $params;
        if (!empty($params['cache'])) {
            $this->_cache = $params['cache'];
            unset($params['cache']);
        }
    }

    /**
     * Obtain the current observations.
     *
     * @return Horde_Service_Weather_Current
     */
    abstract public function getCurrentConditions();

    /**
     * Obtain the forecast for the current location.
     *
     * @param integer $type  The type of forecast to return. A
     *                       Horde_Service_Weather constant.
     *
     * @return Horde_Service_Weather_Forecast
     */
    abstract public function getForecast($type);
 }