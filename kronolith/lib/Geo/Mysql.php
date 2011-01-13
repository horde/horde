<?php
/**
 * Mysql implementation for storing/searching geo location data for events.
 * Makes use of the GIS extensions available in mySQL 4.1 and later.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Geo_Mysql extends Kronolith_Geo_Sql
{
    /**
     * Conversion factor needed by search functions
     *  Roughly 69 miles per distance unit
     *
     * @var integer
     */
    private $_conversionFactor = 69;

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

        /* Do we actually have data? */
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
        if ($count) {
            $sql = 'UPDATE kronolith_events_geo SET event_coordinates = GeomFromText(\'POINT(%F %F)\'), event_zoom = ? WHERE event_id = ?';
        } else {
            $sql = 'INSERT into kronolith_events_geo (event_coordinates, event_zoom, event_id) VALUES(GeomFromText(\'POINT(%F %F)\'), ?, ?)';
        }
        $sql = sprintf($sql, $point['lat'], $point['lon']);
        $values = array($point['zoom'], $event_id);

        try {
            $this->_db->execute($sql, $values);
        } catch (Horde_Db_Error $e) {
            throw new Kronolith_Exception($e);
        }
    }

    /**
     * Get the location of the provided event_id.
     *
     * @see kronolith/lib/Driver/Kronolith_Driver_Geo#getLocation($event_id)
     * @throws Kronolith_Exception
     */
    public function getLocation($event_id)
    {
        $sql = 'SELECT x(event_coordinates) as lat, y(event_coordinates) as lon, event_zoom as zoom FROM kronolith_events_geo WHERE event_id = ?';
        try {
            return $this->_db->selectOne($sql, array($event_id));
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
    }

    /**
     * Search for events "close to" a given point.
     *
     * TODO: If all we really use the geodata for is distance, it really doesn't
     *       make sense to use the GIS extensions since the distance calculations
     *       are done with Euclidian geometry ONLY ... and therefore will give
     *       incorrect results when done on a geocentric coordinate system...
     *       They might be useful if we eventually want to do searches on
     *       MBRs
     *
     * @see kronolith/lib/Driver/Kronolith_Driver_Geo#search($criteria)
     * @throws Kronolith_Exception
     */
    public function search($criteria)
    {
        $point = $criteria['point'];
        $limit = empty($criteria['limit']) ? 10 : $criteria['limit'];
        $radius = empty($criteria['radius']) ? 10 : $criteria['radius'];

        /* Allow overriding the default conversion factor */
        $factor = empty($criteria['factor']) ? $this->_conversionFactor : $criteria['factor'];

        $params = array($factor, $radius, $limit);
        $sql = "SELECT event_id, "
               . "GLength(LINESTRINGFromWKB(LineString(event_coordinates, GeomFromText('POINT(" . (float)$point['lat'] . " " . (float)$point['lon'] . ")')))) * ? as distance, "
               . "x(event_coordinates) as lat, y(event_coordinates) as lon FROM kronolith_events_geo HAVING distance < ?  ORDER BY distance ASC LIMIT ?";

        try {
            $results = $this->_db->selectAll($sql, $params);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        return $results;
    }

}
