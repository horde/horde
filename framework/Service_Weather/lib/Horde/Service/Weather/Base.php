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

    protected $_cache_lifetime = 216000;

    public $units = Horde_Service_Weather::UNITS_STANDARD;

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
     * Obtain a mapping of units for each UNIT type.
     *
     */
    public function getUnits($type = Horde_Service_Weather::UNITS_STANDARD)
    {
        // TODO: Probably don't need these translated, leave for now until we
        // have other translated strings to populate locale with.
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
            'wind' => Horde_Service_Weather_Translation::t('kts'),
            'pres' => Horde_Service_Weather_Translation::t('millibars'),
            'vis' => Horde_Services_Weather_Translation::t('km')
        );

    }

 }