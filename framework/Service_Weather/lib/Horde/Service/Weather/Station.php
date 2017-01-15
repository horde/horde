<?php
/**
 * This file contains the Horde_Service_Weather_Station class for abstracting
 * access to station descriptors.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Station class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 *
 * @property string name          The station's common name.
 * @property string city          The city.
 * @property string state         The state.
 * @property string country       The country's iso3166 name (if available).
 * @property string country_name  The country's common name.
 * @property mixed  tz            The timezone name, or offset from UTC
 *                                depending on the API. @see self::getOffset()
 * @property string lat           The lattitude (if available).
 * @property string lon           The longitude (if available).
 * @property string zip           The postal code.
 * @property string code          The internal identifier for the API.
 */
class Horde_Service_Weather_Station
{
    /**
     * Local properties array.
     *
     * @var array
     */
    protected $_properties = array();

    /**
     * Const'r
     *
     * @param array $properties  The properties for the station.
     */
    public function __construct($properties = array())
    {
        $this->_properties = $properties;
    }

    /**
     * Accessor
     *
     * @param string $property  The property to return.
     *
     * @return mixed  The value of requested property.
     */
    public function __get($property)
    {
        if (isset($this->_properties[$property])) {
            return $this->_properties[$property];
        }
        return '';
    }

    /**
     * Setter.
     *
     * @param string $property  The property name.
     * @param mixed $value      The value to set $property to.
     */
    public function __set($property, $value)
    {
        $this->_properties[$property] = $value;
    }

    /**
     * Return the CURRENT offset from UTC for this station as provided by the
     * API.
     *
     * @return integer  The current offset from UTC.
     * @since 1.2.0
     */
    public function getOffset()
    {
        if (!empty($this->_properties['tz']) && is_numeric($this->_properties['tz'])) {

            return ($this->tz < 0 ? '-' : '') . gmdate('H:i', floor(abs($this->tz) * 60 * 60));

        } elseif (!empty($this->_properties['tz'])) {
            try {
                $d = new Horde_Date(time(), 'UTC');
                $d->setTimezone($this->tz);
                return $d->tzOffset();
            } catch (Horde_Date_Exception $e) {
            }
        }

        return false;
    }

}