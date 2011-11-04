<?php
/**
 * The IMP_Imap:: class provides common functions for interaction with
 * IMAP/POP3 servers via the Horde_Imap_Client:: library.
 *
 * Copyright 2008-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 *
 * @property boolean $changed  If true, this object has changed.
 * @property boolean $imap  If true, this is an IMAP connection.
 * @property boolean $pop3  If true, this is a POP3 connection.
 */
class IMP_Imap implements Serializable
{
    /* Access constants. */
    const ACCESS_FOLDERS = 1;
    const ACCESS_SEARCH = 2;
    const ACCESS_FLAGS = 3;
    const ACCESS_UNSEEN = 4;
    const ACCESS_TRASH = 5;

    /**
     * The Horde_Imap_Client object.
     *
     * @var Horde_Imap_Client
     */
    public $ob = null;

    /**
     * Server configuration file.
     *
     * @var array
     */
    static protected $_config;

    /**
     * Has this object changed?
     *
     * @var boolean
     */
    protected $_changed = false;

    /**
     * Have we logged into server yet?
     *
     * @var boolean
     */
    protected $_login = false;

    /**
     * Default namespace.
     *
     * @var array
     */
    protected $_nsdefault = null;

    /**
     * Temporary data cache (destroyed at end of request).
     *
     * @var array
     */
    protected $_temp = array();

    /**
     */
    public function __get($key)
    {
        switch ($key) {
        case 'changed':
            return $this->_changed || ($this->ob && $this->ob->changed);

        case 'imap':
            return $this->ob && ($this->ob instanceof Horde_Imap_Client_Socket);

        case 'pop3':
            return $this->ob && ($this->ob instanceof Horde_Imap_Client_Socket_Pop3);
        }
    }

    /**
     * Create a new Horde_Imap_Client object.
     *
     * @param string $username  The username to authenticate with.
     * @param string $password  The password to authenticate with.
     * @param string $key       Create a new object using this server key.
     *
     * @return Horde_Imap_Client_Base  Client object.
     * @throws IMP_Imap_Exception
     */
    public function createImapObject($username, $password, $key)
    {
        global $prefs;

        if (!is_null($this->ob)) {
            return $this->ob;
        }

        if (($server = $this->loadServerConfig($key)) === false) {
            $error = new IMP_Imap_Exception('Could not load server configuration.');
            $error->log();
            throw $error;
        }

        $protocol = isset($server['protocol'])
            ? strtolower($server['protocol'])
            : 'imap';

        $imap_config = array(
            'capability_ignore' => empty($server['capability_ignore']) ? array() : $server['capability_ignore'],
            'comparator' => empty($server['comparator']) ? false : $server['comparator'],
            'debug' => isset($server['debug']) ? $server['debug'] : null,
            'debug_literal' => !empty($server['debug_raw']),
            'encryptKey' => array(__CLASS__, 'getEncryptKey'),
            'hostspec' => isset($server['hostspec']) ? $server['hostspec'] : null,
            'id' => empty($server['id']) ? false : $server['id'],
            'lang' => empty($server['lang']) ? false : $server['lang'],
            'password' => $password,
            'port' => isset($server['port']) ? $server['port'] : null,
            'secure' => isset($server['secure']) ? $server['secure'] : false,
            'statuscache' => true,
            'timeout' => empty($server['timeout']) ? null : $server['timeout'],
            'username' => $username,
        );

        /* Initialize caching. */
        if (!empty($server['cache'])) {
            $imap_config['cache'] = $this->loadCacheConfig(is_array($server['cache']) ? $server['cache'] : array());
        }

        try {
            $ob = Horde_Imap_Client::factory(($protocol == 'imap') ? 'Socket' : 'Socket_Pop3', $imap_config);
        } catch (Horde_Imap_Client_Exception $e) {
            $error = new IMP_Imap_Exception($e);
            $error->log();
            throw $error;
        }

        $this->ob = $ob;

        if ($protocol == 'pop') {
            /* Turn some options off if we are working with POP3. */
            $prefs->setValue('save_sent_mail', false);
            $prefs->setLocked('save_sent_mail', true);
            $prefs->setLocked('sent_mail_folder', true);
            $prefs->setLocked('drafts_folder', true);
            $prefs->setLocked('trash_folder', true);
        }

        return $ob;
    }

    /**
     * Prepare the config parameters necessary to use IMAP caching.
     *
     * @param mixed $config  Either a list of cache config parameters, or a
     *                       string containing the name of the driver with
     *                       which to load the cache config from.
     *
     * @return array  The configuration array.
     */
    public function loadCacheConfig($config)
    {
        if (!($ob = $GLOBALS['injector']->getInstance('Horde_Cache'))) {
            return array();
        }

        if (is_string($config)) {
            if ((($server = $this->loadServerConfig($config)) === false) ||
                empty($server['cache'])) {
                return array();
            }
            $config = $server['cache'];
        }

        return array(
            'cacheob' => $ob,
            'lifetime' => empty($config['lifetime']) ? false : $config['lifetime'],
            'slicesize' => empty($config['slicesize']) ? false : $config['slicesize'],
        );
    }

    /**
     * Update the list of mailboxes to ignore when caching FETCH data in the
     * IMAP client object.
     */
    public function updateFetchIgnore()
    {
        if ($this->imap) {
            $special = IMP_Mailbox::getSpecialMailboxes();

            $this->ob->fetchCacheIgnore(array_filter(array(
                strval($special[IMP_Mailbox::SPECIAL_SPAM]),
                strval($special[IMP_Mailbox::SPECIAL_TRASH])
            )));
        }
    }

    /**
     * Checks access rights for a server.
     *
     * @param integer $right  Access right.
     *
     * @return boolean  Does the mailbox have the access right?
     */
    public function access($right)
    {
        switch ($right) {
        case self::ACCESS_FOLDERS:
        case self::ACCESS_TRASH:
            return (!empty($GLOBALS['conf']['user']['allow_folders']) &&
                    !$this->pop3);

        case self::ACCESS_FLAGS:
        case self::ACCESS_SEARCH:
        case self::ACCESS_UNSEEN:
            return !$this->pop3;
        }

        return false;
    }

    /**
     * Get the namespace list.
     *
     * @return array  See Horde_Imap_Client_Base#getNamespaces().
     */
    public function getNamespaceList()
    {
        try {
            return $this->ob->getNamespaces($GLOBALS['session']->get('imp', 'imap_namespace', Horde_Session::TYPE_ARRAY));
        } catch (Horde_Imap_Client_Exception $e) {
            return array();
        }
    }

    /**
     * Get namespace info for a full folder path.
     *
     * @param string $mailbox    The folder path.
     * @param boolean $personal  If true, will return empty namespace only
     *                           if it is a personal namespace.
     *
     * @return mixed  The namespace info for the folder path or null if the
     *                path doesn't exist.
     */
    public function getNamespace($mailbox = null, $personal = false)
    {
        if ($this->pop3) {
            return null;
        }

        $ns = $this->getNamespaceList();

        if (is_null($mailbox)) {
            reset($ns);
            $mailbox = key($ns);
        }

        foreach ($ns as $key => $val) {
            $mbox = $mailbox . $val['delimiter'];
            if (strlen($key) && (strpos($mbox, $key) === 0)) {
                return $val;
            }
        }

        return (isset($ns['']) && (!$personal || ($val['type'] == Horde_Imap_Client::NS_PERSONAL)))
            ? $ns['']
            : null;
    }

    /**
     * Get the default personal namespace.
     *
     * @return mixed  The default personal namespace info.
     */
    public function defaultNamespace()
    {
        if ($this->pop3) {
            return null;
        }

        if ($this->_login && !isset($this->_nsdefault)) {
            foreach ($this->getNamespaceList() as $val) {
                if ($val['type'] == Horde_Imap_Client::NS_PERSONAL) {
                    $this->_nsdefault = $val;
                    $this->_changed = true;
                    break;
                }
            }
        }

        return $this->_nsdefault;
    }

    /**
     * Return the Horde_Imap_Client_Utils object.
     *
     * @return Horde_Imap_Client_Utils  The utility object.
     */
    public function getUtils()
    {
        return $this->ob
            ? $this->ob->utils
            : $GLOBALS['injector']->createInstance('Horde_Imap_Client_Utils');
    }

    /**
     * All other calls to this class are routed to the underlying
     * Horde_Imap_Client_Base object.
     *
     * @param string $method  Method name.
     * @param array $params   Method Parameters.
     *
     * @return mixed  The return from the requested method.
     * @throws BadMethodCallException
     * @throws IMP_Imap_Exception
     */
    public function __call($method, $params)
    {
        if (!$this->ob || !method_exists($this->ob, $method)) {
            throw new BadMethodCallException(sprintf('%s: Invalid method call "%s".', __CLASS__, $method));
        }

        switch ($method) {
        case 'append':
        case 'createMailbox':
        case 'deleteMailbox':
        case 'expunge':
        case 'fetch':
        case 'fetchFromSectionString':
        case 'getACL':
        case 'getCacheId':
        case 'getMetadata':
        case 'getMyACLRights':
        case 'getQuota':
        case 'getQuotaRoot':
        case 'openMailbox':
        case 'setMetadata':
        case 'setQuota':
        case 'status':
        case 'statusMultiple':
        case 'store':
        case 'subscribeMailbox':
        case 'thread':
            // Horde_Imap_Client_Mailbox: these calls all have the mailbox as
            // their first parameter.
            $params[0] = IMP_Mailbox::getImapMboxOb($params[0]);
            break;

        case 'copy':
        case 'renameMailbox':
            // Horde_Imap_Client_Mailbox: these calls all have the mailbox as
            // their first two parameters.
            $params[0] = IMP_Mailbox::getImapMboxOb($params[0]);
            $params[1] = IMP_Mailbox::getImapMboxOb($params[1]);
            break;

        case 'getNamespaces':
            // We know that the namespace strings are in UTF-8 here. For
            // Imap_Client 1.x, we need to explicitly force to a Mailbox
            // object so that UTF7-IMAP auto-detection does not occur.
            // TODO: Remove once Horde_Imap_Client 2.0+ is required.
            $params[0] = IMP_Mailbox::getImapMboxOb($params[0]);
            break;

        case 'listMailboxes':
            // Horde_Imap_Client_Mailbox: these calls all have the mailbox as
            // their first parameter.
            $params[0] = IMP_Mailbox::getImapMboxOb($params[0]);

            // Explicitly add 'utf8' parameter so we are returned mailbox
            // objects, not UTF7-IMAP strings.
            // TODO: Remove once Horde_Imap_Client 2.0+ is required.
            if (!isset($params[2])) {
                $params[2] = array();
            }
            $params[2]['utf8'] = true;
            break;

        case 'listACLRights':
        case 'setACL':
            // These are not mailbox parameters, but for Imap_Client 1.x we
            // need to explicitly force to a Mailbox object so that UTF7-IMAP
            // auto-detection does not occur.
            // TODO: Remove once Horde_Imap_Client 2.0+ is required.
            $params[0] = IMP_Mailbox::getImapMboxOb($params[0]);
            $params[1] = IMP_Mailbox::getImapMboxOb($params[1]);
            break;

        case 'search':
            $params = call_user_func_array(array($this, '_search'), $params);
            break;
        }

        try {
            $result = call_user_func_array(array($this->ob, $method), $params);
        } catch (Horde_Imap_Client_Exception $e) {
            $error = new IMP_Imap_Exception($e);

            switch ($e->getCode()) {
            case Horde_Imap_Client_Exception::DISCONNECT:
                $error->notify(_("Unexpectedly disconnected from the mail server."));
                break;

            case Horde_Imap_Client_Exception::SERVER_READERROR:
                $error->notify(_("Error when communicating with the mail server."));
                break;

            case Horde_Imap_Client_Exception::MAILBOX_NOOPEN:
                if (strcasecmp($method, 'openMailbox') === 0) {
                    $error->notify(sprintf(_("Could not open mailbox \"%s\"."), IMP_Mailbox::get(reset($params)))->label);
                } else {
                    $error->notify(_("Could not open mailbox."));
                }
                break;

            case Horde_Imap_Client_Exception::CATENATE_TOOBIG:
                $error->notify(_("Could not save message data because it is too large."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::NOPERM'):
                $error->notify(_("You do not have adequate permissions to carry out this operation."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::INUSE'):
            case constant('Horde_Imap_Client_Exception::POP3_TEMP_ERROR'):
                $error->notify(_("There was a temporary issue when attempting this operation. Please try again later."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::CORRUPTION'):
            case constant('Horde_Imap_Client_Exception::POP3_PERM_ERROR'):
                $error->notify(_("The mail server is reporting corrupt data in your mailbox. Details have been logged for the administrator."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::LIMIT'):
                $error->notify(_("The mail server has denied the request. Details have been logged for the administrator."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::OVERQUOTA'):
                $error->notify(_("The operation failed because you have exceeded your quota on the mail server."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::ALREADYEXISTS'):
                $error->notify(_("The object could not be created because it already exists."));
                break;

            // BC: Not available in Horde_Imap_Client 1.0.0
            case constant('Horde_Imap_Client_Exception::NONEXISTENT'):
                $error->notify(_("The object could not be deleted because it does not exist."));
                break;
            }

            $error->log();

            throw $error;
        }

        /* Special handling for various methods. */
        switch ($method) {
        case 'createMailbox':
        case 'renameMailbox':
            // Mailbox is first parameter.
            IMP_Mailbox::get($params[0])->expire();
            break;

        case 'getNamespaces':
            // Workaround deprecated UTF7-IMAP return.
            // TODO: Remove once Horde_Imap_Client 2.0+ is required.
            $tmp = array();
            foreach ($result as $key => $val) {
                $key = Horde_Imap_Client_Mailbox::get($key, true);
                $val['name'] = $key;
                $tmp[strval($key)] = $val;
            }
            $result = $tmp;
            break;

        case 'login':
            if (!$this->_login) {
                /* Check for POP3 UIDL support. */
                if ($this->pop3 &&
                    !$this->queryCapability('UIDL')) {
                    $error = new IMP_Imap_Exception('The POP3 server does not support the REQUIRED UIDL capability.');
                    $error->log();
                    throw $error;
                }

                $this->_changed = $this->_login = true;
            }
            break;

        case 'setACL':
            IMP_Mailbox::get($params[0])->expire(IMP_Mailbox::CACHE_ACL);
            break;

        case 'statusMultiple':
            // Workaround deprecated UTF7-IMAP return.
            // TODO: Remove once Horde_Imap_Client 2.0+ is required.
            $tmp = array();
            foreach ($result as $key => $val) {
                $tmp[strval(Horde_Imap_Client_Mailbox::get($key, true))] = $val;
            }
            $result = $tmp;
            break;
        }

        return $result;
    }

    /**
     * Prepares an IMAP search query.  Needed because certain configuration
     * parameters may need to be dynamically altered before passed to the
     * IMAP Client object.
     *
     * @param string $mailbox                        The mailbox to search.
     * @param Horde_Imap_Client_Search_Query $query  The search query object.
     * @param array $opts                            Additional options.
     *
     * @return array  Parameters to use in the search() call.
     */
    protected function _search($mailbox, $query, $opts)
    {
        $imap_charset = null;
        $mailbox = IMP_Mailbox::get($mailbox);

        if (!empty($opts['sort'])) {
            /* SORT (RFC 5256) requires UTF-8 support. So if we are sorting
             * via the server, we know that we can search in UTF-8. */
            if ($sort_cap = $this->queryCapability('SORT')) {
                $imap_charset = 'UTF-8';
            }

            /* If doing a from/to search, use display sorting if possible.
             * Although there is a fallback to a PHP-based display sort, for
             * performance reasons only do a display sort if it is supported
             * on the server. */
            if (is_array($sort_cap) &&
                in_array('DISPLAY', $sort_cap) &&
                $mailbox->access_sort) {
                $pos = array_search(Horde_Imap_Client::SORT_FROM, $opts['sort']);
                if ($pos !== false) {
                    $opts['sort'][$pos] = Horde_Imap_Client::SORT_DISPLAYFROM;
                }

                $pos = array_search(Horde_Imap_Client::SORT_TO, $opts['sort']);
                if ($pos !== false) {
                    $opts['sort'][$pos] = Horde_Imap_Client::SORT_DISPLAYTO;
                }
            }
        }

        /* Make sure we search in the proper charset. */
        if ($query) {
            $query = clone $query;
            if (is_null($imap_charset)) {
                $imap_charset = $this->validSearchCharset('UTF-8')
                    ? 'UTF-8'
                    : 'US-ASCII';
            }
            $query->charset($imap_charset, array('Horde_String', 'convertCharset'));
        }

        return array($mailbox->imap_mbox_ob, $query, $opts);
    }

    /* Static methods. */

    /**
     * Loads the IMP server configuration from backends.php.
     *
     * @param string $server  Returns this labeled entry only.
     *
     * @return mixed  If $server is set return this entry; else, return the
     *                entire servers array. Returns false on error.
     */
    static public function loadServerConfig($server = null)
    {
        if (isset(self::$_config)) {
            $servers = self::$_config;
        } else {
            try {
                $servers = Horde::loadConfiguration('backends.php', 'servers', 'imp');
                if (is_null($servers)) {
                    return false;
                }
            } catch (Horde_Exception $e) {
                Horde::logMessage($e, 'ERR');
                return false;
            }

            foreach (array_keys($servers) as $key) {
                if (!empty($servers[$key]['disabled'])) {
                    unset($servers[$key]);
                }
            }
            self::$_config = $servers;
        }

        if (is_null($server)) {
            return $servers;
        }

        /* Check for the existence of the server in the config file. */
        if (empty($servers[$server]) || !is_array($servers[$server])) {
            $entry = sprintf('Invalid server key "%s" from client [%s]', $server, $_SERVER['REMOTE_ADDR']);
            Horde::logMessage($entry, 'ERR');
            return false;
        }

        return $servers[$server];
    }

    /* Callback functions used in Horde_Imap_Client_Base. */

    static public function getEncryptKey()
    {
        return $GLOBALS['injector']->getInstance('Horde_Secret')->getKey('imp');
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return serialize(array(
            $this->ob,
            $this->_nsdefault,
            $this->_login
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list(
            $this->ob,
            $this->_nsdefault,
            $this->_login
        ) = unserialize($data);
    }

}
