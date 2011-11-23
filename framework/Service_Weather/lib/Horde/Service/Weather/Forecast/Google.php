<?php
/**
 * This file contains the Horde_Service_Weather_Forecast class for abstracting
 * access to forecast data from Google.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Forecast_Google
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Forecast_Google extends Horde_Service_Weather_Forecast_Base
 {
    /**
     * Const'r
     *
     * @see Horde_Service_Weather_Forecast_Base#__construct
     */
    public function __construct(
        $properties,
        Horde_Service_Weather_Base $weather,
        $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD)
    {
        parent::__construct($properties, $weather, $type);
        $this->units = $weather->units;
        $this->_parseStd();
    }

    public function getForecastTime()
    {
        return new Horde_Date(new Horde_Date((string)$results->weather->forecast_information->current_date_time['data']));
    }

    /**
     * Parse standard forecast data.
     *
     * @throws Horde_Service_Weather_Exception
     */
    protected function _parseStd()
    {
        if (empty($this->_properties)) {
            throw new Horde_Service_Weather_Exception('No forecast data to parse.');
        }
        $day = 0;
        foreach ($this->_properties as $item) {
            foreach ($item->forecast_conditions as $period) {
                $this->_periods[] = new Horde_Service_Weather_Period_Google($period, $this, $day++);
            }
        }
    }

 }