<?php
/**
 * This file contains the Horde_Service_Weather class for communicating with
 * the OpenWeatherMap API.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Owm
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Owm extends Horde_Service_Weather_Base
{
    const API_URL    = 'http://api.openweathermap.org/data/2.5';
    /**
     * @see Horde_Service_Weather_Base::$title
     * @var string
     */
    public $title = 'OpenWeatherMap';

    /**
     * @see Horde_Service_Weather_Base::$link
     * @var string
     */
    public $link = 'http://openweathermap.org';

    /**
     * @see Horde_Service_Weather::$iconMap
     */
    public $iconMap = array(
        '01d' => '32.png',
        '01n' => '33.png',
        '02d' => '30.png', //Few Clouds,day
        '02n' => '29.png', //Few Clouds,pm
        '03d' => '28.png', //Broken clouds
        '03n' => '27.png',
        '04d' => '26.png', //Overcast
        '04d' => '26.png',
        '09d' => '11.png',
        '09n' => '40.png',
        '10d' => '12.png',
        '10n' => '30.png',
        '11d' => '0.png',
        '11n' => '47.png',
        '13d' => '16.png',
        '13n' => '42.png',
    );

    /**
     * Owm API key.
     *
     * @var string
     */
    protected $_key;

    protected $_locationCode;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     *     - cache: (Horde_Cache)             Optional Horde_Cache object.
     *     - cache_lifetime: (integer)        Lifetime of cached data, if caching.
     *     - http_client: (Horde_Http_Client) Required http client object.
     *     - apikey: (string)                 Require api key for Wwo.
     *     - apiVersion: (integer)            Version of the API to use.
     *                                        Defaults to v1 for BC reasons.
     *
     * @return Horde_Service_Weather_Wwo
     */
    public function __construct(array $params = array())
    {
        // Check required api key parameters here...
        if (empty($params['apikey'])) {
            throw new InvalidArgumentException('Missing required API Key parameter.');
        }
        $this->_key = $params['apikey'];
        unset($params['apikey']);
        parent::__construct($params);
    }

    /**
     * Obtain the current observations.
     *
     * @see Horde_Service_Weather_Base::getCurrentConditions
     *
     * @return Horde_Service_Weather_Current_Wwo
     */
    public function getCurrentConditions($location)
    {
        $this->_getCommonElements($location);
        return $this->_current;
    }

    /**
     * Obtain the forecast for the current location.
     *
     * @see Horde_Service_Weather_Base::getForecast
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
     * Search for a valid location code.
     *
     * @see Horde_Service_Weather_Base::searchLocations
     */
    public function searchLocations($location, $type = Horde_Service_Weather::SEARCHTYPE_STANDARD)
    {
        return current($this->_parseSearchLocations($this->_searchLocations($location)));
    }

    /**
     * Return an autocomplete request result.
     * @todo Provide switch to use another autocomplete API since
     *       Owm does not provide one. E.g., Wunderground provides free,
     *       key-less access to their autocomplete API.
     *
     * @see Horde_Service_Weather_Base::autocompleteLocation
     */
    public function autocompleteLocation($search)
    {
        $results = $this->searchLocations($search);
        return $this->_parseAutocomplete($results);
    }

    /**
     * Return the supported forecast lengths.
     *
     * @see Horde_Service_Weather_Base::getSupportedForecastLengths
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
     * Populates some common data used by forecasts and current conditions.
     *
     * @param string $location  The location identifier.
     * @param integer $length   The forecast length.
     */
    protected function _getCommonElements($location, $length = Horde_Service_Weather::FORECAST_5DAY)
    {
        if (!empty($this->_current) && $location == $this->_lastLocation
            && $this->_lastLength == $length) {
            return;
        }

        $this->_lastLength = $length;
        $this->_lastLocation = $location;

        $weather_url = new Horde_Url(self::API_URL . '/weather');
        $forecast_url = new Horde_Url(self::API_URL . '/forecast/daily');

        if (is_int($location)) {
            $weather_url->add(array(
                'id' => $location
            ));
            $forecast_url->add(array(
                'id' => $location
            ));
        } else {
            $weather_url->add(array(
                'q' => $location
            ));
            $forecast_url->add(array(
                'q' => $location
            ));
        }

        $current_results = $this->_makeRequest($weather_url);
        $forecast_results = $this->_makeRequest($forecast_url);
        $this->_current = $this->_parseCurrent($current_results);

        // Use the minimum station data provided by forecast request to
        // fetch the full station data.
        // @todo - use the weather station api?
        $station = $this->_parseStation($current_results);

        // // Sunrise/Sunset
        $station->sunrise = new Horde_Date($current_results->sys->sunrise, 'UTC');
        $station->sunset = new Horde_Date($current_results->sys->sunset, 'UTC');
        $station->time = $this->_current->time;
        $this->_station = $station;

        $this->_forecast = $this->_parseForecast($forecast_results->list);
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
        $properties = array(
            'name' => $station->name,
            'city' => $station->name,
            'state' => '',
            'country' => $station->sys->country,
            'country_name' => '',
            'lat' => $station->coord->lat,
            'lon' => $station->coord->lon,
            'zip' => '',
            'code' => $station->id
        );

        return new Horde_Service_Weather_Station($properties);
    }

    /**
     * Parses the forecast data.
     *
     * @param stdClass $forecast The result of the forecast request.
     *
     * @return Horde_Service_Weather_Forecast_Wwo  The forecast.
     */
    protected function _parseForecast($forecast)
    {
        $forecast = new Horde_Service_Weather_Forecast_Owm($forecast, $this);
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
        // The Current object takes care of the parsing/mapping.
        $current = new Horde_Service_Weather_Current_Owm($current, $this);
        return $current;
    }

    /**
     *
     *
     * @param  array $results  An array of Horde_Service_Weather_Station objects.
     *
     * @return [type]          [description]
     */
    protected function _parseAutocomplete($results)
    {
        $return = array();

        foreach($results as $result) {
            $new = new stdClass();
            $new->name = sprintf('%s (%s/%s)', $result->name, $result->lat, $result->lon);
            $new->code = $result->code;
            $return[] = $new;
        }

        return $return;
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
        $url = new Horde_Url(self::API_URL . '/find');
        $url = $url->add(array(
            'q' => $location,
            'type' => 'like')
        );

        return $this->_makeRequest($url);
    }

    /**
     * Return an array of location search results.
     *
     * @param stdClass $response  The results of a find query.
     *
     * @return array  An array of Horde_Service_Weather_Station objects.
     * @throws Horde_Service_Weather_Exception
     */
    protected function _parseSearchLocations($response)
    {
        if (!empty($response->results->error)) {
            throw new Horde_Service_Weather_Exception($response->results->error->message);
        }

        if (!$response->count) {
            return array();
        }
        $results = array();
        foreach ($response->list as $result) {
            $properties = array(
                'name' => $result->name,
                'city' => $result->name,
                'state' => '',
                'country' => $result->sys->country,
                'country_name' => '',
                'lat' => $result->coord->lat,
                'lon' => $result->coord->lon,
                'zip' => '',
                'code' => $result->id,
            );
            $results[] = new Horde_Service_Weather_Station($properties);
        }

        return $results;
    }

    /**
     * Make the remote API call.
     *
     * @param Horde_Url $url  The endpoint.
     *
     * @return mixed  The unserialized results form the remote API call.
     * @throws Horde_Service_Weather_Exception
     */
    protected function _makeRequest(Horde_Url $url)
    {
        // Owm returns temperature data in Kelvin by default!
        if ($this->_units == Horde_Service_Weather::UNITS_METRIC) {
            $url->add('units', 'metric');
        } else {
            $url->add('units', 'imperial');
        }
        $url->add(array(
            'key' => $this->_key
        ))->setRaw(true);

        $cachekey = md5('hordeweather' . $url);
        if ((!empty($this->_cache) &&
             !($results = $this->_cache->get($cachekey, $this->_cache_lifetime))) ||
            empty($this->_cache)) {
            $response = $this->_http->get((string)$url);
            if (!$response->code == '200') {
                throw new Horde_Service_Weather_Exception($response->code);
            }
            $results = $response->getBody();
            if (!empty($this->_cache)) {
               $this->_cache->set($cachekey, $results);
            }
        }
        $results = Horde_Serialize::unserialize($results, Horde_Serialize::JSON);
        if (!($results instanceof StdClass)) {
            throw new Horde_Service_Weather_Exception(sprintf(
                'Error, unable to decode response: %s',
                $results));
        }

        return $results;
    }

 }
