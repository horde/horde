<?php
/**
 * This file contains the Horde_Service_Weather_Base class for abstracting
 * access to various weather providers.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Base class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 abstract class Horde_Service_Weather_Base
 {

    /**
     * Parameters
     *
     * @var array
     */
    protected $_params;

    /**
     * Cache object
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Lifetime for cached data.
     *
     * @var integer
     */
    protected $_cache_lifetime = 216000;

    /**
     * Units to display results in.
     *
     * @var integer
     */
    public $units = Horde_Service_Weather::UNITS_STANDARD;

    /**
     * URL to a logo for this provider
     *
     * @var string
     */
    public $logo;

    /**
     * URL to the provider's site
     *
     * @var string
     */
    public $link;

    /**
     * Title for the provider
     *
     * @var string
     */
    public $title;

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
     * Last location requested.
     *
     * @var string
     */
    protected $_lastLocation;


    /**
     * Constructor
     *
     * @param array $params                                  Parameters.
     *<pre>
     * 'cache'          - optional Horde_Cache object
     * 'cache_lifetime' - Lifetime of cached results.
     *</pre>
     *
     * @return Horde_Service_Weather_Base
     */
    public function __construct(array $params = array())
    {
        if (empty($params['http_client'])) {
            throw new InvalidArgumentException('Missing http_client parameter.');
        }
        $this->_http = $params['http_client'];
        if (!empty($params['cache'])) {
            $this->_cache = $params['cache'];
            unset($params['cache']);
            if (!empty($params['cache_lifetime'])) {
                $this->_cache_lifetime = $params['cache_lifetime'];
                unset($params['cache_lifetime']);
            }
        }

        $this->_params = $params;
    }

    /**
     * Obtain the current observations.
     *
     * @param string $location  The location string.
     *
     * @return Horde_Service_Weather_Current_Base
     */
    abstract public function getCurrentConditions($location);

    /**
     * Obtain the forecast for the current location.
     *
     * @param string  $location The location code.
     * @param integer $length  The forecast length.
     * @param integer $type    The type of forecast to return.
     *
     * @return Horde_Service_Weather_Forecast_Base
     */
    abstract public function getForecast(
        $location,
        $length = Horde_Service_Weather::FORECAST_3DAY,
        $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD);

    /**
     * Search locations
     *
     * @param string $location  The location string to search.
     * @param integer $type     The type of search to perform.
     */
    abstract public function searchLocations($location, $type = Horde_Service_Weather::SEARCHTYPE_STANDARD);

    /**
     * Search for locations that begin with the text in $search.
     *
     * @param string $search  The text to search.
     *
     * @return array  An array of stdClass objects with 'name' and 'code'
     *                properties
     */
    public function autocompleteLocation($search)
    {
        throw new Horde_Service_Weather_Exception('Not implemented');
    }

    /**
     * Obtain a mapping of units for each UNIT type.
     *
     */
    public function getUnits($type = Horde_Service_Weather::UNITS_STANDARD)
    {
        if ($type == Horde_Service_Weather::UNITS_STANDARD) {
            return array(
                'temp' => Horde_Service_Weather_Translation::t('F'),
                'wind' => Horde_Service_Weather_Translation::t('mph'),
                'pres' => Horde_Service_Weather_Translation::t('inches'),
                'vis' => Horde_Service_Weather_Translation::t('miles')
            );
        }

        return array(
            'temp' => Horde_Service_Weather_Translation::t('C'),
            'wind' => Horde_Service_Weather_Translation::t('kph'),
            'pres' => Horde_Service_Weather_Translation::t('millibars'),
            'vis' => Horde_Service_Weather_Translation::t('km')
        );

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

 }