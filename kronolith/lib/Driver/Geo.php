<?php
/**
 * Storage driver for Kronolith's Geo location data.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Kronolith
 */
abstract class Kronolith_Driver_Geo
{
    protected $_params;

    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    abstract public function setLocation($event_id, $point);
    abstract public function getLocation($event_id);
    abstract public function removeLocation($event_id);
    abstract public function search($criteria);
}