<?php
/**
 * Copyright 2005-2007 Alkaloid Networks <http://www.alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Beatnik
 */
class Beatnik_Driver_ldap2dns extends Beatnik_Driver
{
    /**
     * Handle for the current database connection.
     * @var object LDAP $_LDAP
     */
    var $_LDAP;

    /**
     * Boolean indicating whether or not we're connected to the LDAP
     * server.
     * @var boolean $_connected
     */
    var $_connected = false;

    /**
    * Constructs a new Beatnik LDAP driver object.
    *
    * @param array  $params    A hash containing connection parameters.
    */
    function Beatnik_Driver_ldap2dns($params = array())
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
        return array(
            'a+ptr' => 'A + PTR',
        );
    }

    /**
     * Get any fields available specifically in this driver by record type.
     *
     * @param string $type Record type for which fields should be returned
     *
     * @return array Fields specific to this driver
     */
    function getRecDriverFields($type) {
        $recset = array();
        switch($type) {
        case 'a+ptr':
            $recset['hostname'] = array(
                'name' => 'Hostname',
                'description' => 'Hostname',
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 1,
            );
            $recset['cipaddr'] = array(
                'name' => 'IP Address',
                'description' => 'IP Address to be forward and reverse mapped',
                'type' => 'ipaddress',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 2,
            );
            break;
        }

        $recset['timestamp'] = array(
            'name' => 'Timestamp',
            'description' => '"Do Not Issue Before/After" Timestamp',
            'type' => 'int',
            'maxlength' => 0,
            'required' => false,
            'infoset' => 'advanced',
            'index' => 100,
        );
        $recset['location'] = array(
            'name' => 'Location',
            'description' => 'Location Restriction',
            'type' => 'text',
            'maxlength' => 2,
            'required' => false,
            'infoset' => 'advanced',
            'index' => 101,
        );

        return $recset;
    }

    /**
     * Gets all zones for accessible to the user matching the filter
     *
     * @access private
     *
     * @return array Array with zone records numerically indexed
     */
    function _getDomains()
    {
        static $zonedata = array();
        if (count($zonedata) > 0) {
            # If at least one element is in the array then we should have valid
            # cached data.
            return $zonedata;
        }

        // Record format
        // $zonedata =
        //       zone array ( # numerically indexed
        //                   zonename => zone domain name
        //                   serial => zone SOA serial number
        //                   refresh => zone SOA refresh
        //                   retry => zone SOA retry
        //                   expire => zone SOA expiry
        //                   minimum => zone SOA minimum
        //                   admin => zone contact admin
        //                   zonemaster => SOA master NS
        //           )

        $res = ldap_list($this->_LDAP,
            $this->_params['basedn'],
            "(objectClass=dnszone)");

        if ($res === false) {
            throw new Beatnik_Exception("Unable to locate any DNS zones " .
            "underneath ".$this->_params['basedn']);
        }

        $res = ldap_get_entries($this->_LDAP, $res);

        if ($res === false) {
            throw new Beatnik_Exception(sprintf(_("Unable to retrieve data from LDAP results: %s"), @ldap_error($this->_LDAP)));
        }

        $fields = Beatnik::getRecFields('soa');
        $i = 0;
        # FIXME: Add some way to handle missing zone data
        # FIXME: Don't forget to remove error silencers (@whatever)
        while ($i < $res['count']) {
            $tmp = array();

            foreach ($fields as $field => $fieldinfo) {
                $key = strtolower($this->_getAttrByField($field));
                if ($key === null) {
                    // This is not a field we are concerned with.  Skip it
                    continue;
                }
                // Special case for 'dn' as it's not treated as an array
                if ($key == 'dn') {
                    $val = @ldap_explode_dn($res[$i]['dn'], 1);
                    $tmp[$field] = $val[0];
                    continue;
                }
                @$tmp[$field] = $res[$i][$key][0];
            }

            # Push the zone on the stack
            $zonedata[] = $tmp;

            # Next zone, please
            $i++;
        }
        return $zonedata;
    }

    /**
     * Map LDAP Attributes to application record fields
     *
     * @access private
     *
     * @param $field string  LDAP Attribute for which a record field should be
     *                       returned
     *
     * @return string  Application record field name
     */
    function _getAttrByField($field)
    {
        $field = strtolower($field);
        $fields = array(
            'hostname' => 'dnsdomainname',
            'zonename' => 'dnszonename', # FIXME This will go away for ldap2dns 0.4.x
            'serial' => 'dnsserial',
            'refresh' => 'dnsrefresh',
            'retry' => 'dnsretry',
            'expire' => 'dnsexpire',
            'minimum' => 'dnsminimum',
            'zonecontact' => 'dnsadminmailbox',
            'zonens' => 'dnszonemaster',
            'ttl' => 'dnsttl',
            'timestamp' => 'dnstimestamp',
            'location' => 'dnslocation',
            'ipaddr' => 'dnsipaddr',
            'ip6addr' => 'dnsipaddr',
            'cipaddr' => 'dnscipaddr',
            'pointer' => 'dnscname',
            'pref' => 'dnspreference',
            'priority' => 'dnssrvpriority',
            'weight' => 'dnssrvweight',
            'port' => 'dnssrvport',
            'text' => 'dnscname', # FIXME THIS WILL CHANGE IN ldap2dns 0.5.0!!!
            'id' => 'dn',
        );

        if (!isset($fields[$field])) {
            return null;
        }

        return $fields[$field];

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
        $domain = $this->cleanFilterString($domain);
        $dn = $this->_params['dn'].'='.$domain.','.$this->_params['basedn'];
        $res = @ldap_list($this->_LDAP, $dn, '(objectClass=dnsrrset)');

        if ($res === false) {
            throw new Beatnik_Exception("Unable to locate any DNS data for $domain");
        }

        # FIXME Cache these results
        $zonedata = array();
        $res = @ldap_get_entries($this->_LDAP, $res);
        if ($res === false) {
            throw new Beatnik_Exception(sprintf(_("Internal error: %s"), @ldap_error($this->_LDAP)));
        }

        $i = 0;
        while ($i < $res['count']) {
            $rectype = $res[$i]['dnstype'][0];
            // Special case for A+PTR records
            if ($rectype == 'a' && isset($res[$i]['dnscipaddr'])) {
                $rectype = 'a+ptr';
            }
            if (!isset($zonedata[$rectype])) {
                # Initialize this type if it hasn't already been done
                $zonedata[$rectype] = array();
            }
            $tmp = array();
            foreach (Beatnik::getRecFields($rectype) as $field => $fielddata) {
                $key = $this->_getAttrByField($field);
                if ($key === null) {
                    // Not a key we care about
                    continue;
                }
                // Special case for 'dn' as it's not treated as an array
                if ($key == 'dn') {
                    $val = @ldap_explode_dn($res[$i]['dn'], 1);
                    $tmp[$field] = $val[0];
                    continue;
                }

                // Only the first value is used.  All other are ignored.
                $tmp[$field] = @$res[$i][$key][0];
            }
            # Push the record on the stack
            $zonedata[$rectype][] = $tmp;

            # Next entry, please.
            $i++;
        }

        return $zonedata;
    }

    /**
     * Delete record from backend
     *
     * @access private
     *
     * @param array $info  Reference to array of record information for deletion
     *
     * @return boolean true on success
     */
    function _deleteRecord(&$info)
    {
        // Ensure we have a record ID before continuing
        if (!isset($info['id'])) {
            throw new Beatnik_Exception(_("Unable to delete record: No record ID specified."));
        }

        // Attribute used to identify objects
        $dnattr = $this->_params['dn'];

        $suffix = $dnattr . '=' . $_SESSION['beatnik']['curdomain']['zonename'] . ',' . $this->_params['basedn'];
        if ($info['rectype'] == 'soa') {
            // FIXME: Add recursion
            throw new Beatnik_Exception(_("Unsupported recursive delete."));

            $domain = $this->cleanDNString($info['zonename']);
            $dn = $suffix;
        } else {
            $domain = $this->cleanDNString($_SESSION['beatnik']['curdomain']['zonename']);
            // Strip the array fluff and add the attribute
            $dn = $dnattr . '=' . $this->cleanDNString($info['id']) . ',' . $suffix;
        }

        $res = @ldap_delete($this->_LDAP, $dn);
        if ($res === false) {
            throw new Beatnik_Exception(sprintf(_("Unable to delete record.  Reason: %s"), @ldap_error($this->_LDAP)));
        }
        return true;
    }

    /**
     * Saves a new or edited record to the DNS backend
     *
     * @access private
     *
     * @param array $info Array from Horde_Form with record data
     *
     * @return mixed  The new or modified record ID on success;
     */
    function _saveRecord($info)
    {
        // Make sure we have a valid record type
        $rectype = strtolower($info['rectype']);
        $rdata = false;
        foreach (Beatnik::getRecTypes() as $rtype => $rdata) {
            if ($rectype == $rtype) {
                break;
            }
            $rdata = false;
        }

        if (!$rdata) {
            throw new Beatnik_Exception(_("Invalid record type specified."));
        }

        $recfields = Beatnik::getRecFields($rectype);

        $entry = array();

        if ($rectype == 'a+ptr') {
            // Special case for A+PTR Records
            $entry['dnstype'] = 'a';
        } else {
            $entry['dnstype'] = $rectype;
        }

        $id = strtoupper($rectype);

        // Apply each piece of submitted data to the new/updated object
        foreach ($recfields as $field => $fdata) {
            // Translate the key to an LDAP attribute
            $key = $this->_getAttrByField($field);

            if ($key === null || $key == 'dn') {
                // Skip the DN or any other key we don't care about
                continue;
            }

            if (!isset($info[$field]) && isset($fdata['default'])) {
                // No value specified.  Use the default
                $val = $fdata['default'];
            } else {
                // Only populate the field if there is actual data
                if (isset($info[$field]) && strlen($info[$field])) {
                    $entry[$key] = $info[$field];
                } else {
                    // $info[$field] was possibly unset
                    $info[$field] = '';
                    // If the record previously had data, we have to send an
                    // empty array to remove the attribute.  However, always
                    // sending an empty attribute causes PHP to return with
                    // "Protocol Error".  Hence this somewhat expensive check:
                    if (isset($info['id'])) {
                        list($type, $record) = $this->getRecord($info['id']);
                        if ($record && isset($record[$field])) {
                            $entry[$key] = array();
                        }
                    }
                }
            }

            if (!isset($entry[$key]) && $fdata['required']) {
                // No value available but required field
                throw new Beatnik_Exception(sprintf(_("Missing required field %s to save record."), $fdata['name']));
            }

            // Construct an ID for this object as a tuple of its data.
            // This guarantees uniqueness.
            $id .= '-'.$this->cleanDNString($info[$field]);
        }

        // Create and populate the DN
        $key = $this->_params['dn'];
        $dn = '';
        // Special case for SOA records.
        if ($rectype == 'soa') {
            $domain = $this->cleanDNString($info['zonename']);
            $entry[$key] = $domain;
            $id = $domain;
            $dn = $key.'='.$domain;
            $suffix = $this->_params['basedn'];
        } else {
            // Everything else gets full id for DN
            $id = $this->cleanDNString($id);
            $entry[$key] = $id;
            $dn = $key.'='.$id;
            // The domain is held in the session
            $domain = $this->cleanDNString($_SESSION['beatnik']['curdomain']['zonename']);
            // Prepare the DN suffix
            $suffix = $key.'='.$domain.','.$this->_params['basedn'];
        }

        // Check to see if this is a modification
        if (isset($info['id'])) {
            // Get the base name of the old object
            $oldRDN = $key . '=' . $this->cleanDNString($info['id']);
            if ($dn != $oldRDN) {
                // We have an old DN but it doesn't match the new DN.
                // Need to rename the old object
                if ($rectype == 'soa') {
                    throw new Beatnik_Exception(_("Unsupported operation: cannot rename a domain."));
                }
                $res = @ldap_rename($this->_LDAP, $oldRDN . ',' . $suffix,
                    $dn, $suffix, true);
                if ($res === false) {
                    throw new Beatnik_Exception(sprintf(_("Unable to rename old object.  Reason: %s"), @ldap_error($this->_LDAP)));
                }
            }

            // Finish appending the DN suffix information
            $dn .= ',' . $suffix;

            // Modify the existing record
            $res = @ldap_mod_replace($this->_LDAP, $dn, $entry);
            if ($res === false) {
                throw new Beatnik_Exception(sprintf(_("Unable to modify record.  Reason: %s"), @ldap_error($this->_LDAP)));
            }

        } else {
            // Must be a new record
            // Append the suffix to the DN to make it fully qualified
            $dn .= ',' . $suffix;
            // Create the necessary objectClass definitions
            $entry['objectclass'] = array();
            $entry['objectclass'][] = 'top';
            $entry['objectclass'][] = 'dnszone';
            if ($rectype != 'soa') {
                // An objectclass to hold the non-SOA record information
                $entry['objectclass'][] = 'dnsrrset';
            }
            $res = @ldap_add($this->_LDAP, $dn, $entry);
            if ($res === false) {
                throw new Beatnik_Exception(sprintf(_("Unable to add record to LDAP. Reason: %s"), @ldap_error($this->_LDAP)));
            }
        }

        return $id;
    }

    function cleanFilterString($string) {
        return preg_replace(
            array('/\*/',   '/\(/',   '/\)/',   '/\x00/'),
            array('\2a', '\28', '\29', '\00'),
            $string
        );
    }

    function cleanDNString($string) {
        return preg_replace(
            array('/=/', '/,/', '/\+/'),
            array('-', '~', ''),
            $string);
    }

    /**
     * Attempts to open a connection to the LDAP server.
     *
     * @access private
     *
     * @return boolean    True on success.
     * @throws Beatnik_Exception
     *
     * @access private
     */
    function _connect()
    {
        if (!$this->_connected) {
            Horde::assertDriverConfig($this->_params, 'storage',
                array('hostspec', 'basedn', 'binddn', 'password', 'dn'));

            $port = (isset($this->_params['port'])) ?
                $this->_params['port'] : 389;

            $this->_LDAP = ldap_connect($this->_params['hostspec'], $port);
            if (!$this->_LDAP) {
                throw new Beatnik_Exception("Unable to connect to LDAP server $hostname on $port");
            }
            $res = ldap_set_option($this->_LDAP, LDAP_OPT_PROTOCOL_VERSION, $this->_params['version']);
            if ($res === false) {
                throw new Beatnik_Exception("Unable to set LDAP protocol version");
            }
            $res = ldap_bind($this->_LDAP, $this->_params['binddn'], $this->_params['password']);
            if ($res === false) {
                throw new Beatnik_Exception("Unable to bind to the LDAP server. Check authentication credentials.");
            }

            $this->_connected = true;
        }
        return true;
    }
}
