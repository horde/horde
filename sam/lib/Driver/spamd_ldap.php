<?php

require_once dirname(__FILE__) . '/spamd.php';

/**
 * Sam storage implementation for LDAP backend.
 * Requires SpamAssassin patch found at:
 * http://bugzilla.spamassassin.org/show_bug.cgi?id=2205
 *
 * Required parameters:<pre>
 *   'ldapserver'       The hostname of the ldap server.
 *   'basedn'       --  The basedn for user entries.
 *   'attribute'    --  The spamAssassin attribute to use.
 *   'uid'          --  The uid attribute for building userDNs.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chris Bowlby <cbowlby@tenthpowertech.com>
 * @author  Max Kalika <max@horde.org>
 * @author  Neil Sequeira <neil@ncsconsulting.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Sam
 */
class SAM_Driver_spamd_ldap extends SAM_Driver_spamd {

    /**
     * Handle for the current LDAP connection.
     *
     * @var resource
     */
    var $_linkid;

    /**
     * Boolean indicating whether or not we're connected to the LDAP server.
     *
     * @var boolean
     */
    var $_connected = false;

    /**
     * Constructs a new LDAP storage object.
     *
     * @param string $user   The user who owns these SPAM options.
     * @param array $params  A hash containing connection parameters.
     */
    function SAM_Driver_spamd_ldap($user, $params = array())
    {
        $this->_user = $user;
        $this->_params = $params;
    }

    /**
     * Retrieves an option set from the storage backend.
     *
     * @return boolean  True on success or false on failure.
     */
    function retrieve()
    {
        /* Make sure we have a valid LDAP connection. */
        if (!$this->_connect()) { 
            return false; 
        }

        /* Set default values. */
        $this->_setDefaults();
        $eres = false;
        $user = $this->_user;
        $attrib = strtolower($this->_params['attribute']);
        $filter = sprintf('(%s=%s)', $this->_params['uid'], $user);
        $res = @ldap_search($this->_linkid, $this->_params['basedn'],
                            $filter, array($attrib));
        if ($res) {
            $eres = @ldap_get_entries($this->_linkid, $res);
        }
        if (!$res || !$eres) {
            Horde::logMessage('LDAP search failed: ' . ldap_error($this->_linkid), __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }
        if (isset($eres[0][$attrib])) {
            for ($i = 0; $i < $eres[0][$attrib]['count']; $i++) {
                list($a, $v) = explode(' ', $eres[0][$attrib][$i]);
                $ra = $this->_mapOptionToAttribute($a);
                if (is_numeric($v)) {
                    if (strstr($v, '.')) {
                        $newoptions[$ra][] = (float) $v;
                    } else {
                        $newoptions[$ra][] = (int) $v;
                    }
                } else {
                    $newoptions[$ra][] = $v;
                }
            }

            /* Go through new options and pull single values out of their
             * arrays. */
            foreach ($newoptions as $k => $v) {
                if (count($v) > 1) {
                    $this->_options[$k] = $v;
                } else {
                    $this->_options[$k] = $v[0];
                }
            }
        }

        return true;
    }

    /**
     * Set default values.
     *
     * @access private
     */
    function _setDefaults()
    {
        $this->_options = array_merge($this->_options, $this->_params['defaults']);
    }

    /**
     * Stores an option set in the storage backend.
     *
     * @return boolean  True on success or false on failure.
     */
    function store()
    {
        /* Make sure we have a valid LDAP connection. */
        if (!$this->_connect()) {
            return false;
        }
        $user = $this->_user;
        $attrib = $this->_params['attribute'];
        $userdn = sprintf('%s=%s,%s', $this->_params['uid'], $user,$this->_params['basedn']);
        $store = $this->_options;
        foreach ($store as $a => $v) {
            $sa = $this->_mapAttributeToOption($a);
            if (is_array($v)) {
                foreach ($v as $av) {
                    $entry[$attrib][] = $sa . ' ' . $av;
                }
            } else {
                $entry[$attrib][] = $sa . ' ' . $v;
            }
        }

        $result = @ldap_modify($this->_linkid, $userdn, $entry);
        if (!$result) {
            Horde::logMessage('LDAP modify failed: ' . ldap_error($this->_linkid), __FILE__, __LINE__, PEAR_LOG_ERR);
        }

        return $result;
    }

    /**
     * Attempts to open a connection to the LDAP server.
     *
     * @access private
     *
     * @return boolean  True on success or false on failure.
     */
    function _connect()
    {
        if ($this->_connected) {
            return true;
        }

        $bindpass = Horde_Auth::getCredential('password');
        $user = $this->_user;
        $binddn = sprintf('%s=%s,%s', $this->_params['uid'], $user, $this->_params['basedn']);
        Horde::assertDriverConfig($this->_params, 'spamd_ldap',
                                  array('ldapserver', 'basedn', 'attribute', 'uid'),
                                  'SAM backend', 'backends.php', '$backends');

        /* try three times */
        for ($tries = 3; $tries > 0; $tries--) {
            $lc = @ldap_connect($this->_params['ldapserver']);
            if ($lc) {
                $lb = @ldap_bind($lc, $binddn, $bindpass);
                if ($lb) {
                    $this->_linkid = $lc;
                    $this->connected = true;
                    return true;
                } else  {
                    $error = ldap_error($lc);
                    @ldap_unbind($lc);
                }
            } else {
                $error = 'ldap_connect() failed.';
            }
        }

        /* If we reached this point, connection or bind failed. */
        Horde::logMessage('LDAP error: ' . $error, __FILE__, __LINE__, PEAR_LOG_ERR);
        return false;
    }

}
