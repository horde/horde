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
     * Location object
     *
     * @var Horde_Service_Weather_Location_Base
     */
    protected $_location;

    /**
     * Cache object
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Constructor
     *
     * @param Horde_Service_Weather_Location_Base $location  The location object.
     * @param array $params                                  Parameters.
     *<pre>
     * 'cache' optional Horde_Cache object
     *</pre>
     *
     * @return Horde_Service_Weather_Base
     */
    public function __construct(
        Horde_Service_Weather_Location_Base $location,
        array $params = array())
    {
        $this->_location = $location;
        $this->_params = $params;
        if (!empty($params['cache'])) {
            $this->_cache = $params['cache'];
            unset($params['cache']);
        }
    }

    /**
     * Obtain the current observations.
     *
     * @return Horde_Service_Weather_Current_Base
     */
    abstract public function getCurrentConditions();

    /**
     * Obtain the forecast for the current location.
     *
     * @param integer $length  The forecast length.
     * @param integer $type    The type of forecast to return.
     *
     * @return Horde_Service_Weather_Forecast_Base
     */
    abstract public function getForecast(
        $length = Horde_Service_Weather::FORECAST_3DAY,
        $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD);

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
                'wind' => Horde_Service_Weather_Translation::t('mph')
            );
        }

        return array(
            'temp' => Horde_Service_Weather_Translation::t('C'),
            'wind' => Horde_Service_Weather_Translation::t('kts')
        );

    }

 }