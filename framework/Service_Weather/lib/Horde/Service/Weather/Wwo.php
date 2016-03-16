<?php
/**
 * This file contains the Horde_Service_Weather class for communicating with
 * the World Weather Online API.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
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
    const API_URL    = 'http://api.worldweatheronline.com/free/v1/weather.ashx';
    const SEARCH_URL = 'http://api.worldweatheronline.com/free/v1/search.ashx';

    const API_URL_v2 = 'https://api.worldweatheronline.com/free/v2/weather.ashx';
    const SEARCH_URL_v2 = 'https://api.worldweatheronline.com/free/v2/search.ashx';

    /**
     * @see Horde_Service_Weather_Base::$title
     * @var string
     */
    public $title = 'World Weather Online';

    /**
     * @see Horde_Service_Weather_Base::$link
     * @var string
     */
    public $link = 'http://worldweatheronline.com';

    /**
     * @see Horde_Service_Weather::$iconMap
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
     * Wwo API key.
     *
     * @var string
     */
    protected $_key;

    /**
     * API Version
     *
     * @var integer
     */
    protected $_version;

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
        $this->_version = empty($params['apiVersion']) ? 1 : $params['apiVersion'];
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
        switch ($type) {
        case Horde_Service_Weather::SEARCHTYPE_STANDARD:
        case Horde_Service_Weather::SEARCHTYPE_IP:
            return $this->_parseSearchLocations($this->_searchLocations($location));
        }
    }

    /**
     * Return an autocomplete request result.
     *
     * @see Horde_Service_Weather_Base::autocompleteLocation
     */
    public function autocompleteLocation($search)
    {
        $url = new Horde_Url($this->_version == 1 ? self::SEARCH_URL : self::SEARCH_URL_v2);
        $url->add(array(
            'q' => $search,
            'format' => 'json',
            'num_of_results' => 25));

        return $this->_parseAutocomplete($this->_makeRequest($url));
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
            5 => Horde_Service_Weather::FORECAST_5DAY
         );
     }

    /**
     * Populates some common data used by forecasts and current conditions.
     * Weather Underground allows requesting multiple features per request,
     * and only counts it as a single request against your API key. So we trade
     * a bit of request time/traffic for a smaller number of requests to obtain
     * information for e.g., a typical weather portal display.
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

        $url = new Horde_Url($this->_version == 1 ? self::API_URL : self::API_URL_v2);
        // Not sure why, but Wwo chokes if we urlencode the location?
        $url->add(array(
            'q' => $location,
            'num_of_days' => $length,
            'includeLocation' => 'yes',
            'extra' => 'localObsTime')
        );

        if ($this->_version == 1) {
            $url->add(array(
                'timezone' => 'yes')
            );
        }

        // V2 of the API only returns hourly data, so ask for 24 hour avg.
        // @todo Revisit when we support hourly forecast data.
        if ($this->_version == 2) {
            $url->add(array(
                'tp' => 24,
                'showlocaltime' => 'yes',
                'showmap' => 'yes')
            );
        }

        $results = $this->_makeRequest($url);

        // Use the minimum station data provided by forecast request to
        // fetch the full station data.
        $station = $this->_parseStation($results->data->nearest_area[0]);
        $station = $this->searchLocations($station->lat . ',' . $station->lon);

        // Hack some data to allow UTC observation time to be returned.
        $results->data->current_condition[0]->date = new Horde_Date($results->data->current_condition[0]->localObsDateTime);
        $results->data->current_condition[0]->date->hour += -$station->getOffset();

        // Parse it.
        $this->_current = $this->_parseCurrent($results->data->current_condition);

        // Sunrise/Sunset
        // @todo - this is now available in the forecast section in v2
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
            'name' => $station->areaName[0]->value . ', ' . $station->region[0]->value,
            'city' => $station->areaName[0]->value,
            'state' => $station->region[0]->value,
            'country' => $station->country[0]->value,
            'country_name' => '',
            'lat' => $station->latitude,
            'lon' => $station->longitude,
            'zip' => '',
            'code' => $station->latitude . ',' . $station->longitude
        );

        if (isset($station->timezone)) {
            // Only the *current* UTC offset is provided, with no indication
            // if we are in DST or not.
            $properties['tz'] = $station->timezone->offset;
        }

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
        $forecast = $this->_version == 1
            ? new Horde_Service_Weather_Forecast_Wwo($forecast, $this)
            : new Horde_Service_Weather_Forecast_Wwov2($forecast, $this);
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
        $url = new Horde_Url($this->_version == 1 ? self::SEARCH_URL : self::SEARCH_URL_v2);
        $url = $url->add(array(
            'timezone' => 'yes',
            'q' => $location,
            'num_of_results' => 10));

        return $this->_makeRequest($url);
    }

    protected function _parseSearchLocations($response)
    {
        if (!empty($response->results->error)) {
            throw new Horde_Service_Weather_Exception($response->results->error->message);
        }

        // Wwo's location search is pretty useless. It *always* returns multiple
        // matches, even if you pass an explicit identifier. We need to ignore
        // these, and hope for the best.
        if (!empty($response->search_api->result)) {
            return $this->_parseStation($response->search_api->result[0]);
        }

        return array();
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
        $url->add(array(
            'format' => 'json',
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
