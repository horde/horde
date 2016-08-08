<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Metar
 *
 * Responsible for parsing encoded METAR and TAF data.
 *
 * Parsing code adapted from PEAR's Services_Weather_Metar class. Original
 * phpdoc attributes as follows:
 * @author      Alexander Wirtz <alex@pc4p.net>
 * @copyright   2005-2011 Alexander Wirtz
 * @link        http://pear.php.net/package/Services_Weather
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Metar extends Horde_Service_Weather_Base
{
    const UNIT_KEY_TEMP = 'temp';
    const UNIT_KEY_SPEED = 'speed';
    const UNIT_KEY_PRESSURE = 'pressure';
    const UNIT_KEY_DISTANCE = 'distance';

    /**
     * Map of hours descriptors.
     *
     * @var array
     */
    protected $_hours = array(
        'P' => '1',
        '6' => '3/6',
        '7' => '24'
    );

    /**
     * Cloud cover code map.
     *
     * @var array
     */
    protected $_clouds = array();

    /**
     * CloudType map
     *
     * @var array
     */
    protected $_cloudTypes =  array();

    /**
     * Conditions map
     *
     * @var array
     */
    protected $_conditions = array();

    /**
     * Sensors map
     *
     * @var array
     */
    protected $_sensors = array();

    /**
     * Database handle
     *
     * @var Horde_Db_Adapter_Base
     */
    protected $_db;

    /**
     * Default paths to download weather data.
     *
     * @var string
     */
    protected $_metar_path = 'http://tgftp.nws.noaa.gov/data/observations/metar/stations';
    protected $_taf_path = 'http://tgftp.nws.noaa.gov/data/forecasts/taf/stations';

    /**
     * Constructor.
     *
     * In addtion to the params for the parent class, you can also set a
     * database adapter for NOAA station lookups, and if you don't want
     * to use the default METAR/TAF http locations, you can set them here too.
     * Note only HTTP is currently supported, but file and ftp are @todo.
     *
     * @param array $params  Parameters:
     *     - cache: (Horde_Cache)             Optional Horde_Cache object.
     *     - cache_lifetime: (integer)        Lifetime of cached data, if caching.
     *     - http_client: (Horde_Http_Client) Required http client object.
     *     - db: (Horde_Db_Adapter_Base)      DB Adapter for METAR DB.
     *     - metar_path: (string)             Path or URL to METAR data.
     *     - taf_path: (string)               Path or URL to TAF data.
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);
        if (!empty($params['db'])) {
            $this->_db = $params['db'];
        }
        if (!empty($params['metar_path'])) {
            $this->_metar_path = $params['metar_path'];
        }
        if (!empty($params['taf_path'])) {
            $this->_taf_path = $params['taf_path'];
        }

        $this->_init();
    }

    /**
     * Build text maps.
     *
     */
    protected function _init()
    {
        $this->_unitMap = array(
            self::UNIT_KEY_TEMP => $this->_units == Horde_Service_Weather::UNITS_METRIC ? 'c' : 'f',
            self::UNIT_KEY_SPEED => $this->_units == Horde_Service_Weather::UNITS_METRIC ? 'kph' : 'mph',
            self::UNIT_KEY_PRESSURE => $this->_units == Horde_Service_Weather::UNITS_METRIC ? 'mm' : 'in',
            self::UNIT_KEY_DISTANCE => $this->_units == Horde_Service_Weather::UNITS_METRIC ? 'k' : 'sm'
        );

        $this->_clouds = array(
            'skc' => Horde_Service_Weather_Translation::t('sky clear'),
            'nsc' => Horde_Service_Weather_Translation::t('no significant cloud'),
            'few' => Horde_Service_Weather_Translation::t('few'),
            'sct' => Horde_Service_Weather_Translation::t('scattered'),
            'bkn' => Horde_Service_Weather_Translation::t('broken'),
            'ovc' => Horde_Service_Weather_Translation::t('overcast'),
            'vv'  => Horde_Service_Weather_Translation::t('vertical visibility'),
            'tcu' => Horde_Service_Weather_Translation::t('Towering Cumulus'),
            'cb'  => Horde_Service_Weather_Translation::t('Cumulonimbus'),
            'clr' => Horde_Service_Weather_Translation::t('clear below 12,000 ft')
        );

        $this->_cloudTypes =  array(
            'low' => array(
                '/' => Horde_Service_Weather_Translation::t('Overcast'),
                '0' => Horde_Service_Weather_Translation::t('None'),
                '1' => Horde_Service_Weather_Translation::t('Cumulus (fair weather)'),
                '2' => Horde_Service_Weather_Translation::t('Cumulus (towering)'),
                '3' => Horde_Service_Weather_Translation::t('Cumulonimbus (no anvil)'),
                '4' => Horde_Service_Weather_Translation::t('Stratocumulus (from Cumulus)'),
                '5' => Horde_Service_Weather_Translation::t('Stratocumulus (not Cumulus)'),
                '6' => Horde_Service_Weather_Translation::t('Stratus or Fractostratus (fair)'),
                '7' => Horde_Service_Weather_Translation::t('Fractocumulus/Fractostratus (bad weather)'),
                '8' => Horde_Service_Weather_Translation::t('Cumulus and Stratocumulus'),
                '9' => Horde_Service_Weather_Translation::t('Cumulonimbus (thunderstorm)')
            ),
            'middle' => array(
                '/' => Horde_Service_Weather_Translation::t('Overcast'),
                '0' => Horde_Service_Weather_Translation::t('None'),
                '1' => Horde_Service_Weather_Translation::t('Altostratus (thin)'),
                '2' => Horde_Service_Weather_Translation::t('Altostratus (thick)'),
                '3' => Horde_Service_Weather_Translation::t('Altocumulus (thin)'),
                '4' => Horde_Service_Weather_Translation::t('Altocumulus (patchy)'),
                '5' => Horde_Service_Weather_Translation::t('Altocumulus (thickening)'),
                '6' => Horde_Service_Weather_Translation::t('Altocumulus (from Cumulus)'),
                '7' => Horde_Service_Weather_Translation::t('Altocumulus (w/ Altocumulus, Altostratus, Nimbostratus)'),
                '8' => Horde_Service_Weather_Translation::t('Altocumulus (w/ turrets)'),
                '9' => Horde_Service_Weather_Translation::t('Altocumulus (chaotic)')
            ),
            'high' => array(
                '/' => Horde_Service_Weather_Translation::t('Overcast'),
                '0' => Horde_Service_Weather_Translation::t('None'),
                '1' => Horde_Service_Weather_Translation::t('Cirrus (filaments)'),
                '2' => Horde_Service_Weather_Translation::t('Cirrus (dense)'),
                '3' => Horde_Service_Weather_Translation::t('Cirrus (often w/ Cumulonimbus)'),
                '4' => Horde_Service_Weather_Translation::t('Cirrus (thickening)'),
                '5' => Horde_Service_Weather_Translation::t('Cirrus/Cirrostratus (low in sky)'),
                '6' => Horde_Service_Weather_Translation::t('Cirrus/Cirrostratus (high in sky)'),
                '7' => Horde_Service_Weather_Translation::t('Cirrostratus (entire sky)'),
                '8' => Horde_Service_Weather_Translation::t('Cirrostratus (partial)'),
                '9' => Horde_Service_Weather_Translation::t('Cirrocumulus or Cirrocumulus/Cirrus/Cirrostratus')
            )
        );

        $this->_conditions = array(
            '+'   => Horde_Service_Weather_Translation::t('heavy'),
            '-'   => Horde_Service_Weather_Translation::t('light'),
            'vc'  => Horde_Service_Weather_Translation::t('vicinity'),
            're'  => Horde_Service_Weather_Translation::t('recent'),
            'nsw' => Horde_Service_Weather_Translation::t('no significant weather'),
            'mi'  => Horde_Service_Weather_Translation::t('shallow'),
            'bc'  => Horde_Service_Weather_Translation::t('patches'),
            'pr'  => Horde_Service_Weather_Translation::t('partial'),
            'ts'  => Horde_Service_Weather_Translation::t('thunderstorm'),
            'bl'  => Horde_Service_Weather_Translation::t('blowing'),
            'sh'  => Horde_Service_Weather_Translation::t('showers'),
            'dr'  => Horde_Service_Weather_Translation::t('low drifting'),
            'fz'  => Horde_Service_Weather_Translation::t('freezing'),
            'dz'  => Horde_Service_Weather_Translation::t('drizzle'),
            'ra'  => Horde_Service_Weather_Translation::t('rain'),
            'sn'  => Horde_Service_Weather_Translation::t('snow'),
            'sg'  => Horde_Service_Weather_Translation::t('snow grains'),
            'ic'  => Horde_Service_Weather_Translation::t('ice crystals'),
            'pe'  => Horde_Service_Weather_Translation::t('ice pellets'),
            'pl'  => Horde_Service_Weather_Translation::t('ice pellets'),
            'gr'  => Horde_Service_Weather_Translation::t('hail'),
            'gs'  => Horde_Service_Weather_Translation::t('small hail/snow pellets'),
            'up'  => Horde_Service_Weather_Translation::t('unknown precipitation'),
            'br'  => Horde_Service_Weather_Translation::t('mist'),
            'fg'  => Horde_Service_Weather_Translation::t('fog'),
            'fu'  => Horde_Service_Weather_Translation::t('smoke'),
            'va'  => Horde_Service_Weather_Translation::t('volcanic ash'),
            'sa'  => Horde_Service_Weather_Translation::t('sand'),
            'hz'  => Horde_Service_Weather_Translation::t('haze'),
            'py'  => Horde_Service_Weather_Translation::t('spray'),
            'du'  => Horde_Service_Weather_Translation::t('widespread dust'),
            'sq'  => Horde_Service_Weather_Translation::t('squall'),
            'ss'  => Horde_Service_Weather_Translation::t('sandstorm'),
            'ds'  => Horde_Service_Weather_Translation::t('duststorm'),
            'po'  => Horde_Service_Weather_Translation::t('well developed dust/sand whirls'),
            'fc'  => Horde_Service_Weather_Translation::t('funnel cloud'),
            '+fc' => Horde_Service_Weather_Translation::t('tornado/waterspout')
        );
        $this->_sensors = array(
            'rvrno'  => Horde_Service_Weather_Translation::t('Runway Visual Range Detector offline'),
            'pwino'  => Horde_Service_Weather_Translation::t('Present Weather Identifier offline'),
            'pno'    => Horde_Service_Weather_Translation::t('Tipping Bucket Rain Gauge offline'),
            'fzrano' => Horde_Service_Weather_Translation::t('Freezing Rain Sensor offline'),
            'tsno'   => Horde_Service_Weather_Translation::t('Lightning Detection System offline'),
            'visno'  => Horde_Service_Weather_Translation::t('2nd Visibility Sensor offline'),
            'chino'  => Horde_Service_Weather_Translation::t('2nd Ceiling Height Indicator offline')
        );
    }

    /**
     * Returns the current observations (METAR).
     *
     * @param string $location  The location string.
     *
     * @return Horde_Service_Weather_Current_Base
     */
    public function getCurrentConditions($location)
    {
        $url = sprintf('%s/%s.TXT', $this->_metar_path, $location);
        return new Horde_Service_Weather_Current_Metar(
            $this->_parseMetar($this->_makeRequest($url)),
            $this
        );
    }

    protected function _makeRequest($url, $lifetime = 86400)
    {
        $cachekey = md5('hordeweather' . $url);
        if ((!empty($this->_cache) && !$results = $this->_cache->get($cachekey, $lifetime)) ||
            empty($this->_cache)) {
            $url = new Horde_Url($url);
            $response = $this->_http->get((string)$url);
            if (!$response->code == '200') {
                throw new Horde_Service_Weather_Exception($response->code);
            }
            $results = $response->getBody();
            if (!empty($this->_cache)) {
               $this->_cache->set($cachekey, $results);
            }
        }

        return $results;
    }

    /**
     * Returns the forecast for the current location.
     *
     * @param string $location  The location code.
     * @param integer $length   The forecast length, a
     *                          Horde_Service_Weather::FORECAST_* constant.
     *                          (Ignored)
     * @param integer $type     The type of forecast to return, a
     *                          Horde_Service_Weather::FORECAST_TYPE_* constant
     *                          (Ignored)
     *
     * @return Horde_Service_Weather_Forecast_Base
     */
    public function getForecast(
        $location,
        $length = Horde_Service_Weather::FORECAST_3DAY,
        $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD)
    {
        $url = sprintf('%s/%s.TXT', $this->_taf_path, $location);
        return new Horde_Service_Weather_Forecast_Taf(
            $this->_parseTafData($this->_makeRequest($url)),
            $this
        );
    }


    /**
     * Searches locations.
     *
     * @param string $location  The location string to search.
     * @param integer $type     The type of search to perform, a
     *                          Horde_Service_Weather::SEARCHTYPE_* constant.
     *
     * @return Horde_Service_Weather_Station The search location suitable to use
     *                                       directly in a weather request.
     * @throws Horde_Service_Weather_Exception
     */
    public function searchLocations(
        $location,
        $type = Horde_Service_Weather::SEARCHTYPE_STANDARD)
    {

    }

    /**
     * Get array of supported forecast lengths.
     *
     * @return array The array of supported lengths.
     */
     public function getSupportedForecastLengths()
     {

     }

    /**
     * Parse METAR encoded string into an array of human readable properties.
     *
     * @param  string $data  The raw METAR data.
     *
     * @return array  The parsed data array.
     */
    protected function _parseMetar($data)
    {
        // Split on lines.
        $data = preg_split('/\n|\r\n|\n\r/', $data);

        // Eliminate trailing information
        for ($i = 0; $i < sizeof($data); $i++) {
            if (strpos($data[$i], '=') !== false) {
                $data[$i] = substr($data[$i], 0, strpos($data[$i], '='));
                $data = array_slice($data, 0, $i + 1);
                break;
            }
        }

        // Start with parsing the first line for the last update
        $weatherData = array();
        $weatherData['station'] = '';
        $weatherData['dataRaw'] = implode(' ', $data);
        $weatherData['update'] = strtotime(trim($data[0]) .' GMT');
        $weatherData['updateRaw'] = trim($data[0]);

        // and prepare the rest for stepping through
        array_shift($data);
        $metar = explode(' ', preg_replace('/\s{2,}/', ' ', implode(' ', $data)));

        // Trend handling
        $trendCount = 0;

        // Pointer to the array we add the data to. Needed for handling trends.
        $pointer = &$weatherData;

        // Load the metar codes for this go around.
        $metarCode = $this->_getMetarCodes();

        for ($i = 0; $i < sizeof($metar); $i++) {
            $metar[$i] = trim($metar[$i]);
            if (!strlen($metar[$i])) {
                continue;
            }
            $result   = array();
            $resultVF = array();
            $lresult  = array();

            foreach ($metarCode as $key => $regexp) {
                // Check if current code matches current metar snippet
                if (preg_match('/^' . $regexp . '$/i', $metar[$i], $result)) {
                    switch ($key) {
                    case 'station':
                        $pointer['station'] = $result[0];
                        unset($metarCode['station']);
                        break;
                    case 'wind':
                        $pointer['wind'] = Horde_Service_Weather::convertSpeed(
                            $result[2],
                            $result[5],
                            $this->_unitMap[self::UNIT_KEY_SPEED]
                        );
                        $wind_mph = Horde_Service_Weather::convertSpeed(
                            $result[2],
                            $result[5],
                            'mph',
                            $this->_unitMap[self::UNIT_KEY_SPEED]
                        );
                        if ($result[1] == 'VAR' || $result[1] == 'VRB') {
                            // Variable winds
                            $pointer['windDegrees']   = Horde_Service_Weather_Translation::t('Variable');
                            $pointer['windDirection'] = Horde_Service_Weather_Translation::t('Variable');
                        } else {
                            // Save wind degree and calc direction
                            $pointer['windDegrees']   = intval($result[1]);
                            $pointer['windDirection'] = Horde_Service_Weather::degToDirection($result[1]);
                        }
                        if (is_numeric($result[4])) {
                            // Wind with gusts...
                            $pointer['windGust'] = Horde_Service_Weather::convertSpeed(
                                $result[4],
                                $result[5],
                                $this->_unitMap[self::UNIT_KEY_SPEED]
                            );
                        }
                        break;
                    case 'windVar':
                        // Once more wind, now variability around the current wind-direction
                        $pointer['windVariability'] = array(
                            'from' => intval($result[1]),
                            'to' => intval($result[2])
                        );
                        break;
                    case 'visFrac':
                        // Possible fractional visibility here. Check if it matches with the next METAR piece for visibility
                        if (!isset($metar[$i + 1]) ||
                            !preg_match('/^' . $metarCode['visibility'] . '$/i', $result[1] . ' ' . $metar[$i + 1], $resultVF)) {
                            // No next METAR piece available or not matching.
                            break;
                        } else {
                            // Match. Hand over result and advance METAR
                            $key = 'visibility';
                            $result = $resultVF;
                            $i++;
                        }
                    case 'visibility':
                        $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('AT');
                        if (is_numeric($result[1]) && ($result[1] == 9999)) {
                            // Upper limit of visibility range is 10KM.
                            $visibility = Horde_Service_Weather::convertDistance(
                                10,
                                'km',
                                $this->_unitMap[self::UNIT_KEY_DISTANCE]
                            );
                            $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BEYOND');
                        } elseif (is_numeric($result[1])) {
                            // 4-digit visibility in m
                            $visibility = Horde_Service_Weather::convertDistance(
                                $result[1],
                                'm',
                                $this->_unitMap[self::UNIT_KEY_DISTANCE]
                            );
                        } elseif (!isset($result[11]) || $result[11] != 'CAVOK') {
                            if ($result[3] == 'M') {
                                $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BELOW');
                            } elseif ($result[3] == 'P') {
                                $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BEYOND');
                            }
                            if (is_numeric($result[5])) {
                                // visibility as one/two-digit number
                                $visibility = Horde_Service_Weather::convertDistance(
                                    $result[5],
                                    $result[10],
                                    $this->_unitMap[self::UNIT_KEY_DISTANCE]
                                );
                            } else {
                                // the y/z part, add if we had a x part (see visibility1)
                                if (is_numeric($result[7])) {
                                    $visibility = Horde_Service_Weather::convertDistance(
                                        $result[7] + $result[8] / $result[9],
                                        $result[10],
                                        $this->_unitMap[self::UNIT_KEY_DISTANCE]
                                    );
                                } else {
                                    $visibility = Horde_Service_Weather::convertDistance(
                                        $result[8] / $result[9],
                                        $result[10],
                                        $this->_unitMap[self::UNIT_KEY_DISTANCE]
                                    );
                                }
                            }
                        } else {
                            $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BEYOND');
                            $visibility = Horde_Service_Weather::convertDistance(
                                10,
                                'km',
                                $this->_unitMap[self::UNIT_KEY_DISTANCE]
                            );
                            $pointer['clouds'] = array(array(
                                'amount' => Horde_Service_Weather_Translation::t('Clear below'),
                                'height' => 5000)
                            );
                            $pointer['condition'] = Horde_Service_Weather_Translation::t('no significant weather');
                        }
                        $pointer['visibility'] = $visibility;
                        break;
                    case 'condition':
                        if (!isset($pointer['condition'])) {
                            $pointer['condition'] = '';
                        } elseif (strlen($pointer['condition']) > 0) {
                            $pointer['condition'] .= ',';
                        }

                        if (in_array(strtolower($result[0]), $this->_conditions)) {
                            // First try matching the complete string
                            $pointer['condition'] .= ' ' . $this->_conditions[strtolower($result[0])];
                        } else {
                            // No luck, match part by part
                            array_shift($result);
                            $result = array_unique($result);
                            foreach ($result as $condition) {
                                if (strlen($condition) > 0) {
                                    $pointer['condition'] .= ' ' . $this->_conditions[strtolower($condition)];
                                }
                            }
                        }
                        $pointer['condition'] = trim($pointer['condition']);
                        break;
                    case 'clouds':
                        if (!isset($pointer['clouds'])) {
                            $pointer['clouds'] = array();
                        }

                        if (sizeof($result) == 5) {
                            // Only amount and height
                            $cloud = array('amount' => $this->_clouds[strtolower($result[3])]);
                            if ($result[4] == '///') {
                                $cloud['height'] = Horde_Service_Weather_Translation::t('station level or below');
                            } else {
                                $cloud['height'] = $result[4] * 100;
                            }
                        } elseif (sizeof($result) == 6) {
                            // Amount, height and type
                            $cloud = array(
                                'amount' => $this->_clouds[strtolower($result[3])],
                                'type' => $this->_clouds[strtolower($result[5])]
                            );
                            if ($result[4] == '///') {
                                $cloud['height'] = Horde_Service_Weather_Translation::t('station level or below');
                            } else {
                                $cloud['height'] = $result[4] * 100;
                            }
                        } else {
                            // SKC or CLR or NSC
                            $cloud = array('amount' => $this->_clouds[strtolower($result[0])]);
                        }
                        $pointer['clouds'][] = $cloud;
                        break;
                    case 'temperature':
                        // normal temperature in first part
                        // negative value
                        if ($result[1] == 'M') {
                            $result[2] *= -1;
                        }
                        $pointer['temperature'] = Horde_Service_Weather::convertTemperature(
                            $result[2],
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        $temp_f = Horde_Service_Weather::convertTemperature($result[2], 'c', 'f');
                        if (sizeof($result) > 4) {
                            // same for dewpoint
                            if ($result[4] == 'M') {
                                $result[5] *= -1;
                            }
                            $pointer['dewPoint'] = Horde_Service_Weather::convertTemperature(
                                $result[5],
                                'c',
                                $this->_unitMap[self::UNIT_KEY_TEMP]
                            );
                            $pointer['humidity'] = Horde_Service_Weather::calculateHumidity($result[2], $result[5]);
                        }
                        if (isset($pointer['wind'])) {
                            // Now calculate windchill from temperature and windspeed
                            // Note these must be in F and MPH.
                            $pointer['feltTemperature'] = Horde_Service_Weather::calculateWindChill($temp_f, $wind_mph);
                        }
                        break;
                    case 'pressure':
                        if ($result[1] == 'A') {
                            // Pressure provided in inches
                            $pointer['pressure'] = $result[2] / 100;
                        } elseif ($result[3] == 'Q') {
                            // ... in hectopascal
                            $pointer['pressure'] = Horde_Service_Weather::convertPressure(
                                $result[4],
                                'hpa',
                                $this->_unitMap[self::UNIT_KEY_PRESSURE]
                            );
                        }
                        break;
                    case 'trend':
                        // We may have a trend here... extract type and set pointer on
                        // created new array
                        if (!isset($weatherData['trend'])) {
                            $weatherData['trend'] = array();
                            $weatherData['trend'][$trendCount] = array();
                        }
                        $pointer = &$weatherData['trend'][$trendCount];
                        $trendCount++;
                        $pointer['type'] = $result[0];
                        while (isset($metar[$i + 1]) && preg_match('/^(FM|TL|AT)(\d{2})(\d{2})$/i', $metar[$i + 1], $lresult)) {
                            if ($lresult[1] == 'FM') {
                                $pointer['from'] = $lresult[2] . ':' . $lresult[3];
                            } elseif ($lresult[1] == 'TL') {
                                $pointer['to'] = $lresult[2] . ':' . $lresult[3];
                            } else {
                                $pointer['at'] = $lresult[2] . ':' . $lresult[3];
                            }
                            // As we have just extracted the time for this trend
                            // from our METAR, increase field-counter
                            $i++;
                        }
                        break;
                    case 'remark':
                        // Remark part begins
                        $metarCode = $this->_getRemarks();
                        $weatherData['remark'] = array();
                        break;
                    case 'autostation':
                        // Which autostation do we have here?
                        if ($result[1] == 0) {
                            $weatherData['remark']['autostation'] = Horde_Service_Weather_Translation::t('Automatic weatherstation w/o precipitation discriminator');
                        } else {
                            $weatherData['remark']['autostation'] = Horde_Service_Weather_Translation::t('Automatic weatherstation w/ precipitation discriminator');
                        }
                        unset($metarCode['autostation']);
                        break;
                    case 'presschg':
                        // Decoding for rapid pressure changes
                        if (strtolower($result[1]) == 'r') {
                            $weatherData['remark']['presschg'] = Horde_Service_Weather_Translation::t('Pressure rising rapidly');
                        } else {
                            $weatherData['remark']['presschg'] = Horde_Service_Weather_Translation::t('Pressure falling rapidly');
                        }
                        unset($metarCode['presschg']);
                        break;
                    case 'seapressure':
                        // Pressure at sea level (delivered in hpa)
                        // Decoding is a bit obscure as 982 gets 998.2
                        // whereas 113 becomes 1113 -> no real rule here
                        if (strtolower($result[1]) != 'no') {
                            if ($result[1] > 500) {
                                $press = 900 + round($result[1] / 100, 1);
                            } else {
                                $press = 1000 + $result[1];
                            }
                            $weatherData['remark']['seapressure'] = Horde_Service_Weather::convertPressure(
                                $press,
                                'hpa',
                                $this->_unitMap[self::UNIT_KEY_PRESSURE]
                            );
                        }
                        unset($metarCode['seapressure']);
                        break;
                    case 'precip':
                        // Precipitation in inches
                        if (!isset($weatherData['precipitation'])) {
                            $weatherData['precipitation'] = array();
                        }
                        if (!is_numeric($result[2])) {
                            $precip = 'indeterminable';
                        } elseif ($result[2] == '0000') {
                            $precip = 'traceable';
                        } else {
                            $precip = $result[2] / 100;
                        }
                        $weatherData['precipitation'][] = array(
                            'amount' => $precip,
                            'hours'  => $this->_hours[$result[1]]
                        );
                        break;
                    case 'snowdepth':
                        // Snow depth in inches
                        // @todo convert to metric
                        $weatherData['remark']['snowdepth'] = $result[1];
                        unset($metarCode['snowdepth']);
                        break;
                    case 'snowequiv':
                        // Same for equivalent in Water... (inches)
                        // @todo convert
                        $weatherData['remark']['snowequiv'] = $result[1] / 10;
                        unset($metarCode['snowequiv']);
                        break;
                    case 'cloudtypes':
                        // Cloud types
                        $weatherData['remark']['cloudtypes'] = array(
                            'low'    => $this->_cloudTypes['low'][$result[1]],
                            'middle' => $this->_cloudTypes['middle'][$result[2]],
                            'high'   => $this->_cloudTypes['high'][$result[3]]
                        );
                        unset($metarCode['cloudtypes']);
                        break;
                    case 'sunduration':
                        // Duration of sunshine (in minutes)
                        $weatherData['remark']['sunduration'] = sprintf(
                            Horde_Service_Weather_Translation::t('Total minutes of sunshine: %s'),
                            $result[1]
                        );
                        unset($metarCode['sunduration']);
                        break;
                    case '1htempdew':
                        // Temperatures in the last hour in C
                        if ($result[1] == '1') {
                            $result[2] *= -1;
                        }
                        $weatherData['remark']['1htemp'] = Horde_Service_Weather::convertTemperature(
                            $result[2] / 10,
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );

                        if (sizeof($result) > 3) {
                            // same for dewpoint
                            if ($result[4] == '1') {
                                $result[5] *= -1;
                            }
                            $weatherData['remark']['1hdew'] = Horde_Service_Weather::convertTemperature(
                                $result[5] / 10,
                                'c',
                                $this->_unitMap[self::UNIT_KEY_TEMP]
                            );
                        }
                        unset($metarCode['1htempdew']);
                        break;
                    case '6hmaxtemp':
                        // Max temperature in the last 6 hours in C
                        if ($result[1] == '1') {
                            $result[2] *= -1;
                        }
                        $weatherData['remark']['6hmaxtemp'] = Horde_Service_Weather::convertTemperature(
                            $result[2] / 10,
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        unset($metarCode['6hmaxtemp']);
                        break;
                    case '6hmintemp':
                        // Min temperature in the last 6 hours in C
                        if ($result[1] == '1') {
                            $result[2] *= -1;
                        }
                        $weatherData['remark']['6hmintemp'] = Horde_Service_Weather::convertTemperature(
                            $result[2] / 10,
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        unset($metarCode['6hmintemp']);
                        break;
                    case '24htemp':
                        // Max/Min temperatures in the last 24 hours in C
                        if ($result[1] == '1') {
                            $result[2] *= -1;
                        }
                        $weatherData['remark']['24hmaxtemp'] = Horde_Service_Weather::convertTemperature(
                            $result[2] / 10,
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );

                        if ($result[3] == '1') {
                            $result[4] *= -1;
                        }
                        $weatherData['remark']['24hmintemp'] = Horde_Service_Weather::convertTemperature(
                            $result[4] / 10,
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        unset($metarCode['24htemp']);
                        break;
                    case '3hpresstend':
                        // Pressure tendency of the last 3 hours
                        // no special processing, just passing the data
                        $weatherData['remark']['3hpresstend'] = array(
                            'presscode' => $result[1],
                            'presschng' => Horde_Service_Weather::convertPressure($result[2] / 10, 'hpa', $this->_unitMap[self::UNIT_KEY_PRESSURE])
                        );
                        unset($metarCode['3hpresstend']);
                        break;
                    case 'nospeci':
                        // No change during the last hour
                        $weatherData['remark']['nospeci'] = Horde_Service_Weather_Translation::t('No changes in weather conditions');
                        unset($metarCode['nospeci']);
                        break;
                    case 'sensors':
                        // We may have multiple broken sensors, so do not unset
                        if (!isset($weatherData['remark']['sensors'])) {
                            $weatherData['remark']['sensors'] = array();
                        }
                        $weatherData['remark']['sensors'][strtolower($result[0])] = $this->_sensors[strtolower($result[0])];
                        break;
                    case 'maintain':
                        $weatherData['remark']['maintain'] = Horde_Service_Weather_Translation::t('Maintainance needed');
                        unset($metarCode['maintain']);
                        break;
                    default:
                        // Do nothing, just prevent further matching
                        unset($metarCode[$key]);
                        break;
                    }
                }
            }
        }

        return $weatherData;
    }

    /**
     * Parses TAF data.
     *
     * TAF KLGA 271734Z 271818 11007KT P6SM -RA SCT020 BKN200
     *     FM2300 14007KT P6SM SCT030 BKN150
     *     FM0400 VRB03KT P6SM SCT035 OVC080 PROB30 0509 P6SM -RA BKN035
     *     FM0900 VRB03KT 6SM -RA BR SCT015 OVC035
     *         TEMPO 1215 5SM -RA BR SCT009 BKN015
     *         BECMG 1517 16007KT P6SM NSW SCT015 BKN070
     *
     * @param string $data  The TAF encoded weather data string.
     *
     * @return  array
     */
    protected function _parseTafData($data)
    {
        $data = preg_split("/\n|\r\n|\n\r/", $data);

        $tafCode = $this->_getTafCodes();

        // Eliminate trailing information
        for ($i = 0; $i < sizeof($data); $i++) {
            if (strpos($data[$i], '=') !== false) {
                $data[$i] = substr($data[$i], 0, strpos($data[$i], '='));
                $data = array_slice($data, 0, $i + 1);
                break;
            }
        }

        // Ok, we have correct data, start with parsing the first line for the last update
        $forecastData = array();
        $forecastData['station'] = '';
        $forecastData['dataRaw'] = implode(' ', $data);
        $forecastData['update'] = strtotime(trim($data[0]) .' GMT');
        $forecastData['updateRaw'] = trim($data[0]);

        // and prepare the rest for stepping through
        array_shift($data);
        $taf = explode(' ', preg_replace('/\s{2,}/', ' ', implode(' ', $data)));

        // The timeperiod the data gets added to
        $fromTime = '';

        // If we have FMCs (Forecast Meteorological Conditions), we need this
        $fmcCount = 0;

        // Pointer to the array we add the data to
        $pointer = &$forecastData;

        for ($i = 0; $i < sizeof($taf); $i++) {
            $taf[$i] = trim($taf[$i]);
            if (!strlen($taf[$i])) {
                continue;
            }

            // Init
            $result   = array();
            $resultVF = array();
            $lresult  = array();
            $found = false;

            foreach ($tafCode as $key => $regexp) {
                // Check if current code matches current taf snippet
                if (preg_match('/^' . $regexp . '$/i', $taf[$i], $result)) {
                    $insert = array();
                    switch ($key) {
                    case 'station':
                        $pointer['station'] = $result[0];
                        unset($tafCode['station']);
                        break;
                    case 'valid':
                        $pointer['validRaw'] = $result[0];
                        // Generates the timeperiod the report is valid for
                        list($year, $month, $day) = explode('-', gmdate('Y-m-d', $forecastData['update']));
                        // Date is in next month
                        if ($result[1] < $day) {
                            $month++;
                        }
                        $pointer['validFrom'] = gmmktime($result[2], 0, 0, $month, $result[1], $year);
                        $pointer['validTo']   = gmmktime($result[4], 0, 0, $month, $result[3], $year);
                        unset($tafCode['valid']);
                        // Now the groups will start, so initialize the time groups
                        $pointer['time'] = array();
                        $fromTime = $result[2] . ':00';
                        $pointer['time'][$fromTime] = array();
                        // Set pointer to the first timeperiod
                        $pointer = &$pointer['time'][$fromTime];
                        break;
                    case 'wind':
                        if ($result[5] == 'KTS') {
                            $result[5] = 'KT';
                        }
                        $pointer['wind'] = Horde_Service_Weather::convertSpeed(
                            $result[2],
                            $result[5],
                            $this->_unitMap[UNIT_KEY_SPEED]
                        );
                        if ($result[1] == 'VAR' || $result[1] == 'VRB') {
                            $pointer['windDegrees'] = Horde_Service_Weather_Translation::t('Variable');
                            $pointer['windDirection'] = Horde_Service_Weather_Translation::t('Variable');
                        } else {
                            $pointer['windDegrees'] = $result[1];
                            $pointer['windDirection'] = Horde_Service_Weather::degToDirection($result[1]);
                        }
                        if (is_numeric($result[4])) {
                            $pointer['windGust'] = Horde_Service_Weather::convertSpeed(
                                $result[4],
                                $result[5],
                                $this->_unitMap[UNIT_KEY_SPEED]
                            );
                        }
                        if (isset($probability)) {
                            $pointer['windProb'] = $probability;
                            unset($probability);
                        }
                        break;
                    case 'visFrac':
                        // Possible fractional visibility here.
                        // Check if it matches with the next TAF piece for visibility
                        if (!isset($taf[$i + 1]) ||
                            !preg_match('/^' . $tafCode['visibility'] . '$/i', $result[1] . ' ' . $taf[$i + 1], $resultVF)) {
                            // No next TAF piece available or not matching.
                            $found = false;
                            break;
                        } else {
                            // Match. Hand over result and advance TAF
                            $key = 'visibility';
                            $result = $resultVF;
                            $i++;
                        }
                        // Fall through
                    case 'visibility':
                        $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('AT');
                        if (is_numeric($result[1]) && ($result[1] == 9999)) {
                            // Upper limit of visibility range
                            $visibility = Horde_Service_Weather::convertDistance(
                                10,
                                'km',
                                $this->_unitMap[UNIT_KEY_DISTANCE]
                            );
                            $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BEYOND');
                        } elseif (is_numeric($result[1])) {
                            // 4-digit visibility in m
                            $visibility = Horde_Service_Weather::convertDistance(
                                $result[1],
                                'm',
                                $this->_unitMap[UNIT_KEY_DISTANCE]
                            );
                        } elseif (!isset($result[11]) || $result[11] != 'CAVOK') {
                            if ($result[3] == 'M') {
                                $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BELOW');
                            } elseif ($result[3] == 'P') {
                                $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BEYOND');
                            }
                            if (is_numeric($result[5])) {
                                // visibility as one/two-digit number
                                $visibility = Horde_Service_Weather::convertDistance(
                                    $result[5],
                                    $result[10],
                                    $this->_unitMap[UNIT_KEY_DISTANCE]
                                );
                            } else {
                                // the y/z part, add if we had a x part (see visibility1)
                                if (is_numeric($result[7])) {
                                    $visibility = Horde_Service_Weather::convertDistance(
                                        $result[7] + $result[8] / $result[9],
                                        $result[10],
                                        $this->_unitMap[UNIT_KEY_DISTANCE]
                                    );
                                } else {
                                    $visibility = Horde_Service_Weather::convertDistance(
                                        $result[8] / $result[9],
                                        $result[10],
                                        $this->_unitMap[UNIT_KEY_DISTANCE]
                                    );
                                }
                            }
                        } else {
                            $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BEYOND');
                            $visibility = Horde_Service_Weather::convertDistance(
                                10,
                                'km',
                                $this->_unitMap[UNIT_KEY_DISTANCE]
                            );
                            $pointer['clouds'] = array(array(
                                'amount' => Horde_Service_Weather_Translation::t('Clear below'),
                                'height' => 5000)
                            );
                            $pointer['condition'] = Horde_Service_Weather_Translation::t('No significant weather');
                        }
                        if (isset($probability)) {
                            $pointer['visProb'] = $probability;
                            unset($probability);
                        }
                        $pointer['visibility'] = $visibility;
                        break;
                    case 'condition':
                        // First some basic setups
                        if (!isset($pointer['condition'])) {
                            $pointer['condition'] = '';
                        } elseif (strlen($pointer['condition']) > 0) {
                            $pointer['condition'] .= ',';
                        }

                        if (in_array(strtolower($result[0]), $this->_conditions)) {
                            // First try matching the complete string
                            $pointer['condition'] .= ' ' . $this->_conditions[strtolower($result[0])];
                        } else {
                            // No luck, match part by part
                            array_shift($result);
                            $result = array_unique($result);
                            foreach ($result as $condition) {
                                if (strlen($condition) > 0) {
                                    $pointer['condition'] .= ' ' . $this->_conditions[strtolower($condition)];
                                }
                            }
                        }
                        $pointer['condition'] = trim($pointer['condition']);
                        if (isset($probability)) {
                            $pointer['condition'] .= ' (' . $probability
                                . '% '
                                . Horde_Service_Weather_Translation::t('probability')
                                . ').';
                            unset($probability);
                        }
                        break;
                    case 'clouds':
                        if (!isset($pointer['clouds'])) {
                            $pointer['clouds'] = array();
                        }

                        if (sizeof($result) == 5) {
                            // Only amount and height
                            $cloud = array('amount' => $this->_clouds[strtolower($result[3])]);
                            if ($result[4] == '///') {
                                $cloud['height'] = Horde_Service_Weather_Translation::t('station level or below');
                            } else {
                                $cloud['height'] = $result[4] * 100;
                            }
                        } elseif (sizeof($result) == 6) {
                            // Amount, height and type
                            $cloud = array(
                                'amount' => $this->_clouds[strtolower($result[3])],
                                'type' => $this->_clouds[strtolower($result[5])]
                            );
                            if ($result[4] == '///') {
                                $cloud['height'] = Horde_Service_Weather_Translation::t('station level or below');
                            } else {
                                $cloud['height'] = $result[4] * 100;
                            }
                        } else {
                            // SKC or CLR or NSC
                            $cloud = array('amount' => $this->_clouds[strtolower($result[0])]);
                        }
                        if (isset($probability)) {
                            $cloud['prob'] = $probability;
                            unset($probability);
                        }
                        $pointer['clouds'][] = $cloud;
                        break;
                    case 'windshear':
                        // Parse windshear, if available
                        if ($result[4] == 'KTS') {
                            $result[4] = 'KT';
                        }
                        $pointer['windshear'] = Horde_Service_Weather::convertSpeed(
                            $result[3],
                            $result[4],
                            $this->_unitMap[UNIT_KEY_SPEED]
                        );
                        $pointer['windshearHeight'] = $result[1] * 100;
                        $pointer['windshearDegrees'] = $result[2];
                        $pointer['windshearDirection'] = Horde_Service_Weather::degToDirection($result[2]);
                        break;
                    case 'tempmax':
                        $forecastData['temperatureHigh'] = Horde_Service_Weather::convertTemperature(
                            $result[1],
                            'c',
                            $this->_unitMap[UNIT_KEY_TEMP]
                        );
                        break;
                    case 'tempmin':
                        // Parse max/min temperature
                        $forecastData['temperatureLow'] = Horde_Service_Weather::convertTemperature(
                            $result[1],
                            'c',
                            $this->_unitMap[UNIT_KEY_TEMP]
                        );
                        break;
                    case 'tempmaxmin':
                        $forecastData['temperatureHigh'] = Horde_Service_Weather::convertTemperature(
                            $result[1],
                            'c',
                            $this->_unitMap[UNIT_KEY_TEMP]
                        );
                        $forecastData['temperatureLow'] = Horde_Service_Weather::convertTemperature(
                            $result[4],
                            'c',
                            $this->_unitMap[UNIT_KEY_TEMP]
                        );
                        break;
                    case 'from':
                        // Next timeperiod is coming up, prepare array and
                        // set pointer accordingly
                        if (sizeof($result) > 2) {
                            // The ICAO way
                            $fromTime = $result[2] . ':' . $result[3];
                        } else {
                            // The Australian way (Hey mates!)
                            $fromTime = $result[1] . ':00';
                        }
                        $forecastData['time'][$fromTime] = array();
                        $fmcCount = 0;
                        $pointer = &$forecastData['time'][$fromTime];
                        break;
                    case 'fmc';
                        // Test, if this is a probability for the next FMC
                        if (isset($result[2]) && preg_match('/^BECMG|TEMPO$/i', $taf[$i + 1], $lresult)) {
                            // Set type to BECMG or TEMPO
                            $type = $lresult[0];
                            // Set probability
                            $probability = $result[2];
                            // Now extract time for this group
                            if (preg_match('/^(\d{2})(\d{2})$/i', $taf[$i + 2], $lresult)) {
                                $from = $lresult[1] . ':00';
                                $to = $lresult[2] . ':00';
                                $to = ($to == '24:00') ? '00:00' : $to;
                                // As we now have type, probability and time for this FMC
                                // from our TAF, increase field-counter
                                $i += 2;
                            } else {
                                // No timegroup present, so just increase field-counter by one
                                $i += 1;
                            }
                        } elseif (preg_match('/^(\d{2})(\d{2})\/(\d{2})(\d{2})$/i', $taf[$i + 1], $lresult)) {
                            // Normal group, set type and use extracted time
                            $type = $result[1];
                            // Check for PROBdd
                            if (isset($result[2])) {
                                $probability = $result[2];
                            }
                            $from = $lresult[2] . ':00';
                            $to = $lresult[4] . ':00';
                            $to = ($to == '24:00') ? '00:00' : $to;
                            // Same as above, we have a time for this FMC from our TAF,
                            // increase field-counter
                            $i += 1;
                        } elseif (isset($result[2])) {
                            // This is either a PROBdd or a malformed TAF with missing timegroup
                            $probability = $result[2];
                        }

                        // Handle the FMC, generate neccessary array if it's the first...
                        if (isset($type)) {
                            if (!isset($forecastData['time'][$fromTime]['fmc'])) {
                                $forecastData['time'][$fromTime]['fmc'] = array();
                            }
                            $forecastData['time'][$fromTime]['fmc'][$fmcCount] = array();
                            // ...and set pointer.
                            $pointer = &$forecastData['time'][$fromTime]['fmc'][$fmcCount];
                            $fmcCount++;
                            // Insert data
                            $pointer['type'] = $type;
                            unset($type);
                            if (isset($from)) {
                                $pointer['from'] = $from;
                                $pointer['to']   = $to;
                                unset($from, $to);
                            }
                            if (isset($probability)) {
                                $pointer['probability'] = $probability;
                                unset($probability);
                            }
                        }
                        break;
                    default:
                        // Do nothing
                        break;
                    }
                }
            }
        }

        return $forecastData;
    }


    /**
     * Return the array of regexps used to parse METAR text. We don't define
     * this in the declaration since we unset the entries as they are parsed.
     *
     * @return array
     */
    protected function _getMetarCodes()
    {
        return array(
            'report'      => 'METAR|SPECI',
            'station'     => '\w{4}',
            'update'      => '(\d{2})?(\d{4})Z',
            'type'        => 'AUTO|COR',
            'wind'        => '(\d{3}|VAR|VRB)(\d{2,3})(G(\d{2,3}))?(FPS|KPH|KT|KTS|MPH|MPS)',
            'windVar'     => '(\d{3})V(\d{3})',
            'visFrac'     => '(\d{1})',
            'visibility'  => '(\d{4})|((M|P)?((\d{1,2}|((\d) )?(\d)\/(\d))(SM|KM)))|(CAVOK)',
            'runway'      => 'R(\d{2})(\w)?\/(P|M)?(\d{4})(FT)?(V(P|M)?(\d{4})(FT)?)?(\w)?',
            'condition'   => '(-|\+|VC|RE|NSW)?(MI|BC|PR|TS|BL|SH|DR|FZ)?((DZ)|(RA)|(SN)|(SG)|(IC)|(PE)|(PL)|(GR)|(GS)|(UP))*(BR|FG|FU|VA|DU|SA|HZ|PY)?(PO|SQ|FC|SS|DS)?',
            'clouds'      => '(SKC|CLR|NSC|((FEW|SCT|BKN|OVC|VV)(\d{3}|\/{3})(TCU|CB)?))',
            'temperature' => '(M)?(\d{2})\/((M)?(\d{2})|XX|\/\/)?',
            'pressure'    => '(A)(\d{4})|(Q)(\d{4})',
            'trend'       => 'NOSIG|TEMPO|BECMG',
            'remark'      => 'RMK'
        );
    }

    /**
     * Return the array of regexps used to parse METAR remarks section.
     *
     * @return array
     */
    protected function _getRemarks()
    {
        return array(
            'nospeci'     => 'NOSPECI',
            'autostation' => 'AO(1|2)',
            'presschg'    => 'PRES(R|F)R',
            'seapressure' => 'SLP(\d{3}|NO)',
            'precip'      => '(P|6|7)(\d{4}|\/{4})',
            'snowdepth'   => '4\/(\d{3})',
            'snowequiv'   => '933(\d{3})',
            'cloudtypes'  => '8\/(\d|\/)(\d|\/)(\d|\/)',
            'sunduration' => '98(\d{3})',
            '1htempdew'   => 'T(0|1)(\d{3})((0|1)(\d{3}))?',
            '6hmaxtemp'   => '1(0|1)(\d{3})',
            '6hmintemp'   => '2(0|1)(\d{3})',
            '24htemp'     => '4(0|1)(\d{3})(0|1)(\d{3})',
            '3hpresstend' => '5([0-8])(\d{3})',
            'sensors'     => 'RVRNO|PWINO|PNO|FZRANO|TSNO|VISNO|CHINO',
            'maintain'    => '[\$]'
        );
    }

    protected function _getTafCodes()
    {
        return array(
            'report'      => 'TAF|AMD',
            'station'     => '\w{4}',
            'update'      => '(\d{2})?(\d{4})Z',
            'valid'       => '(\d{2})(\d{2})\/(\d{2})(\d{2})',
            'wind'        => '(\d{3}|VAR|VRB)(\d{2,3})(G(\d{2,3}))?(FPS|KPH|KT|KTS|MPH|MPS)',
            'visFrac'     => '(\d{1})',
            'visibility'  => '(\d{4})|((M|P)?((\d{1,2}|((\d) )?(\d)\/(\d))(SM|KM)))|(CAVOK)',
            'condition'   => '(-|\+|VC|RE|NSW)?(MI|BC|PR|TS|BL|SH|DR|FZ)?((DZ)|(RA)|(SN)|(SG)|(IC)|(PE)|(PL)|(GR)|(GS)|(UP))*(BR|FG|FU|VA|DU|SA|HZ|PY)?(PO|SQ|FC|SS|DS)?',
            'clouds'      => '(SKC|CLR|NSC|((FEW|SCT|BKN|OVC|VV)(\d{3}|\/{3})(TCU|CB)?))',
            'windshear'   => 'WS(\d{3})\/(\d{3})(\d{2,3})(FPS|KPH|KT|KTS|MPH|MPS)',
            'tempmax'     => 'TX(\d{2})\/(\d{2})(\w)',
            'tempmin'     => 'TN(\d{2})\/(\d{2})(\w)',
            'tempmaxmin'  => 'TX(\d{2})\/(\d{2})(\w)TN(\d{2})\/(\d{2})(\w)',
            'from'        => 'FM(\d{2})(\d{2})(\d{2})?Z?',
            'fmc'         => '(PROB|BECMG|TEMPO)(\d{2})?'
        );
    }

}