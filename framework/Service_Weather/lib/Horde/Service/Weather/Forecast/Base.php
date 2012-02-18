<?php
/**
 * This file contains the Horde_Service_Weather_Forecast class for abstracting
 * access to forecast data. Provides a simple iterator for a collection of
 * forecast periods.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Current class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Forecast_Base implements IteratorAggregate
 {

    /**
     * The forecast properties as returned from the forecast request.
     *
     * @var array
     */
    protected $_properties = array();

    /**
     * Local cache of forecast periods
     *
     * @var array
     */
    protected $_periods = array();

    /**
     * Parent Weather driver.
     *
     * @var Horde_Service_Weather_Base
     */
    public $weather;

    /**
     * Forecast type
     *
     * @var integer  A Horde_Service_Weather::FORECAST_TYPE_* constant.
     */
    protected $_type;

    /**
     * Maximum forecast length to return. Defaults to sufficiently high number
     * to ensure all available days returned by default.
     */
    protected $_maxDays = 20;


    /**
     * Advertise how detailed the forecast period is.
     *<pre>
     * FORECAST_TYPE_STANDARD - Each Period represents a full day
     * FORECAST_TYPE_DETAILED - Each period represents either day or night.
     * FORECAST_TYPE_HOURLY   - Each period represents a single hour.
     *</pre>
     *
     * @var integer
     */
    public $detail = Horde_Service_Weather::FORECAST_TYPE_STANDARD;

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
        $this->_properties = $properties;
        $this->weather = $weather;
        $this->_type = $type;
    }

    public function getIterator()
    {
        return new ArrayIterator(array_slice($this->_periods, 0, $this->_maxDays));
    }

    public function getForecastDay($day)
    {
        return $this->_periods[$day];
    }

    public function getForecastTime()
    {
        return false;
    }

    /**
     * Limit the returned number of forecast days. Used for emulating a smaller
     * forecast length than the provider supports or for using one, longer
     * request to supply two different forecast length requests.
     *
     * @param integer $days  The number of days to return.
     */
    public function limitLength($days)
    {
        $this->_maxDays = $days;
    }

 }