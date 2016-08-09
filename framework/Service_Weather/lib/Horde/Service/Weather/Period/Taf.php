<?php
/**
 * This file contains the Horde_Service_Weather_Period class for abstracting
 * access to a single forecast period from TAF encoded sources.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Period_Taf
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Period_Taf extends Horde_Service_Weather_Period_Base
{
    /**
     * Property Map
     *
     * @var array
     */
     protected $_map = array(
        'wind_speed' => 'wind',
        'wind_direction' => 'windDirection',
        'wind_degrees' => 'windDegrees',
        'wind_gust' => 'windGust',
        'high' => 'temperatureHigh',
        'low' => 'temperatureLow'
    );

    /**
     * Accessor so we can lazy-parse the results.
     *
     * @param string $property  The property name.
     *
     * @return mixed  The value of requested property
     * @throws Horde_Service_Weather_Exception_InvalidProperty
     */
    public function __get($property)
    {
        switch ($property) {
        case 'is_pm':
        case 'hour':
        case 'humidity':
        case 'precipitation_percent':
        case 'wind_gust':
        case 'snow_total':
        case 'rain_total':
        case 'icon_url':
        case 'icon':
            return false;

        case 'conditions':
            return 'foo ';//Horde_Service_Weather_Translation::t($this->_properties->weather[0]->main);

        case 'date':
            return new Horde_Date($this->_forecast->validFrom);

        default:
            if (!empty($this->_properties[$property])) {
                return $this->_properties[$property];
            }
            if (!empty($this->_map[$property])) {
                return !empty($this->_properties[$this->_map[$property]])
                    ? $this->_properties[$this->_map[$property]]
                    : false;
            }
            throw new Horde_Service_Weather_Exception_InvalidProperty('This provider does not support the "' . $property . '" property');
        }
    }

}