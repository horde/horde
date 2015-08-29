<?php
/**
 * This file contains the Horde_Service_Weather_Period class for abstracting
 * access to a single forecast period from Wwo.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
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
class Horde_Service_Weather_Period_Wwov2 extends Horde_Service_Weather_Period_Base
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
            return $this->_properties->humidity;

        case 'precipitation_percent':
            // There is no "precipitation" field, so if we don't have
            // rain, check for snow, and return 0 if we have neither.
            return !empty($this->_properties->chanceofrain)
                ? $this->_properties->chanceofrain
                : (!empty($this->_properties->chanceofsnow)
                    ? $this->_properties->chanceofsnow
                    : 0);

        case 'wind_gust':
            if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties->WindGustMiles;
            }
            return $this->_properties->WindGustKmph;

        case 'snow_total':
        case 'rain_total':
            if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties->precipInches;
            }
            return $this->_properties->precipMM;

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
                return $this->_properties->maxtempF ;
            }
            return $this->_properties->maxtempC;

        case 'low':
            if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties->mintempF;
            }
            return $this->_properties->mintempC;

        case 'icon':
            return $this->_forecast->weather->iconMap[
                str_replace('.png', '', basename($this->_properties->weatherIconUrl[0]->value))
            ];

        case 'wind_direction':
            return $this->_properties->winddir16Point;

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