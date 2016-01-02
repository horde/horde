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
            'TOR' => _("Tornado Warning"),
            'TOW' => _("Tornado Watch"),
            'WRN' => _("Severe Thunderstorm Warning"),
            'SEW' => _("Severe Thunderstorm Watch"),
            'WIN' => _("Winter Weather Advisory"),
            'FLO' => _("Flood Warning"),
            'WAT' => _("Flood Watch / Statement"),
            'WND' => _("High Wind Advisory"),
            'SVR' => _("Severe Weather Statement"),
            'HEA' => _("Heat Advisory"),
            'FOG' => _("Dense Fog Advisory"),
            'SPE' => _("Special Weather Statement"),
            'FIR' => _("Fire Weather Advisory"),
            'VOL' => _("Volcanic Activity Statement"),
            'HWW' => _("Hurricane Wind Warning"),
            'REC' => _("Record Set"),
            'REP' => _("Public Reports"),
            'PUB' => _("Public Information Statement"),
        );

        $this->_significanceMap = array(
            'W' => _("Warning"),
            'A' => _("Watch"),
            'Y' => _("Advisory"),
            'S' => _("Statement"),
            'F' => _("Forecast"),
            'O' => _("Outlook"),
            'N' => _("Synopsis")
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