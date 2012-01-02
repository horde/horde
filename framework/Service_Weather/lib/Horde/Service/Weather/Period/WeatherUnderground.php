<?php
/**
 * This file contains the Horde_Service_Weather_Period class for abstracting
 * access to a single forecast period from Wunderground.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
        'icon_url' => 'icon_url',
        'precipitation_percent' => 'pop',
        'period' => 'period',
        'humidity' => 'maxhumidity',
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
             // Wunderground supports this, but we don't.
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
            if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['high']->fahrenheit !== '' ?
                    $this->_properties['high']->fahrenheit :
                    Horde_Service_Weather_Translation::t('N/A');
            }
            return $this->_properties['high']->celsius;

        case 'low':
            if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['low']->fahrenheit !== '' ?
                    $this->_properties['low']->fahrenheit :
                    Horde_Service_Weather_Translation::t('N/A');
            }
            return $this->_properties['low']->celsius;

        case 'icon':
            return $this->_forecast->weather->iconMap[$this->_properties['icon']];

        case 'wind_direction':
            return Horde_Service_Weather_Translation::t($this->_properties['avewind']->dir);

        case 'wind_degrees':
            return $this->_properties['avewind']->degrees;

        case 'wind_speed':
           if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
               return $this->_properties['avewind']->mph;
           }
           return $this->_properties['avewind']->kph;

        case 'wind_gust':
           if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
               return $this->_properties['maxwind']->mph;
           }
           return $this->_properties['maxwind']->kph;

        case 'rain_total':
            if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['qpf_allday']->in;
            }
            return $this->_properties['qpf_allday']->mm;

        case 'snow_total':
            if ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_STANDARD) {
                return $this->_properties['snow_allday']->in;
            }
            return $this->_properties['snow_allday']->cm;

        default:
            if (!empty($this->_map[$property])) {
                return Horde_Service_Weather_Translation::t($this->_properties[$this->_map[$property]]);
            }

            throw new Horde_Service_Weather_Exception_InvalidProperty('This provider does not support the "' . $property . '" property');
        }
    }

}