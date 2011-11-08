<?php
/**
 * This file contains the Horde_Service_Weather class for communicating with
 * the (unofficial) google weather API.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Google.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Google extends Horde_Service_Weather_Base
 {

    const API_URL = 'http://www.google.com/ig/api';

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
     *
     * @var Horde_Service_Weather_Station
     */
    protected $_station;

    /**
     * Language to request strings from Google in.
     *
     * @var string
     */
    protected $_langage = 'en';

    /**
     * Cache of last requested location.
     *
     * @var string
     */
    protected $_lastLocation;

    /**
     * Icon map for wunderground. Not some are returned as
     * "sky" conditions and some as "condition" icons. Public
     * so it can be overridded in client code if desired.
     */
    public $iconMap = array(
        'chance_of_rain' => '11.png',
        'chance_of_snow' => '14.png',
        'chance_of_storm' => '3.png',
        'chance_or_tstorm' => '3.png',
        'clear' => '32.png',
        'cloudy' => '26.png',
        'flurries' => '14.png',
        'fog' => '20.png',
        'haze' => '21.png',
        'mostly_cloudy' => '28.png',
        'mostly_sunny' => '34.png',
        'partly_cloudy' => '30.png',
        'partlysunny' => '30.png',
        'sleet' => '10.png',
        'rain' => '12.png',
        'snow' => '16.png',
        'sunny' => '32.png',
        'tstorms' => '3.png',
        'storm' => '12.png',
        'mist' => '29.png',
        'icy' => '7.png',
        'dust' => '19.png',
        'smoke' => '22.png'
    );

    /**
     * Constructor
     *
     * @param array $params                                  Parameters.
     *<pre>
     *  'http_client'  - Required http client object
     *  'units'        -
     *</pre>
     *
     * @return Horde_Service_Weather_Base
     */
    public function __construct(array $params = array())
    {
        // Check required api key parameters here...
        if (empty($params['http_client'])) {
            throw new InvalidArgumentException('Missing required http_client parameter.');
        }
        $this->_http = $params['http_client'];
        unset($params['http_client']);
        if (!empty($params['language'])) {
            $this->_language = $params['language'];
        }

        parent::__construct($params);
    }

    /**
     * Obtain the current observations.
     *
     * @return Horde_Service_Weather_Current
     */
    public function getCurrentConditions($location)
    {
        $this->_getCommonElements($location);
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
        $this->_getCommonElements($location);
        return $this->_forecast;
    }

    /**
     * Get the station information.
     *
     * @return Horde_Service_Weather_Station
     */
    public function getStation()
    {
        return $this->_station;
    }

    /**
     * Search for a valid location code.
     *
     * @param  string $location  A location search string like e.g., Boston,MA
     * @param  integer $type     The type of search being performed.
     *
     * @return string  The search location suitable to use directly in a
     *                 weather request.
     */
    public function searchLocations($location, $type = Horde_Service_Weather::SEARCHTYPE_STANDARD)
    {
        // Google doesn't support any location searching via the weather api.
        // Just return the passed in value and hope for the best.
        if ($type == Horde_Service_Weather::SEARCHTYPE_IP) {
            throw new Horde_Service_Weather_Exception('Location by IP is not supported by this driver.');
        }
        $l = new StdClass();
        $l->code = $location;

        return $l;
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
        $units = $this->units == Horde_Service_Weather::UNITS_STANDARD ? 'F' : 'C';
        $url = new Horde_Url(self::API_URL);
        $url = $url->add(array(
            'weather' => urlencode($location),
            'hl' => $this->_language
        ))->setRaw(true);
        $results = $this->_makeRequest($url);
        $this->units =
            $results->weather->forecast_information->unit_sytem == 'US' ?
                Horde_Service_Weather::UNITS_STANDARD :
                Horde_Service_Weather::UNITS_METRIC;
        $this->_station = $this->_parseStation($results->weather->forecast_information);
        $this->_forecast = $this->_parseForecast($results->weather);
        $this->_current = $this->_parseCurrent($results->weather->current_conditions);
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
            // @TODO: can we parse cith/state from results?
            'name' => urldecode((string)$station->city['data']),
            'city' => urldecode((string)$station->city['data']),
            'state' => urldecode((string)$station->city['data']),
            'country' => '',
            'country_name' => '',
            'tz' => '', // Not provided, can we assume it's the location's local?
            'lat' => '',
            'lon' => '',
            'zip' => '',
            'code' => (string)$station->postal_code['data']
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
        $forecast = new Horde_Service_Weather_Forecast_Google($forecast, $this);
        return $forecast;
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
        return true;
        // The Current object takes care of the parsing/mapping.
        $current = new Horde_Service_Weather_Current_Google($current);
        $current->units = $this->units;

        return $current;
    }

    /**
     *
     * @return SimplexmlElement
     */
    protected function _makeRequest($url)
    {
        $cachekey = md5('hordeweather' . $url);
        if (!empty($this->_cache) && !$results = $this->_cache->get($cachekey, $this->_cache_lifetime)) {
            $response = $this->_http->get($url);
            if (!$response->code == '200') {
                // @todo parse exception etc..
                throw new Horde_Service_Weather_Exception($response->code);
            }
            $ct = $response->getHeader('content-type');
            $results = $response->getBody();
            $matches = array();
            if (preg_match("@charset=([^;\"'/>]+)@i", $ct, $matches)) {
                $results = Horde_String::convertCharset($results, $matches[1], 'utf-8');
            }
            if (!empty($this->_cache)) {
               $this->_cache->set($cachekey, $results);
            }
        }

        return new SimplexmlElement($results);
    }

 }