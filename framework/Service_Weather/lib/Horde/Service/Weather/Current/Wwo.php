<?php
/**
 * This file contains the Horde_Service_Weather_Current class for abstracting
 * access to current observations from WorldWeatherOnline.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Current_Wwo class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Current_Wwo extends Horde_Service_Weather_Current_Base
 {
    protected $_map = array(
        'humidity' => 'humidity',
        'wind_direction' => 'winddir16Point',
        'wind_degrees' => 'winddirDegree'
    );

    public function __isset($property)
    {
        return !empty($this->_properties->$property);
    }

    public function __get($property)
    {
        // Maybe someday I can add a better $_map array with 'type' fields etc..
        // for now, just as easy to manually check for these exceptions.
        switch ($property) {
        case 'wind_gust':
        case 'dewpoint':
        case 'heat_index':
        case 'wind_chill':
        case 'pressure_trend':
        case 'logo_url':
            return null;

        case 'condition':
            return Horde_Service_Weather_Translation::t($this->_properties->weatherDesc[0]->value);

        case 'time':
            return new Horde_Date($this->_properties->observation_time);

        case 'temp':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties->temp_F;
            }
            return $this->_properties->temp_C;

        case 'wind_speed':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties->windspeedMiles;
            }
            return $this->_properties->windspeedKmph;

        case 'visibility':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_METRIC) {
                return $this->_properties->visibility;
            } else {
                return round($this->_properties->visibility * Horde_Service_Weather::CONVERSION_KPH_TO_MPH);
            }

        case 'pressure':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return round($this->_properties->pressure * Horde_Service_Weather::CONVERSION_MB_TO_INCHES, 2);
            }
            return $this->_properties->pressure;

        case 'icon':
           return $this->_weather->iconMap[
                str_replace('.png', '', basename($this->_properties->weatherIconUrl[0]->value))
            ];

        default:
            if (empty($this->_map[$property])) {
                throw new Horde_Service_Weather_Exception_InvalidProperty();
            }
            return Horde_Service_Weather_Translation::t($this->_properties->{$this->_map[$property]});
        }
    }

 }