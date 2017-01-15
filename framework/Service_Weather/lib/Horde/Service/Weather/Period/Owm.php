<?php
/**
 * This file contains the Horde_Service_Weather_Period class for abstracting
 * access to a single forecast period from Wwo.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Period_Wwo
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Period_Owm extends Horde_Service_Weather_Period_Base
{
    /**
     * Property Map
     *
     * @TODO Figure out what to do with the 'skyicon' value - which is sometimes
     *       different than the icon and icon_url. Also, should add a icon set
     *       property to allow using other icon sets e.g., {icon_set_url}/{icon}.gif
     *
     * @var array
     */
     protected $_map = array();

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
        case 'humidity':
        case 'precipitation_percent':
        case 'wind_gust':
        case 'snow_total':
        case 'rain_total':
            return false;

        case 'conditions':
            return Horde_Service_Weather_Translation::t($this->_properties->weather[0]->main);

        case 'is_pm':
            return false;

        case 'hour':
            return false;

        case 'date':
            return new Horde_Date($this->_properties->date);

        case 'high':
            return round($this->_properties->temp->day);

        case 'low':
            return round($this->_properties->temp->night);

        case 'icon':
            return $this->_forecast->weather->iconMap[
                str_replace('.png', '', $this->_properties->weather[0]->icon)
            ];

        case 'wind_direction':
            return Horde_Service_Weather::degToDirection($this->_properties->deg);

        case 'wind_degrees':
            return $this->_properties->deg;

        case 'wind_speed':
           return $this->_properties->speed;

        default:
            throw new Horde_Service_Weather_Exception_InvalidProperty('This provider does not support the "' . $property . '" property');
        }
    }

}