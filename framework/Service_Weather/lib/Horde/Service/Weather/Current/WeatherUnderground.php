<?php
/**
 * This file contains the Horde_Service_Weather_Current class for abstracting
 * access to current observations from Wunderground.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Current_WeatherUnderground class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Current_WeatherUnderground extends Horde_Service_Weather_Current_Base
 {
    protected $_map = array(
        'condition' => 'weather',
        'humidity' => 'relative_humidity',
        'wind_direction' => 'wind_dir',
        'wind_degrees' => 'wind_degrees',
        'icon_url' => 'icon_url'
    );

    /**
     * Magic __isset method.
     *
     * @param string $property  Property name.
     *
     * @return boolen
     */
    public function __isset($property)
    {
        return !empty($this->_properties[$property]);
    }

    /**
     * Accessor
     *
     * @param string $property  Property to get
     *
     * @return mixed  The property value
     */
    public function __get($property)
    {
        // Maybe someday I can add a better $_map array with 'type' fields etc..
        // for now, just as easy to manually check for these exceptions.
        switch ($property) {
        case 'logo_url':
            return $this->_properties['image']->url;
        case 'time':
            $time = $this->_properties['observation_time_rfc822'];
            $time = new Horde_Date($time);
            $time->setTimezone($this->_properties['local_tz_long']);
            return $time;
        case 'temp':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return round($this->_properties['temp_f']);
            }
            return round($this->_properties['temp_c']);
        case 'wind_speed':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['wind_mph'];
            }
            return round($this->_properties['wind_mph'] * Horde_Service_Weather::CONVERSION_MPH_TO_KPH);
        case 'wind_gust':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['wind_gust_mph'];
            }
            return round($this->_properties['wind_gust_mph'] * Horde_Service_Weather::CONVERSION_MPH_TO_KPH);

        case 'dewpoint':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['dewpoint_f'];
            }
            return $this->_properties['dewpoint_c'];

        case 'heat_index':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['heat_index_f'];
            }
            return $this->_properties['heat_index_c'];

        case 'wind_chill':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['wind_chill_f'];
            }
            return $this->_properties['wind_chill_c'];

        case 'visibility':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return round($this->_properties['visibility_mi']);
            }
            return round($this->_properties['visibility_km']);

        case 'pressure':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['pressure_in'];
            }
            return $this->_properties['pressure_mb'];

        case 'pressure_trend':
            switch ($this->_properties['pressure_trend']) {
            case '0':
                return Horde_Service_Weather_Translation::t('steady');
            case '+':
                return Horde_Service_Weather_Translation::t('rising');
            case '-':
                return Horde_Service_Weather_Translation::t('falling');
            }
            break;

        case 'icon':
           return $this->_weather->iconMap[$this->_properties['icon']];

        default:
            if (empty($this->_map[$property])) {
                throw new Horde_Service_Weather_Exception_InvalidProperty();
            }
            if (strpos($this->_properties[$this->_map[$property]], '-999') !== false) {
                return Horde_Service_Weather_Translation::t('N/A');
            }
            return Horde_Service_Weather_Translation::t($this->_properties[$this->_map[$property]]);
        }
    }

 }