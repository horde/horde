<?php
/**
 * This file contains the Horde_Service_Weather_Current class for abstracting
 * access to current observations from Google.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Current_Google class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Current_Google extends Horde_Service_Weather_Current_Base
 {
    protected $_map = array(
        'condition' => 'condition',
        'humidity' => 'humidity',
        'wind' => 'wind_condition',
        'icon_url' => 'icon',
    );

    public $time;

    public function __construct($properties, $weather)
    {
        parent::__construct($properties, $weather);
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
        case 'pressure':
        case 'pressure_trend':
        case 'logo_url':
        case 'dewpoint':
        case 'humidity':
        case 'wind_direction':
        case 'wind_speed':
        case 'wind_gust':
        case 'visibility':
            return false;
        case 'temp':
            if ($this->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties->temp_f['data'];
            }
            return $this->_properties->temp_c['data'];

        case 'icon':
           return $this->_weather->iconMap[basename((string)$this->_properties->icon['data'], '.gif')];


        default:
            if (empty($this->_map[$property])) {
                throw new Horde_Service_Weather_Exception_InvalidProperty();
            }
            return $this->_properties->{$this->_map[$property]}['data'];
        }
    }

 }