<?php
/**
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Provides common functions for interaction with IMAP/POP3 servers via the
 * Horde_Imap_Client package.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2008-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read boolean $changed  If true, this object has changed.
 * @property-read Horde_Imap_Client_Base $client_ob  The IMAP client object.
 * @property-read IMP_Imap_Config $config  Base backend config settings.
 * @property-read boolean $init  Has the IMAP object been initialized?
 * @property-read integer $max_compose_recipients  The maximum number of
 *                                                 recipients to send to per
 *                                                 compose message.
 * @property-read integer $max_compose_timelimit  The maximum number of
 *                                                recipients to send to in the
 *                                                configured timelimit.
 * @property-read integer $max_create_mboxes  The maximum number of mailboxes
 *                                            a user can create.
 * @property-read string $server_key  Server key used to login.
 * @property-read string $thread_algo  The threading algorithm to use.
 */
class IMP_Imap implements Serializable
{
    /* Access constants. */
    const ACCESS_FOLDERS = 1;
    const ACCESS_SEARCH = 2;
    const ACCESS_FLAGS = 3;
    const ACCESS_UNSEEN = 4;
    const ACCESS_TRASH = 5;
    const ACCESS_CREATEMBOX = 6;
    const ACCESS_CREATEMBOX_MAX = 7;
    const ACCESS_COMPOSE_RECIPIENTS = 8;
    const ACCESS_COMPOSE_TIMELIMIT = 9;
    const ACCESS_ACL = 10;
    const ACCESS_DRAFTS = 11;
    const ACCESS_REMOTE = 12;

    /**
     * Cached backend configuration.
     *
     * @var array
     */
    static protected $_backends = array();

    /**
     * Has this object changed?
     *
     * @var boolean
     */
    protected $_changed = false;

    /**
     * Backend config.
     *
     * @var IMP_Imap_Config
     */
    protected $_config;

    /**
     * Object identifier.
     *
     * @var string
     */
    protected $_id;

    /**
     * The IMAP client object.
     *
     * @var Horde_Imap_Client_Base
     */
    protected $_ob;

    /**
     * Temporary data cache (destroyed at end of request).
     *
     * @var array
     */
    protected $_temp = array();

    /**
     * Constructor.
     *
     * @param string $id  Object identifier.
     */
    public function __construct($id)
    {
        $this->_id = strval($id);
    }

    /**
     */
    public function __get($key)
    {
        switch ($key) {
        case 'changed':
            return $this->_changed;

        case 'client_ob':
            return $this->init
                ? $this->_ob
                : null;

        case 'config':
            return isset($this->_config)
                ? $this->_config
                : new Horde_Support_Stub();

        case 'init':
            return isset($this->_ob);

        case 'max_compose_recipients':
        case 'max_compose_timelimit':
            return intval($GLOBALS['injector']->getInstance('Horde_Perms')->getPermissions('imp:' . str_replace('max_compose', 'max', $key), $GLOBALS['registry']->getAuth()));

        case 'max_create_mboxes':
            return intval($GLOBALS['injector']->getInstance('Horde_Perms')->getPermissions('imp:' . $this->_getPerm($key), $GLOBALS['registry']->getAuth()));

        case 'server_key':
            return $this->init
                ? $this->_ob->getParam('imp:backend')
                : null;

        case 'thread_algo':
            if (!$this->init) {
                return 'ORDEREDSUBJECT';
            }

            if ($thread = $this->_ob->getParam('imp:thread_algo')) {
                return $thread;
            }

            $thread = $this->config->thread;
            $thread_cap = $this->queryCapability('THREAD');
            if (!in_array($thread, is_array($thread_cap) ? $thread_cap : array())) {
                $thread = 'ORDEREDSUBJECT';
            }

            $this->_ob->setParam('imp:thread_algo', $thread);
            $this->_changed = true;

            return $thread;
        }
    }

    /**
     */
    public function __toString()
    {
        return $this->_id;
    }

    /**
     * Get the full permission name for a permission.
     *
     * @param string $perm  The permission.
     *
     * @return string  The full (backend-specific) permission name.
     */
    protected function _getPerm($perm)
    {
        return ($this->init ? $this->server_key . ':' : '') . $perm;
    }

    /**
     * Determine if this is a connection to an IMAP server.
     *
     * @return boolean  True if connected to IMAP server, false if connected
     *                  to a POP3 server.
     */
    public function isImap()
    {
        return (!$this->init ||
                ($this->_ob instanceof Horde_Imap_Client_Socket));
    }

    /**
     * Is sorting available for a mailbox?
     *
     * @param IMP_Mailbox $mbox  The mailbox to query.
     *
     * @return boolean  True if sorting is available.
     */
    public function canSort(IMP_Mailbox $mbox)
    {
        return ($this->config->sort_force ||
                $this->_ob->queryCapability('SORT'));
    }

    /**
     * Create the base Horde_Imap_Client object (from an entry in
     * backends.php).
     *
     * @param string $username  The username to authenticate with.
     * @param string $password  The password to authenticate with.
     * @param string $skey      Create a new object using this server key.
     *
     * @return Horde_Imap_Client_Base  Client object.
     * @throws IMP_Imap_Exception
     */
    public function createBaseImapObject($username, $password, $skey)
    {
        if ($this->init) {
            return $this->client_ob;
        }

        if (($config = $this->loadServerConfig($skey)) === false) {
            $error = new IMP_Imap_Exception('Could not load server configuration.');
            Horde::log($error);
            throw $error;
        }

        $imap_config = array(
            'hostspec' => $config->hostspec,
            'id' => $config->id,
            'password' => new IMP_Imap_Password($password),
            'port' => $config->port,
            'secure' => (($secure = $config->secure) ? $secure : false),
            'username' => $username,
            // IMP specific config
            'imp:backend' => $skey
        );

        /* Needed here to set config information in createImapObject(). */
        $this->_config = $config;

        try {
            return $this->createImapObject($imap_config, ($config->protocol == 'imap'));
        } catch (IMP_Imap_Exception $e) {
            unset($this->_config);
            throw $e;
        }
    }

    /**
     * Create a Horde_Imap_Client object.
     *
     * @param array $config  The IMAP configuration.
     * @param boolean $imap  True if IMAP connection, false if POP3.
     *
     * @return Horde_Imap_Client_Base  Client object.
     * @throws IMP_Imap_Exception
     */
    public function createImapObject($config, $imap = true)
    {
        if ($this->init) {
            return $this->_ob;
        }

        $sconfig = $this->config;
        $config = array_merge(array(
            'cache' => $sconfig->cache_params,
            'capability_ignore' => $sconfig->capability_ignore,
            'comparator' => $sconfig->comparator,
            'debug' => $sconfig->debug,
            'debug_literal' => $sconfig->debug_raw,
            'lang' => $sconfig->lang,
            'timeout' => $sconfig->timeout,
            // 'imp:login' - Set in __call()
            // 'imp:nsdefault' - Set in defaultNamespace()
        ), $config);

        try {
            $this->_ob = $imap
                ? new Horde_Imap_Client_Socket($config)
                : new Horde_Imap_Client_Socket_Pop3($config);
            return $this->_ob;
        } catch (Horde_Imap_Client_Exception $e) {
            $error = new IMP_Imap_Exception($e);
            Horde::log($error);
            throw $error;
        }
    }

    /**
     * Perform post-login tasks.
     */
    public function doPostLoginTasks()
    {
        global $prefs;

        switch ($this->_config->protocol) {
        case 'imap':
            /* Overwrite default special mailbox names. */
            foreach ($this->_config->special_mboxes as $key => $val) {
                if ($key != IMP_Mailbox::MBOX_USERSPECIAL) {
                    $prefs->setValue($key, $val, array(
                        'force' => true,
                        'nosave' => true
                    ));
                }
            }
            break;

        case 'pop':
            /* Turn some options off if we are working with POP3. */
            foreach (array('newmail_notify', 'save_sent_mail') as $val) {
                $prefs->setValue($val, false, array(
                    'force' => true,
                    'nosave' => true
                ));
                $prefs->setLocked($val, true);
            }
            $prefs->setLocked(IMP_Mailbox::MBOX_DRAFTS, true);
            $prefs->setLocked(IMP_Mailbox::MBOX_SENT, true);
            $prefs->setLocked(IMP_Mailbox::MBOX_SPAM, true);
            $prefs->setLocked(IMP_Mailbox::MBOX_TEMPLATES, true);
            $prefs->setLocked(IMP_Mailbox::MBOX_TRASH, true);
            break;
        }

        $this->updateFetchIgnore();
    }

    /**
     * Update the list of mailboxes to ignore when caching FETCH data in the
     * IMAP client object.
     */
    public function updateFetchIgnore()
    {
        if ($this->isImap()) {
            $special = IMP_Mailbox::getSpecialMailboxes();
            $cache = $this->_ob->getParam('cache');
            $cache['fetch_ignore'] = array_filter(array(
                strval($special[IMP_Mailbox::SPECIAL_SPAM]),
                strval($special[IMP_Mailbox::SPECIAL_TRASH])
            ));
            $this->_ob->setParam('cache', $cache);
        }
    }

    /**
     * Checks access rights for a server.
     *
     * @param integer $right  Access right.
     *
     * @return boolean  Does the access right exist?
     */
    public function access($right)
    {
        global $injector;

        if (!$this->init) {
            return false;
        }

        switch ($right) {
        case self::ACCESS_ACL:
            return ($this->config->acl && $this->queryCapability('ACL'));

        case self::ACCESS_CREATEMBOX:
            return ($this->isImap() &&
                    $injector->getInstance('Horde_Core_Perms')->hasAppPermission($this->_getPerm('create_mboxes')));

        case self::ACCESS_CREATEMBOX_MAX:
            return ($this->isImap() &&
                    $injector->getInstance('Horde_Core_Perms')->hasAppPermission($this->_getPerm('max_create_mboxes')));

        case self::ACCESS_DRAFTS:
        case self::ACCESS_FLAGS:
        case self::ACCESS_SEARCH:
        case self::ACCESS_UNSEEN:
            return $this->isImap();

        case self::ACCESS_FOLDERS:
        case self::ACCESS_TRASH:
            return ($this->isImap() &&
                    $injector->getInstance('Horde_Core_Perms')->hasAppPermission($this->_getPerm('allow_folders')));

        case self::ACCESS_REMOTE:
            return $injector->getInstance('Horde_Core_Perms')->hasAppPermission($this->_getPerm('allow_remote'));
        }

        return false;
    }

    /**
     * Checks compose access rights for a server.
     *
     * @param integer $right        Access right.
     * @param integer $email_count  The number of e-mail recipients.
     *
     * @return boolean  Is the access allowed?
     */
    public function accessCompose($right, $email_count)
    {
        switch ($right) {
        case self::ACCESS_COMPOSE_RECIPIENTS:
        case self::ACCESS_COMPOSE_TIMELIMIT:
            return $GLOBALS['injector']->getInstance('Horde_Core_Perms')->hasAppPermission(
                ($right == self::ACCESS_COMPOSE_RECIPIENTS) ? 'max_recipients' : 'max_timelimit',
                array(
                    'opts' => array(
                        'value' => $email_count
                    )
                )
            );
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
            $ns = $this->config->namespace;
            return $this->getNamespaces(is_null($ns) ? array() : $ns);
        } catch (Horde_Imap_Client_Exception $e) {
            return array();
        }
    }

    /**
     * Get namespace info for a full mailbox path.
     *
     * @param string $mailbox    The mailbox path.
     * @param boolean $personal  If true, will return empty namespace only
     *                           if it is a personal namespace.
     *
     * @return mixed  The namespace info for the mailbox path or null if the
     *                path doesn't exist.
     */
    public function getNamespace($mailbox = null, $personal = false)
    {
        if (!$this->isImap($mailbox)) {
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
        if (!$this->init ||
            !$this->isImap() ||
            !$this->_ob->getParam('imp:login')) {
            return null;
        }

        if (is_null($ns = $this->_ob->getParam('imp:nsdefault'))) {
            foreach ($this->getNamespaceList() as $val) {
                if ($val['type'] == Horde_Imap_Client::NS_PERSONAL) {
                    $this->_ob->setParam('imp:nsdefault', $val);
                    $this->_changed = true;
                    return $val;
                }
            }
        }

        return $ns;
    }

    /**
     * Return the cache ID for this mailbox.
     *
     * @param string $mailbox  The mailbox name (UTF-8).
     * @param array $addl      Local IMP metadata to add to the cache ID.
     *
     * @return string  The cache ID.
     */
    public function getCacheId($mailbox, array $addl = array())
    {
        return $this->getSyncToken($mailbox) .
            (empty($addl) ? '' : ('|' . implode('|', $addl)));
    }

    /**
     * Parses the cache ID for this mailbox.
     *
     * @param string $id  Cache ID generated by getCacheId().
     *
     * @return array  Two element array:
     *   - date: (integer) Date information (day of year), if embedded in
     *           cache ID.
     *   - token: (string) Mailbox sync token.
     */
    public function parseCacheId($id)
    {
        $out = array('date' => null);

        if ((($pos = strrpos($id, '|')) !== false) &&
            (substr($id, $pos + 1, 1) == 'D')) {
            $out['date'] = substr($id, $pos + 2);
        }

        $out['token'] = (($pos = strpos($id, '|')) === false)
            ? $id
            : substr($id, 0, $pos);

        return $out;
    }

    /**
     * Returns a list of messages, split into slices based on the total
     * message size.
     *
     * @param string $mbox                IMAP mailbox.
     * @param Horde_Imap_Client_Ids $ids  ID list.
     * @param integer $size               Maximum size of a slice.
     *
     * @return array  An array of Horde_Imap_Client_Ids objects.
     */
    public function getSlices(
        $mbox, Horde_Imap_Client_Ids $ids, $size = 5242880
    )
    {
        $imp_imap = IMP_Mailbox::get($mbox)->imp_imap;

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->size();

        try {
            $res = $imp_imap->fetch($mbox, $query, array(
                'ids' => $ids,
                'nocache' => true
            ));
        } catch (IMP_Imap_Exception $e) {
            return array();
        }

        $curr = $slices = array();
        $curr_size = 0;

        foreach ($res as $key => $val) {
            $curr_size += $val->getSize();
            if ($curr_size > $size) {
                $slices[] = $imp_imap->getIdsOb($curr, $ids->sequence);
                $curr = array();
            }
            $curr[] = $key;
        }

        $slices[] = $imp_imap->getIdsOb($curr, $ids->sequence);

        return $slices;
    }

    /**
     * Handle statusMultiple() calls. This call may hit multiple servers, so
     * need to handle separately from other IMAP calls.
     *
     * @see Horde_Imap_Client_Base#statusMultiple()
     */
    public function statusMultiple()
    {
        global $injector;

        $args = func_get_args();
        $imap_factory = $injector->getInstance('IMP_Factory_Imap');
        $accounts = $mboxes = $out = array();

        foreach (IMP_Mailbox::get($args[0]) as $val) {
            if ($raccount = $val->remote_account) {
                $accounts[strval($raccount)] = $raccount;
            }
            $mboxes[strval($raccount)][] = $val;
        }

        foreach ($mboxes as $key => $val) {
            $imap = $imap_factory->create($key);
            if ($imap->init) {
                $tmp = $args;
                $tmp[0] = IMP_Mailbox::getImapMboxOb($val);

                foreach ($imap->__call('statusMultiple', $tmp) as $key2 => $val2) {
                    $out[isset($accounts[$key]) ? $accounts[$key]->mailbox($key2) : $key2] = $val2;
                }
            }
        }

        return $out;
    }

    /**
     * All other calls to this class are routed to the underlying
     * Horde_Imap_Client_Base object.
     *
     * @param string $method  Method name.
     * @param array $params   Method parameters.
     *
     * @return mixed  The return from the requested method.
     * @throws BadMethodCallException
     * @throws IMP_Imap_Exception
     */
    public function __call($method, $params)
    {
        if (!$this->init) {
            /* Fallback for these methods. */
            switch ($method) {
            case 'getIdsOb':
                $ob = new Horde_Imap_Client_Ids();
                call_user_func_array(array($ob, 'add'), $params);
                return $ob;
            }

            throw new Horde_Exception_AuthenticationFailure('IMP is marked as authenticated, but no credentials can be found in the session.', Horde_Auth::REASON_SESSION);
        }

        if (!method_exists($this->_ob, $method)) {
            throw new BadMethodCallException(sprintf('%s: Invalid method call "%s".', __CLASS__, $method));
        }

        switch ($method) {
        case 'append':
        case 'createMailbox':
        case 'deleteMailbox':
        case 'expunge':
        case 'fetch':
        case 'getACL':
        case 'getMetadata':
        case 'getMyACLRights':
        case 'getQuota':
        case 'getQuotaRoot':
        case 'getSyncToken':
        case 'setMetadata':
        case 'setQuota':
        case 'status':
        // case 'statusMultiple': (Handled in statusMultiple() command)
        case 'store':
        case 'subscribeMailbox':
        case 'sync':
        case 'thread':
            // Horde_Imap_Client_Mailbox: these calls all have the mailbox as
            // their first parameter.
            $params[0] = IMP_Mailbox::getImapMboxOb($params[0]);
            break;

        case 'copy':
        case 'renameMailbox':
            // These calls may hit multiple servers.
            $source = IMP_Mailbox::get($params[0]);
            $dest = IMP_Mailbox::get($params[1]);
            if ($source->remote_account != $dest->remote_account) {
                return call_user_func_array(array($this, '_' . $method), $params);
            }

            // Horde_Imap_Client_Mailbox: these calls all have the mailbox as
            // their first two parameters.
            $params[0] = $source->imap_mbox_ob;
            $params[1] = $dest->imap_mbox_ob;
            break;

        case 'openMailbox':
            if (IMP_Mailbox::get($params[0])->search) {
                /* Can't open a search mailbox. */
                return;
            }
            $params[0] = IMP_Mailbox::getImapMboxOb($params[0]);
            break;

        case 'search':
            $params = call_user_func_array(array($this, '_search'), $params);
            break;
        }

        try {
            $result = call_user_func_array(array($this->_ob, $method), $params);
        } catch (Horde_Imap_Client_Exception $e) {
            $error = new IMP_Imap_Exception($e);
            if ($auth_e = $error->authException(false)) {
                throw $auth_e;
            }

            Horde::log($error);
            throw $error;
        }

        /* Special handling for various methods. */
        switch ($method) {
        case 'createMailbox':
        case 'renameMailbox':
            // Mailbox is first parameter.
            IMP_Mailbox::get($params[0])->expire();
            break;

        case 'login':
            if (!$this->_ob->getParam('imp:login')) {
                /* Check for POP3 UIDL support. */
                if (!$this->isImap() &&
                    !$this->queryCapability('UIDL')) {
                    $error = new IMP_Imap_Exception('The POP3 server does not support the REQUIRED UIDL capability.');
                    Horde::log($error);
                    throw $error;
                }

                $this->_ob->setParam('imp:login', true);
                $this->_changed = true;
            }
            break;

        case 'setACL':
            IMP_Mailbox::get($params[0])->expire(IMP_Mailbox::CACHE_ACL);
            break;
        }

        return $result;
    }

    /**
     * Prepares an IMAP search query.  Needed because certain configuration
     * parameters may need to be dynamically altered before passed to the
     * Imap_Client object.
     *
     * @param string $mailbox                        The mailbox to search.
     * @param Horde_Imap_Client_Search_Query $query  The search query object.
     * @param array $opts                            Additional options.
     *
     * @return array  Parameters to use in the search() call.
     */
    protected function _search($mailbox, $query = null, array $opts = array())
    {
        $mailbox = IMP_Mailbox::get($mailbox);

        if (!empty($opts['sort']) && $mailbox->access_sort) {
            /* If doing a from/to search, use display sorting if possible.
             * Although there is a fallback to a PHP-based display sort, for
             * performance reasons only do a display sort if it is supported
             * on the server. */
            foreach ($opts['sort'] as $key => $val) {
                switch ($val) {
                case Horde_Imap_Client::SORT_FROM:
                    $opts['sort'][$key] = Horde_Imap_Client::SORT_DISPLAYFROM_FALLBACK;
                    break;

                case Horde_Imap_Client::SORT_TO:
                    $opts['sort'][$key] = Horde_Imap_Client::SORT_DISPLAYTO_FALLBACK;
                    break;
                }
            }
        }

        if (!is_null($query)) {
            $query->charset('UTF-8', false);
        }

        return array($mailbox->imap_mbox_ob, $query, $opts);
    }

    /**
     * Handle copy() calls that hit multiple servers.
     *
     * @see Horde_Imap_Client_Base#copy()
     */
    protected function _copy()
    {
        global $injector;

        $args = func_get_args();
        $imap_factory = $injector->getInstance('IMP_Factory_Imap');
        $source_imap = $imap_factory->create($args[0]);
        $dest_imap = $imap_factory->create($args[1]);

        $create = !empty($args[2]['create']);
        $ids = isset($args[2]['ids'])
            ? $args[2]['ids']
            : $source_imap->getIdsOb(Horde_Imap_Client_Ids::ALL);
        $move = !empty($args[2]['move']);
        $retval = true;

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->fullText(array(
            'peek' => true
        ));

        foreach ($this->getSlices($args[0], $ids) as $val) {
            try {
                $res = $source_imap->fetch($args[0], $query, array(
                    'ids' => $val,
                    'nocache' => true
                ));

                $append = array();
                foreach ($res as $msg) {
                    $append[] = array(
                        'data' => $msg->getFullMsg(true)
                    );
                }

                $dest_imap->append($args[1], $append, array(
                    'create' => $create
                ));

                if ($move) {
                    $source_imap->expunge($args[0], array(
                        'delete' => true,
                        'ids' => $val
                    ));
                }
            } catch (IMP_Imap_Exception $e) {
                $retval = false;
            }
        }

        return $retval;
    }

    /**
     * Handle copy() calls. This call may hit multiple servers, so
     * need to handle separately from other IMAP calls.
     *
     * @see Horde_Imap_Client_Base#renameMailbox()
     */
    protected function _renameMailbox()
    {
        $args = func_get_args();
        $source = IMP_Mailbox::get($args[0]);

        if ($source->create() && $this->copy($source, $args[1])) {
            $source->delete();
        } else {
            throw new IMP_Imap_Exception(_("Could not move all messages between mailboxes, so the original mailbox was not removed."));
        }
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
        if (empty(self::$_backends)) {
            try {
                $s = Horde::loadConfiguration('backends.php', 'servers', 'imp');
                if (is_null($s)) {
                    return false;
                }
            } catch (Horde_Exception $e) {
                Horde::log($e, 'ERR');
                return false;
            }

            foreach ($s as $key => $val) {
                if (empty($val['disabled'])) {
                    self::$_backends[$key] = new IMP_Imap_Config($val);
                }
            }
        }

        return is_null($server)
            ? self::$_backends
            : (isset(self::$_backends[$server]) ? self::$_backends[$server] : false);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return $GLOBALS['injector']->getInstance('Horde_Pack')->pack(
            array(
                $this->_ob,
                $this->_id,
                $this->_config
            ),
            array(
                'compression' => false,
                'phpob' => true
            )
        );
    }

    /**
     */
    public function unserialize($data)
    {
        list(
            $this->_ob,
            $this->_id,
            $this->_config
        ) = $GLOBALS['injector']->getInstance('Horde_Pack')->unpack($data);
    }

}
