<?php
/**
 * The main Horde_Ldap class.
 *
 * @package   Ldap
 * @author    Tarjej Huse <tarjei@bergfald.no>
 * @author    Jan Wagner <wagner@netsols.de>
 * @author    Del <del@babel.com.au>
 * @author    Benedikt Hallinger <beni@php.net>
 * @author    Ben Klang <ben@alkaloid.net>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2009-2010 The Horde Project
 * @copyright 2003-2007 Tarjej Huse, Jan Wagner, Del Elson, Benedikt Hallinger
 * @license   http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 */
class Horde_Ldap
{
    /**
     * Class configuration array
     *
     * - hostspec:       the LDAP host to connect to (may be an array of
     *                   several hosts to try).
     * - port:           the server port.
     * - version:        LDAP version (defaults to 3).
     * - tls:            when set, ldap_start_tls() is run after connecting.
     * - binddn:         the DN to bind as when searching.
     * - bindpw:         password to use when searching LDAP.
     * - basedn:         LDAP base.
     * - options:        hash of LDAP options to set.
     * - filter:         default search filter.
     * - scope:          default search scope.
     * - user:           configuration parameters for {@link findUserDN()},
     *                   must contain 'uid', and 'filter' or 'objectclass'
     *                   entries.
     * - auto_reconnect: if true, the class will automatically
     *                   attempt to reconnect to the LDAP server in certain
     *                   failure conditions when attempting a search, or other
     *                   LDAP operations.  Defaults to false.  Note that if you
     *                   set this to true, calls to search() may block
     *                   indefinitely if there is a catastrophic server failure.
     * - min_backoff:    minimum reconnection delay period (in seconds).
     * - current_backof: initial reconnection delay period (in seconds).
     * - max_backoff:    maximum reconnection delay period (in seconds).
     *
     * @var array
     */
    protected $_config = array(
        'hostspec'        => 'localhost',
        'port'            => 389,
        'version'         => 3,
        'tls'             => false,
        'binddn'          => '',
        'bindpw'          => '',
        'basedn'          => '',
        'options'         => array(),
        'filter'          => '(objectClass=*)',
        'scope'           => 'sub',
        'user'            => array(),
        'auto_reconnect'  => false,
        'min_backoff'     => 1,
        'current_backoff' => 1,
        'max_backoff'     => 32);

    /**
     * List of hosts we try to establish a connection to.
     *
     * @var array
     */
    protected $_hostList = array();

    /**
     * List of hosts that are known to be down.
     *
     * @var array
     */
    protected $_downHostList = array();

    /**
     * LDAP resource link.
     *
     * @var resource
     */
    protected $_link;

    /**
     * Schema object.
     *
     * @see schema()
     * @var Horde_Ldap_Schema
     */
    protected $_schema;

    /**
     * Schema cache function callback.
     *
     * @see registerSchemaCache()
     * @var string
     */
    protected $_schemaCache;

    /**
     * Cache for attribute encoding checks.
     *
     * @var array Hash with attribute names as key and boolean value
     *            to determine whether they should be utf8 encoded or not.
     */
    protected $_schemaAttrs = array();

    /**
     * Cache for rootDSE objects
     *
     * Hash with requested rootDSE attr names as key and rootDSE
     * object as value.
     *
     * Since the RootDSE object itself may request a rootDSE object,
     * {@link rootDSE()} caches successful requests.
     * Internally, Horde_Ldap needs several lookups to this object, so
     * caching increases performance significally.
     *
     * @var array
     */
    protected $_rootDSECache = array();

    /**
     * Constructor.
     *
     * @see $_config
     *
     * @param array $config Configuration array.
     */
    public function __construct($config = array())
    {
        $this->setConfig($config);
        $this->bind();
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Sets the internal configuration array.
     *
     * @param array $config Configuration hash.
     */
    protected function setConfig($config)
    {
        /* Parameter check -- probably should raise an error here if
         * config is not an array. */
        if (!is_array($config)) {
            return;
        }

        foreach ($config as $k => $v) {
            if (isset($this->_config[$k])) {
                $this->_config[$k] = $v;
            }
        }

        /* Ensure the host list is an array. */
        if (is_array($this->_config['hostspec'])) {
            $this->_hostList = $this->_config['hostspec'];
        } else {
            if (strlen($this->_config['hostspec'])) {
                $this->_hostList = array($this->_config['hostspec']);
            } else {
                $this->_hostList = array();
                /* This will cause an error in _connect(), so
                 * the user is notified about the failure. */
            }
        }

        /* Reset the down host list, which seems like a sensible thing
         * to do if the config is being reset for some reason. */
        $this->_downHostList = array();
    }

    /**
     * Bind or rebind to the LDAP server.
     *
     * This function binds with the given DN and password to the
     * server. In case no connection has been made yet, it will be
     * started and STARTTLS issued if appropiate.
     *
     * The internal bind configuration is not being updated, so if you
     * call bind() without parameters, you can rebind with the
     * credentials provided at first connecting to the server.
     *
     * @param string $dn       DN for binding.
     * @param string $password Password for binding.
     *
     * @throws Horde_Ldap_Exception
     */
    public function bind($dn = null, $password = null)
    {
        /* Fetch current bind credentials. */
        if (empty($dn)) {
            $dn = $this->_config['binddn'];
        }
        if (empty($password)) {
            $password = $this->_config['bindpw'];
        }

        /* Connect first, if we haven't so far.  This will also bind
         * us to the server. */
        if (!$this->_link) {
            /* Store old credentials so we can revert them later, then
             * overwrite config with new bind credentials. */
            $olddn = $this->_config['binddn'];
            $oldpw = $this->_config['bindpw'];

            /* Overwrite bind credentials in config so
             * _connect() knows about them. */
            $this->_config['binddn'] = $dn;
            $this->_config['bindpw'] = $password;

            /* Try to connect with provided credentials. */
            $msg = $this->_connect();

            /* Reset to previous config. */
            $this->_config['binddn'] = $olddn;
            $this->_config['bindpw'] = $oldpw;
            return;
        }

        /* Do the requested bind as we are asked to bind manually. */
        if (is_null($dn)) {
            /* Anonymous bind. */
            $msg = @ldap_bind($this->_link);
        } else {
            /* Privileged bind. */
            $msg = @ldap_bind($this->_link, $dn, $password);
        }
        if (!$msg) {
            throw new Horde_Ldap_Exception('Bind failed: ' . @ldap_error($this->_link),
                                           @ldap_errno($this->_link));
        }
    }

    /**
     * Connects to the LDAP server.
     *
     * This function connects to the LDAP server specified in the
     * configuration, binds and set up the LDAP protocol as needed.
     *
     * @throws Horde_Ldap_Exception
     */
    protected function _connect()
    {
        /* Connecting is briefly described in RFC1777. Basicly it works like
         * this:
         *  1. set up TCP connection
         *  2. secure that connection if neccessary
         *  3a. setVersion to tell server which version we want to speak
         *  3b. perform bind
         *  3c. setVersion to tell server which version we want to speak
         *      together with a test for supported versions
         *  4. set additional protocol options */

        /* Return if we are already connected. */
        if ($this->_link) {
            return;
        }

        /* Connnect to the LDAP server if we are not connected.  Note that
         * ldap_connect() may return a link value even if no connection is
         * made.  We need to do at least one anonymous bind to ensure that a
         * connection is actually valid.
         *
         * See: http://www.php.net/manual/en/function.ldap-connect.php */

        /* Default error message in case all connection attempts fail but no
         * message is set. */
        $current_error = new Horde_Ldap_Exception('Unknown connection error');

        /* Catch empty $_hostList arrays. */
        if (!is_array($this->_hostList) || !count($this->_hostList)) {
            throw new Horde_Ldap_Exception('No servers configured');
        }

        /* Cycle through the host list. */
        foreach ($this->_hostList as $host) {
            /* Ensure we have a valid string for host name. */
            if (is_array($host)) {
                $current_error = new Horde_Ldap_Exception('No Servers configured');
                continue;
            }

            /* Skip this host if it is known to be down. */
            if (in_array($host, $this->_downHostList)) {
                continue;
            }

            /* Record the host that we are actually connecting to in case we
             * need it later. */
            $this->_config['hostspec'] = $host;

            /* Attempt a connection. */
            $this->_link = @ldap_connect($host, $this->_config['port']);
            if (!$this->_link) {
                $current_error = new Horde_Ldap_Exception('Could not connect to ' .  $host . ':' . $this->_config['port']);
                $this->_downHostList[] = $host;
                continue;
            }

            /* If we're supposed to use TLS, do so before we try to bind, as
             * some strict servers only allow binding via secure
             * connections. */
            if ($this->_config['tls']) {
                try {
                    $this->startTLS();
                } catch (Horde_Ldap_Exception $e) {
                    $current_error           = $e;
                    $this->_link             = false;
                    $this->_downHostList[] = $host;
                    continue;
                }
            }

            /* Try to set the configured LDAP version on the connection if LDAP
             * server needs that before binding (eg OpenLDAP).
             * This could be necessary since RFC 1777 states that the protocol
             * version has to be set at the bind request.
             * We use force here which means that the test in the rootDSE is
             * skipped; this is neccessary, because some strict LDAP servers
             * only allow to read the LDAP rootDSE (which tells us the
             * supported protocol versions) with authenticated clients.
             * This may fail in which case we try again after binding.
             * In this case, most probably the bind() or setVersion() call
             * below will also fail, providing error messages. */
            $version_set = false;
            $this->setVersion(0, true);

            /* Attempt to bind to the server. If we have credentials
             * configured, we try to use them, otherwise it's an anonymous
             * bind.
             * As stated by RFC 1777, the bind request should be the first
             * operation to be performed after the connection is established.
             * This may give an protocol error if the server does not support
             * v2 binds and the above call to setVersion() failed.
             * If the above call failed, we try an v2 bind here and set the
             * version afterwards (with checking to the rootDSE). */
            try {
                $this->bind();
            } catch (Exception $e) {
                /* The bind failed, discard link and save error msg.
                 * Then record the host as down and try next one. */
                if ($this->errorName($e->getCode()) == 'LDAP_PROTOCOL_ERROR' &&
                    !$version_set) {
                    /* Provide a finer grained error message if protocol error
                     * arises because of invalid version. */
                    $e = new Horde_Ldap_Exception($e->getMessage() . ' (could not set LDAP protocol version to ' . $this->_config['version'].')', $e->getCode());
                }
                $this->_link             = false;
                $current_error           = $e;
                $this->_downHostList[] = $host;
                continue;
            }

            /* Set desired LDAP version if not successfully set before.
             * Here, a check against the rootDSE is performed, so we get a
             * error message if the server does not support the version.
             * The rootDSE entry should tell us which LDAP versions are
             * supported. However, some strict LDAP servers only allow
             * bound users to read the rootDSE. */
            if (!$version_set) {
                try {
                    $this->setVersion();
                } catch (Exception $e) {
                    $current_error           = $e;
                    $this->_link             = false;
                    $this->_downHostList[] = $host;
                    continue;
                }
            }

            /* Set LDAP parameters, now that we know we have a valid
             * connection. */
            if (isset($this->_config['options']) &&
                is_array($this->_config['options']) &&
                count($this->_config['options'])) {
                foreach ($this->_config['options'] as $opt => $val) {
                    try {
                        $this->setOption($opt, $val);
                    } catch (Exception $e) {
                        $current_error           = $e;
                        $this->_link             = false;
                        $this->_downHostList[] = $host;
                        continue 2;
                    }
                }
            }

            /* At this stage we have connected, bound, and set up options, so
             * we have a known good LDAP server.  Time to go home. */
            return;
        }

        /* All connection attempts have failed, return the last error. */
        throw $current_error;
    }

    /**
     * Reconnects to the LDAP server.
     *
     * In case the connection to the LDAP service has dropped out for some
     * reason, this function will reconnect, and re-bind if a bind has been
     * attempted in the past.  It is probably most useful when the server list
     * provided to the new() or _connect() function is an array rather than a
     * single host name, because in that case it will be able to connect to a
     * failover or secondary server in case the primary server goes down.
     *
     * This method just tries to re-establish the current connection.  It will
     * sleep for the current backoff period (seconds) before attempting the
     * connect, and if the connection fails it will double the backoff period,
     * but not try again.  If you want to ensure a reconnection during a
     * transient period of server downtime then you need to call this function
     * in a loop.
     *
     * @throws Horde_Ldap_Exception
     */
    protected function _reconnect()
    {
        /* Return if we are already connected. */
        if ($this->_link) {
            return;
        }

        /* Sleep for a backoff period in seconds. */
        sleep($this->_config['current_backoff']);

        /* Retry all available connections. */
        $this->_downHostList = array();

        try {
            $this->_connect();
        } catch (Horde_Ldap_Exception $e) {
            $this->_config['current_backoff'] *= 2;
            if ($this->_config['current_backoff'] > $this->_config['max_backoff']) {
                $this->_config['current_backoff'] = $this->_config['max_backoff'];
            }
            throw $e;
        }

        /* Now we should be able to safely (re-)bind. */
        try {
            $this->bind();
        } catch (Exception $e) {
            $this->_config['current_backoff'] *= 2;
            if ($this->_config['current_backoff'] > $this->_config['max_backoff']) {
                $this->_config['current_backoff'] = $this->_config['max_backoff'];
            }

            /* $this->_config['hostspec'] should have had the last connected
             * host stored in it by _connect().  Since we are unable to
             * bind to that host we can safely assume that it is down or has
             * some other problem. */
            $this->_downHostList[] = $this->_config['hostspec'];
            throw $e;
        }

        /* At this stage we have connected, bound, and set up options, so we
         * have a known good LDAP server. Time to go home. */
        $this->_config['current_backoff'] = $this->_config['min_backoff'];
    }

    /**
     * Closes the LDAP connection.
     */
    public function disconnect()
    {
        @ldap_close($this->_link);
    }

    /**
     * Starts an encrypted session.
     *
     * @throws Horde_Ldap_Exception
     */
    public function startTLS()
    {
        /* Test to see if the server supports TLS first.
         * This is done via testing the extensions offered by the server.
         * The OID 1.3.6.1.4.1.1466.20037 tells whether TLS is supported. */
        try {
            $rootDSE = $this->rootDSE();
        } catch (Exception $e) {
            throw new Horde_Ldap_Exception('Unable to fetch rootDSE entry to see if TLS is supoported: ' . $e->getMessage(), $e->getCode());
        }

        try {
            $supported_extensions = $rootDSE->getValue('supportedExtension');
        } catch (Exception $e) {
            throw new Horde_Ldap_Exception('Unable to fetch rootDSE attribute "supportedExtension" to see if TLS is supoported: ' . $e->getMessage(), $e->getCode());
        }

        if (!in_array('1.3.6.1.4.1.1466.20037', $supported_extensions)) {
            throw new Horde_Ldap_Exception('Server reports that it does not support TLS');
        }

        if (!@ldap_start_tls($this->_link)) {
            throw new Horde_Ldap_Exception('TLS not started: ' . @ldap_error($this->_link),
                                           @ldap_errno($this->_link));
        }
    }

    /**
     * Adds a new entry to the directory.
     *
     * This also links the entry to the connection used for the add, if it was
     * a fresh entry.
     *
     * @see HordeLdap_Entry::createFresh()
     *
     * @param Horde_Ldap_Entry $entry An LDAP entry.
     *
     * @throws Horde_Ldap_Exception
     */
    public function add(Horde_Ldap_Entry $entry)
    {
        /* Continue attempting the add operation in a loop until we get a
         * success, a definitive failure, or the world ends. */
        while (true) {
            $link = $this->getLink();
            if ($link === false) {
                /* We do not have a successful connection yet.  The call to
                 * getLink() would have kept trying if we wanted one. */
                throw new Horde_Ldap_Exception('Could not add entry ' . $entry->dn() . ' no valid LDAP connection could be found.');
            }

            if (@ldap_add($link, $entry->dn(), $entry->getValues())) {
                /* Entry successfully added, we should update its Horde_Ldap
                 * reference in case it is not set so far (fresh entry). */
                try {
                    $entry->getLDAP();
                } catch (Horde_Ldap_Exception $e) {
                    $entry->setLDAP($this);
                }
                /* Store that the entry is present inside the directory. */
                $entry->markAsNew(false);
                return;
            }

            /* We have a failure.  What kind?  We may be able to reconnect and
             * try again. */
            $error_code = @ldap_errno($link);
            if ($this->errorName($error_code) != 'LDAP_OPERATIONS_ERROR' |
                !$this->_config['auto_reconnect']) {
                /* Errors other than the above are just passed back to the user
                 * so he may react upon them. */
                throw new Horde_Ldap_Exception('Could not add entry ' . $entry->dn() . ': ' . ldap_err2str($error_code), $error_code);
            }

            /* The server has disconnected before trying the operation.  We
             * should try again, possibly with a different server. */
            $this->_link = false;
            $this->_reconnect();
        }
    }

    /**
     * Deletes an entry from the directory.
     *
     * @param string|Horde_Ldap_Entry $dn        DN string or Horde_Ldap_Entry.
     * @param boolean                 $recursive Should we delete all children
     *                                           recursivelx as well?
     * @throws Horde_Ldap_Exception
     */
    public function delete($dn, $recursive = false)
    {
        if ($dn instanceof Horde_Ldap_Entry) {
             $dn = $dn->dn();
        }
        if (!is_string($dn)) {
            throw new Horde_Ldap_Exception('Parameter is not a string nor an entry object!');
        }

        /* Recursive delete searches for children and calls delete for them. */
        if ($recursive) {
            $result = @ldap_list($this->_link, $dn, '(objectClass=*)', array(null), 0, 0);
            if ($result && @ldap_count_entries($this->_link, $result)) {
                for ($subentry = @ldap_first_entry($this->_link, $result);
                     $subentry;
                     $subentry = @ldap_next_entry($this->_link, $subentry)) {
                    $this->delete(@ldap_get_dn($this->_link, $subentry), true);
                }
            }
        }

        /* Continue the delete operation in a loop until we get a success, or a
         * definitive failure. */
        while (true) {
            $link = $this->getLink();
            if (!$link) {
                /* We do not have a successful connection yet.  The call to
                 * getLink() would have kept trying if we wanted one. */
                throw new Horde_Ldap_Exception('Could not add entry ' . $dn . ' no valid LDAP connection could be found.');
            }

            $s = @ldap_delete($link, $dn);
            if ($s) {
                /* Entry successfully deleted. */
                return;
            }

            /* We have a failure.  What kind? We may be able to reconnect and
             * try again. */
            $error_code = @ldap_errno($link);
            if ($this->errorName($error_code) == 'LDAP_OPERATIONS_ERROR' &&
                $this->_config['auto_reconnect']) {
                /* The server has disconnected before trying the operation.  We
                 * should try again, possibly with a different server. */
                $this->_link = false;
                $this->_reconnect();
            } elseif ($this->errorName($error_code) == 'LDAP_NOT_ALLOWED_ON_NONLEAF') {
                /* Subentries present, server refused to delete.
                 * Deleting subentries is the clients responsibility, but since
                 * the user may not know of the subentries, we do not force
                 * that here but instead notify the developer so he may take
                 * actions himself. */
                throw new Horde_Ldap_Exception('Could not delete entry ' . $dn . ' because of subentries. Use the recursive parameter to delete them.', $error_code);
            } else {
                /* Errors other than the above catched are just passed back to
                 * the user so he may react upon them. */
                throw new Horde_Ldap_Exception('Could not delete entry ' . $dn . ': ' . ldap_err2str($error_code), $error_code);
            }
        }
    }

    /**
     * Modifies an LDAP entry on the server.
     *
     * The $params argument is an array of actions and should be something like
     * this:
     * <code>
     * array('add' => array('attribute1' => array('val1', 'val2'),
     *                      'attribute2' => array('val1')),
     *       'delete' => array('attribute1'),
     *       'replace' => array('attribute1' => array('val1')),
     *       'changes' => array('add' => ...,
     *                          'replace' => ...,
     *                          'delete' => array('attribute1', 'attribute2' => array('val1')))
     * </code>
     *
     * The order of execution is as following:
     *   1. adds from 'add' array
     *   2. deletes from 'delete' array
     *   3. replaces from 'replace' array
     *   4. changes (add, replace, delete) in order of appearance
     *
     * The function calls the corresponding functions of an Horde_Ldap_Entry
     * object. A detailed description of array structures can be found there.
     *
     * Unlike the modification methods provided by the Horde_Ldap_Entry object,
     * this method will instantly carry out an update() after each operation,
     * thus modifying "directly" on the server.
     *
     * @see Horde_Ldap_Entry::add()
     * @see Horde_Ldap_Entry::delete()
     * @see Horde_Ldap_Entry::replace()
     *
     * @param string|Horde_Ldap_Entry $entry DN string or Horde_Ldap_Entry.
     * @param array                   $parms Array of changes
     *
     * @throws Horde_Ldap_Exception
     */
    public function modify($entry, $parms = array())
    {
        if (is_string($entry)) {
            $entry = $this->getEntry($entry);
        }
        if (!($entry instanceof Horde_Ldap_Entry)) {
            throw new Horde_Ldap_Exception('Parameter is not a string nor an entry object!');
        }

        /* Perform changes mentioned separately. */
        foreach (array('add', 'delete', 'replace') as $action) {
            if (!isset($parms[$action])) {
                continue;
            }
            $entry->$action($parms[$action]);
            $entry->setLDAP($this);

            /* Because the ldap_*() functions are called inside
             * Horde_Ldap_Entry::update(), we have to trap the error codes
             * issued from that if we want to support reconnection. */
            while (true) {
                try {
                    $entry->update();
                    break;
                } catch (Exception $e) {
                    /* We have a failure.  What kind?  We may be able to
                     * reconnect and try again. */
                    $error_code = $e->getCode();
                    $error_name = $this->errorName($error_code);

                    if ($this->errorName($error_code) != 'LDAP_OPERATIONS_ERROR' ||
                        !$this->_config['auto_reconnect']) {
                        /* Errors other than the above catched are just passed
                         * back to the user so he may react upon them. */
                        throw new Horde_Ldap_Exception('Could not modify entry: ' . $e->getMessage());
                    }
                    /* The server has disconnected before trying the operation.
                     * We should try again, possibly with a different
                     * server. */
                    $this->_link = false;
                    $this->_reconnect();
                }
            }
        }

        if (!isset($parms['changes']) || !is_array($parms['changes'])) {
            return;
        }

        /* Perform combined changes in 'changes' array. */
        foreach ($parms['changes'] as $action => $value) {
            $this->modify($entry, array($action => $value));
        }
    }

    /**
     * Runs an LDAP search query.
     *
     * $base and $filter may be ommitted. The one from config will then be
     * used. $base is either a DN-string or an Horde_Ldap_Entry object in which
     * case its DN will be used.
     *
     * $params may contain:
     * - scope: The scope which will be used for searching:
     *          - base: Just one entry
     *          - sub: The whole tree
     *          - one: Immediately below $base
     * - sizelimit: Limit the number of entries returned
     *              (default: 0 = unlimited)
     * - timelimit: Limit the time spent for searching (default: 0 = unlimited)
     * - attrsonly: If true, the search will only return the attribute names
     * - attributes: Array of attribute names, which the entry should contain.
     *               It is good practice to limit this to just the ones you
     *               need.
     *
     * You cannot override server side limitations to sizelimit and timelimit:
     * You can always only lower a given limit.
     *
     * @todo implement search controls (sorting etc)
     *
     * @param string|Horde_Ldap_Entry  $base   LDAP searchbase.
     * @param string|Horde_Ldap_Filter $filter LDAP search filter.
     * @param array                    $params Array of options.
     *
     * @return Horde_Ldap_Search  The search result.
     * @throws Horde_Ldap_Exception
     */
    public function search($base = null, $filter = null, $params = array())
    {
        if (is_null($base)) {
            $base = $this->_config['basedn'];
        }
        if ($base instanceof Horde_Ldap_Entry) {
            /* Fetch DN of entry, making searchbase relative to the entry. */
            $base = $base->dn();
        }
        if (is_null($filter)) {
            $filter = $this->_config['filter'];
        }
        if ($filter instanceof Horde_Ldap_Filter) {
            /* Convert Horde_Ldap_Filter to string representation. */
            $filter = (string)$filter;
        }

        /* Setting search parameters.  */
        $sizelimit  = isset($params['sizelimit']) ? $params['sizelimit'] : 0;
        $timelimit  = isset($params['timelimit']) ? $params['timelimit'] : 0;
        $attrsonly  = isset($params['attrsonly']) ? $params['attrsonly'] : 0;
        $attributes = isset($params['attributes']) ? $params['attributes'] : array();

        /* Ensure $attributes to be an array in case only one attribute name
         * was given as string. */
        if (!is_array($attributes)) {
            $attributes = array($attributes);
        }

        /* Reorganize the $attributes array index keys sometimes there are
         * problems with not consecutive indexes. */
        $attributes = array_values($attributes);

        /* Scoping makes searches faster! */
        $scope = isset($params['scope'])
            ? $params['scope']
            : $this->_config['scope'];

        switch ($scope) {
        case 'one':
            $search_function = 'ldap_list';
            break;
        case 'base':
            $search_function = 'ldap_read';
            break;
        default:
            $search_function = 'ldap_search';
        }

        /* Continue attempting the search operation until we get a success or a
         * definitive failure. */
        while (true) {
            $link = $this->getLink();
            $search = @call_user_func($search_function,
                                      $link,
                                      $base,
                                      $filter,
                                      $attributes,
                                      $attrsonly,
                                      $sizelimit,
                                      $timelimit);

            if ($errno = @ldap_errno($link)) {
                $err = $this->errorName($errno);
                if ($err == 'LDAP_NO_SUCH_OBJECT' ||
                    $err == 'LDAP_SIZELIMIT_EXCEEDED') {
                    return new Horde_Ldap_Search($search, $this, $attributes);
                }
                if ($err == 'LDAP_FILTER_ERROR') {
                    /* Bad search filter. */
                    throw new Horde_Ldap_Exception(ldap_err2str($errno) . ' ($filter)', $errno);
                }
                if ($err == 'LDAP_OPERATIONS_ERROR' &&
                    $this->_config['auto_reconnect']) {
                    $this->_link = false;
                    $this->_reconnect();
                } else {
                    $msg = "\nParameters:\nBase: $base\nFilter: $filter\nScope: $scope";
                    throw new Horde_Ldap_Exception(ldap_err2str($errno) . $msg, $errno);
                }
            } else {
                return new Horde_Ldap_Search($search, $this, $attributes);
            }
        }
    }

    /**
     * Returns the DN of a user.
     *
     * The purpose is to quickly find the full DN of a user so it can be used
     * to re-bind as this user. This method requires the 'user' configuration
     * parameter to be set.
     *
     * @param string $user  The user to find.
     *
     * @return string  The user's full DN.
     * @throws Horde_Ldap_Exception
     * @throws Horde_Exception_NotFound
     */
    public function findUserDN($user)
    {
        $filter = Horde_Ldap_Filter::combine(
            'and',
            array(Horde_Ldap_Filter::build($this->_config['user']),
                  Horde_Ldap_Filter::create($this->_config['user']['uid'], 'equals', $user)));
        $search = $this->search(
            null,
            $filter,
            array('attributes' => array($this->_config['user']['uid'])));
        if (!$search->count()) {
            throw new Horde_Exception_NotFound();
        }
        $entry = $search->shiftEntry();
        return $entry->currentDN();
    }

    /**
     * Sets an LDAP option.
     *
     * @param string $option Option to set.
     * @param mixed  $value  Value to set option to.
     *
     * @throws Horde_Ldap_Exception
     */
    public function setOption($option, $value)
    {
        if (!$this->_link) {
            throw new Horde_Ldap_Exception('Could not set LDAP option: No LDAP connection');
        }
        if (!defined($option)) {
            throw new Horde_Ldap_Exception('Unkown option requested');
        }
        if (@ldap_set_option($this->_link, constant($option), $value)) {
            return;
        }
        $err = @ldap_errno($this->_link);
        if ($err) {
            throw new Horde_Ldap_Exception(ldap_err2str($err), $err);
        }
        throw new Horde_Ldap_Exception('Unknown error');
    }

    /**
     * Returns an LDAP option value.
     *
     * @param string $option Option to get.
     *
     * @return Horde_Ldap_Error|string Horde_Ldap_Error or option value
     * @throws Horde_Ldap_Exception
     */
    public function getOption($option)
    {
        if (!$this->_link) {
            throw new Horde_Ldap_Exception('No LDAP connection');
        }
        if (!defined($option)) {
            throw new Horde_Ldap_Exception('Unkown option requested');
        }
        if (@ldap_get_option($this->_link, constant($option), $value)) {
            return $value;
        }
        $err = @ldap_errno($this->_link);
        if ($err) {
            throw new Horde_Ldap_Exception(ldap_err2str($err), $err);
        }
        throw new Horde_Ldap_Exception('Unknown error');
    }

    /**
     * Returns the LDAP protocol version that is used on the connection.
     *
     * A lot of LDAP functionality is defined by what protocol version
     * the LDAP server speaks. This might be 2 or 3.
     *
     * @return integer  The protocol version.
     */
    public function getVersion()
    {
        if ($this->_link) {
            $version = $this->getOption('LDAP_OPT_PROTOCOL_VERSION');
        } else {
            $version = $this->_config['version'];
        }
        return $version;
    }

    /**
     * Sets the LDAP protocol version that is used on the connection.
     *
     * @todo Checking via the rootDSE takes much time - why? fetching
     *       and instanciation is quick!
     *
     * @param integer $version LDAP version that should be used.
     * @param boolean $force   If set to true, the check against the rootDSE
     *                         will be skipped.
     *
     * @throws Horde_Ldap_Exception
     */
    public function setVersion($version = 0, $force = false)
    {
        if (!$version) {
            $version = $this->_config['version'];
        }

        /* Check to see if the server supports this version first.
         *
         * TODO: Why is this so horribly slow? $this->rootDSE() is very fast,
         * as well as Horde_Ldap_RootDse(). Seems like a problem at copying the
         * object inside PHP??  Additionally, this is not always
         * reproducable... */
        if (!$force) {
            $rootDSE = $this->rootDSE();
            $supported_versions = $rootDSE->getValue('supportedLDAPVersion');
            if (is_string($supported_versions)) {
                $supported_versions = array($supported_versions);
            }
            $check_ok = in_array($version, $supported_versions);
        }
        $check_ok = true;

        if ($force || $check_ok) {
            return $this->setOption('LDAP_OPT_PROTOCOL_VERSION', $version);
        }
        throw new Horde_Ldap_Exception('LDAP Server does not support protocol version ' . $version);
    }


    /**
     * Returns whether a DN exists in the directory.
     *
     * @param string|Horde_Ldap_Entry $dn The DN of the object to test.
     *
     * @return boolean  True if the DN exists.
     * @throws Horde_Ldap_Exception
     */
    public function exists($dn)
    {
        if ($dn instanceof Horde_Ldap_Entry) {
             $dn = $dn->dn();
        }
        if (!is_string($dn)) {
            throw new Horde_Ldap_Exception('Parameter $dn is not a string nor an entry object!');
        }

        /* Make dn relative to parent. */
        $base = Horde_Ldap_Util::explodeDN($dn, array('casefold' => 'none', 'reverse' => false, 'onlyvalues' => false));

        $entry_rdn = array_shift($base);
        if (is_array($entry_rdn)) {
            /* Maybe the dn consist of a multivalued RDN. We must
             * build the dn in this case because the $entry_rdn is an
             * array. */
            $filter_dn = Horde_Ldap_Util::canonicalDN($entry_rdn);
        }
        $base = Horde_Ldap_Util::canonicalDN($base);

        $result = @ldap_list($this->_link, $base, $entry_rdn, array(), 1, 1);
        if (@ldap_count_entries($this->_link, $result)) {
            return true;
        }
        if ($this->errorName(@ldap_errno($this->_link)) == 'LDAP_NO_SUCH_OBJECT') {
            return false;
        }
        if (@ldap_errno($this->_link)) {
            throw new Horde_Ldap_Exception(@ldap_error($this->_link), @ldap_errno($this->_link));
        }
        return false;
    }


    /**
     * Returns a specific entry based on the DN.
     *
     * @todo Maybe a check against the schema should be done to be
     *       sure the attribute type exists.
     *
     * @param string $dn   DN of the entry that should be fetched.
     * @param array  $attributes Array of Attributes to select. If ommitted, all
     *                     attributes are fetched.
     *
     * @return Horde_Ldap_Entry  A Horde_Ldap_Entry object.
     * @throws Horde_Ldap_Exception
     */
    public function getEntry($dn, $attributes = array())
    {
        if (!is_array($attributes)) {
            $attributes = array($attributes);
        }
        $result = $this->search($dn, '(objectClass=*)',
                                array('scope' => 'base', 'attributes' => $attributes));
        if (!$result->count()) {
            throw new Horde_Exception_NotFound(sprintf('Could not fetch entry %s: no entry found', $dn));
        }
        $entry = $result->shiftEntry();
        if (!$entry) {
            throw new Horde_Ldap_Exception('Could not fetch entry (error retrieving entry from search result)');
        }
        return $entry;
    }

    /**
     * Renames or moves an entry.
     *
     * This method will instantly carry out an update() after the
     * move, so the entry is moved instantly.
     *
     * You can pass an optional Horde_Ldap object. In this case, a
     * cross directory move will be performed which deletes the entry
     * in the source (THIS) directory and adds it in the directory
     * $target_ldap.
     *
     * A cross directory move will switch the entry's internal LDAP
     * reference so updates to the entry will go to the new directory.
     *
     * If you want to do a cross directory move, you need to pass an
     * Horde_Ldap_Entry object, otherwise the attributes will be
     * empty.
     *
     * @param string|Horde_Ldap_Entry $entry       An LDAP entry.
     * @param string                  $newdn       The new location.
     * @param Horde_Ldap              $target_ldap Target directory for cross
     *                                             server move.
     *
     * @throws Horde_Ldap_Exception
     */
    public function move($entry, $newdn, $target_ldap = null)
    {
        if (is_string($entry)) {
            if ($target_ldap && $target_ldap !== $this) {
                throw new Horde_Ldap_Exception('Unable to perform cross directory move: operation requires a Horde_Ldap_Entry object');
            }
            $entry = $this->getEntry($entry);
        }
        if (!$entry instanceof Horde_Ldap_Entry) {
            throw new Horde_Ldap_Exception('Parameter $entry is expected to be a Horde_Ldap_Entry object! (If DN was passed, conversion failed)');
        }
        if ($target_ldap && !($target_ldap instanceof Horde_Ldap)) {
            throw new Horde_Ldap_Exception('Parameter $target_ldap is expected to be a Horde_Ldap object!');
        }

        if (!$target_ldap || $target_ldap === $this) {
            /* Local move. */
            $entry->dn($newdn);
            $entry->setLDAP($this);
            $entry->update();
            return;
        }

        /* Cross directory move. */
        if ($target_ldap->exists($newdn)) {
            throw new Horde_Ldap_Exception('Unable to perform cross directory move: entry does exist in target directory');
        }
        $entry->dn($newdn);
        try {
            $target_ldap->add($entry);
        } catch (Exception $e) {
            throw new Horde_Ldap_Exception('Unable to perform cross directory move: ' . $e->getMessage() . ' in target directory');
        }

        try {
            $this->delete($entry->currentDN());
        } catch (Exception $e) {
            try {
                $add_error_string = '';
                /* Undo add. */
                $target_ldap->delete($entry);
            } catch (Exception $e) {
                $add_error_string = ' Additionally, the deletion (undo add) of $entry in target directory failed.';
            }
            throw new Horde_Ldap_Exception('Unable to perform cross directory move: ' . $e->getMessage() . ' in source directory.' . $add_error_string);
        }
        $entry->setLDAP($target_ldap);
    }

    /**
     * Copies an entry to a new location.
     *
     * The entry will be immediately copied. Only attributes you have
     * selected will be copied.
     *
     * @param Horde_Ldap_Entry $entry An LDAP entry.
     * @param string           $newdn New FQF-DN of the entry.
     *
     * @return Horde_Ldap_Entry  The copied entry.
     * @throws Horde_Ldap_Exception
     */
    public function copy($entry, $newdn)
    {
        if (!$entry instanceof Horde_Ldap_Entry) {
            throw new Horde_Ldap_Exception('Parameter $entry is expected to be a Horde_Ldap_Entry object');
        }

        $newentry = Horde_Ldap_Entry::createFresh($newdn, $entry->getValues());
        $this->add($newentry);

        return $newentry;
    }


    /**
     * Returns the string for an LDAP errorcode.
     *
     * Made to be able to make better errorhandling.  Function based
     * on DB::errorMessage().
     *
     * Hint: The best description of the errorcodes is found here:
     * http://www.directory-info.com/Ldap/LDAPErrorCodes.html
     *
     * @param integer $errorcode An error code.
     *
     * @return string The description for the error.
     */
    public static function errorName($errorcode)
    {
        $errorMessages = array(
            0x00 => 'LDAP_SUCCESS',
            0x01 => 'LDAP_OPERATIONS_ERROR',
            0x02 => 'LDAP_PROTOCOL_ERROR',
            0x03 => 'LDAP_TIMELIMIT_EXCEEDED',
            0x04 => 'LDAP_SIZELIMIT_EXCEEDED',
            0x05 => 'LDAP_COMPARE_FALSE',
            0x06 => 'LDAP_COMPARE_TRUE',
            0x07 => 'LDAP_AUTH_METHOD_NOT_SUPPORTED',
            0x08 => 'LDAP_STRONG_AUTH_REQUIRED',
            0x09 => 'LDAP_PARTIAL_RESULTS',
            0x0a => 'LDAP_REFERRAL',
            0x0b => 'LDAP_ADMINLIMIT_EXCEEDED',
            0x0c => 'LDAP_UNAVAILABLE_CRITICAL_EXTENSION',
            0x0d => 'LDAP_CONFIDENTIALITY_REQUIRED',
            0x0e => 'LDAP_SASL_BIND_INPROGRESS',
            0x10 => 'LDAP_NO_SUCH_ATTRIBUTE',
            0x11 => 'LDAP_UNDEFINED_TYPE',
            0x12 => 'LDAP_INAPPROPRIATE_MATCHING',
            0x13 => 'LDAP_CONSTRAINT_VIOLATION',
            0x14 => 'LDAP_TYPE_OR_VALUE_EXISTS',
            0x15 => 'LDAP_INVALID_SYNTAX',
            0x20 => 'LDAP_NO_SUCH_OBJECT',
            0x21 => 'LDAP_ALIAS_PROBLEM',
            0x22 => 'LDAP_INVALID_DN_SYNTAX',
            0x23 => 'LDAP_IS_LEAF',
            0x24 => 'LDAP_ALIAS_DEREF_PROBLEM',
            0x30 => 'LDAP_INAPPROPRIATE_AUTH',
            0x31 => 'LDAP_INVALID_CREDENTIALS',
            0x32 => 'LDAP_INSUFFICIENT_ACCESS',
            0x33 => 'LDAP_BUSY',
            0x34 => 'LDAP_UNAVAILABLE',
            0x35 => 'LDAP_UNWILLING_TO_PERFORM',
            0x36 => 'LDAP_LOOP_DETECT',
            0x3C => 'LDAP_SORT_CONTROL_MISSING',
            0x3D => 'LDAP_INDEX_RANGE_ERROR',
            0x40 => 'LDAP_NAMING_VIOLATION',
            0x41 => 'LDAP_OBJECT_CLASS_VIOLATION',
            0x42 => 'LDAP_NOT_ALLOWED_ON_NONLEAF',
            0x43 => 'LDAP_NOT_ALLOWED_ON_RDN',
            0x44 => 'LDAP_ALREADY_EXISTS',
            0x45 => 'LDAP_NO_OBJECT_CLASS_MODS',
            0x46 => 'LDAP_RESULTS_TOO_LARGE',
            0x47 => 'LDAP_AFFECTS_MULTIPLE_DSAS',
            0x50 => 'LDAP_OTHER',
            0x51 => 'LDAP_SERVER_DOWN',
            0x52 => 'LDAP_LOCAL_ERROR',
            0x53 => 'LDAP_ENCODING_ERROR',
            0x54 => 'LDAP_DECODING_ERROR',
            0x55 => 'LDAP_TIMEOUT',
            0x56 => 'LDAP_AUTH_UNKNOWN',
            0x57 => 'LDAP_FILTER_ERROR',
            0x58 => 'LDAP_USER_CANCELLED',
            0x59 => 'LDAP_PARAM_ERROR',
            0x5a => 'LDAP_NO_MEMORY',
            0x5b => 'LDAP_CONNECT_ERROR',
            0x5c => 'LDAP_NOT_SUPPORTED',
            0x5d => 'LDAP_CONTROL_NOT_FOUND',
            0x5e => 'LDAP_NO_RESULTS_RETURNED',
            0x5f => 'LDAP_MORE_RESULTS_TO_RETURN',
            0x60 => 'LDAP_CLIENT_LOOP',
            0x61 => 'LDAP_REFERRAL_LIMIT_EXCEEDED',
            1000 => 'Unknown Error');

         return isset($errorMessages[$errorcode]) ?
            $errorMessages[$errorcode] :
            'Unknown Error (' . $errorcode . ')';
    }

    /**
     * Returns a rootDSE object
     *
     * This either fetches a fresh rootDSE object or returns it from
     * the internal cache for performance reasons, if possible.
     *
     * @param array $attrs Array of attributes to search for.
     *
     * @return Horde_Ldap_RootDse Horde_Ldap_RootDse object
     * @throws Horde_Ldap_Exception
     */
    public function rootDSE(array $attrs = array())
    {
        $attrs_signature = serialize($attrs);

        /* See if we need to fetch a fresh object, or if we already
         * requested this object with the same attributes. */
        if (!isset($this->_rootDSECache[$attrs_signature])) {
            $this->_rootDSECache[$attrs_signature] = new Horde_Ldap_RootDse($this, $attrs);
        }

        return $this->_rootDSECache[$attrs_signature];
    }

    /**
     * Returns a schema object
     *
     * @param string $dn Subschema entry dn.
     *
     * @return Horde_Ldap_Schema  Horde_Ldap_Schema object
     * @throws Horde_Ldap_Exception
     */
    public function schema($dn = null)
    {
        /* If a schema caching object is registered, we use that to fetch
         * a schema object.
         * See registerSchemaCache() for more info on this.
         * FIXME: Convert to Horde_Cache */
        if ($this->_schema === null) {
            if ($this->_schemaCache) {
               $cached_schema = $this->_schemaCache->loadSchema();
               if ($cached_schema instanceof Horde_Ldap_Schema) {
                   $this->_schema = $cached_schema;
               }
            }
        }

        /* Fetch schema, if not tried before and no cached version
         * available.  If we are already fetching the schema, we will
         * skip fetching. */
        if ($this->_schema === null) {
            /* Store a temporary error message so subsequent calls to
             * schema() can detect that we are fetching the schema
             * already. Otherwise we will get an infinite loop at
             * Horde_Ldap_Schema. */
            $this->_schema = new Horde_Ldap_Exception('Schema not initialized');
            $this->_schema = new Horde_Ldap_Schema($this, $dn);

            /* If schema caching is active, advise the cache to store
             * the schema. */
            if ($this->_schemaCache) {
                $this->_schemaCache->storeSchema($this->_schema);
            }
        }

        if ($this->_schema instanceof Horde_Ldap_Exception) {
            throw $this->_schema;
        }
        return $this->_schema;
    }

    /**
     * Enable/disable persistent schema caching
     *
     * Sometimes it might be useful to allow your scripts to cache
     * the schema information on disk, so the schema is not fetched
     * every time the script runs which could make your scripts run
     * faster.
     *
     * This method allows you to register a custom object that
     * implements your schema cache. Please see the SchemaCache interface
     * (SchemaCache.interface.php) for informations on how to implement this.
     * To unregister the cache, pass null as $cache parameter.
     *
     * For ease of use, Horde_Ldap provides a simple file based cache
     * which is used in the example below. You may use this, for example,
     * to store the schema in a linux tmpfs which results in the schema
     * beeing cached inside the RAM which allows nearly instant access.
     * <code>
     *    // Create the simple file cache object that comes along with Horde_Ldap
     *    $mySchemaCache_cfg = array(
     *      'path'    =>  '/tmp/Horde_Ldap_Schema.cache',
     *      'max_age' =>  86400   // max age is 24 hours (in seconds)
     *    );
     *    $mySchemaCache = new Horde_Ldap_SimpleFileSchemaCache($mySchemaCache_cfg);
     *    $ldap = new Horde_Ldap::connect(...);
     *    $ldap->registerSchemaCache($mySchemaCache); // enable caching
     *    // now each call to $ldap->schema() will get the schema from disk!
     * </code>
     *
     * @param Horde_Ldap_SchemaCache|null $cache Object implementing the Horde_Ldap_SchemaCache interface
     *
     * @return true|Horde_Ldap_Error
     * FIXME: Convert to Horde_Cache
     */
    public function registerSchemaCache($cache) {
        if (is_null($cache)
        || (is_object($cache) && in_array('Horde_Ldap_SchemaCache', class_implements($cache))) ) {
            $this->_schemaCache = $cache;
            return true;
        } else {
            throw new Horde_Ldap_Exception('Custom schema caching object is either no '.
                'valid object or does not implement the Horde_Ldap_SchemaCache interface!');
        }
    }

    /**
     * Checks if PHP's LDAP extension is loaded.
     *
     * If it is not loaded, it tries to load it manually using PHP's dl().
     * It knows both windows-dll and *nix-so.
     *
     * @throws Horde_Ldap_Exception
     */
    public static function checkLDAPExtension()
    {
        if (!extension_loaded('ldap') && !@dl('ldap.' . PHP_SHLIB_SUFFIX)) {
            throw new Horde_Ldap_Exception('Unable to locate PHP LDAP extension. Please install it before using the Horde_Ldap package.');
        } else {
            return true;
        }
    }

    /**
     * @todo Remove this and expect all data to be UTF-8.
     *
     * Encodes given attributes to UTF8 if needed by schema.
     *
     * This function takes attributes in an array and then checks
     * against the schema if they need UTF8 encoding. If that is the
     * case, they will be encoded. An encoded array will be returned
     * and can be used for adding or modifying.
     *
     * $attributes is expected to be an array with keys describing
     * the attribute names and the values as the value of this attribute:
     * <code>$attributes = array('cn' => 'foo', 'attr2' => array('mv1', 'mv2'));</code>
     *
     * @param array $attributes An array of attributes.
     *
     * @return array|Horde_Ldap_Error An array of UTF8 encoded attributes or an error.
     */
    public function utf8Encode($attributes)
    {
        return $this->utf8($attributes, 'utf8_encode');
    }

    /**
     * @todo Remove this and expect all data to be UTF-8.
     *
     * Decodes the given attribute values if needed by schema
     *
     * $attributes is expected to be an array with keys describing
     * the attribute names and the values as the value of this attribute:
     * <code>$attributes = array('cn' => 'foo', 'attr2' => array('mv1', 'mv2'));</code>
     *
     * @param array $attributes Array of attributes
     *
     * @access public
     * @see utf8Encode()
     * @return array|Horde_Ldap_Error Array with decoded attribute values or Error
     */
    public function utf8Decode($attributes)
    {
        return $this->utf8($attributes, 'utf8_decode');
    }

    /**
     * @todo Remove this and expect all data to be UTF-8.
     *
     * Encodes or decodes attribute values if needed
     *
     * @param array $attributes Array of attributes
     * @param array $function   Function to apply to attribute values
     *
     * @access protected
     * @return array Array of attributes with function applied to values.
     */
    protected function utf8($attributes, $function)
    {
        if (!is_array($attributes) || array_key_exists(0, $attributes)) {
            throw new Horde_Ldap_Exception('Parameter $attributes is expected to be an associative array');
        }

        if (!$this->_schema) {
            $this->_schema = $this->schema();
        }

        if (!$this->_link || !function_exists($function)) {
            return $attributes;
        }

        if (is_array($attributes) && count($attributes) > 0) {

            foreach ($attributes as $k => $v) {

                if (!isset($this->_schemaAttrs[$k])) {

                    try {
                        $attr = $this->_schema->get('attribute', $k);
                    } catch (Exception $e) {
                        continue;
                    }

                    if (false !== strpos($attr['syntax'], '1.3.6.1.4.1.1466.115.121.1.15')) {
                        $encode = true;
                    } else {
                        $encode = false;
                    }
                    $this->_schemaAttrs[$k] = $encode;

                } else {
                    $encode = $this->_schemaAttrs[$k];
                }

                if ($encode) {
                    if (is_array($v)) {
                        foreach ($v as $ak => $av) {
                            $v[$ak] = call_user_func($function, $av);
                        }
                    } else {
                        $v = call_user_func($function, $v);
                    }
                }
                $attributes[$k] = $v;
            }
        }
        return $attributes;
    }

    /**
     * Returns the LDAP link resource.
     *
     * It will loop attempting to re-establish the connection if the
     * connection attempt fails and auto_reconnect has been turned on
     * (see the _config array documentation).
     *
     * @return resource LDAP link.
     */
    public function getLink()
    {
        if ($this->_config['auto_reconnect']) {
            while (true) {
                /* Return the link handle if we are already connected.
                 * Otherwise try to reconnect. */
                if ($this->_link) {
                    return $this->_link;
                }
                $this->_reconnect();
            }
        }
        return $this->_link;
    }

    /**
     * Builds an LDAP search filter fragment.
     *
     * @param string $lhs    The attribute to test.
     * @param string $op     The operator.
     * @param string $rhs    The comparison value.
     * @param array $params  Any additional parameters for the operator.
     *
     * @return string  The LDAP search fragment.
     */
    public static function buildClause($lhs, $op, $rhs, $params = array())
    {
        switch ($op) {
        case 'LIKE':
            if (empty($rhs)) {
                return '(' . $lhs . '=*)';
            }
            if (!empty($params['begin'])) {
                return sprintf('(|(%s=%s*)(%s=* %s*))', $lhs, self::quote($rhs), $lhs, self::quote($rhs));
            }
            if (!empty($params['approximate'])) {
                return sprintf('(%s=~%s)', $lhs, self::quote($rhs));
            }
            return sprintf('(%s=*%s*)', $lhs, self::quote($rhs));

        default:
            return sprintf('(%s%s%s)', $lhs, $op, self::quote($rhs));
        }
    }


    /**
     * Escapes characters with special meaning in LDAP searches.
     *
     * @param string $clause  The string to escape.
     *
     * @return string  The escaped string.
     */
    public static function quote($clause)
    {
        return str_replace(array('\\',   '(',  ')',  '*',  "\0"),
                           array('\\5c', '\(', '\)', '\*', "\\00"),
                           $clause);
    }

    /**
     * Takes an array of DN elements and properly quotes it according to RFC
     * 1485.
     *
     * @param array $parts  An array of tuples containing the attribute
     *                      name and that attribute's value which make
     *                      up the DN. Example:
     *                      <code>
     *                      $parts = array(0 => array('cn', 'John Smith'),
     *                                     1 => array('dc', 'example'),
     *                                     2 => array('dc', 'com'));
     *                      </code>
     *
     * @return string  The properly quoted string DN.
     */
    public static function quoteDN($parts)
    {
        $dn = '';
        $count = count($parts);
        for ($i = 0; $i < $count; $i++) {
            if ($i > 0) {
                $dn .= ',';
            }
            $dn .= $parts[$i][0] . '=';

            // See if we need to quote the value.
            if (preg_match('/^\s|\s$|\s\s|[,+="\r\n<>#;]/', $parts[$i][1])) {
                $dn .= '"' . str_replace('"', '\\"', $parts[$i][1]) . '"';
            } else {
                $dn .= $parts[$i][1];
            }
        }

        return $dn;
    }
}
