<?php
/**
 * This file contains the Horde_Service_Weather_Forecast class for abstracting
 * access to forecast data from TAF encoded weather sources.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Forecast_Taf
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Forecast_Taf extends Horde_Service_Weather_Forecast_Base
 {
    /**
     * Const'r
     *
     * @param array $properties                    Forecast properties.
     * @param Horde_Service_Weather_base $weather  The base driver.
     * @param integer $type                        The forecast type.
     */
    public function __construct(
        $properties,
        Horde_Service_Weather_Base $weather,
        $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD)
    {
        parent::__construct($properties, $weather, $type);
        $this->_parsePeriods();
    }

    /**
     * Return the time of the forecast, in local (to station) time.
     *
     * @return Horde_Date  The time of the forecast.
     */
    public function getForecastTime()
    {
        return new Horde_Date($this->_properties['update']);
    }

    protected function _parsePeriods()
    {
        foreach ($this->_properties['time'] as $time => $data) {
            $data['period'] = $time;
            $this->_periods[] = new Horde_Service_Weather_Period_Taf($data, $this);
        }
    }

 }