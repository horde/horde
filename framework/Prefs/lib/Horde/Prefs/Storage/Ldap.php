<?php
/**
 * Preferences storage implementation for LDAP servers.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jon Parise <jon@horde.org>
 * @author   Ben Klang <ben@alkaloid.net>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Prefs
 */
class Horde_Prefs_Storage_Ldap extends Horde_Prefs_Storage_Base
{
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
     * Constructor.
     *
     * @param string $user   The username.
     * @param array $params  Configuration options:
     * <pre>
     * basedn - (string) [REQUIRED] The base DN for the LDAP server.
     * hostspec - (string) [REQUIRED] The hostname of the LDAP server.
     * uid - (string) [REQUIRED] The username search key.
     * writeas - (string) [REQUIRED] One of "user", "admin", or "search"
     *
     * Optional parameters:
     * binddn - (string) The DN of the administrative account to bind for
     *          write operations.
     * bindpw - (string) binddn's password for bind authentication.
     * port - (integer) The port of the LDAP server.
     *        DEFAULT: 389
     * searchdn - (string) The DN of a user with search permissions on the
     *            directory.
     * searchpw - (string) searchdn's password for binding.
     * tls - (boolean) Whether to use TLS connections.
     *       DEFAULT: false
     * version - (integer) The version of the LDAP protocol to use.
     *           DEFAULT: NONE (system default will be used)
     * </pre>
     */
    public function __construct($user, array $params = array())
    {
        throw new Horde_Prefs_Exception('This driver needs to be refactored to use Horde_Ldap.');
        /* If a valid server port has not been specified, set the default. */
        if (!isset($params['port']) || !is_integer($params['port'])) {
            $params['port'] = 389;
        }

        parent::__construct($user, $params);
    }

    /**
     */
    public function get($scope_ob)
    {
        $this->_connect();

        // Search for the multi-valued field containing the array of
        // preferences.
        $search = @ldap_search(
            $this->_connection,
            $this->_params['basedn'],
            $this->_params['uid'] . '=' . $this->params['user'],
            array($scope_ob->scope . 'Prefs'));
        if ($search === false) {
            throw new Horde_Prefs_Exception(sprintf('Error while searching for the user\'s prefs: [%d]: %s', @ldap_errno($this->_connection), @ldap_error($this->_connection)));
        }

        $result = @ldap_get_entries($this->_connection, $search);
        if ($result === false) {
            throw new Horde_Prefs_Exception(sprintf('Error while retrieving LDAP search results for the user\'s prefs: [%d]: %s', @ldap_errno($this->_connection), @ldap_error($this->_connection)));
        }

        // Preferences are stored as colon-separated name:value pairs.
        // Each pair is stored as its own attribute off of the multi-
        // value attribute named in: $scope_ob->scope . 'Prefs'

        // ldap_get_entries() converts attribute indexes to lowercase.
        $field = Horde_String::lower($scope_ob->scope . 'prefs');
        $prefs = isset($result[0][$field])
            ? $result[0][$field]
            : array();

        foreach ($prefs as $prefstr) {
            // If the string doesn't contain a colon delimiter, skip it.
            if (strpos($prefstr, ':') !== false) {
                // Split the string into its name:value components.
                list($name, $val) = explode(':', $prefstr, 2);
                $scope_ob->set($name, base64_decode($val));
            }
        }

        return $scope_ob;
    }

    /**
     */
    public function store($scope_ob)
    {
        $this->_connect();

        // Build a hash of the preferences and their values that need
        // to be stored on the LDAP server. Because we have to update
        // all of the values of a multi-value entry wholesale, we
        // can't just pick out the dirty preferences; we must update
        // the entire dirty scope.
        $new_vals = array();

        /* Driver has no support for storing locked status. */
        foreach ($scope_ob->getDirty() as $name) {
            $new_vals[$scope_ob->scope . 'Prefs'][] = $name . ':' . base64_encode($scope_ob->get($name));
        }

        // Entries must have the objectclasses 'top' and 'hordeperson'
        // to successfully store LDAP prefs. Check for both of them,
        // and add them if necessary.
        $search = @ldap_search(
            $this->_connection,
            $this->_params['basedn'],
            $this->_params['uid'] . '=' . $this->prefs['user'],
            array('objectclass')
        );
        if ($search === false) {
            throw new Horde_Prefs_Exception(sprintf('Error searching the directory for required objectClasses: [%d] %s', @ldap_errno($this->_connection), @ldap_error($this->_connection)));
        }

        $result = @ldap_get_entries($this->_connection, $search);
        if ($result === false) {
            throw new Horde_Prefs_Exception(sprintf('Error retrieving results while checking for required objectClasses: [%d] %s', @ldap_errno($this->_connection), @ldap_error($this->_connection)));
        }

        if ($result['count'] > 0) {
            $hordeperson = $top = false;

            for ($i = 0; $i < $result[0]['objectclass']['count']; ++$i) {
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
        $result = @ldap_mod_replace($this->_connection, $this->_dn, $new_vals);
        if ($result === false) {
            throw new Horde_Prefs_Exception(sprintf('Unable to modify user\'s objectClass for preferences: [%d] %s', @ldap_errno($this->_connection), @ldap_error($this->_connection)));
        }
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        // TODO: Implement scope/pref-level removal
        if (!is_null($scope) || !is_null($pref)) {
            throw new Horde_Prefs_Exception('Removal not supported.');
        }

        $this->_connect();

        // TODO: Move to horde/Core
        $attrs = $GLOBALS['registry']->listApps(array('inactive', 'active', 'hidden', 'notoolbar', 'admin'));
        foreach ($attrs as $key => $val) {
            $attrs[$key] = $val . 'Prefs';
        }

        $search = @ldap_read($this->_connection, $this->_dn,
                            'objectClass=hordePerson', $attrs, 1);
        if ($search === false) {
            throw new Horde_Prefs_Exception(sprintf('Error while getting preferenes from LDAP: [%d] %s', @ldap_errno($this->_connection), @ldap_error($this->_connection)));
        }

        $result = @ldap_get_entries($this->_connection, $search);
        if ($result === false) {
            throw new Horde_Prefs_Exception(sprintf('Error while retrieving results from LDAP: [%d] %s', @ldap_errno($this->_connection), @ldap_error($this->_connection)));
        }

        $attrs = array();
        for ($i = 0; $i < $result[0]['count']; $i++) {
            $attrs[$result[0][$i]] = array();
        }
        $result = @ldap_mod_del($this->_connection, $this->_dn, $attrs);
        if ($result === false) {
            throw new Horde_Prefs_Exception(sprintf('Unable to clear user\'s preferences: [%d] %s', @ldap_errno($this->_connection), @ldap_error($this->_connection)));
        }
    }

    /* LDAP Helper functions.
     * TODO: Use Horde_LDAP */

    /**
     * Opens a connection to the LDAP server.
     *
     * @throws Horde_Prefs_Exception
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return;
        }

        if (!Horde_Util::extensionExists('ldap')) {
            throw new Horde_Prefs_Exception('Required LDAP extension not found.');
        }

        Horde::assertDriverConfig($this->_params, 'prefs',
            array('hostspec', 'basedn', 'uid', 'writeas'),
            'preferences LDAP');

        /* Connect to the LDAP server anonymously. */
        $conn = ldap_connect($this->_params['hostspec'], $this->_params['port']);
        if (!$conn) {
            throw new Horde_Prefs_Exception(sprintf('Failed to open an LDAP connection to %s.', $this->_params['hostspec']));
        }

        /* Set the LDAP protocol version. */
        if (isset($this->_params['version'])) {
            $result = @ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION,
                                       $this->_params['version']);
            if ($result === false) {
                throw new Horde_Prefs_Exception(sprintf('Set LDAP protocol version to %d failed: [%d] %s', $this->_params['version'], @ldap_errno($conn), @ldap_error($conn)));
            }
        }

        /* Start TLS if we're using it. */
        if (!empty($this->_params['tls'])) {
            @ldap_start_tls($conn);
        }

        /* If necessary, bind to the LDAP server as the user with search
         * permissions. */
        if (!empty($this->_params['searchdn'])) {
            $bind = @ldap_bind($conn, $this->_params['searchdn'],
                               $this->_params['searchpw']);
            if ($bind === false) {
                throw new Horde_Prefs_Exception(sprintf('Bind to server %s:%d with DN %s failed: [%d] %s', $this->_params['hostspec'], $this->_params['port'], $this->_params['searchdn'], @ldap_errno($conn), @ldap_error($conn)));
            }
        }

        /* Register our callback function to handle referrals. */
        if (function_exists('ldap_set_rebind_proc')) {
            $result = @ldap_set_rebind_proc($conn, array($this, 'rebindProc'));
            if ($result === false) {
                throw new Horde_Prefs_Exception(sprintf('Setting referral callback failed: [%d] %s', @ldap_errno($conn), @ldap_error($conn)));
            }
        }

        /* Store the connection handle at the instance level. */
        $this->_connection = $conn;

        /* Search for the user's full DN. */
        $search = @ldap_search($this->_connection, $this->_params['basedn'],
                               $this->_params['uid'] . '=' . $this->params['user'], array('dn'));
        if ($search === false) {
            throw new Horde_Prefs_Exception(sprintf('Error while searching the directory for the user\'s DN: [%d]: %s', @ldap_errno($this->_connection), @ldap_error($this->_connection)));
        }

        $result = @ldap_get_entries($this->_connection, $search);
        if ($result === false) {
            throw new Horde_Prefs_Exception(sprintf('Error while retrieving LDAP search results for the user\'s DN: [%d]: %s', @ldap_errno($this->_connection), @ldap_error($this->_connection)));
        }

        if ($result['count'] != 1) {
            throw new Horde_Prefs_Exception('Zero or more than one DN returned from search; unable to determine user\'s correct DN.');
        }
        $this->_dn = $result[0]['dn'];

        // Now we should have the user's DN.  Re-bind as appropriate with write
        // permissions to be able to store preferences.
        switch($this->_params['writeas']) {
        case 'user':
            $result = @ldap_bind($this->_connection,
                                 $this->_dn, $this->_opts['password']);
            break;

        case 'admin':
            $result = @ldap_bind($this->_connection,
                                 $this->_params['binddn'],
                                 $this->_params['bindpw']);
            break;

        case 'search':
            // Since we've already bound as the search DN above, no rebinding
            // is necessary.
            $result = true;
            break;
        }

        if ($result === false) {
            throw new Horde_Prefs_Exception(sprintf('Error rebinding for prefs writing: [%d]: %s', @ldap_errno($this->_connection), @ldap_error($this->_connection)));
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
            if ($this->_opts['logger']) {
                $this->_opts['logger']->log(sprintf('Referral target %s for DN %s is not in the authorized server list.', $who, $bind_dn), 'ERR');
            }
            return 1;
        }

        /* Figure out the DN of the authenticating user. */
        switch($this->_params['writeas']) {
        case 'user':
            $bind_dn = $this->_dn;
            $bind_pw = $this->_opts['password'];
            break;

        case 'admin':
            $bind_dn = $this->_params['binddn'];
            $bind_pw = $this->_params['bindpw'];
            break;

        case 'search':
            $bind_dn = $this->_params['searchdn'];
            $bind_dn = $this->_params['searchpw'];
            break;
        }

        /* Bind to the new server. */
        $bind = @ldap_bind($conn, $bind_dn, $bind_pw);
        if (($bind === false) && $this->_opts['logger']) {
            $this->_opts['logger']->log(sprintf('Rebind to server %s:%d with DN %s failed: [%d] %s', $this->_params['hostspec'], $this->_params['port'], $bind_dn, @ldap_errno($this->_connection), @ldap_error($this->_connection)), 'ERR');
        }

        return 0;
    }


}
