<?php
/**
 * This file contains the Horde_Service_Weather class for communicating with
 * the weather underground service.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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

    public $logo = 'weather/wundergroundlogo.png';

    /**
     * Language to request strings from Google in.
     *
     * @var string
     */
    protected $_language = 'en';

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
        if (empty($params['apikey'])) {
            throw new InvalidArgumentException('Missing required API Key parameter.');
        }
        if (!empty($params['language'])) {
            $this->_language = $params['language'];
        }
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
        $this->_getCommonElements(rawurlencode($location));
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
        $this->_getCommonElements(rawurlencode($location), $length);
        return $this->_forecast;
    }

    /**
     * Search for a valid location code.
     *
     * @param  string $location  A location search string like e.g., Boston,MA
     * @param  integer $type     The type of search being performed.
     *
     * @return string  The search location suitable to use directly in a
     *                 weather request.
     * @throws Horde_Service_Weather_Exception
     */
    public function searchLocations($location, $type = Horde_Service_Weather::SEARCHTYPE_STANDARD)
    {
        switch ($type) {
        case Horde_Service_Weather::SEARCHTYPE_STANDARD:
        case Horde_Service_Weather::SEARCHTYPE_ZIP:
        case Horde_Service_Weather::SEARCHTYPE_CITYSTATE:
            return $this->_parseSearchLocations($this->_searchLocations(rawurlencode($location)));

        case Horde_Service_Weather::SEARCHTYPE_IP:
            return $this->_parseSearchLocations($this->_getLocationByIp(rawurlencode($location)));
        }
    }

    public function autocompleteLocation($search)
    {
        $url = new Horde_Url('http://autocomplete.wunderground.com/aq');
        $url->add(array('query' => $search, 'format' => 'JSON'));

        return $this->_parseAutocomplete($this->_makeRequest($url));
    }

    /**
     * Get array of supported forecast lengths.
     *
     * @return array The array of supported lengths.
     */
     public function getSupportedForecastLengths()
     {
         return array(
            3 => Horde_Service_Weather::FORECAST_3DAY,
            5 => Horde_Service_Weather::FORECAST_5DAY,
            7 => Horde_Service_Weather::FORECAST_7DAY,
            10 => Horde_Service_Weather::FORECAST_10DAY
         );
     }

    /**
     * Perform an IP location search.
     *
     * @param  string $ip  The IP address to use.
     *
     * @return string  The location code.
     */
    protected function _getLocationByIp($ip)
    {
        if ($this->_ipIsUnique($ip)) {
            return $this->_makeRequest(
                self::API_URL . '/api/' . $this->_apiKey
                    . '/geolookup/q/autoip.json?geo_ip=' . $ip);
        } else {
            return $this->_makeRequest(
                self::API_URL . '/api/' . $this->_apiKey
                    . '/geolookup/q/autoip.json');
        }
    }

    /**
     * Execute a location search.
     *
     * @param  string $location The location text to search.
     *
     * @return string  The location code result(s).
     */
    protected function _searchLocations($location)
    {
        return $this->_makeRequest(self::API_URL . '/api/' . $this->_apiKey
            . '/geolookup/q/' . $location . '.json');
    }

    /**
     * Weather Underground allows requesting multiple features per request,
     * and only counts it as a single request against your API key. So we trade
     * a bit of request time/traffic for a smaller number of requests to obtain
     * information for e.g., a typical weather portal display.
     */
    protected function _getCommonElements($location, $length = Horde_Service_Weather::FORECAST_10DAY)
    {
        if (!empty($this->_current) && $location == $this->_lastLocation
            && $this->_lastLength >= $length) {

            if ($this->_lastLength > $length) {
                $this->_forecast->limitLength($length);
            }

            return;
        }

        $this->_lastLength = $length;
        $this->_lastLocation = $location;

        switch ($length) {
        case Horde_Service_Weather::FORECAST_3DAY:
            $l = 'forecast';
            break;
        case Horde_Service_Weather::FORECAST_5DAY:
        case Horde_Service_Weather::FORECAST_7DAY:
            $l = 'forecast7day';
            break;
        case Horde_Service_Weather::FORECAST_10DAY:
            $l = 'forecast10day';
            break;
        }
        $url = self::API_URL . '/api/' . $this->_apiKey
            . '/geolookup/conditions/' . $l . '/astronomy/q/' . $location . '.json';
        $results = $this->_makeRequest($url, $this->_cache_lifetime);
        $station = $this->_parseStation($results->location);
        $this->_current = $this->_parseCurrent($results->current_observation);
        $astronomy = $results->moon_phase;
        $date = clone $this->_current->time;
        $date->hour = $astronomy->sunrise->hour;
        $date->min = $astronomy->sunrise->minute;
        $date->sec = 0;

        $station->sunrise = $date;
        $station->sunset = clone $date;
        $station->sunset->hour = $astronomy->sunset->hour;
        $station->sunset->min = $astronomy->sunset->minute;
        // Station information doesn't include any type of name string, so
        // get it from the currentConditions request.
        $station->name = $results->current_observation->display_location->full;
        $this->_station = $station;
        $this->_forecast = $this->_parseForecast($results->forecast);
        $this->_forecast->limitLength($length);
        $this->link = $results->current_observation->image->link;
        $this->title = $results->current_observation->image->title;
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
        return new Horde_Service_Weather_Forecast_WeatherUnderground(
            (array)$forecast, $this);
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
        return new Horde_Service_Weather_Current_WeatherUnderground((array)$current, $this);
    }

    protected function _parseSearchLocations($response)
    {
        if (!empty($response->response->error)) {
            throw new Horde_Service_Weather_Exception($response->response->error->description);
        }
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

    protected function _parseAutocomplete($results)
    {
        $return = array();
        foreach($results->RESULTS as $result) {
            $new = new stdClass();
            $new->name = $result->name;
            $new->code = $result->l;
            $return[] = $new;
        }

        return $return;
    }

    protected function _makeRequest($url, $lifetime = 86400)
    {
        $cachekey = md5('hordeweather' . $url);
        if ((!empty($this->_cache) && !$results = $this->_cache->get($cachekey, $lifetime)) ||
            empty($this->_cache)) {
            $url = new Horde_Url($url);
            $response = $this->_http->get($url);
            if (!$response->code == '200') {
                Horde::logMessage($response->getBody());
                throw new Horde_Service_Weather_Exception($response->code);
            }
            $results = $response->getBody();
            if (!empty($this->_cache)) {
               $this->_cache->set($cachekey, $results);
            }
        }
        $results = Horde_Serialize::unserialize($results, Horde_Serialize::JSON);
        if (!($results instanceof StdClass)) {
            throw new Horde_Service_Weather_Exception('Error, unable to decode response.');
        }

        return $results;
    }

 }