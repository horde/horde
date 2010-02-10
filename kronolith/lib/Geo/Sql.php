<?php
/**
 * General SQL implementation for storing/searching geo location data for events.
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
class Kronolith_Geo_Sql extends Kronolith_Geo
{
    protected $_write_db;
    protected $_db;

    /**
     * Still needs to return a PEAR_Error since Kronolith_Driver still expects it
     *
     * @return mixed boolean || PEAR_Error
     */
    public function initialize()
    {
        Horde::assertDriverConfig($this->_params, 'calendar', array('phptype'));

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }
        if (!isset($this->_params['table'])) {
            $this->_params['table'] = 'kronolith_events_geo';
        }

        /* Connect to the SQL server using the supplied parameters. */
        $this->_write_db = DB::connect($this->_params,
                                       array('persistent' => !empty($this->_params['persistent']),
                                             'ssl' => !empty($this->_params['ssl'])));
        if (is_a($this->_write_db, 'PEAR_Error')) {
            return $this->_write_db;
        }
        $this->_initConn($this->_write_db);

        /* Check if we need to set up the read DB connection
         * seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = DB::connect($params,
                                     array('persistent' => !empty($params['persistent']),
                                           'ssl' => !empty($params['ssl'])));
            if (is_a($this->_db, 'PEAR_Error')) {
                return $this->_db;
            }
            $this->_initConn($this->_db);
        } else {
            /* Default to the same DB handle for the writer too. */
            $this->_db = $this->_write_db;
        }

        return true;
    }

    protected function _initConn(&$db)
    {
        // Set DB portability options.
        switch ($db->phptype) {
        case 'mssql':
            $db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;
        default:
            $db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Handle any database specific initialization code to run. */
        switch ($db->dbsyntax) {
        case 'oci8':
            $query = "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'";

            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Kronolith_Driver_Sql::_initConn(): user = "%s"; query = "%s"',
                                      Horde_Auth::getAuth(), $query),
                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

            $db->query($query);
            break;

        case 'pgsql':
            $query = "SET datestyle TO 'iso'";

            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Kronolith_Driver_Sql::_initConn(): user = "%s"; query = "%s"',
                                      Horde_Auth::getAuth(), $query),
                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

            $db->query($query);
            break;
        }
    }

    /**
     * Set the location of the specified event _id
     *
     * @see kronolith/lib/Driver/Kronolith_Driver_Geo#setLocation($event_id, $point)
     */
    public function setLocation($event_id, $point)
    {
        /* First make sure it doesn't already exist */
        $sql = 'SELECT COUNT(*) FORM kronolith_events_geo WHERE event_id = ?';
        $count = $this->_db->getOne($sql, array($event_id));
        if ($count instanceof PEAR_Error) {
            throw new Horde_Exception($count->getMessage());
        }

        /* Do we actually have data? If not, see if we are deleting an
         * existing entry.
         */
        if ((empty($point['lat']) || empty($point['lon'])) && $count) {
            // Delete the record.
            $this->removeLocation($event_id);
            return;
        } elseif (empty($point['lat']) || empty($point['lon'])) {
            return;
        }

        /* INSERT or UPDATE */
        $params = array($point['lat'], $point['lng'], $event_id);
        if ($count) {
            $sql = 'UPDATE kronolith_events_geo SET event_lat = ?, event_lng = ? WHERE event_id = ?';
        } else {
            $sql = 'INSERT into kronolith_events_geo (event_lat, event_lng, event_id) VALUES(?, ?, ?)';
        }
        $result = $this->_write_db->query($sql, $params);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception($result->getMessage());
        }

        return $result;
    }

    /**
     * Get the location of the provided event_id.
     *
     * @see kronolith/lib/Driver/Kronolith_Driver_Geo#getLocation($event_id)
     */
    public function getLocation($event_id)
    {
        $sql = 'SELECT event_lat as lat, event_lng as lng FROM kronolith_events_geo WHERE event_id = ?';
        $result = $this->_db->getRow($sql, array($event_id), DB_FETCHMODE_ASSOC);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception($result->getMessage());
        }

        return $result;
    }

    /**
     * Deletes an entry from storage
     *
     * @see kronolith/lib/Driver/Kronolith_Driver_Geo#removeLocation($event_id)
     *
     * @param string $event_id
     *
     * @throws Kronolith_Exception
     */
    public function deleteLocation($event_id)
    {
        $sql = 'DELETE FROM kronolith_events_geo WHERE event_id = ?';
        $this->_write_db->query($sql, array($event_id));
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
     */
    public function search($criteria)
    {
        throw new Horde_Exception(_("Searching requires a GIS enabled database."));
    }
}