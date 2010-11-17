<?php
/**
 * Beatnik base class
 *
 * Copyright 2005-2007 Alkaloid Networks <http://www.alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Beatnik
 */
class Beatnik {

    /**
     * Build Beatnik's list of menu items.
     */
    function getMenu($returnType = 'object')
    {
        // We are editing rather than adding if an ID was passed
        $editing = Horde_Util::getFormData('id');
        $editing = !empty($editing);

        $menu = new Horde_Menu();

        $menu->add(Horde::url('listzones.php'), _('List Domains'), 'website.png');
        if (!empty($_SESSION['beatnik']['curdomain'])) {
            $menu->add(Horde_Util::addParameter(Horde::url('editrec.php'), 'curdomain', $_SESSION['beatnik']['curdomain']['zonename']), ($editing) ? _("Edit Record") : _("Add Record"), 'edit.png');
        } else {
            $menu->add(Horde::url('editrec.php?rectype=soa'), _("Add Zone"), 'edit.png');
        }

        $url = Horde_Util::addParameter(Horde::selfUrl(true), array('expertmode' => 'toggle'));
        $menu->add($url, _('Expert Mode'), 'hide_panel.png', null, '', null, ($_SESSION['beatnik']['expertmode']) ? 'current' : '');

        if (count(Beatnik::needCommit())) {
            $url = Horde_Util::addParameter(Horde::url('commit.php'), array('domain' => 'all'));
            $menu->add($url, _('Commit All'), 'commit-all.png');
        }

        if ($returnType == 'object') {
           return $menu;
        } else {
            return $menu->render();
        }
    }

    /**
     * Get possible records
     *
     * The keys of this array are the IDs of the record type.  The values
     * are a human friendly description of the record type.
     */
    function getRecTypes()
    {
        $beatnik = $GLOBALS['registry']->getApiInstance('beatnik', 'application');

        $records = array(
            'soa' => _("SOA (Start of Authority)"),
            'ns' => _("NS (Name Server)"),
            'a' => _("A (Address)"),
            'aaaa' => _("AAAA (IPv6 Address)"),
            'ptr' => _("PTR (Reverse DNS)"),
            'cname' => _("CNAME (Alias)"),
            'mx' => _("MX (Mail eXchange)"),
            'srv' => _("SRV (Service Record)"),
            'txt' => _("TXT (Text Record)"),
        );

        return array_merge($records, $beatnik->driver->getRecDriverTypes());
    }

    /**
     */
    function getRecFields($recordtype)
    {
        // Record Format:
        // $recset is an array of fields.  The field IDs are the keys.
        // Each field is an array with the following keys:
        // 'name': The short name of the field.  This key is also used
        //      to reference the help system.
        // 'description': Long description of the field
        // 'type': Field type.  Choose from any available from Horde_Form
        // 'maxlength': Maximum field length.  0 is unlimited
        // 'required': If true, the field will be required by the form
        // 'infoset': one of 'basic' or 'advanced'.  This is used to help keep
        //      the forms simple for non-power-users.  If 'required' is true and
        //      'infoset' is false then 'default' MUST be specified
        // 'default': the default value of the field.
        // 'index': Crude sort ordering.  Lower means show higher in the group

        $beatnik = $GLOBALS['registry']->getApiInstance('beatnik', 'application');

        // Attempt to return cached results.
        static $recset = array();

        if (isset($recset[$recordtype])) {
            return $recset[$recordtype];
        }
        $recset[$recordtype] = array();

        $recset[$recordtype]['id'] = array(
            'name' => _("UID"),
            'description' => _("Unique Identifier (Used as Record ID)"),
            'type' => 'hidden',
            'maxlength' => 0,
            'required' => false, // Empty for "new" entries
            'infoset' => 'basic',
            'index' => 0,
        );

        switch (strtolower($recordtype)) {
        case 'soa':
            $recset[$recordtype]['zonename'] = array(
                'name' => _("Domain Name"),
                'description' => _("Zone Domain Name"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 1,
            );
            $recset[$recordtype]['zonens'] = array(
                'name' => _("Primary Nameserver"),
                'description' => _("Primary nameserver for this zone"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 2,
            );
            $recset[$recordtype]['zonecontact'] = array(
                'name' => _("Zone Contact"),
                'description' => _("Contact e-mail address for this zone"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 2,
            );
            $recset[$recordtype]['serial'] = array(
                'name' => _("Serial"),
                'description' => _("Zone Serial Number"),
                'type' => 'int',
                'default' => date('Ymd'). '00',
                'maxlength' => 0,
                'required' => false,
                'infoset' => 'advanced',
                'index' => 3,
            );
            $recset[$recordtype]['refresh'] = array(
                'name' => 'Refresh',
                'description' => _("Zone Refresh"),
                'type' => 'int',
                'maxlength' => 0,
                'required' => false,
                'infoset' => 'advanced',
                'index' => 4,
            );
            $recset[$recordtype]['retry'] = array(
                'name' => _("Retry"),
                'description' => _("Zone Retry"),
                'type' => 'int',
                'maxlength' => 0,
                'required' => false,
                'infoset' => 'advanced',
                'index' => 5,
            );
            $recset[$recordtype]['expire'] = array(
                'name' => _("Expiration"),
                'description' => _("Zone Expiry"),
                'type' => 'int',
                'maxlength' => 0,
                'required' => false,
                'infoset' => 'advanced',
                'index' => 6,
            );
            $recset[$recordtype]['minimum'] = array(
                'name' => _("Minimum"),
                'description' => _("Zone Minimum"),
                'type' => 'int',
                'maxlength' => 0,
                'required' => false,
                'infoset' => 'advanced',
                'index' => 7,
            );
            break;

        case 'a':
            $recset[$recordtype]['hostname'] = array(
                'name' => _("Hostname"),
                'description' => _("Short hostname for this record"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 1,
            );
            $recset[$recordtype]['ipaddr'] = array(
                'name' => _("IP Address"),
                'description' => _("IPv4 Network Address"),
                'type' => 'ipaddress',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 2,
            );
            break;

        case 'aaaa':
            $recset[$recordtype]['hostname'] = array(
                'name' => _("Hostname"),
                'description' => _("Short hostname for this record"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 1,
            );
            $recset[$recordtype]['ip6addr'] = array(
                'name' => _("IPv6 Address"),
                'description' => _("IPv6 Network Address"),
                'type' => 'ip6address',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 2,
            );
            break;

        case 'ptr':
            $recset[$recordtype]['hostname'] = array(
                'name' => _("Hostname"),
                'description' => _("IP in Reverse notation (.in-addr.arpa)"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 1,
            );
            $recset[$recordtype]['pointer'] = array(
                'name' => _("Hostname Target"),
                'description' => _("Hostname for Reverse DNS"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 2,
            );
            break;

        case 'mx':
            $recset[$recordtype]['pointer'] = array(
                'name' => _("Hostname Target"),
                'description' => _("Hostname of Mail eXchanger"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 1,
            );
            $recset[$recordtype]['pref'] = array(
                'name' => _("Preference"),
                'description' => _("MX Preference (lower is more preferred)"),
                'type' => 'int',
                'default' => 0,
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 2,
            );
            break;

        case 'cname':
            $recset[$recordtype]['hostname'] = array(
                'name' => _("Hostname"),
                'description' => _("Short hostname for this record"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 1,
            );
            $recset[$recordtype]['pointer'] = array(
                'name' => _("Hostname Target"),
                'description' => _("Hostname for CNAME alias"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 2,
            );
            break;

        case 'ns':
            $recset[$recordtype]['hostname'] = array(
                'name' => _("Domain Name"),
                'description' => _("Short sub-domain for NS record (leave blank unless creating a subdomain)"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => false,
                // If we have a current domain name, use it for the default val
                'default' => @$GLOBALS['curdomain']['zonename'],
                'infoset' => 'basic',
                'index' => 1,
            );
            $recset[$recordtype]['pointer'] = array(
                'name' => _("Hostname Target"),
                'description' => _("Hostname of Authoritative Name Server"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 2,
            );
            break;

        case 'srv':
            $recset[$recordtype]['hostname'] = array(
                'name' => _("Hostname"),
                'description' => _("Short hostname for this record"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 1,
            );
            $recset[$recordtype]['pointer'] = array(
                'name' => _("Hostname Target"),
                'description' => _("Hostname for DNS Service Record"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 2,
            );
            $recset[$recordtype]['priority'] = array(
                'name' => _("SRV Priority"),
                'description' => _("DNS Service Record Priority"),
                'type' => 'int',
                'default' => 0,
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 3,
            );
            $recset[$recordtype]['weight'] = array(
                'name' => _("SRV Weight"),
                'description' => _("DNS Service Record Weight"),
                'type' => 'int',
                'default' => 0,
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 4,
            );
            $recset[$recordtype]['port'] = array(
                'name' => _("SRV Port"),
                'description' => _("DNS Service Record Port Number"),
                'type' => 'int',
                'default' => 0,
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 5,
            );
            break;

        case 'txt':
            $recset[$recordtype]['hostname'] = array(
                'name' => _("Hostname"),
                'description' => _("Short hostname for this record"),
                'type' => 'text',
                'maxlength' => 0,
                'required' => true,
                'infoset' => 'basic',
                'index' => 1,
            );
            $recset[$recordtype]['text'] = array(
                'name' => 'Text',
                'description' => _("String payload for DNS TXT"),
                'type' => 'text',
                'maxlength' => 256,
                'required' => true,
                'infoset' => 'basic',
                'index' => 2,
            );
            break;
        }

        $recset[$recordtype]['ttl'] = array(
            'name' => _("TTL"),
            'description' => _("Record Time-To-Live (seconds)"),
            'type' => 'int',
            'maxlength' => 0,
            'required' => false,
            'infoset' => 'advanced',
            'index' => 100,
            'default' => $GLOBALS['prefs']->getValue('default_ttl')
        );

        //$recset[$recordtype] = array_merge($recset[$recordtype], $beatnik->driver->getRecDriverFields($recordtype));
        uasort($recset[$recordtype], array('Beatnik', 'fieldSort'));

        return $recset[$recordtype];
    }

    /**
     * Check or set a flag to show that a domain has outstanding changes that
     * need to be committed.
     *
     * @param optional string  $domain      Domain to check whether a commit is
     *                                      necessary
     * @param optional boolean $needcommit  true adds the domain to the list
     *                                      that needs committing; false removes
     *                                      the domain from the list
     *
     * @return mixed  Array of domains needing committing if no arguments are
     *                passed.
     *                Boolean if only a $domain is passed: True if $domain has
     *                outstanding changes, false if not.
     *                Mixed if both $domain and $needcommit are passed.  True
     *                on success, PEAR::Error on error.
     */
    function needCommit($domain = null, $needcommit = null)
    {
        // Make sure we have a valid array with which to work
        if (!isset($_SESSION['beatnik']['needcommit'])) {
            $_SESSION['beatnik']['needcommit'] = array();
        }

        if ($domain === null && $needcommit === null) {
            // Return the stored list of domains needing changes committed.
            return array_keys($_SESSION['beatnik']['needcommit']);
        } elseif ($domain !== null && $needcommit === null) {
            // Check if domain need committing
            return isset($_SESSION['beatnik']['needcommit'][$domain]);
        } elseif ($domain !== null && is_bool($needcommit)) {
            // Flag domain for committing
            if ($needcommit) {
                if(!isset($_SESSION['beatnik']['needcommit'][$domain])) {
                    $_SESSION['beatnik']['needcommit'][$domain] = true;
                }
            } else {
                if (isset($_SESSION['beatnik']['needcommit'][$domain])) {
                    unset($_SESSION['beatnik']['needcommit'][$domain]);
                }
            }
            return true;
        } else {
            // Somebody sent something they should not have...
            throw new Beatnik_Exception(_("Unable to determine if domain needs committing: invalid parameter."));
        }
    }

    /**
     * Checks for the given permissions for the current user on the given
     * permissions node.  Optionally check for the requested permssion for a
     * given number of steps up the tree.
     *
     * @param string $permname  Name of the permission to check
     *
     * @param optional int $permmask  Bitfield of permissions to check for
     *
     * @param options int $numparents  Check for the same permissions this
     *                                 many levels up the tree
     *
     * @return boolean True if the user has permission, False if not
     */
    function hasPermission($permname, $permmask = null, $numparents = 0)
    {
        if ($GLOBALS['registry']->isAdmin()) {
            return true;
        }

        if ($permmask === null) {
            $permmask = Horde_Perms::SHOW | Horde_Perms::READ;
        }

        # Default deny all permissions
        $user = 0;
        $superadmin = 0;

        $superadmin = $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('beatnik:domains', $GLOBALS['registry']->getAuth(), $permmask);

        while ($numparents >= 0) {
            $tmpuser = $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission($permname, $GLOBALS['registry']->getAuth(), $permmask);

            $user = $user | $tmpuser;
            if ($numparents > 0) {
                $pos = strrpos($permname, ':');
                if ($pos) {
                    $permname = substr($permname, 0, $pos);
                }
            }
            $numparents--;
        }
        return (($superadmin | $user) & $permmask);
    }

    /**
     * Autogenerate a set of records from a template defined in
     * config/autogenerate.php
     *
     * @param object $vars  Horde_Variables object from Autogenerate form
     *
     * @return mixed  true on success, PEAR::Error on failure
     */
    function autogenerate(&$vars)
    {
        $beatnik = $GLOBALS['registry']->getApiInstance('beatnik', 'application');

        require BEATNIK_BASE . '/config/autogenerate.php';
        $template = $templates[$vars->get('template')];
        try {
            $zonedata = $beatnik->driver->getRecords($_SESSION['beatnik']['curdomain']['zonename']);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e);
        }

        foreach ($template['types'] as $rectype => $definitions) {
            // Only attempt to delete records if the type is already defined
            if (isset($zonedata[$rectype])) {
                // Check for collisions and handle as requested
                switch($definitions['replace']) {
                case 'all':
                    foreach ($zonedata[$rectype] as $record) {
                        try {
                            $result = $beatnik->driver->deleteRecord($record);
                        } catch (Exception $e) {
                            $GLOBALS['notification']->push($e);
                        }
                    }
                    break;

                case 'match':
                    foreach ($zonedata[$rectype] as $record) {
                        // Check every record in the template to see if the
                        // hostname matches
                        foreach ($definitions['records'] as $Trecord) {
                            if ($record['hostname'] == $Trecord['hostname']) {
                                try {
                                    $result = $beatnik->driver->deleteRecord($record);
                                } catch (Exception $e) {
                                    $GLOBALS['notification']->push($e);
                                }
                            }
                        }
                    }
                    break;

                #case 'none':
                #default:
                }
            }

            $defaults = array('rectype' => $rectype,
                              'zonename'=> $_SESSION['beatnik']['curdomain']['zonename']);
            foreach ($definitions['records'] as $info) {
                if ($beatnik->driver->recordExists($info, $rectype)) {
                    $GLOBALS['notification']->push(_("Skipping existing identical record"));
                    continue;
                }
                try {
                    $result = $beatnik->driver->saveRecord(array_merge($defaults, $info));
                    $GLOBALS['notification']->push(sprintf(_('Record added: %s/%s'), $rectype, $info['hostname']), 'horde.success');
                } catch (Exception $e) {
                    $GLOBALS['notification']->push($e->getMessage(), 'horde.error');
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Increments a domain serial number.
     *
     * @param int $serial  Serial number to be incremented
     *
     * @return int  Incremented serial number
     */
    function incrementSerial($serial)
    {
        // Create a serial number of the ad-hoc standard YYYYMMDDNN
        // where YYYYMMDD is the year/month/day of the last update to this
        // odmain and NN is an incrementer to handle multiple updates in a
        // given day.
        $newserial = (int) (date('Ymd') . '00');
        if ($serial < $newserial) {
            return $newserial;
        } else {
            return ++$serial;
        }
    }

    /**
     * Callback for usort to make field data print in a friendly order
     *
     * @param mixed $a First sort variable
     * @param mixed $b Second sort variable
     *
     * @return int -1, 0, 1 based on relative sort order
     */
    function fieldSort($a, $b)
    {
        if ($a['index'] < $b['index']) {
            return -1;
        } elseif ($a['index'] > $b['index']) {
            return 1;
        } else {
            return 0;
        }
    }

}
