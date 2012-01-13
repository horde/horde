<?php
/**
 * This file contains the Horde_Service_Weather_Current class for abstracting
 * access to current observations from Google.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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

    /**
     * Accessor
     *
     * @param string $property  The  property to retrieve.
     *
     * @return mixed  The property value.
     */
    public function __get($property)
    {
        // Maybe someday I can add a better $_map array with 'type' fields etc..
        // for now, just as easy to manually check for these exceptions.
        switch ($property) {
        case 'pressure':
        case 'pressure_trend':
        case 'logo_url':
        case 'dewpoint':
        case 'wind_direction':
        case 'wind_degrees':
        case 'wind_speed':
        case 'wind_gust':
        case 'visibility':
        case 'heat_index':
        case 'wind_chill':
            return null;

        case 'temp':
            if ($this->_weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return (float)$this->_properties->temp_f['data'];
            }
            return (float)$this->_properties->temp_c['data'];

        case 'icon':
           return $this->_weather->iconMap[basename((string)$this->_properties->icon['data'], '.gif')];


        default:
            if (empty($this->_map[$property])) {
                throw new Horde_Service_Weather_Exception_InvalidProperty();
            }

            return (string)$this->_properties->{$this->_map[$property]}['data'];
        }
    }

 }