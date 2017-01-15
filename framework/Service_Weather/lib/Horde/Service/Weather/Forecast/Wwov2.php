<?php
/**
 * This file contains the Horde_Service_Weather_Forecast class for abstracting
 * access to forecast data from WorldWideWeather using the V2 API.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Forecast_Wwo
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Forecast_Wwov2 extends Horde_Service_Weather_Forecast_Wwo
 {
    /**
     * @see Horde_Service_Weather_Forecast_Base::$fields
     */
    public $fields = array(
        Horde_Service_Weather::FORECAST_FIELD_WIND,
        Horde_Service_Weather::FORECAST_FIELD_HUMIDITY,
        Horde_Service_Weather::FORECAST_FIELD_PRECIPITATION);

    /**
     * Parse a stdRequest
     *
     * @throws Horde_Service_Weather_Exception
     */
    protected function _parseStd()
    {
        if (empty($this->_properties)) {
            throw new Horde_Service_Weather_Exception('No forecast data to parse.');
        }

        // @todo Need to refactor this when we support hourly data.
        foreach ($this->_properties as $period => $values) {
            $data = new stdClass();
            foreach ($values as $k => $v) {
                if ($k != 'hourly') {
                    $data->{$k} = $v;
                }
            }
            foreach ($values->hourly[0] as $k => $v) {
                $data->{$k} = $v;
            }
            $period = new Horde_Service_Weather_Period_Wwov2($data, $this);
            $this->_periods[] = $period;
        }
    }

 }