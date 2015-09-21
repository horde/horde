<?php
/**
 * This file contains the Horde_Service_Weather_Forecast class for abstracting
 * access to forecast data from WorldWideWeather
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
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
 class Horde_Service_Weather_Forecast_Owm extends Horde_Service_Weather_Forecast_Base
 {

    /**
     * @see Horde_Service_Weather_Forecast_Base::$fields
     */
    public $fields = array(Horde_Service_Weather::FORECAST_FIELD_WIND);

    /**
     * Const'r
     *
     * @see Horde_Service_Weather_Forecast_Base::__construct()
     */
    public function __construct(
        $properties,
        Horde_Service_Weather_Base $weather,
        $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD)
    {
        parent::__construct($properties, $weather, $type);
        $this->_parseStd();

    }

    /**
     * Return the forecast time. Note that Wwo doesn't provide the validity time
     * for the forecast, so we use the last known station time. File this under
     * the "good enough" file.
     *
     * @see Horde_Service_Weather_Forecast_Base::getForecastTime()
     */
    public function getForecastTime()
    {
        return new Horde_Date($this->weather->getStation()->time);
    }

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

        foreach ($this->_properties as $period => $values) {
            $period = new Horde_Service_Weather_Period_Owm($values, $this);
            $this->_periods[] = $period;
        }
    }

 }