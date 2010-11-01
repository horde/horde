<?php
/**
 * The Beatnik_Driver_sql class implements a SQL driver for managing DNS records
 * in the PowerDNS generic SQL driver.  The PowerDNS generic SQL driver aims to
 * support MySQL, PostgreSQL, SQLite and Oracle.  This driver attempts to do the
 * same as long as the default queries are used.
 *
 * Copyright 2008 The Horde Project <http://www.horde.org>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Beatnik
 */

class Beatnik_Driver_pdnsgsql extends Beatnik_Driver
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
    function __construct($params = array())
    {
        $params = array_merge(array(
            'domains_table' => 'domains',
            'records_table' => 'records'
        ), $params);

        parent::__construct($params);
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
        $this->_connect();

        $query = 'SELECT d.id, d.name AS name, r.content AS content, ' .
                 'r.ttl AS ttl FROM ' . $this->_params['domains_table'] .
                 ' AS d JOIN ' . $this->_params['records_table'] . ' AS r ON ' .
                 'r.domain_id = d.id WHERE r.type = \'SOA\'';
        Horde::logMessage('SQL Query by Beatnik_Driver_pdnsgsql::_getDomains(): ' .  $query, 'DEBUG');

        $domainlist =  $this->_db->getAll($query, null, DB_FETCHMODE_ASSOC);
        if (is_a($domainlist, 'PEAR_Error')) {
            Horde::logMessage($domainlist, 'ERR');
            throw new Beatnik_Exception(_("Error getting domain list.  Details have been logged for the administrator."));
        }

        $results = array();
        foreach ($domainlist as $info) {
            $soa = explode(' ', $info['content']);
            if (count($soa) != 7) {
                Horde::logMessage(sprintf('Invalid SOA found for %s, skipping.', $info['name']), 'WARN');
                continue;
            }

            $d = array();
            $d['id'] = $info['id'];
            $d['zonename'] = $info['name'];
            $d['zonemaster'] = $d['zonens'] = $soa[0];
            $d['admin'] = $d['zonecontact'] = $soa[1];
            $d['serial'] = $soa[2];
            $d['refresh'] = $soa[3];
            $d['retry'] = $soa[4];
            $d['expire'] = $soa[5];
            $d['minimum'] = $soa[6];
            $results[] = $d;
        }

        return $results;
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
        $this->_connect();

        $query = 'SELECT d.id AS id, d.name AS name, r.content AS content, ' .
                 'r.ttl AS ttl FROM ' . $this->_params['domains_table'] .
                 ' AS d JOIN ' . $this->_params['records_table'] . ' AS r ON ' .
                 'r.domain_id = d.id WHERE r.type = \'SOA\' AND d.name = ?';
        $values = array($domainname);
        Horde::logMessage('SQL Query by Beatnik_Driver_pdnsgsql::getDomain(): ' .  $query, 'DEBUG');

        $result =  $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            throw new Beatnik_Exception(_("An error occurred while searching the database.  Details have been logged for the administrator."), __FILE__, __LINE__, PEAR_LOG_ERR);
        }

        if (count($result) != 1) {
            throw new Beatnik_Exception(_("Too many domains matched that name.  Contact your administrator."));
        }

        $info = $result[0];

        $soa = explode(' ', $info['content']);
        if (count($soa) != 7) {
            Horde::logMessage(sprintf('Invalid SOA found for %s, skipping.', $info['name']), 'WARN');
            throw new Beatnik_Exception(_("Corrupt SOA found for zone.  Contact your administrator."), __FILE__, __LINE__, PEAR_LOG_ERR);
        }

        $ret = array();
        $ret['id'] = $info['id'];
        $ret['zonename'] = $info['name'];
        $ret['zonemaster'] = $soa[0];
        $ret['admin'] = $soa[1];
        $ret['serial'] = $soa[2];
        $ret['refresh'] = $soa[3];
        $ret['retry'] = $soa[4];
        $ret['expire'] = $soa[5];
        $ret['minimum'] = $soa[6];

        return $ret;
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
        $this->_connect();

        $zonedata = array();

        $query = 'SELECT d.id AS domain_id, r.id AS id, d.name AS domain, ' .
                 'r.name AS name, r.type AS type, r.content AS content, ' .
                 'r.ttl AS ttl, r.prio AS prio FROM ' .
                  $this->_params['domains_table'] . ' AS d JOIN ' .
                  $this->_params['records_table'] . ' AS r ON ' .
                  'd.id = r.domain_id AND d.name = ?';
        $values = array($domain);

        Horde::logMessage('SQL Query by Beatnik_Driver_pdnsgsql::getRecords(): ' . $query, 'DEBUG');
        $result = $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            throw new Beatnik_Exception(_("An error occurred while searching the database.  Details have been logged for the administrator."), __FILE__, __LINE__, PEAR_LOG_ERR);
        }

        foreach ($result as $rec) {
            $type = strtolower($rec['type']);
            if (!isset($zonedata[$type])) {
                $zonedata[$type] = array();
            }

            $tmp = array();
            $tmp['id'] = $rec['id'];
            $tmp['ttl'] = $rec['ttl'];
            switch($type) {
            case 'soa':
                $soa = explode(' ', $rec['content']);
                if (count($soa) != 7) {
                    Horde::logMessage(sprintf('Invalid SOA found for %s, skipping.', $info['name']), 'WARN');
                }

                $tmp['zonename'] = $rec['name'];
                $tmp['zonens'] = $soa[0];
                $tmp['zonecontact'] = $soa[1];
                $tmp['serial'] = $soa[2];
                $tmp['refresh'] = $soa[3];
                $tmp['retry'] = $soa[4];
                $tmp['expire'] = $soa[5];
                $tmp['minimum'] = $soa[6];
                break;

            case 'a':
                $tmp['hostname'] = $rec['name'];
                $tmp['ipaddr'] = $rec['content'];
                break;

            case 'ptr':
                $tmp['hostname'] = $rec['name'];
                $tmp['pointer'] = $rec['content'];
                break;

            case 'mx':
                $tmp['pointer'] = $rec['content'];
                $tmp['pref'] = $rec['prio'];
                break;

            case 'cname':
                $tmp['hostname'] = $rec['name'];
                $tmp['pointer'] = $rec['content'];
                break;

            case 'ns':
                $tmp['hostname'] = $rec['name'];
                $tmp['pointer'] = $rec['content'];
                break;

            case 'srv':
                $srv = preg_split('/\s+/', trim($rec['content']));
                if (count($srv) != 3) {
                    Horde::logMessage(sprintf('Invalid SRV data found for %s, skipping.', $rec['name']), 'WARN');
                    continue;
                }
                $tmp['hostname'] = $rec['name'];
                $tmp['weight'] = $srv[0];
                $tmp['port'] = $srv[1];
                $tmp['pointer'] = $srv[2];
                $tmp['priority'] = $rec['prio'];
                break;

            case 'txt':
                $tmp['hostname'] = $rec['name'];
                $tmp['text'] = $rec['content'];
                break;
            }

            $zonedata[$type][] = $tmp;
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
        $this->_connect();

        $change_date = time();
        $domain_id = $_SESSION['beatnik']['curdomain']['id'];

        switch($info['rectype']) {
        case 'soa':
            if (empty($info['refresh'])) {
                // 24 hours
                $info['refresh'] = 86400;
            }
            if (empty($info['retry'])) {
               // 2 hours
               $info['retry'] = 7200;
            }
            if (empty($info['expire'])) {
               // 1000 hours
               $info['expire'] = 3600000;
            }
            if (empty($info['minimum'])) {
               // 2 days
               $info['miniumum'] = 172800;
            }

            $name = $info['zonename'];
            $type = 'SOA';
            $content = $info['zonens'] . ' ' . $info['zonecontact'] . ' ' .
                       $info['serial'] . ' ' . $info['refresh'] . ' ' .
                       $info['retry'] . ' ' . $info['expire'] . ' ' .
                       $info['minimum'];
            $ttl = $info['ttl'];
            $prio = null;
            break;

        case 'a':
            $name = $info['hostname'];
            $type = 'A';
            $content = $info['ipaddr'];
            $ttl = $info['ttl'];
            $prio = null;
            break;

        case 'ptr':
            $name = $info['hostname'];
            $type = 'PTR';
            $content = $info['pointer'];
            $ttl = $info['ttl'];
            $prio = null;
            break;

        case 'mx':
            $name = $_SESSION['beatnik']['curdomain']['zonename'];
            $type = 'MX';
            $content = $info['pointer'];
            $ttl = $info['ttl'];
            $prio = $info['pref'];
            break;

        case 'cname':
            $name = $info['hostname'];
            $type = 'CNAME';
            $content = $info['pointer'];
            $ttl = $info['ttl'];
            $prio = null;
            break;

        case 'ns':
            $name = $info['hostname'];
            $type = 'NS';
            $content = $info['pointer'];
            $ttl = $info['ttl'];
            $prio = null;
            break;

        case 'srv':
            $name = $info['hostname'];
            $type = 'SRV';
            $content = $info['weight'] . ' ' . $info['port'] . ' ' .
                       $info['pointer'];
            $ttl = $info['ttl'];
            $prio = $info['priority'];
            break;

        case 'txt':
            $name = $info['hostname'];
            $type = 'TXT';
            $content = $info['text'];
            $ttl = $info['ttl'];
            $prio = null;
            break;
        }

        if (!empty($info['id'])) {
            $query = 'UPDATE ' . $this->_params['records_table'] . ' SET ' .
                     'name = ?, type = ?, content = ?, ttl = ?, ' .
                     'prio = ' . (empty($prio) ? 'NULL' : $prio) . ', ' .
                     'change_date = ? WHERE id = ?';
            $values = array($name, $type, $content, $ttl);
            if (!empty($prio)) {
                $values[] = $prio;
            }
            $values[] = $change_date;
            $values[] = $info['id'];
        } else {
            $query = 'INSERT INTO ' . $this->_params['records_table'] . ' ' .
                     '(domain_id, name, type, content, ttl, prio, ' .
                     'change_date) VALUES (?, ?, ?, ?, ?, ' .
                     (empty($prio) ? 'NULL' : '?') . ', ?)';
            $values = array($domain_id, $name, $type, $content, $ttl);
            if (!empty($prio)) {
                $values[] = $prio;
            }
            $values[] = $change_date;
        }

        Horde::logMessage('SQL Query by Beatnik_Driver_pdnsgsql::_saveRecord(): ' . $query, 'DEBUG');
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return true;
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
        $this->_connect();

        throw new Beatnik_Exception(_("Not implemented."));
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @access private
     *
     * @return boolean  True on success.
     */
    function _connect()
    {
        if ($this->_connected) {
            return true;
        }

        $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('read', 'beatnik', 'storage');
        $this->_write_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('rw', 'beatnik', 'storage');

        return true;
    }

    /**
     * Disconnects from the SQL server and cleans up the connection.
     *
     * @access private
     *
     * @return boolean  True on success, false on failure.
     */
    function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            $this->_db->disconnect();
            $this->_write_db->disconnect();
        }

        return true;
    }

}
