<?php
/**
 * This file contains the Horde_Service_Weather_Current class for abstracting
 * access to current observations.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Current class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Current_WeatherUnderground extends Horde_Service_Weather_Current
 {
    protected $_map = array(
        'condition' => 'weather',
        'humidity' => 'relative_humidity',
        'wind_direction' => 'wind_dir',
        'wind_degrees' => 'wind_degrees',
        'pressure' => 'pressure_in',
        'pressure_trend' => 'pressure_trend',
        'icon' => 'icon',
        'icon_url' => 'icon_url'
    );

    public function __construct($properties)
    {
        parent::__construct($properties);
        $this->location = new StdClass();
        $location = $properties['observation_location'];
        $this->location->location = $location->full;
        $this->location->lat = $location->latitude;
        $this->location->lon = $location->longitude;
        $this->location->elevation = $location->elevation;
    }

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
            if ($this->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['temp_f'];
            }
            return $this->_properties['temp_c'];
        case 'wind_speed':
            if ($this->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['wind_mph'];
            }
            return (int)$this->_properties['wind_mph'] * Horde_Service_Weather::CONVERSION_MPH_TO_KNOTS;
        case 'wind_gust':
            if ($this->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['wind_gust_mph'];
            }
            return (int)$this->_properties['wind_gust_mph'] * Horde_Service_Weather::CONVERSION_MPH_TO_KNOTS;

        case 'dewpoint':
            if ($this->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['dewpoint_f'];
            }
            return $this->_properties['dewpoint_c'];

        case 'heat_index':
            if ($this->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['heat_index_f'];
            }
            return $this->_properties['heat_index_c'];

        case 'wind_chill':
            if ($this->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['wind_chill_f'];
            }
            return $this->_properties['wind_chill_c'];

        case 'visibility':
            if ($this->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['visibility_mi'];
            }
            return $this->_properties['visibility_km'];

        default:
            if (empty($this->_map[$property])) {
                throw new Horde_Service_Weather_Exception_InvalidProperty();
            }
            return $this->_properties[$this->_map[$property]];
        }
    }

 }