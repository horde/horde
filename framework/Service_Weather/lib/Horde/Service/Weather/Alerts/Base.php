<?php
/**
 * This file contains the Horde_Service_Weather_Alerts_Base class for
 * abstracting access to weather alerts.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Alerts_Base implements IteratorAggregate
{
    protected $_properties;
    protected $_parsedAlerts = array();

    protected $_typeMap;
    protected $_significanceMap;

    public function __construct($properties, Horde_Service_Weather_Base $weather)
    {
        $this->_properties = $properties;
        $this->_weather = $weather;
        $this->_typeMap = array(
            'HUR' => Horde_Service_Weather_Translation::t("Hurricane Local Statement"),
            'TOR' => Horde_Service_Weather_Translation::t("Tornado Warning"),
            'TOW' => Horde_Service_Weather_Translation::t("Tornado Watch"),
            'WRN' => Horde_Service_Weather_Translation::t("Severe Thunderstorm Warning"),
            'SEW' => Horde_Service_Weather_Translation::t("Severe Thunderstorm Watch"),
            'WIN' => Horde_Service_Weather_Translation::t("Winter Weather Advisory"),
            'FLO' => Horde_Service_Weather_Translation::t("Flood Warning"),
            'WAT' => Horde_Service_Weather_Translation::t("Flood Watch / Statement"),
            'WND' => Horde_Service_Weather_Translation::t("High Wind Advisory"),
            'SVR' => Horde_Service_Weather_Translation::t("Severe Weather Statement"),
            'HEA' => Horde_Service_Weather_Translation::t("Heat Advisory"),
            'FOG' => Horde_Service_Weather_Translation::t("Dense Fog Advisory"),
            'SPE' => Horde_Service_Weather_Translation::t("Special Weather Statement"),
            'FIR' => Horde_Service_Weather_Translation::t("Fire Weather Advisory"),
            'VOL' => Horde_Service_Weather_Translation::t("Volcanic Activity Statement"),
            'HWW' => Horde_Service_Weather_Translation::t("Hurricane Wind Warning"),
            'REC' => Horde_Service_Weather_Translation::t("Record Set"),
            'REP' => Horde_Service_Weather_Translation::t("Public Reports"),
            'PUB' => Horde_Service_Weather_Translation::t("Public Information Statement"),
        );

        $this->_significanceMap = array(
            'W' => Horde_Service_Weather_Translation::t("Warning"),
            'A' => Horde_Service_Weather_Translation::t("Watch"),
            'Y' => Horde_Service_Weather_Translation::t("Advisory"),
            'S' => Horde_Service_Weather_Translation::t("Statement"),
            'F' => Horde_Service_Weather_Translation::t("Forecast"),
            'O' => Horde_Service_Weather_Translation::t("Outlook"),
            'N' => Horde_Service_Weather_Translation::t("Synopsis")
        );
    }

    public function getAlerts()
    {
        return $this->_parsedAlerts;
    }

    /**
     * Return an ArrayIterator
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_parsedAlerts);
    }

}