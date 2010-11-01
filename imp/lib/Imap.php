<?php
/**
 * The IMP_Imap:: class provides common functions for interaction with
 * IMAP/POP3 servers via the Horde_Imap_Client:: library.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Imap
{
    /**
     * The Horde_Imap_Client object.
     *
     * @var Horde_Imap_Client
     */
    public $ob = null;

    /**
     * Server key for this instance.
     *
     * @var string
     */
    protected $_serverkey = '';

    /**
     * Is connection read-only?
     *
     * @var array
     */
    protected $_readonly = array();

    /**
     * Default namespace.
     *
     * @var array
     */
    protected $_nsdefault;

    /**
     * UIDVALIDITY check cache.
     *
     * @var array
     */
    protected $_uidvalid = array();

    /**
     * Constructor.
     *
     * @param string $serverkey  Server key for this instance.
     *
     * @throws IMP_Exception
     */
    public function __construct($serverkey)
    {
        $this->_serverkey = $serverkey;

        /* Rebuild the Horde_Imap_Client object. */
        if (!$this->_loadImapObject()) {
            register_shutdown_function(array($this, 'shutdown'));
        }
    }

    /**
     * Save the Horde_Imap_Client object on session shutdown.
     */
    public function shutdown()
    {
        /* Only need to serialize object once a session. */
        if ($this->ob) {
            $GLOBALS['session']['imp:imap_ob/' . $this->_serverkey] = $this->ob;
        }
    }

    /**
     * Loads the Horde_Imap_Client object from serialized session data.
     *
     * @return boolean  True on success, false on error.
     * @throws IMP_Exception
     */
    protected function _loadImapObject()
    {
        global $session;

        if (!is_null($this->ob)) {
            return true;
        }

        try {
            if (!($this->ob = $GLOBALS['session']['imp:imap_ob/' . $this->_serverkey])) {
                return false;
            }
        } catch (Exception $e) {
            /* Throw fatal error here - should never reach here and if we
             * do, we are out of luck. */
            throw new IMP_Exception(_("Could not acquire mail server credentials from the session."));
        }

        $this->_postcreate($session['imp:protocol']);

        return true;
    }

    /**
     * Create a new Horde_Imap_Client object.
     *
     * @param string $username  The username to authenticate with.
     * @param string $password  The password to authenticate with.
     * @param string $key       Create a new object using this server key.
     *
     * @return boolean  The object on success, false on error.
     */
    public function createImapObject($username, $password, $key)
    {
        if (!is_null($this->ob)) {
            return $this->ob;
        }

        if (($server = $this->loadServerConfig($key)) === false) {
            return false;
        }

        $protocol = isset($server['protocol'])
            ? strtolower($server['protocol'])
            : 'imap';

        $imap_config = array(
            'capability_ignore' => empty($server['capability_ignore']) ? array() : $server['capability_ignore'],
            'comparator' => empty($server['comparator']) ? false : $server['comparator'],
            'debug' => isset($server['debug']) ? $server['debug'] : null,
            'encryptKey' => array(__CLASS__, 'getEncryptKey'),
            'hostspec' => isset($server['hostspec']) ? $server['hostspec'] : null,
            'id' => empty($server['id']) ? false : $server['id'],
            'lang' => empty($server['lang']) ? false : $server['lang'],
            'log' => array(__CLASS__, 'logError'),
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
            return false;
        }

        $this->ob = $ob;
        $this->_postcreate($protocol);

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
     * Alter some IMP settings once we load/create the object.
     *
     * @param string $protocol  The protocol used to connect.
     */
    protected function _postcreate($protocol)
    {
        global $conf, $prefs;

        switch ($protocol) {
        case 'pop':
            /* Turn some options off if we are working with POP3. */
            $conf['user']['allow_folders'] = false;
            $prefs->setValue('save_sent_mail', false);
            $prefs->setLocked('save_sent_mail', true);
            $prefs->setLocked('sent_mail_folder', true);
            $prefs->setLocked('drafts_folder', true);
            $prefs->setLocked('trash_folder', true);
            break;
        }
    }

    /**
     * Is the given mailbox read-only?
     *
     * @param string $mailbox  The mailbox to check.
     *
     * @return boolean  Is the mailbox read-only?
     * @throws Horde_Exception
     */
    public function isReadOnly($mailbox)
    {
        if (!isset($this->_readonly[$mailbox])) {
            $res = false;

            /* These tests work on both regular and search mailboxes. */
            try {
                $res = Horde::callHook('mbox_readonly', array($mailbox), 'imp');
            } catch (Horde_Exception_HookNotSet $e) {}

            /* This check can only be done for regular IMAP mailboxes. */
            // TODO: POP3 also?
            if (!$res &&
                ($GLOBALS['session']['imp:protocol'] == 'imap') &&
                !$GLOBALS['injector']->getInstance('IMP_Search')->isSearchMbox($mailbox)) {
                try {
                    $status = $this->ob->status($mailbox, Horde_Imap_Client::STATUS_UIDNOTSTICKY);
                    $res = $status['uidnotsticky'];
                } catch (Horde_Imap_Client_Exception $e) {}
            }

            $this->_readonly[$mailbox] = $res;
        }

        return $this->_readonly[$mailbox];
    }

    /**
     * Do a UIDVALIDITY check - needed if UIDs are passed between page
     * accesses.
     *
     * @param string $mailbox  The mailbox to check. Must be an IMAP mailbox.
     *
     * @return string  The mailbox UIDVALIDITY.
     * @throws IMP_Exception
     */
    public function checkUidvalidity($mailbox)
    {
        global $session;

        // TODO: POP3 also?
        if ($session['imp:protocol'] == 'pop') {
            return;
        }

        if (!isset($this->_uidvalid[$mailbox])) {
            $status = $this->ob->status($mailbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
            $val = isset($session['imp:uidvalid/' . $mailbox])
                ? $session['imp:uidvalid/' . $mailbox]
                : null;
            $session['imp:uidvalid/' . $mailbox] = $status['uidvalidity'];

            $this->_uidvalid[$mailbox] = (!is_null($val) && ($status['uidvalidity'] != $val));
        }

        if ($this->_uidvalid[$mailbox]) {
            throw new IMP_Exception(_("Mailbox structure on server has changed."));
        }

        return $session['imp:uidvalid/' . $mailbox];
    }

    /**
     * Get the namespace list.
     *
     * @return array  An array of namespace information.
     */
    public function getNamespaceList()
    {
        try {
            return $this->ob->getNamespaces($GLOBALS['session']['imp:imap_namespace;array']);
        } catch (Horde_Imap_Client_Exception $e) {
            // @todo Error handling
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
        if ($GLOBALS['session']['imp:protocol'] == 'pop') {
            return null;
        }

        $ns = $this->getNamespaceList();

        if ($mailbox === null) {
            reset($ns);
            $mailbox = key($ns);
        }

        foreach ($ns as $key => $val) {
            $mbox = $mailbox . $val['delimiter'];
            if (!empty($key) && (strpos($mbox, $key) === 0)) {
                return $val;
            }
        }

        return (isset($ns['']) && (!$personal || ($val['type'] == 'personal')))
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
        if ($GLOBALS['session']['imp:protocol'] == 'pop') {
            return null;
        }

        if (!isset($this->_nsdefault)) {
            $this->_nsdefault = null;
            foreach ($this->getNamespaceList() as $val) {
                if ($val['type'] == 'personal') {
                    $this->_nsdefault = $val;
                    break;
                }
            }
        }

        return $this->_nsdefault;
    }

    /**
     * Make sure a user-entered mailbox contains namespace information.
     *
     * @param string $mbox  The user-entered mailbox string.
     *
     * @return string  The mailbox string with any necessary namespace info
     *                 added.
     */
    public function appendNamespace($mbox)
    {
        $ns_info = $this->getNamespace($mbox);
        if (is_null($ns_info)) {
            $ns_info = $this->defaultNamespace();
        }
        return $ns_info['name'] . $mbox;
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
     */
    public function __call($method, $params)
    {
        if (!$this->ob || !method_exists($this->ob, $method)) {
            throw new BadMethodCallException(sprintf('%s: Invalid method call "%s".', __CLASS__, $method));
        }

        return call_user_func_array(array($this->ob, $method), $params);
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
        try {
            $servers = Horde::loadConfiguration('backends.php', 'servers', 'imp');
            if ($servers === null) {
                $servers = false;
            }
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return false;
        }

        if (is_null($server)) {
            /* Remove any prompt entries (underscores in front of key). */
            foreach (array_keys($servers) as $key) {
                if ($key[0] == '_') {
                    unset($servers[$key]);
                }
            }
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

    public function getEncryptKey()
    {
        return $GLOBALS['injector']->getInstance('Horde_Secret')->getKey('imp');
    }

    public function logError($e)
    {
        Horde::logMessage($e, 'ERR');
    }

}
