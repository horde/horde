<?php
/**
 * General SQL implementation for storing/searching geo location data for
 * events.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Kronolith
 */
class Kronolith_Geo_Sql extends Kronolith_Geo_Base
{
    /**
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Set the location of the specified event _id
     *
     * @see Kronolith_Geo_Base#setLocation()
     * @throws Kronolith_Exception
     */
    public function setLocation($event_id, $point)
    {
        /* First make sure it doesn't already exist */
        $sql = 'SELECT COUNT(*) FROM kronolith_events_geo WHERE event_id = ?';
        try {
            $count = $this->_db->selectValue($sql, array($event_id));
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        /* Do we actually have data? If not, see if we are deleting an
         * existing entry. */
        if ((empty($point['lat']) || empty($point['lon'])) && $count) {
            // Delete the record.
            $this->deleteLocation($event_id);
            return;
        } elseif (empty($point['lat']) || empty($point['lon'])) {
            return;
        }

        if (empty($point['zoom'])) {
            $point['zoom'] = 0;
        }

        /* INSERT or UPDATE */
        $params = array($point['lat'], $point['lon'], $point['zoom'], $event_id);
        if ($count) {
            $sql = 'UPDATE kronolith_events_geo SET event_lat = ?, event_lon = ?, event_zoom = ? WHERE event_id = ?';
        } else {
            $sql = 'INSERT into kronolith_events_geo (event_lat, event_lon, event_zoom, event_id) VALUES(?, ?, ?, ?)';
        }
        try {
            $this->_db->execute($sql, $params);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Get the location of the provided event_id.
     *
     * @see Kronolith_Geo_Base#getLocation()
     * @throws Kronolith_Exception
     */
    public function getLocation($event_id)
    {
        $sql = 'SELECT event_lat as lat, event_lon as lon, event_zoom as zoom FROM kronolith_events_geo WHERE event_id = ?';
        try {
            return $this->_db->selectOne($sql, array($event_id));
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
    }

    /**
     * Deletes an entry from storage
     *
     * @see Kronolith_Geo_Base#removeLocation()
     *
     * @param string $event_id
     *
     * @throws Kronolith_Exception
     */
    public function deleteLocation($event_id)
    {
        $sql = 'DELETE FROM kronolith_events_geo WHERE event_id = ?';
        try {
            $this->_db->delete($sql, array($event_id));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Search for events "close to" a given point.
     *
     * TODO: If all we really use the geodata for is distance, it really doesn't
     *       make sense to use the GIS extensions since the distance calculations
     *       are done with Euclidian geometry ONLY ... and therefore will give
     *       incorrect results when done on a geocentric coordinate system.
     *       They might be useful if we eventually want to do searches on
     *       MBRs
     *
     * @see kronolith/lib/Driver/Kronolith_Driver_Geo#search($criteria)
     * @throws Kronolith_Exception
     */
    public function search($criteria)
    {
        throw new Horde_Exception(_("Searching requires a GIS enabled database."));
    }
}
