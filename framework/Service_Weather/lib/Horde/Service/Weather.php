<?php
/**
 * Horde_Service_Weather class for abstracting access to various weather
 * providers.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
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
    const FORECAST_10DAY = 10;

    /** Standard forecast summary **/
    const FORECAST_TYPE_STANDARD = 1;

    /** Detailed forecast, contains a day/night component for each day **/
    const FORECAST_TYPE_DETAILED = 2;

    /** Hourly forecast **/
    const FORECAST_TYPE_HOURLY = 3;

    const FORECAST_FIELD_WIND = 'wind';
    const FORECAST_FIELD_PRECIPITATION = 'pop';
    const FORECAST_FIELD_HUMIDITY = 'humidity';
    const FORECAST_FIELD_ACCUMULATION = 'accum';

    /** Unit constants **/
    const UNITS_STANDARD = 1;
    const UNITS_METRIC = 2;

    /** Conversion constants **/
    const CONVERSION_MPH_TO_KNOTS = 0.868976242;
    const CONVERSION_MPH_TO_KPH = 1.609344;
    const CONVERSION_KPH_TO_MPH = 0.621371192;
    const CONVERSION_MB_TO_INCHES = 0.0295301;

    /** Location search types **/
    const SEARCHTYPE_STANDARD = 1;
    const SEARCHTYPE_IP = 2;
    const SEARCHTYPE_ZIP = 3;
    const SEARCHTYPE_CITYSTATE = 4;

    /**
     * Utility function to return textual cardinal compass directions from degress.
     *
     * @param integer $degree  The degree direction (0 - 360).
     *
     * @return string  The cardinal direction.
     * @since 2.3.0
     */
    public static function degToDirection($degree)
    {
        $cardinal = array('N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW');
        $deg_delta = (int)($degree/22.5 + .5);

        return $cardinal[$deg_delta % 16];
    }

}