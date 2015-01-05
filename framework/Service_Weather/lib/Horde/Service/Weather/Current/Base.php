<?php
/**
 * This file contains the Horde_Service_Weather_Current_Base class for
 * abstracting access to current observations.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Current_Base class
 *
 * @property string pressure          The barometric pressure.
 * @property string pressure_trend    The pressure trend.
 * @property string logo_url          URL to a provider logo.
 * @property string dewpoint          The dewpoint.
 * @property string wind_direction    The cardinal wind direction.
 * @property string wind_degrees      The wind direction, in degrees.
 * @property string wind_speed        The wind speed, in requested units.
 * @property string wind_gust         The wind gust speed.
 * @property string visibility        The visisbility, in requested units.
 * @property string wind_chill        The wind chill.
 * @property string heat_index        Heat index.
 * @property string temp              The temperature.
 * @property string icon              Icon name to represent conditions.
 * @property string condition         The condition string.
 * @property string humidity          The humidity.
 * @property string wind              Full wind description string.
 * @property string icon_url          Url to icon.
 * @property string logo_url          Url to logo.
 * @property Horde_Date time          Forecast time in local (to station) time.
 *                                    NOTE the timezone property of the date
 *                                    object is NOT guarenteed to be correct,
 *                                    and might be presented as the servers'
 *                                    default timezone. This is because not
 *                                    all APIs return the timezone identifier.
 * @property Horde_Date time_utc      Forecast time in UTC. @since 1.2.0
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Current_Base
 {
    /**
     * Local properties cache. Property names differ depending on the backend.
     * Concrete classes map them to the available properties.
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