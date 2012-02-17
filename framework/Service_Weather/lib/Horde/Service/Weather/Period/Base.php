<?php
/**
 * This file contains the Horde_Service_Weather_Period_Base class for
 * abstracting access to a single forecast period.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Period_Base class
 *
 * @property is_pm                  Indicates if this is for an evening period.
 * @property hour                   Indicates if this is for an hourly period.
 * @property precipitation_percent  Change of precipitation percent.
 * @property period                 The cardinal number of this period.
 * @property humidity               Humidity percent.
 * @property wind_speed             Wind speed, in currently configured units.
 * @property wind_direction         Cardinal wind direction e.g., NNW.
 * @property wind_degrees           Wind direction, in degrees.
 * @property wind_gust              Wind gust speed, in currently configured units.
 * @property snow_total             Daily snow totals, in configured units.
 * @property rain_total             Daily rain totals, in configured units.
 * @property conditions             Forecast conditions e.g., "Sunny".
 * @property icon_url               Url to a condition icon.
 * @property icon                   Name of condition icon.
 * @property date                   Date for this period.
 * @property high                   Forecast high temp, in configured units.
 * @property low                    Forecast low temp, in configured units.
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
     * @var mixed
     */
    protected $_properties;

    /**
     * Reference to parent forecast object.
     *
     * @var Horde_Service_Weather_Forecast_Base;
     */
    protected $_forecast;

    /**
     * Const'r
     *
     * @param mixed $properties                      Current properties.
     * @param Horde_Service_Forecast_Base $forecast  The parent forecast.
     *
     * @return Horde_Service_Weather_Current
     */
    public function __construct($properties, Horde_Service_Weather_Forecast_Base $forecast)
    {
        $this->_forecast = $forecast;
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

 }