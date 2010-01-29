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
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Imap
{
    /**
     * The Horde_Imap_Client object.
     *
     * @var Horde_Imap_Client
     */
    protected $_ob = null;

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
     */
    public function __construct()
    {
        /* Register the logging callback. */
        Horde_Imap_Client_Exception::$logCallback = array($this, 'logException');

        /* Rebuild the Horde_Imap_Client object. */
        $this->_loadImapObject();

        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Save the Horde_Imap_Client object on session shutdown.
     */
    public function shutdown()
    {
        /* Only need to serialize object once a session. When we do
         * serialize, make sure we login in order to ensure we have done the
         * necessary initialization. */
        if ($this->_ob &&
            isset($_SESSION['imp']) &&
            empty($_SESSION['imp']['imap_ob'])) {
            $this->_ob->login();

            /* First login may occur on a non-viewable page. However,
             * any login alerts received should be displayed to the user at
             * some point. We need to do an explicit grab of the alarms
             * right now. */
            $notification = Horde_Notification::singleton();
            foreach ($this->_ob->alerts() as $alert) {
                $notification->push($alert, 'horde.warning');
            }

            $_SESSION['imp']['imap_ob'][$_SESSION['imp']['server_key']] = serialize($this->_ob);
        }
    }

    /**
     * Loads the IMP server configuration from servers.php.
     *
     * @param string $server  Returns this labeled entry only.
     *
     * @return mixed  If $server is set, then return this entry, or return the
     *                entire servers array. Returns false on error.
     */
    static public function loadServerConfig($server = null)
    {
        try {
            $servers = Horde::loadConfiguration('servers.php', 'servers', 'imp');
            if ($servers === null) {
                $servers = false;
            }
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }

        if (is_null($server)) {
            return $servers;
        }

        /* Check for the existence of the server in the config file. */
        if (empty($servers[$server]) || !is_array($servers[$server])) {
            $entry = sprintf('Invalid server key "%s" from client [%s]', $server, $_SERVER['REMOTE_ADDR']);
            Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }

        return $servers[$server];
    }

    /**
     * Loads the Horde_Imap_Client object from serialized session data.
     *
     * @return boolean  True on success, false on error.
     */
    protected function _loadImapObject()
    {
        if (!is_null($this->_ob)) {
            return true;
        }

        if (empty($_SESSION['imp']) ||
            empty($_SESSION['imp']['server_key']) ||
            empty($_SESSION['imp']['imap_ob'][$_SESSION['imp']['server_key']])) {
            return false;
        }

        Horde_Imap_Client::$encryptKey = Horde_Secret::getKey('imp');

        $old_error = error_reporting(0);
        $this->_ob = unserialize($_SESSION['imp']['imap_ob'][$_SESSION['imp']['server_key']]);
        error_reporting($old_error);

        if (empty($this->_ob)) {
            // @todo How to handle bad unserialize?
            // @todo Log message
            return false;
        }

        $this->_postcreate($_SESSION['imp']['protocol']);

        return true;
    }

    /**
     * Create a new Horde_Imap_Client object.
     *
     * @param string $username  The username to authenticate with.
     * @param string $password  The password to authenticate with.
     * @param string $key       Create a new object using this server key.
     * @param boolean $global   If true, treate the created object as the IMP
     *                          global IMAP object.
     *
     * @return boolean  The object on success, false on error.
     */
    public function createImapObject($username, $password, $key,
                                     $global = true)
    {
        if ($global && !is_null($this->_ob)) {
            return $GLOBALS['imp_imap'];
        }

        if (($server = $this->loadServerConfig($key)) === false) {
            return false;
        }

        $protocol = isset($server['protocol'])
            ? strtolower($server['protocol'])
            : 'imap';

        $imap_config = array(
            'comparator' => empty($server['comparator']) ? false : $server['comparator'],
            'debug' => isset($server['debug']) ? $server['debug'] : null,
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
            $c = $server['cache'];
            $driver = $GLOBALS['conf']['cache']['driver'];
            if ($driver != 'none') {
                $imap_config['cache'] = array(
                    'compress' => empty($c['compress']) ? false : $c['compress'],
                    'driver' => $driver,
                    'driver_params' => Horde::getDriverConfig('cache', $driver),
                    'lifetime' => empty($c['lifetime']) ? false : $c['lifetime'],
                    'slicesize' => empty($c['slicesize']) ? false : $c['slicesize'],
                );
            }
        }

        try {
            $ob = Horde_Imap_Client::factory(($protocol == 'imap') ? 'Socket' : 'Socket_Pop3', $imap_config);
        } catch (Horde_Imap_Client_Exception $e) {
            return false;
        }

        if ($global) {
            $this->_ob = $ob;
            $this->_postcreate($protocol);
        }

        return $ob;
    }

    /**
     * Alter some IMP settings once we load/create the global object.
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
                ($_SESSION['imp']['protocol'] == 'imap') &&
                !$GLOBALS['imp_search']->isSearchMbox($mailbox)) {
                try {
                    $status = $this->_ob->status($mailbox, Horde_Imap_Client::STATUS_UIDNOTSTICKY);
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
     * @throws Horde_Exception
     */
    public function checkUidvalidity($mailbox)
    {
        // TODO: POP3 also?
        if ($_SESSION['imp']['protocol'] == 'pop') {
            return;
        }

        if (!isset($this->_uidvalid[$mailbox])) {
            $status = $this->_ob->status($mailbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
            $ptr = &$_SESSION['imp']['cache'];
            $val = isset($ptr['uidvalid'][$mailbox])
                ? $ptr['uidvalid'][$mailbox]
                : null;
            $ptr['uidvalid'][$mailbox] = $status['uidvalidity'];

            $this->_uidvalid[$mailbox] = (!is_null($val) && ($status['uidvalidity'] != $val));
        }

        if ($this->_uidvalid[$mailbox]) {
            throw new Horde_Exception(_("Mailbox structure on server has changed."));
        }

        return $_SESSION['imp']['cache']['uidvalid'][$mailbox];
    }

    /**
     * Logs an exception from Horde_Imap_Client.
     *
     * @param object Horde_Imap_Client_Exception $e  The exception object.
     */
    public function logException($e)
    {
        Horde::logMessage($e, $e->getFile(), $e->getLine(), PEAR_LOG_ERR);
    }

    /**
     * Get the namespace list.
     *
     * @return array  An array of namespace information.
     */
    public function getNamespaceList()
    {
        try {
            return $this->_ob->getNamespaces(!empty($_SESSION['imp']['imap']['namespace']) ? $_SESSION['imp']['imap']['namespace'] : array());
        } catch (Horde_Imap_Client_Exception $e) {
            // @todo Error handling
            return array();
        }
    }

    /**
     * Get namespace info for a full folder path.
     *
     * @param string $mailbox  The folder path. If empty, will return info
     *                         on the default namespace (i.e. the first
     *                         personal namespace).
     *
     * @return mixed  The namespace info for the folder path or null if the
     *                path doesn't exist.
     */
    public function getNamespace($mailbox = null)
    {
        if ($_SESSION['imp']['protocol'] == 'pop') {
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

        return isset($ns['']) ? $ns[''] : null;
    }

    /**
     * Get the default personal namespace.
     *
     * @return mixed  The default personal namespace info.
     */
    public function defaultNamespace()
    {
        if ($_SESSION['imp']['protocol'] == 'pop') {
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
     * Return the Horde_Imap_Client object.
     *
     * @return Horde_Imap_Client  The imap object.
     */
    public function ob()
    {
        return $this->_ob;
    }

}
