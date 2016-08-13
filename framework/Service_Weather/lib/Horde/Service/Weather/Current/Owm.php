<?php
/**
 * This file contains the Horde_Service_Weather_Current class for abstracting
 * access to current observations from OpenWeatherMap.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
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
 class Horde_Service_Weather_Current_Owm extends Horde_Service_Weather_Current_Base
 {
    protected $_map = array(
        'wind_direction' => 'wind_dir'
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
        case 'visibility':
            return null;

        case 'condition':
        case 'conditions':
            return Horde_Service_Weather_Translation::t($this->_properties->weather[0]->main);

        case 'time':
            return new Horde_Date($this->_properties->dt, 'UTC');

        case 'time_utc':
            return $this->time;

        case 'temp':
            return $this->_properties->main->temp;

        case 'wind_speed':
            return $this->_properties->wind->speed;

        case 'wind_degrees':
            return $this->_properties->wind->deg;

        case 'wind_dir':
            // @todo - Map degrees to direction.
            return '';

        case 'visibility':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_METRIC) {
                return $this->_properties->visibility;
            } else {
                return round($this->_properties->visibility * Horde_Service_Weather::CONVERSION_KPH_TO_MPH);
            }

        case 'pressure':
            return $this->_properties->main->pressure;

        case 'humidity':
            return $this->_properties->main->humidity . '%';

        case 'icon':
           return !empty($this->_weather->iconMap[$this->_properties->weather[0]->icon])
               ? $this->_weather->iconMap[$this->_properties->weather[0]->icon]
               : 'na.png';

        default:
            if (empty($this->_map[$property])) {
                throw new Horde_Service_Weather_Exception_InvalidProperty();
            }
            return Horde_Service_Weather_Translation::t($this->_properties->{$this->_map[$property]});
        }
    }

 }