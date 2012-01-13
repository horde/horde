<?php
/**
 * This file contains the Horde_Service_Weather_Period class for abstracting
 * access to a single forecast period from Wwo.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Service_Weather_Period_Wwo extends Horde_Service_Weather_Period_Base
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
            return Horde_Service_Weather_Translation::t($this->_properties->weatherDesc[0]->value);

        case 'icon_url':
            return $this->_properties->weatherIconUrl[0]->value;

        case 'is_pm':
            return false;

        case 'hour':
            return false;

        case 'date':
            return new Horde_Date($this->_properties->date);

        case 'high':
            if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties->tempMaxF ;
            }
            return $this->_properties->tempMaxC;

        case 'low':
            if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties->tempMinF;
            }
            return $this->_properties->tempMinC;

        case 'icon':
            return $this->_forecast->weather->iconMap[
                str_replace('.png', '', basename($this->_properties->weatherIconUrl[0]->value))
            ];

        case 'wind_direction':
            return $this->_properties->winddirection;

        case 'wind_degrees':
            return $this->_properties->winddirDegree;

        case 'wind_speed':
           if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
               return $this->_properties->windspeedMiles;
           }
           return $this->_properties->windspeedKmph;

        default:
            throw new Horde_Service_Weather_Exception_InvalidProperty('This provider does not support the "' . $property . '" property');
        }
    }

}