<?php
/**
 * Storage driver for Kronolith's Geo location data.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Kronolith
 */
abstract class Kronolith_Geo_Base
{
    /**
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     *
     * @param Horde_Db_Adapter $adapter  The Horde_Db adapter
     *
     * @return Kronolith_Geo_Base
     */
    public function __construct(Horde_Db_Adapter $adapter)
    {
        $this->_db = $adapter;
    }

    /**
     * Save location of event to storage
     *
     * @param string $event_id  The event id
     * @param array  $point     Hash containing 'lat' and 'lon' coordinates
     */
    abstract public function setLocation($event_id, $point);

    /**
     * Retrieve the location of the specified event.
     *
     * @param string $event_id  The event id
     *
     * @return array  A hash containing 'lat' and 'lon'
     */
    abstract public function getLocation($event_id);

    /**
     * Removes the event's location from storage.
     *
     * @param string $event_id  The event it.
     */
    abstract public function deleteLocation($event_id);

    /**
     * Search for events close to a given point.
     *
     * @param array $criteria  An array of:
     *<pre>
     * point  - lat/lon hash
     * radius - the radius to search in
     * limit  - limit the number of hits
     * factor - Conversion factor for miles per distance unit [default is 69].
     *</pre>
     *
     * @return array of event ids with locations near the specified criteria.
     */
    abstract public function search($criteria);

}