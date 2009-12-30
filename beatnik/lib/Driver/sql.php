<?php
/**
 * Copyright 2006-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Beatnik
 */

class Beatnik_Driver_sql extends Beatnik_Driver
{
    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    var $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    var $_write_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     *
     * @var boolean
     */
    var $_connected = false;

    /**
    * Constructs a new Beatnik DB driver object.
    *
    * @param array  $params    A hash containing connection parameters.
    */
    function Beatnik_Driver_sql($params = array())
    {
        parent::Beatnik_Driver($params);
        $this->_connect();
    }

    /**
     * Get any record types  available specifically in this driver.
     *
     * @return array Records available only to this driver
     */
    function getRecDriverTypes()
    {
        return array();
    }


    /**
     * Get any fields available specifically in this driver by record type.
     *
     * @param string $type Record type for which fields should be returned
     *
     * @return array Fields specific to this driver
     */
    function getRecDriverFields($type) {

        return array();
    }

    /**
     * Gets all zones
     *
     * @access private
     *
     * @return array Array with zone records numerically indexed
     */
    function _getDomains()
    {
        $query = 'SELECT * FROM beatnik_soa ORDER BY zonename';
        return $this->_db->getAll($query, null, DB_FETCHMODE_ASSOC);
    }

    /**
     * Return SOA for a single domain
     *
     * @param string $domain   Domain for which to return SOA information
     *
     * @return array           Domain SOA
     */
    function getDomain($domainname)
    {
        $query = 'SELECT * FROM beatnik_soa WHERE zonename = ? ORDER BY zonename';
        return $this->_db->getRow($query, array($domainname), DB_FETCHMODE_ASSOC);
    }

    /**
     * Gets all records associated with the given zone
     *
     * @param string $domain Retrieve records for this domain
     *
     * @return array Array with zone records
     */
    function getRecords($domain)
    {
        $zonedata = array();
        $params = array($domain);

        foreach (array_keys(Beatnik::getRecTypes()) as $type) {
            if ($type == 'soa') {
                continue;
            }
            if ($type == 'mx') {
                $order = 'pointer';
            } else {
                $order = 'hostname';
            }

            $query = 'SELECT * FROM beatnik_' . $type . ' WHERE zonename = ? ORDER BY ' .  $order . ' ASC';
            $result = $this->_db->getAll($query, $params, DB_FETCHMODE_ASSOC);
            if (is_a($result, 'PEAR_Error') || empty($result)) {
                continue;
            }

            $zonedata[$type] = $result;
        }

        return $zonedata;
    }

    /**
     * Saves a new or edited record to the DNS backend
     *
     * @access private
     *
     * @param array $info Array of record data
     *
     * @return boolean true on success
     */
    function _saveRecord($info)
    {
        $fields = array_keys(Beatnik::getRecFields($info['rectype']));
        $params = array();
        foreach ($fields as $i => $key) {
            if (!isset($info[$key])) {
                unset($fields[$i]);
                continue;
            }
            $params[$key] = $info[$key];
        }

        if (isset($params['id']) && $params['id']) {
            unset($params['id'], $fields[0]);
            $query = 'UPDATE beatnik_' . $info['rectype'] . ' SET ';
            foreach ($fields as $key) {
                $query .= $key . ' = ?, ';
                $params[$key] = $info[$key];
            }
            $query = substr($query, 0, -2) . ' WHERE id = ?';
            $params['id'] = $info['id'];
        } else {
            unset($params['id'], $fields[0]);
            if ($info['rectype'] != 'soa') {
                $fields[] = 'zonename';
                $params['zonename'] =  $_SESSION['beatnik']['curdomain']['zonename'];
            }
            $query = 'INSERT INTO beatnik_' . $info['rectype'] . ' (' . implode(', ', $fields) . ') ' . 
                     ' VALUES (' . substr(str_repeat('?, ', sizeof($params)), 0, -2) . ')';
        }

        return $this->_write_db->query($query, $params);
    }

    /**
     * Delete record from backend
     *
     * @access private
     *
     * @param array $data  Reference to array of record data to be deleted
     *
     * @return boolean true on success, PEAR::Error on error
     */
    function _deleteRecord($data)
    {
        // delete just one record
        if ($data['rectype'] != 'soa') {
            return $this->_write_db->query('DELETE FROM beatnik_' . $data['rectype'] . ' WHERE id = ?', array($data['id']));
        }

        // delete all subrecords
        $params = array($data['curdomain']);
        foreach (array_keys(Beatnik::getRecTypes()) as $type) {
            if ($type == 'soa') {
                continue;
            }
            $result = $this->_write_db->query('DELETE FROM beatnik_' . $type . ' WHERE zonename = ?', $params);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
        }

        // we are cuccesfull so, delete even soa
        return $this->_write_db->query('DELETE FROM beatnik_soa WHERE zonename = ?', $params);
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @access private
     *
     * @return boolean  True on success; exits (Horde::fatal()) on error.
     */
    function _connect()
    {
        if ($this->_connected) {
            return true;
        }

        Horde::assertDriverConfig($this->_params, 'storage',
                                  array('phptype', 'charset'));

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }

        /* Connect to the SQL server using the supplied parameters. */
        require_once 'DB.php';
        $this->_write_db = &DB::connect($this->_params,
                                        array('persistent' => !empty($this->_params['persistent'])));
        if (is_a($this->_write_db, 'PEAR_Error')) {
            Horde::fatal($this->_write_db, __FILE__, __LINE__);
        }

        // Set DB portability options.
        switch ($this->_write_db->phptype) {
        case 'mssql':
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;
        default:
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Check if we need to set up the read DB connection seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = &DB::connect($params,
                                      array('persistent' => !empty($params['persistent'])));
            if (is_a($this->_db, 'PEAR_Error')) {
                Horde::fatal($this->_db, __FILE__, __LINE__);
            }

            // Set DB portability options.
            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;
            default:
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }

        } else {
            /* Default to the same DB handle for the writer too. */
            $this->_db =& $this->_write_db;
        }

        $this->_connected = true;

        return true;
    }
}
