<?php
/**
 * Horde_Service_Weather class for abstracting access to various weather
 * providers.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */

class Horde_Service_Weather
{
    /** Forecast length constants **/
    const FORECAST_3DAY = 3;
    const FORECAST_5DAY = 5;
    const FORECAST_7DAY = 7;

    /** Standard forecast summary **/
    const FORECAST_TYPE_STANDARD = 1;

    /** Detailed forecast, contains a day/night component for each day **/
    const FORECAST_TYPE_DETAILED = 2;

    /** Hourly forecast **/
    const FORECAST_TYPE_HOURLY = 3;

    /** Unit constants **/
    const UNITS_STANDARD = 1;
    const UNITS_METRIC = 2;

    /** Conversion constants **/
    const CONVERSION_MPH_TO_KNOTS = 0.868976242;
}