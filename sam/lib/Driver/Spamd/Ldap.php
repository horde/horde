<?php
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
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
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
class Sam_Driver_Spamd_Ldap extends Sam_Driver_Spamd_Base
{
    /**
     * Handle for the current LDAP connection.
     *
     * @var resource
     */
    protected $_ldap;

    /**
     * Constructs a new LDAP storage object.
     *
     * @param string $user   The user who owns these SPAM options.
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($user, $params = array())
    {
        $this->_user = $user;
        $this->_params = $params;
    }

    /**
     * Retrieves an option set from the storage backend.
     *
     * @throws Sam_Exception
     */
    public function retrieve()
    {
        /* Set default values. */
        $this->_setDefaults();
        $attrib = Horde_String::lower($this->_params['attribute']);

        try {
            $search = $this->_ldap->search(
                $this->_params['basedn'],
                Horde_Ldap_Filter::create($this->_params['uid'], 'equals', $this->_user),
                array('attributes' => array($attrib)));

            $entry = $search->shiftEntry();
            if (!$entry) {
                throw new Sam_Exception(sprintf('LDAP user "%s" not found.', $this->_user));
            }

            foreach ($entry->getValue($attrib, 'all') as $attribute) {
                list($a, $v) = explode(' ', $attribute);
                $ra = $this->_mapOptionToAttribute($a);
                if (is_numeric($v)) {
                    if (strstr($v, '.')) {
                        $newoptions[$ra][] = (float)$v;
                    } else {
                        $newoptions[$ra][] = (int)$v;
                    }
                } else {
                    $newoptions[$ra][] = $v;
                }
            }
        } catch (Horde_Ldap_Exception $e) {
            throw new Sam_Exception($e);
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

    /**
     * Set default values.
     *
     * @access private
     */
    protected function _setDefaults()
    {
        $this->_options = array_merge($this->_options, $this->_params['defaults']);
    }

    /**
     * Stores an option set in the storage backend.
     *
     * @throws Sam_Exception
     */
    public function store()
    {
        $entry = array();
        foreach ($this->_options as $a => $v) {
            $sa = $this->_mapAttributeToOption($a);
            if (is_array($v)) {
                foreach ($v as $av) {
                    $entry[] = $sa . ' ' . $av;
                }
            } else {
                $entry[] = $sa . ' ' . $v;
            }
        }

        $userdn = sprintf('%s=%s,%s',
                          $this->_params['uid'],
                          $this->_user,
                          $this->_params['basedn']);
        try {
            $this->_ldap->modify(
                $userdn,
                array('replace' => array($this->_params['attribute'] => $entry)));
        } catch (Horde_Ldap_Exception $e) {
            throw new Sam_Exception($e);
        }
    }

    /**
     * Attempts to open a connection to the LDAP server.
     *
     * @access private
     *
     * @return boolean  True on success or false on failure.
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return true;
        }

        $bindpass = $GLOBALS['registry']->getAuthCredential('password');
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
                    $this->_ldap = $lc;
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
