<?php
/**
 * This file contains the Horde_Service_Weather_Current_Base class for
 * abstracting access to current observations.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Current_Base class
 *
 * @property pressure          The barometric pressure.
 * @property pressure_trend    The pressure trend.
 * @property logo_url          URL to a provider logo.
 * @property dewpoint          The dewpoint.
 * @property wind_direction    The cardinal wind direction.
 * @property wind_degrees      The wind direction, in degrees.
 * @property wind_speed        The wind speed, in requested units.
 * @property wind_gust         The wind gust speed.
 * @property visibility        The visisbility, in requested units.
 * @property wind_chill        The wind chill.
 * @property heat_index        Heat index.
 * @property temp              The temperature.
 * @property icon              Icon name to represent conditions.
 * @property condition         The condition string.
 * @property humidity          The humidity.
 * @property wind              Full wind description string.
 * @property icon_url          Url to icon.
 * @property logo_url          Url to logo.
 * @property time              Forecast time.
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
    }

    public function __get($property)
    {
        if (isset($this->_properties[$property])) {
            return $this->_properties[$property];
        }

        throw new Horde_Service_Weather_Exception_InvalidProperty('This station does not support that property');
    }

 }