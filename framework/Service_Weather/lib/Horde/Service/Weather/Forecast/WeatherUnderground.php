<?php
/**
 * This file contains the Horde_Service_Weather_Forecast class for abstracting
 * access to forecast data from Weatherunderground.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Forecast_WeatherUnderground
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Forecast_WeatherUnderground extends Horde_Service_Weather_Forecast_Base
 {
    public function __construct(array $properties, $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD)
    {
        parent::__construct($properties);
        switch ($type) {
        case Horde_Service_Weather::FORECAST_TYPE_STANDARD:
            $this->_parseStd();
            break;
        case Horde_Service_Weather::FORECAST_TYPE_HOURLY:
            $this->_parseHourly();
        }
    }

    /**
     * [_parse description]
     *
     * @throws Horde_Service_Weather_Exception
     */
    protected function _parseStd()
    {
        if (empty($this->_properties)) {
            throw new Horde_Service_Weather_Exception('No forecast data to parse.');
        }

        foreach ($this->_properties['simpleforecast']->forecastday as $period => $values) {
            $this->_periods[] = new Horde_Service_Weather_Period_WeatherUnderground((array)$values);
        }
    }

    protected function _parseHourly()
    {
        // @TODO: Use the hourly forecast to maybe get a "day"/"night"
    }

 }