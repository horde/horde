<?php
/**
 * This file contains the Horde_Service_Weather_Forecast class for abstracting
 * access to forecast data. Provides a simple iterator for a collection of
 * forecast periods.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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

    protected $_type;

    public function __construct(array $properties, $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD)
    {
        $this->_properties = $properties;
        $this->_type = $type;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_periods);
    }

    public function getForecastDay($day)
    {
        return $_periods[$day];
    }

 }