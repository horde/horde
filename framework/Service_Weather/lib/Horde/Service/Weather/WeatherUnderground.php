<?php
/**
 * This file contains the Horde_Service_Weather class for communicating with
 * the weather underground service.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_WeatherUnderground.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_WeatherUnderground extends Horde_Service_Weather_Base
 {

    const API_URL = 'http://api.wunderground.com';

    /**
     * The http client
     *
     * @var Horde_Http_Client
     */
    protected $_http;

    /**
     * Local cache of current conditions
     *
     */
    protected $_current;

    /**
     * Local cache of forecast
     *
     * @var array
     */
    protected $_forecast = array();

    /**
     * Local cache of station data
     * @var Horde_Service_Weather_Station
     */
    protected $_station;


    protected $_lastLocation;

    /**
     * Icon map for wunderground. Not some are returned as
     * "sky" conditions and some as "condition" icons. Public
     * so it can be overridded in client code if desired.
     */
    public $iconMap = array(
        'chanceflurries' => '15.png',
        'chancerain' => '11.png',
        'chancesleet' => '8.png',
        'chancesnow' => '14.png',
        'chancetstorms' => '3.png',
        'clear' => '32.png',
        'cloudy' => '26.png',
        'flurries' => '14.png',
        'fog' => '20.png',
        'hazy' => '21.png',
        'mostlycloudy' => '28.png',
        'mostlysunny' => '34.png',
        'partlycloudy' => '30.png',
        'partlysunny' => '30.png',
        'sleet' => '10.png',
        'rain' => '12.png',
        'snow' => '16.png',
        'sunny' => '32.png',
        'tstorms' => '3.png',

        // Nighttime
        'nt_chanceflurries' => '46.png',
        'nt_chancerain' => '45.png',
        'nt_chancesleet' => '10.png',
        'nt_chancesnow' => '46.png',
        'nt_chancetstorms' => '45.png',
        'nt_clear' => '31.png',
        'nt_cloudy' => '26.png',
        'nt_flurries' => '46.png',
        'nt_fog' => '20.png',
        'nt_hazy' => '21.png',
        'nt_mostlycloudy' => '45.png',
        'nt_partlycloudy' => '29.png',
        'nt_sleet' => '10.png',
        'nt_rain' => '45.png',
        'nt_snow' => '46.png',
        'nt_tstorms' => '47.png'
    );

    /**
     * Constructor
     *
     * @param Horde_Service_Weather_Location_Base $location  The location object.
     * @param array $params                                  Parameters.
     *<pre>
     *  'http_client'  - Required http client object
     *  'apikey'       - Required API key for wunderground.
     *</pre>
     *
     * @return Horde_Service_Weather_Base
     */
    public function __construct(array $params = array())
    {
        // Check required api key parameters here...
        if (empty($params['http_client']) || empty($params['apikey'])) {
            throw new InvalidArgumentException('Missing required http_client parameter.');
        }
        $this->_http = $params['http_client'];
        unset($params['http_client']);
        $this->_apiKey = $params['apikey'];
        unset($params['apikey']);
        parent::__construct($params);
    }

    /**
     * Obtain the current observations.
     *
     * @return Horde_Service_Weather_Current
     */
    public function getCurrentConditions($location)
    {
        $this->_getCommonElements(urlencode($location));
        return $this->_current;
    }

    /**
     * Obtain the forecast for the current location.
     *
     * @see Horde_Service_Weather_Base#getForecast
     */
    public function getForecast(
        $location,
        $length = Horde_Service_Weather::FORECAST_3DAY,
        $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD)
    {
        $this->_getCommonElements(urlencode($location));
        return $this->_forecast;
    }

    public function getStation()
    {
        return $this->_station;
    }

    public function searchLocations($location, $type = Horde_Service_Weather::SEARCHTYPE_STANDARD)
    {
        $location = urlencode($location);
        switch ($type) {
        case Horde_Service_Weather::SEARCHTYPE_STANDARD:
            return $this->_parseSearchLocations($this->_searchLocations($location));
            break;

        case Horde_Service_Weather::SEARCHTYPE_IP;
            // IP search always returns a single location.
            return $this->_parseSearchLocations($this->_getLocationByIp($location));
        }
    }

    protected function _getLocationByIp($ip)
    {
        $url = $this->_addJsonFormat(
            $this->_addAutoLookupQuery(
                $this->_addGeoLookupFeature(
                    $this->_addApiKey(self::API_URL)
                )
            )
        );

        return $this->_makeRequest($url);
    }

    protected function _searchLocations($location)
    {
        $url = $this->_addJsonFormat(
            $this->_addLocation(
                $this->_addGeoLookupFeature(
                    $this->_addApiKey(self::API_URL)
                ),
                $location
            )
        );

        return $this->_makeRequest($url);
    }

    /**
     * Weather Underground allows requesting multiple features per request,
     * and only counts it as a single request against your API key. So we trade
     * a bit of request time/traffic for a smaller number of requests to obtain
     * information for e.g., a typical weather portal display.
     */
    protected function _getCommonElements($location)
    {
        if (!empty($this->_current) && $location == $this->_lastLocation) {
            return;
        }

        $this->_lastLocation = $location;
        $url = $this->_addJsonFormat(
            $this->_addLocation(
                $this->_addAstronomyFeature(
                    $this->_addForecastFeature(
                        $this->_addConditionFeature(
                            $this->_addGeoLookupFeature($this->_addApiKey(self::API_URL))
                        )
                    )
                ),
                $location
            )
        );

        $results = $this->_makeRequest($url);
        $station = $this->_parseStation($results->location);

        // @TODO
        //$astronomy = $this->_parseAstronomy($results->moon_phase);
        $astronomy = $results->moon_phase;

        // Sunrise/Sunset
        $date = new Horde_Date(time(), $station->tz);
        $date->hour = $astronomy->sunrise->hour;
        $date->min = $astronomy->sunrise->minute;
        $date->sec = 0;
        $station->sunrise = $date;
        $station->sunset = clone $date;
        $station->sunset->hour = $astronomy->sunset->hour;
        $station->sunset->min = $astronomy->sunset->minute;

        $current = $this->_parseCurrent($results->current_observation);
        $forecast = $this->_parseForecast($results->forecast);

        // Station information doesn't include any type of name string, so
        // get it from the currentConditions request.
        $station->name = $current->location->location;

        // Cache the data in the object
        $this->_station = $station;
        $this->_current = $current;
        $this->_forecast = $forecast;
    }

    /**
     * Parses the JSON response for a location request into a station object.
     *
     * @param  StdClass $station  The response from a Location request.
     *
     * @return Horde_Service_Weather_Station
     */
    protected function _parseStation($station)
    {
        // @TODO: Create a subclass of Station for wunderground, parse the
        //  "close stations" and "pws" properties - allow for things like
        //  displaying other, nearby weather station conditions etc...
        $properties = array(
            'city' => $station->city,
            'state' => $station->state,
            'country' => $station->country_iso3166,
            'country_name' => $station->country_name,
            'tz' => $station->tz_long,
            'lat' => $station->lat,
            'lon' => $station->lon,
            'zip' => $station->zip,
            'code' => str_replace('/q/', '', $station->l)
        );

        return new Horde_Service_Weather_Station($properties);
    }

    /**
     * Parses the forecast data.
     *
     * @param stdClass $forecast The result of the forecast request.
     *
     * @return Horde_Service_Weather_Forecast_WeatherUnderground  The forecast.
     */
    protected function _parseForecast($forecast)
    {
        return new Horde_Service_Weather_Forecast_WeatherUnderground((array)$forecast);
    }

    /**
     * Parses astronomy information. Returned as an array since this will be
     * added to the station information.
     *
     * @param  {[type]} $astronomy [description]
     * @return {[type]}
     */
    protected function _parseAstronomy($astronomy)
    {
        // For now, just cast to array and pass back, we need to normalize
        // at least the moon data. (Given in percent illumindated and age -
        // need to parse that into phases.)
        return (array)$astronomy;
    }

    /**
     * Parse the current_conditions response.
     *
     * @param  stdClass $current  The current_condition request response object
     *
     * @return Horde_Service_Weather_Current
     */
    protected function _parseCurrent($current)
    {
        // The Current object takes care of the parsing/mapping.
        return new Horde_Service_Weather_Current_WeatherUnderground((array)$current);
    }

    protected function _parseSearchLocations($response)
    {
        if (!empty($response->response->results)) {
            $results = array();
            foreach ($response->response->results as $location) {
                $results[] = $this->_parseStation($location);
            }
            return $results;
        } else {
            return $this->_parseStation($response->location);
        }
    }

    protected function _makeRequest($url)
    {
        $cachekey = md5('hordeweather' . $url);
        if (!empty($this->_cache) && !$results = $this->_cache->get($cachekey, $this->_cache_lifetime)) {
            $url = new Horde_Url($url);
            $response = $this->_http->get($url);
            if (!$response->code == '200') {
                // @todo parse exception etc..
                throw new Horde_Service_Weather_Exception($response->code);
            }
            $results = $response->getBody();
            if (!empty($this->_cache)) {
               $this->_cache->set($cachekey, $results);
            }
        }

        return Horde_Serialize::unserialize($results, Horde_Serialize::JSON);
    }

    protected function _addLocation($url, $location)
    {
        return $url . '/q/' . $location;
    }

    protected function _addAutoLookupQuery($url)
    {
        return $url . '/q/autoip';
    }

    protected function _addApiKey($url)
    {
        return $url . '/api/' . $this->_apiKey;
    }

    protected function _addGeoLookupFeature($url)
    {
        return $url . '/geolookup';
    }

    protected function _addConditionFeature($url)
    {
        return $url . '/conditions';
    }

    protected function _addAstronomyFeature($url)
    {
         return $url . '/astronomy';
    }

    protected function _addForecastFeature($url)
    {
        return $url . '/forecast';
    }

    protected function _addJsonFormat($url)
    {
        return $url . '.json';
    }

    protected function _autoLookupQuery($url)
    {
        return $url . '/q/autoip';
    }

 }