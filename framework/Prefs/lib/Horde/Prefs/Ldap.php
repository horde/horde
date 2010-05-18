<?php
/**
 * Preferences storage implementation for PHP's LDAP extension.
 *
 * Required parameters:
 * 'basedn' - The base DN for the LDAP server.
 * 'hostspec' - The hostname of the LDAP server.
 * 'uid' - The username search key.
 * 'writedn' - One of "user", "admin", or "searchdn"
 *
 * Optional parameters:
 * 'admindn' - The DN of the administrative account to bind for
 *             write operations.
 * 'adminpw' - 'admindn's password for bind authentication.
 * 'port' - The port of the LDAP server.
 *          DEFAULT: 389
 * 'searchdn' - The DN of a user with search permissions on the directory
 * 'searchpw' - 'searchdn's password for binding
 * 'tls' - Whether to use TLS connections.
 *         DEFAULT: false
 * 'version' - The version of the LDAP protocol to use.
 *             DEFAULT: NONE (system default will be used)
 *
 * If setting up as the Horde preference handler in conf.php, the following
 * is an example configuration.
 * The schemas needed for ldap are in horde/scripts/ldap.
 *
 * <code>
 * $conf['prefs']['driver'] = 'ldap';
 * $conf['prefs']['params']['hostspec'] = 'localhost';
 * $conf['prefs']['params']['port'] = '389';
 * $conf['prefs']['params']['basedn'] = 'dc=example,dc=org';
 * $conf['prefs']['params']['uid'] = 'mail';
 * </code>
 *
 * The following is valid but would only be necessary if users do NOT have
 * permission to modify their own LDAP accounts.
 *
 * <code>
 * $conf['prefs']['params']['admindn'] = 'cn=Manager,dc=example,dc=org';
 * $conf['prefs']['params']['adminpw'] = 'password';
 * </code>
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jon Parise <jon@horde.org>
 * @author   Ben Klang <ben@alkaloid.net>
 * @category Horde
 * @package  Prefs
 */
class Horde_Prefs_Ldap extends Horde_Prefs
{
    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Handle for the current LDAP connection.
     *
     * @var resource
     */
    protected $_connection;

    /**
     * Boolean indicating whether or not we're connected to the LDAP server.
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * String holding the user's DN.
     *
     * @var string
     */
    protected $_dn = '';

    /**
     * String holding the user's password.
     *
     * @var string
     */
    protected $_password = '';

    /**
     * Constructor.
     *
     * @param string $scope     The current application scope.
     * @param string $user      The user who owns these preferences.
     * @param string $password  The password associated with $user.
     * @param array $params     A hash containing connection parameters.
     * @param boolean $caching  Should caching be used?
     */
    public function __construct($scope, $user, $password, $params, $caching)
    {
        /* If a valid server port has not been specified, set the default. */
        if (!isset($params['port']) || !is_int($params['port'])) {
            $params['port'] = 389;
        }

        parent::__construct($scope, $user, $password, $params, $caching);
    }

    /**
     * Opens a connection to the LDAP server.
     *
     * @throws Horde_Exception
     */
    function _connect()
    {
        if ($this->_connected) {
            return;
        }

        if (!Horde_Util::extensionExists('ldap')) {
            throw new Horde_Exception('Required LDAP extension not found.');
        }

        Horde::assertDriverConfig($this->_params, 'prefs',
            array('hostspec', 'basedn', 'uid', 'writedn'),
            'preferences LDAP');

        /* Connect to the LDAP server anonymously. */
        $conn = ldap_connect($this->_params['hostspec'], $this->_params['port']);
        if (!$conn) {
            Horde::logMessage(
                sprintf('Failed to open an LDAP connection to %s.',
                        $this->_params['hostspec']), 'ERR');
            throw new Horde_Exception('Internal LDAP error. Details have been logged for the administrator.');
        }

        /* Set the LDAP protocol version. */
        if (isset($this->_params['version'])) {
            $result = @ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION,
                                       $this->_params['version']);
            if ($result === false) {
                Horde::logMessage(
                    sprintf('Set LDAP protocol version to %d failed: [%d] %s',
                            $this->_params['version'],
                            @ldap_errno($conn),
                            @ldap_error($conn)), 'WARN');
                throw new Horde_Exception('Internal LDAP error. Details have been logged for the administrator.');
            }
        }

        /* Start TLS if we're using it. */
        if (!empty($this->_params['tls'])) {
            if (!@ldap_start_tls($conn)) {
                Horde::logMessage(
                    sprintf('STARTTLS failed: [%d] %s',
                            @ldap_errno($this->_ds),
                            @ldap_error($this->_ds)), 'ERR');
            }
        }

        /* If necessary, bind to the LDAP server as the user with search
         * permissions. */
        if (!empty($this->_params['searchdn'])) {
            $bind = @ldap_bind($conn, $this->_params['searchdn'],
                               $this->_params['searchpw']);
            if ($bind === false) {
                Horde::logMessage(
                    sprintf('Bind to server %s:%d with DN %s failed: [%d] %s',
                            $this->_params['hostspec'],
                            $this->_params['port'],
                            $this->_params['searchdn'],
                            @ldap_errno($conn),
                            @ldap_error($conn)), 'ERR');
                throw new Horde_Exception('Internal LDAP error. Details have been logged for the administrator.', @ldap_errno($conn));
            }
        }

        /* Register our callback function to handle referrals. */
        if (function_exists('ldap_set_rebind_proc')) {
            $result = @ldap_set_rebind_proc($conn, array($this, 'rebindProc'));
            if ($result === false) {
                Horde::logMessage(
                    sprintf('Setting referral callback failed: [%d] %s',
                            @ldap_errno($conn),
                            @ldap_error($conn)), 'WARN');
                throw new Horde_Exception(_("Internal LDAP error.  Details have been logged for the administrator."), @ldap_errno($conn));
            }
        }

        /* Store the connection handle at the instance level. */
        $this->_connection = $conn;

        /* Search for the user's full DN. */
        $search = @ldap_search($this->_connection, $this->_params['basedn'],
                               $this->_params['uid'] . '=' . $this->_user, array('dn'));
        if ($search === false) {
            Horde::logMessage(
                sprintf('Error while searching the directory for the user\'s DN: [%d]: %s',
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
            throw new Horde_Exception('Internal LDAP error. Details have been logged for the administrator.', @ldap_errno($conn));
        }

        $result = @ldap_get_entries($this->_connection, $search);
        if ($result === false) {
            Horde::logMessage(
                sprintf('Error while retrieving LDAP search results for the user\'s DN: [%d]: %s',
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
            throw new Horde_Exception('Internal LDAP error. Details have been logged for the administrator.', @ldap_errno($this->_connection));
        }

        if ($result['count'] != 1) {
            Horde::logMessage(
                'Zero or more than one DN returned from search; unable to determine user\'s correct DN.', 'ERR');
            throw new Horde_Exception('Internal LDAP error. Details have been logged for the administrator.');
        }
        $this->_dn = $result[0]['dn'];

        // Now we should have the user's DN.  Re-bind as appropriate with write
        // permissions to be able to store preferences.
        switch($this->_params['writedn']) {
        case 'user':
            $result = @ldap_bind($this->_connection,
                                 $this->_dn, $this->_password);
            break;

        case 'admin':
            $result = @ldap_bind($this->_connection,
                                 $this->_params['admindn'],
                                 $this->_params['adminpw']);
            break;

        case 'searchdn':
            // Since we've already bound as the search DN above, no rebinding
            // is necessary.
            $result = true;
            break;
        }

        if ($result === false) {
            Horde::logMessage(
                sprintf('Error rebinding for prefs writing: [%d]: %s',
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
            throw new Horde_Exception('Internal LDAP error. Details have been logged for the administrator.', @ldap_errno($this->_connection));
        }

        // We now have a ready-to-use connection.
        $this->_connected = true;
    }

    /**
     * Callback function for LDAP referrals.  This function is called when an
     * LDAP operation returns a referral to an alternate server.
     *
     * @return integer  1 on error, 0 on success.
     */
    public function rebindProc($conn, $who)
    {
        /* Strip out the hostname we're being redirected to. */
        $who = preg_replace(array('|^.*://|', '|:\d*$|'), '', $who);

        /* Make sure the server we're being redirected to is in our list of
           valid servers. */
        if (strpos($this->_params['hostspec'], $who) === false) {
            Horde::logMessage(
                sprintf('Referral target %s for DN %s is not in the authorized server list.',
                        $who, $bind_dn), 'ERR');
            return 1;
        }

        /* Figure out the DN of the authenticating user. */
        switch($this->_params['writedn']) {
        case 'user':
            $bind_dn = $this->_dn;
            $bind_pw = $this->_password;
            break;

        case 'admin':
            $bind_dn = $this->_params['admindn'];
            $bind_pw = $this->_params['adminpw'];
            break;

        case 'searchdn':
            $bind_dn = $this->_params['searchdn'];
            $bind_dn = $this->_params['searchpw'];
            break;
        }

        /* Bind to the new server. */
        $bind = @ldap_bind($conn, $bind_dn, $bind_pw);
        if ($bind === false) {
            Horde::logMessage(
                sprintf('Rebind to server %s:%d with DN %s failed: [%d] %s',
                        $this->_params['hostspec'],
                        $this->_params['port'],
                        $bind_dn,
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
        }

        return 0;
    }

    /**
     * Retrieves the requested set of preferences from the user's LDAP entry.
     *
     * @param string $scope  Scope specifier.
     */
    function _retrieve($scope)
    {
        try {
            $this->_connect();
        } catch (Horde_Exception $e) {
            if (empty($_SESSION['prefs_cache']['unavailable'])) {
                $_SESSION['prefs_cache']['unavailable'] = true;
                $GLOBALS['notification']->push(_("The preferences backend is currently unavailable and your preferences have not been loaded. You may continue to use the system with default settings."));
            }
            return;
        }

        // Search for the multi-valued field containing the array of
        // preferences.
        $search = @ldap_search($this->_connection, $this->_params['basedn'],
                              $this->_params['uid'] . '=' . $this->_user,
                              array($scope . 'Prefs'));
        if ($search === false) {
            Horde::logMessage(
                sprintf('Error while searching for the user\'s prefs: [%d]: %s',
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
            return;
        }

        $result = @ldap_get_entries($this->_connection, $search);
        if ($result === false) {
            Horde::logMessage(
                sprintf('Error while retrieving LDAP search results for the user\'s prefs: [%d]: %s',
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
            return;
        }

        // Preferences are stored as colon-separated name:value pairs.
        // Each pair is stored as its own attribute off of the multi-
        // value attribute named in: $scope . 'Prefs'

        // ldap_get_entries() converts attribute indexes to lowercase.
        $field = Horde_String::lower($scope . 'prefs');
        $prefs = isset($result[0][$field])
            ? $result[0][$field]
            : array();

        foreach ($prefs as $prefstr) {
            // If the string doesn't contain a colon delimiter, skip it.
            if (strpos($prefstr, ':') === false) {
                continue;
            }

            // Split the string into its name:value components.
            list($name, $val) = explode(':', $prefstr, 2);
            if (isset($this->_scopes[$scope][$name])) {
                $this->_scopes[$scope][$name]['v'] = base64_decode($val);
                $this->_scopes[$scope][$name]['m'] &= ~self::PREFS_DEFAULT;
            } else {
                // This is a shared preference.
                $this->_scopes[$scope][$name] = array('v' => base64_decode($val),
                                                      'm' => 0,
                                                      'd' => null);
            }
        }
    }

    /**
     * Stores preferences to the LDAP server.
     *
     * @throws Horde_Exception
     */
    public function store()
    {
        // Get the list of preferences that have changed. If there are
        // none, no need to hit the backend.
        $dirty_prefs = $this->_dirtyPrefs();
        if (!$dirty_prefs) {
            return;
        }
        $dirty_scopes = array_keys($dirty_prefs);

        $this->_connect();

        // Build a hash of the preferences and their values that need
        // to be stored on the LDAP server. Because we have to update
        // all of the values of a multi-value entry wholesale, we
        // can't just pick out the dirty preferences; we must update
        // every scope that has dirty preferences.
        $new_values = array();
        foreach ($dirty_scopes as $scope) {
            foreach ($this->_scopes[$scope] as $name => $pref) {
                // Don't store locked preferences.
                if (!($pref['m'] & self::LOCKED)) {
                    $new_values[$scope . 'Prefs'][] =
                        $name . ':' . base64_encode($pref['v']);
                }
            }
        }

        // Entries must have the objectclasses 'top' and 'hordeperson'
        // to successfully store LDAP prefs. Check for both of them,
        // and add them if necessary.
        $search = @ldap_search($this->_connection, $this->_params['basedn'],
                              $this->_params['uid'] . '=' . $this->_user,
                              array('objectclass'));
        if ($search === false) {
            Horde::logMessage(
                sprintf('Error searching the directory for required objectClasses: [%d] %s',
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
            return;
        }

        $result = @ldap_get_entries($this->_connection, $search);
        if ($result === false) {
            Horde::logMessage(
                sprintf('Error retrieving results while checking for required objectClasses: [%d] %s',
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
            return;
        }

        if ($result['count'] > 0) {
            $top = false;
            $hordeperson = false;

            for ($i = 0; $i < $result[0]['objectclass']['count']; $i++) {
                if ($result[0]['objectclass'][$i] == 'top') {
                    $top = true;
                } elseif ($result[0]['objectclass'][$i] == 'hordePerson') {
                    $hordeperson = true;
                }
            }

            // Add any missing objectclasses.
            if (!$top) {
                @ldap_mod_add($this->_connection, $this->_dn, array('objectclass' => 'top'));
            }

            if (!$hordeperson) {
                @ldap_mod_add($this->_connection, $this->_dn, array('objectclass' => 'hordePerson'));
            }
        }

        // Send the hash to the LDAP server.
        $result = @ldap_mod_replace($this->_connection, $this->_dn,
                                    $new_values);
        if ($result === false) {
            Horde::logMessage(
                sprintf('Unable to modify user\'s objectClass for preferences: [%d] %s',
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
            return;
        }

        // Clean the preferences since they were just saved.
        foreach ($dirty_prefs as $scope => $prefs) {
            foreach ($prefs as $name => $pref) {
                $this->_scopes[$scope][$name]['m'] &= ~self::DIRTY;
            }

            // Update the cache for this scope.
            $this->_cacheUpdate($scope, array_keys($prefs));
        }
    }

    /**
     * Clears all preferences from the LDAP backend.
     *
     * @throws Horde_Exception
     */
    public function clear()
    {
        $this->_connect();

        $attrs = $GLOBALS['registry']->listApps(array('inactive', 'active', 'hidden', 'notoolbar', 'admin'));
        foreach ($attrs as $key => $val) {
            $attrs[$key] = $val . 'Prefs';
        }

        $search = @ldap_read($this->_connection, $this->_dn,
                            'objectClass=hordePerson', $attrs, 1);
        if ($search === false) {
            Horde::logMessage(
                sprintf('Error while getting preferenes from LDAP: [%d] %s',
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
            return;
        }

        $result = @ldap_get_entries($this->_connection, $search);
        if ($result === false) {
            Horde::logMessage(
                sprintf('Error while retrieving results from LDAP: [%d] %s',
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
            return;
        }

        $attrs = array();
        for ($i = 0; $i < $result[0]['count']; $i++) {
            $attrs[$result[0][$i]] = array();
        }
        $result = @ldap_mod_del($this->_connection, $this->_dn, $attrs);
        if ($result === false) {
            Horde::logMessage(
                sprintf('Unable to clear user\'s preferences: [%d] %s',
                        @ldap_errno($this->_connection),
                        @ldap_error($this->_connection)), 'ERR');
        }

        $this->cleanup(true);
    }

}
