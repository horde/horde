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
 *
 * @package Kronolith
 */
class Kronolith_Geo_Mysql extends Kronolith_Geo_Sql
{
    // Rouughly 69 miles per distance unit
    private $_conversionFactor = 69;

    /**
     * Set the location of the specified event _id
     *
     * @see kronolith/lib/Driver/Kronolith_Driver_Geo#setLocation($event_id, $point)
     * @throws Kronolith_Exception
     */
    public function setLocation($event_id, $point)
    {
        /* First make sure it doesn't already exist */
        $sql = 'SELECT COUNT(*) FROM kronolith_events_geo WHERE event_id = ?';
        $count = $this->_db->getOne($sql, array($event_id));
        if ($count instanceof PEAR_Error) {
            Horde::logMessage($count, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception($count);
        }

        /* Do we actually have data? */
        if ((empty($point['lat']) || empty($point['lon'])) && $count) {
            // Delete the record.
            $this->deleteLocation($event_id);
            return;
        } elseif (empty($point['lat']) || empty($point['lon'])) {
            return;
        }

        /* INSERT or UPDATE */
        if ($count) {
            $sql = "UPDATE kronolith_events_geo SET event_coordinates = GeomFromText('POINT(" . $point['lat'] . " " . $point['lon'] . ")') WHERE event_id = ?";
        } else {
            $sql = "INSERT into kronolith_events_geo (event_id, event_coordinates) VALUES(?, GeomFromText('POINT(" . $point['lat'] . " " . $point['lon'] . ")'))";
        }
        $result = $this->_write_db->query($sql, array($event_id));
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception($result);
        }

        return $result;
    }

    /**
     * Get the location of the provided event_id.
     *
     * @see kronolith/lib/Driver/Kronolith_Driver_Geo#getLocation($event_id)
     * @throws Kronolith_Exception
     */
    public function getLocation($event_id)
    {
        $sql = 'SELECT x(event_coordinates) as lat, y(event_coordinates) as lon FROM kronolith_events_geo WHERE event_id = ?';
        $result = $this->_db->getRow($sql, array($event_id), DB_FETCHMODE_ASSOC);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception($result);
        }
        return $result;
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

        $params = array($point['lat'] . ' ' . $point['lon'], $factor, $radius, $limit);
        $sql = "SELECT event_id, "
               . "GLength(LINESTRINGFromWKB(LineString(event_coordinates, GeomFromText('POINT(?)')))) * ? as distance, "
               . "x(event_coordinates) as lat, y(event_coordinates) as lon FROM kronolith_events_geo HAVING distance < ?  ORDER BY distance ASC LIMIT ?";

        $results = $this->_db->getAssoc($sql, false, $params, DB_FETCHMODE_ASSOC);
        if ($results instanceof PEAR_Error) {
            Horde::logMessage($results, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception($results);
        }

        return $results;
    }
}
