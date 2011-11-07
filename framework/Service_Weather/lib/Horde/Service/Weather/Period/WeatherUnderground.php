<?php
/**
 * This file contains the Horde_Service_Weather_Period class for abstracting
 * access to a single forecast period from Wunderground.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Period_WeatherUnderground
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Period_WeatherUnderground extends Horde_Service_Weather_Period_Base
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
    protected $_map = array(
        'conditions' => 'conditions',
        'icon' => 'icon',
        'icon_url' => 'icon_url',
        'precipitation_percent' => 'pop',
        'period' => 'period'
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
             // Wunderground only supports standard
            return false;
        case 'hour':
             // Wunderground only supports standard
            return false;
        case 'date':
            $date = new Horde_Date(array(
                'year' => $this->_properties['date']->year,
                'month' => $this->_properties['date']->month,
                'mday' => $this->_properties['date']->day));
            $date->hour = $this->_properties['date']->hour;
            $date->min = $this->_properties['date']->min;
            $date->setTimezone($this->_properties['date']->tz_long);

            return $date;

        case 'high':
            if ($this->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['high']->fahrenheit;
            }
            return $this->_properties['high']->celcius;

        case 'low':
            if ($this->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['low']->fahrenheit;
            }
            return $this->_properties['low']->celcius;

        default:
            if (!empty($this->_map[$property])) {
                return $this->_properties[$this->_map[$property]];
            }

            throw new Horde_Service_Weather_Exception_InvalidProperty('This provider does not support that property');
        }
    }

}