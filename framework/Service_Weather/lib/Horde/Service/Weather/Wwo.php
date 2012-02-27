<?php
/**
 * This file contains the Horde_Service_Weather class for communicating with
 * the World Weather Online API.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Wwo
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Wwo extends Horde_Service_Weather_Base
 {

    const API_URL = 'http://free.worldweatheronline.com/feed/weather.ashx';
    const SEARCH_URL = 'http://www.worldweatheronline.com/feed/search.ashx';

    public $title = 'World Weather Online';
    public $link = 'http://worldweatheronline.com';

    protected $_key;


    /**
     * Icon map for wunderground. Not some are returned as
     * "sky" conditions and some as "condition" icons. Public
     * so it can be overridded in client code if desired.
     */
    public $iconMap = array(
        'wsymbol_0001_sunny' => '32.png',
        'wsymbol_0002_sunny_intervals' => '30.png',
        'wsymbol_0003_white_cloud' => '26.png',
        'wsymbol_0004_black_low_cloud' => '26.png',
        'wsymbol_0006_mist' => '34.png',
        'wsymbol_0007_fog' => '20.png',
        'wsymbol_0008_clear_sky_night' => '33.png',
        'wsymbol_0009_light_rain_showers' => '11.png',
        'wsymbol_0010_heavy_rain_showers' => '12.png',
        'wsymbol_0011_light_snow_showers' => '14.png',
        'wsymbol_0012_heavy_snow_showers' => '16.png',
        'wsymbol_0013_sleet_showers' => '7.png',
        'wsymbol_0016_thundery_showers' => '0.png',
        'wsymbol_0017_cloudy_with_light_rain' => '11.png',
        'wsymbol_0018_cloudy_with_heavy_rain' => '12.png',
        'wsymbol_0019_cloudy_with_light_snow' => '13.png',
        'wsymbol_0020_cloudy_with_heavy_snow' => '16.png',
        'wsymbol_0021_cloudy_with_sleet' => '8.png',
        'wsymbol_0024_thunderstorms' => '0.png',
        'wsymbol_0025_light_rain_showers_night' => '40.png',
        'wsymbol_0026_heavy_rain_showers_night' => '30.png',
        'wsymbol_0027_light_snow_showers_night' => '41.png',
        'wsymbol_0028_heavy_snow_showers_night' => '42.png',
        'wsymbol_0029_sleet_showers_night' => '7.png',
        'wsymbol_0032_thundery_showers_night' => '47.png',
        'wsymbol_0033_cloudy_with_light_rain_night' => '45.png',
        'wsymbol_0034_cloudy_with_heavy_rain_night' => '45.png',
        'wsymbol_0035_cloudy_with_light_snow_night' => '46.png',
        'wsymbol_0036_cloudy_with_heavy_snow_night' => '46.png',
        'wsymbol_0037_cloudy_with_sleet_night' => '8.png',
        'wsymbol_0040_thunderstorms_night' => '47.png'
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
        $this->_key = $params['apikey'];
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
        switch ($type) {
        case Horde_Service_Weather::SEARCHTYPE_STANDARD:
        case Horde_Service_Weather::SEARCHTYPE_IP;
            return $this->_parseSearchLocations($this->_searchLocations($location));
        }
    }


    public function autocompleteLocation($search)
    {
        $url = new Horde_Url(self::SEARCH_URL);
        $url->add(array(
            'query' => $search,
            'format' => 'json',
            'num_of_results' => 25));

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
            5 => Horde_Service_Weather::FORECAST_5DAY
         );
     }

    /**
     * Weather Underground allows requesting multiple features per request,
     * and only counts it as a single request against your API key. So we trade
     * a bit of request time/traffic for a smaller number of requests to obtain
     * information for e.g., a typical weather portal display.
     */
    protected function _getCommonElements($location, $length = Horde_Service_Weather::FORECAST_5DAY)
    {
        if (!empty($this->_current) && $location == $this->_lastLocation
            && $this->_lastLength == $length) {
            return;
        }

        $this->_lastLength = $length;
        $this->_lastLocation = $location;

        $url = new Horde_Url(self::API_URL);
        // Not sure why, but Wwo chokes if we urlencode the location?
        $url->add(array(
            'q' => $location,
            'num_of_days' => $length,
            'includeLocation' => 'yes',
            'localObsTime' => 'yes'));

        $results = $this->_makeRequest($url);
        $station = $this->_parseStation($results->data->nearest_area[0]);

        // Current conditions
        $this->_current = $this->_parseCurrent($results->data->current_condition);

        // Sunrise/Sunset
        $date = $this->_current->time;
        $station->sunset = new Horde_Date(
            date_sunset(
                $date->timestamp(),
                SUNFUNCS_RET_TIMESTAMP,
                $station->lat,
                $station->lon)
        );
        $station->sunrise = new Horde_Date(
            date_sunrise(
                $date->timestamp(),
                SUNFUNCS_RET_TIMESTAMP,
                $station->lat,
                $station->lon)
        );
        $station->time = (string)$date;
        $this->_station = $station;
        $this->_forecast = $this->_parseForecast($results->data->weather);
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
            // @TODO: can we parse cith/state from results?
            'name' => $station->areaName[0]->value . ', ' . $station->region[0]->value,
            'city' => $station->areaName[0]->value,
            'state' => $station->region[0]->value,
            'country' => $station->country[0]->value,
            'country_name' => '',
            'tz' => '', // Not provided, can we assume it's the location's local?
            'lat' => $station->latitude,
            'lon' => $station->longitude,
            'zip' => '',
            'code' => $station->latitude . ',' . $station->longitude
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
        $forecast = new Horde_Service_Weather_Forecast_Wwo($forecast, $this);
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
        $current = new Horde_Service_Weather_Current_Wwo($current[0], $this);
        return $current;
    }

    protected function _parseAutocomplete($results)
    {
        $return = array();
        if (!empty($results->search_api->result)) {
            foreach($results->search_api->result as $result) {
                if (!empty($result->region[0]->value)) {
                    $new = new stdClass();
                    $new->name = $result->areaName[0]->value . ', ' . $result->region[0]->value;
                    $new->code = $result->latitude . ',' . $result->longitude;
                    $return[] = $new;
                }
            }
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
        $url = new Horde_Url(self::SEARCH_URL);
        $url = $url->add(array(
            'timezone' => 'yes',
            'query' => $location,
            'num_of_results' => 10));

        return $this->_makeRequest($url);
    }

    protected function _parseSearchLocations($response)
    {
        if (!empty($response->error)) {
            throw new Horde_Service_Weather_Exception($response->error->msg);
        }

        // Wwo's location search is pretty useless. It *always* returns multiple
        // matches, even if you pass an explicit identifier. We need to ignore
        // these, and hope for the best.
        if (!empty($response->search_api->result)) {
            $results = array();
            return $this->_parseStation($response->search_api->result[0]);
        }

        return array();
    }

    /**
     *
     * @param Horde_Url $url
     *
     * @return SimplexmlElement
     */
    protected function _makeRequest(Horde_Url $url)
    {
        $url->add(
            array(
                'format' => 'json',
                'key' => $this->_key)
        )->setRaw(true);

        $cachekey = md5('hordeweather' . $url);
        if ((!empty($this->_cache) && !$results = $this->_cache->get($cachekey, $this->_cache_lifetime)) ||
            empty($this->_cache)) {
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