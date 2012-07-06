<?php
/**
 * This file contains the Horde_Service_Weather class for communicating with
 * the (unofficial) google weather API.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
     * Language to request strings from Google in.
     *
     * @var string
     */
    protected $_language = 'en';

    /**
     * Holds the units that Google actually returns the results in.
     *
     * Google returns forecast values in the units it sees as appropriate for
     * the requested location.
     *
     * @var integer
     */
    public $internalUnits;

    public $title = 'Google Weather';

    public $link = 'http://google.com';

    /**
     * Icon map for wunderground.
     *
     * Note some are returned as "sky" conditions and some as "condition"
     * icons. Public so it can be overridded in client code if desired.
     */
    public $iconMap = array(
        'chance_of_rain' => '11.png',
        'chance_of_snow' => '14.png',
        'chance_of_storm' => '3.png',
        'chance_of_tstorm' => '3.png',
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
        'thunderstorm' => '3.png',
        'storm' => '12.png',
        'mist' => '29.png',
        'icy' => '7.png',
        'dust' => '19.png',
        'smoke' => '22.png'
    );

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     *                       - 'http_client': Required http client object
     *                       - 'language': Language code for returned strings.
     *
     * @return Horde_Service_Weather_Base
     */
    public function __construct(array $params = array())
    {
        if (!empty($params['language'])) {
            $this->_language = $params['language'];
        }

        parent::__construct($params);
    }

    /**
     * Returns the current observations.
     *
     * @return Horde_Service_Weather_Current
     */
    public function getCurrentConditions($location)
    {
        $this->_getCommonElements($location);
        return $this->_current;
    }

    /**
     * Returns the forecast for the current location.
     *
     * @see Horde_Service_Weather_Base#getForecast
     */
    public function getForecast($location,
                                $length = Horde_Service_Weather::FORECAST_3DAY,
                                $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD)
    {
        $this->_getCommonElements($location);
        return $this->_forecast;
    }

    /**
     * Searches for a valid location code.
     *
     * @param string $location  A location search string like e.g., Boston,MA.
     * @param integer $type     The type of search being performed.
     *
     * @return string  The search location suitable to use directly in a
     *                 weather request.
     */
    public function searchLocations($location,
                                    $type = Horde_Service_Weather::SEARCHTYPE_STANDARD)
    {
        // Google doesn't support any location searching via the weather api.
        // Just return the passed in value and hope for the best.
        if ($type == Horde_Service_Weather::SEARCHTYPE_IP) {
            throw new Horde_Service_Weather_Exception('Location by IP is not supported by this driver.');
        }

        $this->_getCommonElements($location);
        return $this->_station;
    }

    /**
     * Returns a list of supported forecast lengths.
     *
     * @return array The array of supported lengths.
     */
    public function getSupportedForecastLengths()
    {
        return array(3 => Horde_Service_Weather::FORECAST_3DAY);
    }

    /**
     */
    protected function _getCommonElements($location)
    {
        if (!empty($this->_current) && $location == $this->_lastLocation) {
            return;
        }

        $this->_lastLocation = $location;
        $url = new Horde_Url(self::API_URL);
        $url = $url->add(array(
            'weather' => $location,
            'hl' => $this->_language
        ))->setRaw(true);
        $results = $this->_makeRequest($url);
        if ($results->weather->problem_cause) {
            throw new Horde_Service_Weather_Exception(
                Horde_Service_Weather_Translation::t("There was a problem with the weather request. Maybe an invalid location?"));
        }
        $this->internalUnits = $results->weather->forecast_information->unit_system['data'] == 'US' ?
                Horde_Service_Weather::UNITS_STANDARD :
                Horde_Service_Weather::UNITS_METRIC;

        $this->_station = $this->_parseStation($results->weather->forecast_information);

        // Sunrise/Sunset
        if (!empty($this->_station->lat)) {
            $date = new Horde_Date(time());
            $this->_station->sunset = new Horde_Date(date_sunset($date->timestamp(), SUNFUNCS_RET_TIMESTAMP, $this->_station->lat, $this->_station->lon));
            $this->_station->sunrise = new Horde_Date(date_sunrise($date->timestamp(), SUNFUNCS_RET_TIMESTAMP, $this->_station->lat, $this->_station->lon));
        }
        $this->_forecast = $this->_parseForecast($results->weather);
        $this->_current = $this->_parseCurrent($results->weather->current_conditions);
        $this->_current->time = new Horde_Date((string)$results->weather->forecast_information->current_date_time['data']);
    }

    /**
     * Parses the JSON response for a location request into a station object.
     *
     * @param object $station  The response from a Location request.
     *
     * @return Horde_Service_Weather_Station
     */
    protected function _parseStation($station)
    {
        $properties = array(
            // @TODO: can we parse cith/state from results?
            'name' => urldecode((string)$station->city['data']),
            'city' => urldecode((string)$station->city['data']),
            'state' => urldecode((string)$station->city['data']),
            'country' => '',
            'country_name' => '',
            'tz' => '', // Not provided, can we assume it's the location's local?
            'lat' => $station->latitude_e6['data'],
            'lon' => $station->longitude_e6['data'],
            'zip' => '',
            'code' => (string)$station->postal_code['data'],
            'time' => (string)$station->current_date_time['data']
        );

        return new Horde_Service_Weather_Station($properties);
    }

    /**
     * Parses the forecast data.
     *
     * @param object $forecast  The result of the forecast request.
     *
     * @return Horde_Service_Weather_Forecast_Google  The forecast.
     */
    protected function _parseForecast($forecast)
    {
        $forecast = new Horde_Service_Weather_Forecast_Google($forecast, $this);
        return $forecast;
    }

    /**
     * Parses the current_conditions response.
     *
     * @param object $current  The current_condition request response object.
     *
     * @return Horde_Service_Weather_Current_Google
     */
    protected function _parseCurrent($current)
    {
        // The Current object takes care of the parsing/mapping.
        $current = new Horde_Service_Weather_Current_Google($current, $this);
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
        if ((!empty($this->_cache) &&
             !($results = $this->_cache->get($cachekey, $this->_cache_lifetime))) ||
            empty($this->_cache)) {
            $response = $this->_http->get($url);
            if (!$response->code == '200') {
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