<?php
/**
 * Horde_Service_Weather class for abstracting access to various weather
 * providers.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
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
    const CONVERSION_KPH_TO_KNOTS = 0.5399568;
    const CONVERSION_MPH_TO_KPH = 1.609344;
    const CONVERSION_KPH_TO_MPH = 0.621371192;
    const CONVERSION_MB_TO_INCHES = 0.0295301;
    const CONVERSION_KM_TO_SM  = 0.6213699;

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

    /**
     * Calculate windchill from temperature and windspeed.
     *
     * Temperature has to be entered in deg F, speed in mph!
     *
     * @param double  $temperature  The temperature in degrees F.
     * @param double  $speed        The wind speed in MPH.
     *
     * @return double  The windchill factor.
     * @link    http://www.nws.noaa.gov/om/windchill/
     */
    public static function calculateWindChill($temperature, $speed)
    {
        return (35.74 + 0.6215 * $temperature - 35.75 * pow($speed, 0.16) + 0.4275 * $temperature * pow($speed, 0.16));
    }

    /**
     * Calculate humidity from temperature and dewpoint
     * This is only an approximation, there is no exact formula, this
     * one here is called Magnus-Formula
     *
     * Temperature and dewpoint have to be entered in deg C!
     *
     * @param double $temperature  Temperature in degrees C.
     * @param double $dewPoint     Dewpoint in degrees C.
     *
     * @return  double
     * @link    http://www.faqs.org/faqs/meteorology/temp-dewpoint/
     */
    public static function calculateHumidity($temperature, $dewPoint)
    {
        // First calculate saturation steam pressure for both temperatures
        if ($temperature >= 0) {
            $a = 7.5;
            $b = 237.3;
        } else {
            $a = 7.6;
            $b = 240.7;
        }
        $tempSSP = 6.1078 * pow(10, ($a * $temperature) / ($b + $temperature));

        if ($dewPoint >= 0) {
            $a = 7.5;
            $b = 237.3;
        } else {
            $a = 7.6;
            $b = 240.7;
        }
        $dewSSP  = 6.1078 * pow(10, ($a * $dewPoint) / ($b + $dewPoint));

        return (100 * $dewSSP / $tempSSP);
    }

    /**
     * Calculate dewpoint from temperature and humidity
     * This is only an approximation, there is no exact formula, this
     * one here is called Magnus-Formula
     *
     * Temperature has to be entered in deg C!
     *
     * @param double $temperature  Temperature in degrees C.
     * @param double $humidity     Humidity.
     *
     * @return double
     * @link    http://www.faqs.org/faqs/meteorology/temp-dewpoint/
     */
    public static function calculateDewPoint($temperature, $humidity)
    {
        if ($temperature >= 0) {
            $a = 7.5;
            $b = 237.3;
        } else {
            $a = 7.6;
            $b = 240.7;
        }

        // First calculate saturation steam pressure for temperature
        $SSP = 6.1078 * pow(10, ($a * $temperature) / ($b + $temperature));

        // Steam pressure
        $SP  = $humidity / 100 * $SSP;

        $v   = log($SP / 6.1078, 10);

        return ($b * $v / ($a - $v));
    }

    /**
     * Convert pressure between in, hpa, mb, mm and atm
     *
     * @param double $pressure  The pressure in $from units.
     * @param  string $from  Units converting from.
     * @param  string $to    Units converting to.
     *
     * @return float  The converted pressure
     */
    public static function convertPressure($pressure, $from, $to)
    {
        $factor = array(
            'in' => array(
                'in' => 1,
                'hpa' => 33.863887,
                'mb' => 33.863887,
                'mm' => 25.4,
                'atm' => 0.0334213
            ),
            'hpa' => array(
                'in' => 0.02953,
                'hpa' => 1,
                'mb' => 1,
                'mm' => 0.7500616,
                'atm' => 0.0009869
            ),
            'mb' => array(
                'in' => 0.02953,
                'hpa' => 1,
                'mb' => 1,
                'mm' => 0.7500616,
                'atm' => 0.0009869
            ),
            'mm' => array(
                'in' => 0.0393701,
                'hpa' => 1.3332239,
                'mb' => 1.3332239,
                'mm' => 1,
                'atm' => 0.0013158
            ),
            'atm' => array(
                'in' => 29,921258,
                'hpa' => 1013.2501,
                'mb' => 1013.2501,
                'mm' => 759.999952,
                'atm' => 1
            )
        );

        $from = strtolower($from);
        $to   = strtolower($to);

        return ($pressure * $factor[$from][$to]);
    }

    /**
     * Convert speed between mph, kph, kt, mps, fps and bft
     *
     * Function will return 'false' when trying to convert from
     * Beaufort, as it is a scale and not a true measurement
     *
     * @param double $speed     The speed in $from units.
     * @param string $from      The units to convert from.
     * @param string $to        The units to convert to.
     *
     * @return double|integer|boolean
     * @link    http://www.spc.noaa.gov/faq/tornado/beaufort.html
     */
    public static function convertSpeed($speed, $from, $to)
    {
        $factor = array(
            'mph' => array(
                'mph' => 1,
                'kph' => 1.609344,
                'kt' => 0.8689762,
                'mps' => 0.44704,
                'fps' => 1.4666667
            ),
            'kph' => array(
                'mph' => 0.6213712,
                'kph' => 1,
                'kt' => 0.5399568,
                'mps' => 0.2777778,
                'fps' => 0.9113444
            ),
            'kt'  => array(
                'mph' => 1.1507794,
                'kph' => 1.852,
                'kt' => 1,
                'mps' => 0.5144444,
                'fps' => 1.6878099
            ),
            'mps' => array(
                'mph' => 2.2369363,
                'kph' => 3.6,
                'kt' => 1.9438445,
                'mps' => 1,
                'fps' => 3.2808399
            ),
            'fps' => array(
                'mph' => 0.6818182,
                'kph' => 1.09728,
                'kt' => 0.5924838,
                'mps' => 0.3048,
                'fps' => 1
            )
        );

        $from = strtolower($from);
        $to   = strtolower($to);

        if ($from == 'bft') {
            return false;
        } elseif ($to == 'bft') {
            $beaufort = array(
                  1,   3,   6,  10,
                 16,  21,  27,  33,
                 40,  47,  55,  63
            );
            $speed = round($speed * $factor[$from]['kt'], 0);
            for ($i = 0; $i < sizeof($beaufort); $i++) {
                if ($speed <= $beaufort[$i]) {
                    return $i;
                }
            }
            return sizeof($beaufort);
        } else {
            return ($speed * $factor[$from][$to]);
        }
    }

    /**
     * Convert distance between m, km, ft and sm
     *
     * @param double $distance  The distance in $from units.
     * @param string $from      The units to convert from.
     * @param string $to        The units to convert to.
     *
     * @return double
     */
    public static function convertDistance($distance, $from, $to)
    {
        $factor = array(
            'm' => array(
                'm' => 1,
                'km' => 1000,
                'ft' => 3.280839895,
                'sm' => 0.0006213699
            ),
            'km' => array(
                'm' => 0.001,
                'km' => 1,
                'ft' => 3280.839895,
                'sm' => 0.6213699
            ),
            'ft' => array(
                'm' => 0.3048,
                'km' => 0.0003048,
                'ft' => 1,
                'sm' => 0.0001894
            ),
            'sm' => array(
                'm' => 0.0016093472,
                'km' => 1.6093472,
                'ft' => 5280.0106,
                'sm' => 1
            )
        );
        $to   = strtolower($to);
        $from = strtolower($from);

        return round($distance * $factor[$from][$to]);
    }

    /**
     * Convert temperature between f and c
     *
     * @param double $temperature  The temperature in $from units.
     * @param string $from         Units to convert from.
     * @param string $to           Units to convert to.
     *
     * @return double
     */
    public static function convertTemperature($temperature, $from, $to)
    {
        if ($temperature == 'N/A') {
            return $temperature;
        }

        $from = strtolower($from{0});
        $to   = strtolower($to{0});

        $result = array(
            'f' => array(
                'f' => $temperature,
                'c' => ($temperature - 32) / 1.8
            ),
            'c' => array(
                'f' => 1.8 * $temperature + 32,
                'c' => $temperature
            )
        );

        return $result[$from][$to];
    }

}