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
    /**
     * Database handle. Expects to have the following table available:
     *
     * @var Horde_Db_Adapter_Base
     */
    protected $_db;

    /**
     * Name of table containing the NOAA METAR database.
     *
     * @var string
     */
    protected $_tableName = 'horde_metar_airports';

    /**
     * Default paths to download weather data.
     *
     * @var string
     */
    protected $_metar_path = 'http://tgftp.nws.noaa.gov/data/observations/metar/stations';
    protected $_taf_path = 'http://tgftp.nws.noaa.gov/data/forecasts/taf/stations';

    /**
     * Local cache of locations.
     *
     * @var array
     */
    protected $_locations;

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
        // Mini-hack to avoid passing a http_client object if
        // we really don't need it.
        if (empty($params['http_client']) &&
            !empty($params['metar_path']) &&
            !empty($params['taf_path'])) {
            $params['http_client'] = true;
        }

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
        if (!empty($params['table_name'])) {
            $this->_tableName = $params['table_name'];
        }
    }

    /**
     * Returns the current observations (METAR).
     *
     * @param string $location  The location string.
     *
     * @return Horde_Service_Weather_Current_Base
     * @throws  Horde_Service_Weather_Exception
     */
    public function getCurrentConditions($location)
    {
        $this->_station = $this->_getStation($location);

        // Sniff out type of request.
        $endpoint = sprintf('%s/%s.TXT', $this->_metar_path, $location);
        $pathinfo = parse_url($endpoint);
        if (empty($pathinfo['scheme']) && file_exists($endpoint)) {
            $pathinfo['scheme'] = 'file';
        } elseif (empty($pathinfo['scheme'])) {
            throw new Horde_Service_Weather_Exception('Invalid path to METAR data.');
        }
        switch ($pathinfo['scheme']) {
        case 'http':
            $data = $this->_makeRequest($endpoint);
            break;
        case 'file':
            $data = file_get_contents(realpath($endpoint));
            break;
        }
        if (empty($data)) {
            throw new Horde_Service_Weather_Exception('METAR file not found.');
        }

        $parser = new Horde_Service_Weather_Parser_Metar(array('units' => $this->units));

        return new Horde_Service_Weather_Current_Metar(
            $parser->parse($data),
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
     * @throws  Horde_Service_Weather_Exception
     */
    public function getForecast(
        $location,
        $length = Horde_Service_Weather::FORECAST_3DAY,
        $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD)
    {
        $this->_station = $this->_getStation($location);

        // Sniff out type of request.
        $endpoint = sprintf('%s/%s.TXT', $this->_taf_path, $location);
        $pathinfo = parse_url($endpoint);
        if (empty($pathinfo['scheme']) && file_exists($endpoint)) {
            $pathinfo['scheme'] = 'file';
        } elseif (empty($pathinfo['scheme'])) {
            throw new Horde_Service_Weather_Exception('Invalid path to TAF data.');
        }
        switch ($pathinfo['scheme']) {
        case 'http':
            $url = sprintf('%s/%s.TXT', $this->_taf_path, $location);
            $data = $this->_makeRequest($url);
            break;
        case 'file':
            $data = file_get_contents(realpath($pathinfo['path']));
            break;
        }
        if (empty($data)) {
            throw new Horde_Service_Weather_Exception('TAF file not found.');
        }
        $parser = new Horde_Service_Weather_Parser_Taf(array('units' => $this->units));
        return new Horde_Service_Weather_Forecast_Taf(
            $parser->parse($data),
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
        return new Horde_Service_Weather_Station(array(
            'code' => $location
        ));
    }

    /**
     * Get array of supported forecast lengths.
     *
     * @return array The array of supported lengths.
     */
    public function getSupportedForecastLengths()
    {
        // There are no "normal" forecast lengths in TAF data.
         return array();
    }

    /**
     * Return an array containing all available METAR locations/airports.
     *
     * @return array
     */
    public function getLocations()
    {
        if (empty($this->_locations)) {
            $this->_locations = $this->_getLocations();
        }

        return $this->_locations;
    }

    /**
     * Searches for locations that begin with the text in $search.
     *
     * @param string $search  The text to search.
     *
     * @return array  An array of stdClass objects with 'name' and 'code'
     *                properties.
     * @throws Horde_Service_Weather_Exception
     */
    public function autocompleteLocation($search)
    {
        if (empty($this->_db)) {
            return array();
        }

        $sql = 'SELECT icao, name FROM ' . $this->_tableName . ' WHERE '
            . 'name LIKE ? OR icao LIKE ?';
        try {
            $rows = $this->_db->select($sql, array($search . '%', $search . '%'));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Service_Weather_Exception($e);
        }

        $results = array();
        foreach ($rows as $row) {
            $obj = new stdClass();
            $obj->name = $row['name'];
            $obj->code = $row['icao'];
            $results[] = $obj;
        }

        return $results;
    }

    /**
     * Perform DB query to obtain list of airport codes.
     *
     * @return array
     */
    protected function _getLocations()
    {
        if (empty($this->_db)) {
            return array();
        }
        $sql = 'SELECT icao, name, country FROM ' . $this->_tableName . ' ORDER BY country';
        try {
            return $this->_db->selectAll($sql);
        } catch (Horde_Exception $e) {
            throw new Horde_Service_Weather_Exception($e);
        }
    }

    protected function _getStation($code)
    {
        if (empty($this->_db)) {
            // @todo when DB handle is available.
            return new Horde_Service_Weather_Station(array(
                'code' => $code,
                'name' => $code
            ));
        }
        $sql = 'SELECT icao, name, country, latitude, longitude from ' . $this->_tableName . ' WHERE icao = ?';
        try {
            $result = $this->_db->selectOne($sql, array($code));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Service_Weather_Exception($e);
        }

        return new Horde_Service_Weather_Station(array(
            'name' => $result['name'],
            'code' => $code,
            'country_name' => $result['country'],
            'lat' => $result['latitude'],
            'lon' => $result['lon']
        ));
    }

}