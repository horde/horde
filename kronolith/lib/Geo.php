<?php
/**
 * Storage driver for Kronolith's Geo location data.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Kronolith
 */
abstract class Kronolith_Geo
{
    protected $_params;

    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Obtain a Kronolith_Geo object. Currently all drivers are SQL based,
     * so use the sql config by default.
     *
     * @param string $driver  The type of object to return
     * @param unknown_type $params  Any driver specific parameters
     *
     * @return Kronolith_Geo
     * @throws Kronolith_Exception
     */
    static public function factory($driver = null, $params = array())
    {
        $driver = basename($driver);
        $class = 'Kronolith_Geo_' . $driver;
        $driver = new $class(Horde::getDriverConfig('calendar', 'sql'));
        $driver->initialize();
        return $driver;
    }

    abstract public function setLocation($event_id, $point);
    abstract public function getLocation($event_id);
    abstract public function deleteLocation($event_id);
    abstract public function search($criteria);
}