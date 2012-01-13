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
class Horde_Service_Weather_Period_Google extends Horde_Service_Weather_Period_Base
{
    /**
     * Workaround google not returning any timestamps with each period.
     *
     * @var integer
     */
    public $period = 0;

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
        'conditions' => 'condition',
        'icon_url' => 'icon',
    );

    /**
     * Const'r - overrides parent by including the $period value, which should
     * be the day number of the forecast (0 = today, 1 = tomorrow etc...).
     *
     */
    public function __construct(
        $properties,
        Horde_Service_Weather_Forecast_Base $forecast,
        $period)
    {
        parent::__construct($properties, $forecast);
        $this->period = $period;
    }

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
        case 'hour':
        case 'precipitation_percent':
        case 'period':
        case 'humidity':
        case 'wind_speed':
        case 'wind_direction':
        case 'wind_degrees':
        case 'wind_gust':
        case 'snow_total':
        case 'rain_total':
            // Not supported by Google.
            return false;
        case 'date':
            // Do the best we can with the forecast date.
            $date = new Horde_Date(time());
            $date->mday += $this->period;
            return $date;

        case 'high':
            return round($this->_fromInternalUnits($this->_properties->high['data']));

        case 'low':
            return round($this->_fromInternalUnits($this->_properties->low['data']));

        case 'icon':
            return $this->_forecast->weather->iconMap[
                str_replace('.gif', '', pathinfo(parse_url((string)$this->_properties->icon['data'], PHP_URL_PATH), PATHINFO_FILENAME))
            ];

        default:
            if (!empty($this->_map[$property])) {
                return (string)$this->_properties->{$this->_map[$property]}['data'];
            }

            throw new Horde_Service_Weather_Exception_InvalidProperty('This provider does not support the "' . $property . '" property');
        }
    }

    /**
     * Convert from units Google returns value in to units we want.
     *
     * @param  float $value  The value in Google's units
     *
     *  @return float  The converted value.
     */
    protected function _fromInternalUnits($value)
    {
        if ($this->_forecast->weather->internalUnits == $this->_forecast->weather->units) {
            return $value;
        } elseif ($this->_forecast->weather->units == Horde_Service_Weather::UNITS_METRIC) {
            return ($value - 32) * .5556;
        } else {
            return $value * 1.8 + 32;
        }
    }

}