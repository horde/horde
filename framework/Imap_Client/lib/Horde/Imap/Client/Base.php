<?php
/**
 * Horde_Imap_Client_Base:: provides an abstracted API interface to various
 * IMAP backends supporting the IMAP4rev1 protocol (RFC 3501).
 *
 * Required/Optional Parameters: See Horde_Imap_Client::.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 */
abstract class Horde_Imap_Client_Base
{
    /**
     * The Horde_Imap_Client_Utils object
     *
     * @var Horde_Imap_Client_Utils
     */
    public $utils;

    /**
     * The Horde_Imap_Client_Cache object.
     *
     * @var Horde_Imap_Client_Cache
     */
    public $cache = null;

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Is there an active authenticated connection to the IMAP Server?
     *
     * @var boolean
     */
    protected $_isAuthenticated = false;

    /**
     * Is there a secure connection to the IMAP Server?
     *
     * @var boolean
     */
    protected $_isSecure = false;

    /**
     * The currently selected mailbox.
     *
     * @var string
     */
    protected $_selected = null;

    /**
     * The current mailbox selection mode.
     *
     * @var integer
     */
    protected $_mode = 0;

    /**
     * Server data that will be cached when serialized.
     *
     * @var array
     */
    protected $_init = array(
        'enabled' => array(),
        'namespace' => array(),
        's_charset' => array()
    );

    /**
     * The debug stream.
     *
     * @var resource
     */
    protected $_debug = null;

    /**
     * Temp array (destroyed at end of process).
     *
     * @var array
     */
    protected $_temp = array();

    /**
     * Constructs a new Horde_Imap_Client object.
     *
     * @param array $params  A hash containing configuration parameters.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function __construct($params = array())
    {
        if (!isset($params['username']) || !isset($params['password'])) {
            throw new Horde_Imap_Client_Exception('Horde_Imap_Client requires a username and password.');
        }

        // Default values.
        if (empty($params['hostspec'])) {
            $params['hostspec'] = 'localhost';
        }

        if (empty($params['port'])) {
            $params['port'] = (isset($params['secure']) && ($params['secure'] == 'ssl')) ? 993 : 143;
        }

        if (empty($params['timeout'])) {
            $params['timeout'] = 30;
        }

        if (empty($params['cache'])) {
            $params['cache'] = array('fields' => array());
        } elseif (empty($params['cache']['fields'])) {
            $params['cache']['fields'] = array(
                Horde_Imap_Client::FETCH_STRUCTURE => 1,
                Horde_Imap_Client::FETCH_ENVELOPE => 1,
                Horde_Imap_Client::FETCH_FLAGS => 1,
                Horde_Imap_Client::FETCH_DATE => 1,
                Horde_Imap_Client::FETCH_SIZE => 1,
                Horde_Imap_Client::FETCH_HEADERS => 1
            );
        } else {
            $params['cache']['fields'] = array_flip($params['cache']['fields']);
        }

        $this->_params = $params;

        $this->utils = new Horde_Imap_Client_Utils();

        // This will initialize debugging, if needed.
        $this->__wakeup();
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->_closeDebug();
    }

    /**
     * Do cleanup prior to serialization.
     */
    public function __sleep()
    {
        $this->_closeDebug();

        // Don't store Horde_Imap_Client_Cache object or temp data.
        $this->cache = null;
        $this->_temp = array();

        // Encrypt password in serialized object.
        if (!isset($this->_params['_passencrypt'])) {
            $key = Horde_Imap_Client::$encryptKey;
            if (!is_null($key)) {
                $secret = new Horde_Secret();
                $this->_params['_passencrypt'] = $secret->write($key, $this->_params['password']);
                $this->_params['password'] = null;
            }
        }
    }

    /**
     * Do re-initialization on unserialize().
     */
    public function __wakeup()
    {
        if (isset($this->_params['_passencrypt']) &&
            !is_null(Horde_Imap_Client::$encryptKey)) {
            $secret = new Horde_Secret();
            $this->_params['password'] = $secret->read(Horde_Imap_Client::$encryptKey, $this->_params['_passencrypt']);
        }

        if (!empty($this->_params['debug'])) {
            $this->_debug = @fopen($this->_params['debug'], 'a');
        }
    }

    /**
     * Close debugging output.
     */
    protected function _closeDebug()
    {
        if (is_resource($this->_debug)) {
            fflush($this->_debug);
            fclose($this->_debug);
            $this->_debug = null;
        }
    }

    /**
     * Initialize the Horde_Imap_Client_Cache object, if necessary.
     *
     * @param boolean $current  If true, we are going to update the currently
     *                          selected mailbox. Add an additional check to
     *                          see if caching is available in current
     *                          mailbox.
     *
     * @return boolean  Returns true if caching is enabled.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _initCache($current = false)
    {
        if (empty($this->_params['cache']['fields']) ||
            !empty($this->_temp['nocache'])) {
            return false;
        }

        if (is_null($this->cache)) {
            $p = $this->_params;
            $this->cache = Horde_Imap_Client_Cache::singleton(array_merge($p['cache'], array(
                'debug' => $this->_debug,
                'hostspec' => $p['hostspec'],
                'port' => $p['port'],
                'username' => $p['username']
            )));
        }

        if (!$current) {
            return true;
        }

        /* If UIDs are labeled as not sticky, don't cache since UIDs will
         * change on every access. */
        $status = $this->status($this->_selected, Horde_Imap_Client::STATUS_UIDNOTSTICKY);
        return !$status['uidnotsticky'];
    }

    /**
     * Returns a value from the internal params array.
     *
     * @param string $key  The param key.
     *
     * @return mixed  The param value, or null if not found.
     */
    public function getParam($key)
    {
        return isset($this->_params[$key]) ? $this->_params[$key] : null;
    }

    /**
     * Returns the Horde_Imap_Client_Cache object used, if available.
     *
     * @return mixed  Either the object or null.
     */
    public function getCache()
    {
        $this->_initCache();
        return $this->cache;
    }

    /**
     * Returns whether the IMAP server supports the given capability
     * (See RFC 3501 [6.1.1]).
     *
     * @param string $capability  The capability string to query.
     *
     * @param mixed  True if the server supports the queried capability,
     *               false if it doesn't, or an array if the capability can
     *               contain multiple values.
     */
    public function queryCapability($capability)
    {
        if (!isset($this->_init['capability'])) {
            try {
                $this->capability();
            } catch (Horde_Imap_Client_Exception $e) {
                return false;
            }
        }
        $capability = strtoupper($capability);
        return isset($this->_init['capability'][$capability])
            ? $this->_init['capability'][$capability]
            : false;
    }

    /**
     * Get CAPABILITY information from the IMAP server.
     *
     * @return array  The capability array.
     * @throws Horde_Imap_Client_Exception
     */
    public function capability()
    {
        if (!isset($this->_init['capability'])) {
            $this->_init['capability'] = $this->_capability();

            if (!empty($this->_params['capability_ignore'])) {
                $this->_init['capability'] = array_diff_key($this->_init['capability'], array_flip($this->_params['capability_ignore']));
            }
        }

        return $this->_init['capability'];
    }

    /**
     * Get CAPABILITY information from the IMAP server.
     *
     * @return array  The capability array.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _capability();

    /**
     * Send a NOOP command (RFC 3501 [6.1.2]).
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function noop()
    {
        // NOOP only useful if we are already authenticated.
        if ($this->_isAuthenticated) {
            $this->_noop();
        }
    }

    /**
     * Send a NOOP command.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _noop();

    /**
     * Get the NAMESPACE information from the IMAP server (RFC 2342).
     *
     * @param array $additional  If the server supports namespaces, any
     *                           additional namespaces to add to the
     *                           namespace list that are not broadcast by
     *                           the server. The namespaces must either be in
     *                           UTF7-IMAP or UTF-8.
     *
     * @return array  An array of namespace information with the name as the
     *                key and the following values:
     * <pre>
     * 'delimiter' - (string) The namespace delimiter.
     * 'hidden' - (boolean) Is this a hidden namespace?
     * 'name' - (string) The namespace name.
     * 'translation' - OPTIONAL (string) This entry only present if the IMAP
     *                 server supports RFC 5255 and the language has previous
     *                 been set via setLanguage(). The translation will be in
     *                 UTF7-IMAP.
     * 'type' - (string) The namespace type (either 'personal', 'other' or
     *          'shared').
     * </pre>
     * @throws Horde_Imap_Client_Exception
     */
    public function getNamespaces($additional = array())
    {
        $additional = array_map(array('Horde_Imap_Client_Utf7imap', 'Utf7ImapToUtf8'), $additional);

        $sig = hash('md5', serialize($additional));

        if (isset($this->_init['namespace'][$sig])) {
            return $this->_init['namespace'][$sig];
        }

        $ns = $this->_getNamespaces();

        foreach ($additional as $val) {
            /* Skip namespaces if we have already auto-detected them. Also,
             * hidden namespaces cannot be empty. */
            $val = trim($val);
            if (empty($val) || isset($ns[$val])) {
                continue;
            }

            $mbox = $this->listMailboxes($val, Horde_Imap_Client::MBOX_ALL, array('delimiter' => true));
            $first = reset($mbox);

            if ($first && ($first['mailbox'] == $val)) {
                $ns[$val] = array(
                    'name' => $val,
                    'delimiter' => $first['delimiter'],
                    'type' => 'shared',
                    'hidden' => true
                );
            }
        }

        if (empty($ns)) {
            /* This accurately determines the namespace information of the
             * base namespace if the NAMESPACE command is not supported.
             * See: RFC 3501 [6.3.8] */
            $mbox = $this->listMailboxes('', Horde_Imap_Client::MBOX_ALL, array('delimiter' => true));
            $first = reset($mbox);
            $ns[''] = array(
                'name' => '',
                'delimiter' => $first['delimiter'],
                'type' => 'personal',
                'hidden' => false
            );
        }

        $this->_init['namespace'][$sig] = $ns;

        return $ns;
    }

    /**
     * Get the NAMESPACE information from the IMAP server.
     *
     * @return array  An array of namespace information.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getNamespaces();

    /**
     * Display if connection to the server has been secured via TLS or SSL.
     *
     * @return boolean  True if the IMAP connection is secured.
     */
    public function isSecureConnection()
    {
        return $this->_isSecure;
    }

    /**
     * Return a list of alerts that MUST be presented to the user (RFC 3501
     * [7.1]).
     *
     * @return array  An array of alert messages.
     */
    abstract public function alerts();

    /**
     * Login to the IMAP server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function login()
    {
        if ($this->_isAuthenticated) {
            return;
        }

        if ($this->_login()) {
            if (!empty($this->_params['id'])) {
                try {
                    $this->sendID();
                } catch (Horde_Imap_Client_Exception $e) {
                    // Ignore if server doesn't support ID
                    if ($e->getCode() != Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT) {
                        throw $e;
                    }
                }
            }

            if (!empty($this->_params['comparator'])) {
                try {
                    $this->setComparator();
                } catch (Horde_Imap_Client_Exception $e) {
                    // Ignore if server doesn't support I18NLEVEL=2
                    if ($e->getCode() != Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT) {
                        throw $e;
                    }
                }
            }

            /* Check for ability to cache flags here. */
            if (isset($this->_params['cache']['fields'][Horde_Imap_Client::FETCH_FLAGS]) &&
                !isset($this->_init['enabled']['CONDSTORE'])) {
                unset($this->_params['cache']['fields'][Horde_Imap_Client::FETCH_FLAGS]);
            }
        }

        $this->_isAuthenticated = true;
    }

    /**
     * Login to the IMAP server.
     *
     * @return boolean  Return true if global login tasks should be run.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _login();

    /**
     * Logout from the IMAP server (see RFC 3501 [6.1.3]).
     */
    public function logout()
    {
        $this->_logout();
        $this->_isAuthenticated = false;
        $this->_selected = null;
        $this->_mode = 0;
    }

    /**
     * Logout from the IMAP server (see RFC 3501 [6.1.3]).
     */
    abstract protected function _logout();

    /**
     * Send ID information to the IMAP server (RFC 2971).
     *
     * @param array $info  Overrides the value of the 'id' param and sends
     *                     this information instead.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function sendID($info = null)
    {
        if (!$this->queryCapability('ID')) {
            throw new Horde_Imap_Client_Exception('The IMAP server does not support the ID extension.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
        }

        $this->_sendID(is_null($info) ? (empty($this->_params['id']) ? array() : $this->_params['id']) : $info);
    }

    /**
     * Send ID information to the IMAP server (RFC 2971).
     *
     * @param array $info  The information to send to the server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _sendID($info);

    /**
     * Return ID information from the IMAP server (RFC 2971).
     *
     * @return array  An array of information returned, with the keys as the
     *                'field' and the values as the 'value'.
     * @throws Horde_Imap_Client_Exception
     */
    public function getID()
    {
        if (!$this->queryCapability('ID')) {
            throw new Horde_Imap_Client_Exception('The IMAP server does not support the ID extension.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
        }

        return $this->_getID();
    }

    /**
     * Return ID information from the IMAP server (RFC 2971).
     *
     * @return array  An array of information returned, with the keys as the
     *                'field' and the values as the 'value'.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getID();

    /**
     * Sets the preferred language for server response messages (RFC 5255).
     *
     * @param array $info  Overrides the value of the 'lang' param and sends
     *                     this list of preferred languages instead. The
     *                     special string 'i-default' can be used to restore
     *                     the language to the server default.
     *
     * @return string  The language accepted by the server, or null if the
     *                 default language is used.
     * @throws Horde_Imap_Client_Exception
     */
    public function setLanguage($langs = null)
    {
        if (!$this->queryCapability('LANGUAGE')) {
            return null;
        }

        $lang = is_null($langs) ? (empty($this->_params['lang']) ? null : $this->_params['lang']) : $langs;
        if (is_null($lang)) {
            return null;
        }

        return $this->_setLanguage($lang);
    }

    /**
     * Sets the preferred language for server response messages (RFC 5255).
     *
     * @param array $info  The preferred list of languages.
     *
     * @return string  The language accepted by the server, or null if the
     *                 default language is used.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setLanguage($langs);

    /**
     * Gets the preferred language for server response messages (RFC 5255).
     *
     * @param array $list  If true, return the list of available languages.
     *
     * @return mixed  If $list is true, the list of languages available on the
     *                server (may be empty). If false, the language used by
     *                the server, or null if the default language is used.
     * @throws Horde_Imap_Client_Exception
     */
    public function getLanguage($list = false)
    {
        if (!$this->queryCapability('LANGUAGE')) {
            return $list ? array() : null;
        }

        return $this->_getLanguage($list);
    }

    /**
     * Gets the preferred language for server response messages (RFC 5255).
     *
     * @param array $list  If true, return the list of available languages.
     *
     * @return mixed  If $list is true, the list of languages available on the
     *                server (may be empty). If false, the language used by
     *                the server, or null if the default language is used.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getLanguage($list);

    /**
     * Open a mailbox.
     *
     * @param string $mailbox  The mailbox to open. Either in UTF7-IMAP or
     *                         UTF-8.
     * @param integer $mode    The access mode. Either
     *                         Horde_Imap_Client::OPEN_READONLY,
     *                         Horde_Imap_Client::OPEN_READWRITE, or
     *                         Horde_Imap_Client::OPEN_AUTO.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function openMailbox($mailbox, $mode = Horde_Imap_Client::OPEN_AUTO)
    {
        $change = false;

        $mailbox = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox);

        if ($mode == Horde_Imap_Client::OPEN_AUTO) {
            if (is_null($this->_selected) || ($this->_selected != $mailbox)) {
                $mode = Horde_Imap_Client::OPEN_READONLY;
                $change = true;
            }
        } elseif (is_null($this->_selected) ||
                  ($this->_selected != $mailbox) ||
                  ($mode != $this->_mode)) {
            $change = true;
        }

        if ($change) {
            $this->_openMailbox($mailbox, $mode);
            $this->_selected = $mailbox;
            $this->_mode = $mode;
            unset($this->_temp['statuscache'][$mailbox]);
        }
    }

    /**
     * Open a mailbox.
     *
     * @param string $mailbox  The mailbox to open (UTF7-IMAP).
     * @param integer $mode    The access mode.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _openMailbox($mailbox, $mode);

    /**
     * Return the currently opened mailbox and access mode.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'utf8' - (boolean) True if 'mailbox' should be in UTF-8.
     *          DEFAULT: 'mailbox' returned in UTF7-IMAP.
     * </pre>
     *
     * @return mixed  Either an array with two elements - 'mailbox' and
     *                'mode' - or null if no mailbox selected.
     * @throws Horde_Imap_Client_Exception
     */
    public function currentMailbox($options = array())
    {
        return is_null($this->_selected)
            ? null
            : array(
                'mailbox' => empty($options['utf8']) ? $this->_selected : Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($this->_selected),
                'mode' => $this->_mode
            );
    }

    /**
     * Create a mailbox.
     *
     * @param string $mailbox  The mailbox to create. Either in UTF7-IMAP or
     *                         UTF-8.
     * @param array $opts      Additional options:
     * <pre>
     * 'special_use' - (array) An array of special-use flags to mark the
     *                 mailbox with.  The server must support broadcast the
     *                 CREATE-SPECIAL-USE capability string.
     * </pre>
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function createMailbox($mailbox, array $opts = array())
    {
        if (!$this->queryCapability('CREATE-SPECIAL-USE')) {
            unset($opts['special_use']);
        }

        $this->_createMailbox(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox), $opts);
    }

    /**
     * Create a mailbox.
     *
     * @param string $mailbox  The mailbox to create (UTF7-IMAP).
     * @param array $opts      Additional options. See self::createMailbox().
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _createMailbox($mailbox, $opts);

    /**
     * Delete a mailbox.
     *
     * @param string $mailbox  The mailbox to delete. Either in UTF7-IMAP or
     *                         UTF-8.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function deleteMailbox($mailbox)
    {
        $mailbox = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox);

        $this->_deleteMailbox($mailbox);

        /* Delete mailbox caches. */
        if ($this->_initCache()) {
            $this->cache->deleteMailbox($mailbox);
        }
        unset($this->_temp['statuscache'][$mailbox]);

        /* Unsubscribe from mailbox. */
        try {
            $this->subscribeMailbox($mailbox, false);
        } catch (Horde_Imap_Client_Exception $e) {
            // Ignore failed unsubscribe request
        }
    }

    /**
     * Delete a mailbox.
     *
     * @param string $mailbox  The mailbox to delete (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _deleteMailbox($mailbox);

    /**
     * Rename a mailbox.
     *
     * @param string $old  The old mailbox name. Either in UTF7-IMAP or UTF-8.
     * @param string $new  The new mailbox name. Either in UTF7-IMAP or UTF-8.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function renameMailbox($old, $new)
    {
        $old = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($old);
        $new = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($new);

        /* Check if old mailbox was subscribed to. */
        $subscribed = $this->listMailboxes($old, Horde_Imap_Client::MBOX_SUBSCRIBED, array('flat' => true));

        $this->_renameMailbox($old, $new);

        /* Delete mailbox caches. */
        if ($this->_initCache()) {
            $this->cache->deleteMailbox($old);
        }
        unset($this->_temp['statuscache'][$old]);

        /* Clean up subscription information. */
        try {
            $this->subscribeMailbox($old, false);
            if (count($subscribed)) {
                $this->subscribeMailbox($new, true);
            }
        } catch (Horde_Imap_Client_Exception $e) {
            // Ignore failed unsubscribe request
        }
    }

    /**
     * Rename a mailbox.
     *
     * @param string $old     The old mailbox name (UTF7-IMAP).
     * @param string $new     The new mailbox name (UTF7-IMAP).
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _renameMailbox($old, $new);

    /**
     * Manage subscription status for a mailbox.
     *
     * @param string $mailbox     The mailbox to [un]subscribe to. Either in
     *                            UTF7-IMAP or UTF-8.
     * @param boolean $subscribe  True to subscribe, false to unsubscribe.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function subscribeMailbox($mailbox, $subscribe = true)
    {
        $this->_subscribeMailbox(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox), (bool)$subscribe);
    }

    /**
     * Manage subscription status for a mailbox.
     *
     * @param string $mailbox     The mailbox to [un]subscribe to (UTF7-IMAP).
     * @param boolean $subscribe  True to subscribe, false to unsubscribe.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _subscribeMailbox($mailbox, $subscribe);

    /**
     * Obtain a list of mailboxes matching a pattern.
     *
     * @param mixed $pattern   The mailbox search pattern(s) (see RFC 3501
     *                         [6.3.8] for the format). Either a string or an
     *                         array of strings. Either in UTF7-IMAP or
     *                         UTF-8.
     * @param integer $mode    Which mailboxes to return.  Either
     *                         Horde_Imap_Client::MBOX_SUBSCRIBED,
     *                         Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS,
     *                         Horde_Imap_Client::MBOX_UNSUBSCRIBED, or
     *                         Horde_Imap_Client::MBOX_ALL.
     * @param array $options   Additional options:
     * <pre>
     * 'attributes' - (boolean) If true, return attribute information under
     *                the 'attributes' key. The attributes will be returned
     *                in an array with each attribute in lowercase.
     *                DEFAULT: Do not return this information.
     * 'children' - (boolean) Tell server to return children attribute
     *              information (\HasChildren, \HasNoChildren). Requires the
     *              LIST-EXTENDED extension to guarantee this information is
     *              returned. Server MAY return this attribute without this
     *              option, or if the CHILDREN extension is available, but it
     *              is not guaranteed.
     *              DEFAULT: false
     * 'delimiter' - (boolean) If true, return delimiter information under
     *               the 'delimiter' key.
     *               DEFAULT: Do not return this information.
     * 'flat' - (boolean) If true, return a flat list of mailbox names only.
     *          Overrides both the 'attributes' and 'delimiter' options.
     *          DEFAULT: Do not return flat list.
     * 'recursivematch' - (boolean) Force the server to return information
     *                    about parent mailboxes that don't match other
     *                    selection options, but have some submailboxes that
     *                    do. Information about children is returned in the
     *                    CHILDINFO extended data item ('extended'). Requires
     *                    the LIST-EXTENDED extension.
     *                    DEFAULT: false
     * 'remote' - (boolean) Tell server to return mailboxes that reside on
     *            another server. Requires the LIST-EXTENDED extension.
     *            DEFAULT: false
     * 'special_use' - (boolean) Tell server to return special-use attribute
     *                 information (\Drafts, \Flagged, \Junk, \Sent, \Trash,
     *                 \All, \Archive)). Server must support the SPECIAL-USE
     *                 return option for this setting to have any effect.
     *                 Server MAY return this attribute without this option.
     *                 Aidditionaly, server SHOULD return this information if
     *                 this option is given, but it is not guaranteed.
     *                 DEFAULT: false
     * 'status' - (integer) Tell server to return status information. The
     *            value is a bitmask that may contain the following:
     *            Horde_Imap_Client::STATUS_MESSAGES,
     *            Horde_Imap_Client::STATUS_RECENT,
     *            Horde_Imap_Client::STATUS_UIDNEXT,
     *            Horde_Imap_Client::STATUS_UIDVALIDITY,
     *            Horde_Imap_Client::STATUS_UNSEEN, and
     *            Horde_Imap_Client::STATUS_HIGHESTMODSEQ.
     *            Requires the LIST-STATUS extension.
     *            DEFAULT: 0
     * 'sort' - (boolean) If true, return a sorted list of mailboxes?
     *          DEFAULT: Do not sort the list.
     * 'sort_delimiter' - (string) If 'sort' is true, this is the delimiter
     *                    used to sort the mailboxes.
     *                    DEFAULT: '.'
     * 'utf8' - (boolean) True to return mailbox names in UTF-8.
     *          DEFAULT: Names are returned in UTF7-IMAP.
     * </pre>
     *
     * @return array  If 'flat' option is true, the array values are the list
     *                of mailboxes.  Otherwise, the array values are arrays
     *                with the following keys: 'mailbox', 'attributes' (only
     *                if 'attributes' option is true), 'delimiter' (only
     *                if 'delimiter' option is true), 'extended' (only
     *                if 'recursivematch' option is true and LIST-EXTENDED
     *                extension is supported on the server), and 'status'
     *                (only if 'status' option is true and LIST-STATUS
     *                extension is supported on the server).
     * @throws Horde_Imap_Client_Exception
     */
    public function listMailboxes($pattern, $mode = Horde_Imap_Client::MBOX_ALL,
                                  $options = array())
    {
        $pattern = is_array($pattern)
            ? array_map(array('Horde_Imap_Client_Utf7imap', 'Utf8ToUtf7Imap'), $pattern)
            : Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($pattern);

        $ret = $this->_listMailboxes($pattern, $mode, $options);

        if (!empty($options['sort'])) {
            Horde_Imap_Client_Sort::sortMailboxes($ret, array('delimiter' => empty($options['sort_delimiter']) ? '.' : $options['sort_delimiter'], 'index' => false, 'keysort' => empty($options['flat'])));
        }

        return $ret;
    }

    /**
     * Obtain a list of mailboxes matching a pattern.
     *
     * @param mixed $pattern  The mailbox search pattern(s) (UTF7-IMAP).
     * @param integer $mode   Which mailboxes to return.
     * @param array $options  Additional options.
     *
     * @return array  See self::listMailboxes().
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _listMailboxes($pattern, $mode, $options);

    /**
     * Obtain status information for a mailbox.
     *
     * @param string $mailbox  The mailbox to query. Either in UTF7-IMAP or
     *                         or UTF-8.
     * @param integer $flags   A bitmask of information requested from the
     *                         server. Allowed flags:
     * <pre>
     * Flag: Horde_Imap_Client::STATUS_MESSAGES
     *   Return key: 'messages'
     *   Return format: (integer) The number of messages in the mailbox.
     *
     * Flag: Horde_Imap_Client::STATUS_RECENT
     *   Return key: 'recent'
     *   Return format: (integer) The number of messages with the '\Recent'
     *                  flag set
     *
     * Flag: Horde_Imap_Client::STATUS_UIDNEXT
     *   Return key: 'uidnext'
     *   Return format: (integer) The next UID to be assigned in the mailbox.
     *
     * Flag: Horde_Imap_Client::STATUS_UIDVALIDITY
     *   Return key: 'uidvalidity'
     *   Return format: (integer) The unique identifier validity of the
     *                  mailbox.
     *
     * Flag: Horde_Imap_Client::STATUS_UNSEEN
     *   Return key: 'unseen'
     *   Return format: (integer) The number of messages which do not have
     *                  the '\Seen' flag set.
     *
     * Flag: Horde_Imap_Client::STATUS_FIRSTUNSEEN
     *   Return key: 'firstunseen'
     *   Return format: (integer) The sequence number of the first unseen
     *                  message in the mailbox.
     *
     * Flag: Horde_Imap_Client::STATUS_FLAGS
     *   Return key: 'flags'
     *   Return format: (array) The list of defined flags in the mailbox (all
     *                  flags are in lowercase).
     *
     * Flag: Horde_Imap_Client::STATUS_PERMFLAGS
     *   Return key: 'permflags'
     *   Return format: (array) The list of flags that a client can change
     *                  permanently (all flags are in lowercase).
     *
     * Flag: Horde_Imap_Client::STATUS_HIGHESTMODSEQ
     *   Return key: 'highestmodseq'
     *   Return format: (integer) If the server supports the CONDSTORE IMAP
     *                  extension, this will be the highest mod-sequence value
     *                  of all messages in the mailbox. Else 0 if CONDSTORE
     *                  not available or the mailbox does not support
     *                  mod-sequences.
     *
     * Flag: Horde_Imap_Client::STATUS_LASTMODSEQ
     *   Return key: 'lastmodseq'
     *   Return format: (integer) If the server supports the CONDSTORE IMAP
     *                  extension, this will be the cached mod-sequence value
     *                  of the mailbox when it was first opened if
     *                  HIGHESTMODSEQ changed. Else 0 if CONDSTORE not
     *                  available, the mailbox does not support mod-sequences,
     *                  or the mod-sequence did not change.
     *
     * Flag: Horde_Imap_Client::STATUS_LASTMODSEQUIDS
     *   Return key: 'lastmodsequids'
     *   Return format: (array) If the server supports the CONDSTORE IMAP
     *                  extension, this will be the list of UIDs changed in
     *                  the mailbox when it was first opened if HIGHESTMODSEQ
     *                  changed. Else an empty array if CONDSTORE not
     *                  available, the mailbox does not support mod-sequences,
     *                  or the mod-sequence did not change.
     *
     * Flag: Horde_Imap_Client::STATUS_UIDNOTSTICKY
     *   Return key: 'uidnotsticky'
     *   Return format: (boolean) If the server supports the UIDPLUS IMAP
     *                  extension, and the queried mailbox does not support
     *                  persistent UIDs, this value will be true. In all
     *                  other cases, this value will be false.
     *
     * Flag: Horde_Imap_Client::STATUS_ALL (DEFAULT)
     *   A shortcut to return 'messages', 'recent', 'uidnext', 'uidvalidity',
     *   and 'unseen'.
     * </pre>
     *
     * @return array  An array with the requested keys (see above).
     * @throws Horde_Imap_Client_Exception
     */
    public function status($mailbox, $flags = Horde_Imap_Client::STATUS_ALL)
    {
        $unselected_flags = array(
            'messages' => Horde_Imap_Client::STATUS_MESSAGES,
            'recent' => Horde_Imap_Client::STATUS_RECENT,
            'unseen' => Horde_Imap_Client::STATUS_UNSEEN,
            'uidnext' => Horde_Imap_Client::STATUS_UIDNEXT,
            'uidvalidity' => Horde_Imap_Client::STATUS_UIDVALIDITY
        );

        if ($flags & Horde_Imap_Client::STATUS_ALL) {
            foreach ($unselected_flags as $val) {
                $flags |= $val;
            }
        }

        $mailbox = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox);
        $curr_mbox = ($this->_selected == $mailbox);
        $ret = array();

        /* Check for cached information. */
        if (!$curr_mbox &&
            !empty($this->_params['statuscache']) &&
            isset($this->_temp['statuscache'][$mailbox])) {
            $ptr = &$this->_temp['statuscache'][$mailbox];

            foreach ($unselected_flags as $key => $val) {
                if (($flags & $val) && isset($ptr[$key])) {
                    $ret[$key] = $ptr[$key];
                    $flags &= ~$val;
                }
            }
        }

        /* Catch flags that are not supported. */
        if (($flags & Horde_Imap_Client::STATUS_HIGHESTMODSEQ) &&
            !isset($this->_init['enabled']['CONDSTORE'])) {
            $ret['highestmodseq'] = 0;
            $flags &= ~Horde_Imap_Client::STATUS_HIGHESTMODSEQ;
        }

        if (($flags & Horde_Imap_Client::STATUS_UIDNOTSTICKY) &&
            !$this->queryCapability('UIDPLUS')) {
            $ret['uidnotsticky'] = false;
            $flags &= ~Horde_Imap_Client::STATUS_UIDNOTSTICKY;
        }

        /* Handle LASTMODSEQ related options. */
        if ($flags & Horde_Imap_Client::STATUS_LASTMODSEQ) {
            $ret['lastmodseq'] = 0;
            if (isset($this->_init['enabled']['CONDSTORE']) &&
                isset($this->_temp['lastmodseq'][$mailbox])) {
                $ret['lastmodseq'] = $this->_temp['lastmodseq'][$mailbox];
            }
            $flags &= ~Horde_Imap_Client::STATUS_LASTMODSEQ;
        }

        if ($flags & Horde_Imap_Client::STATUS_LASTMODSEQUIDS) {
            $ret['lastmodsequids'] = array();
            if (isset($this->_init['enabled']['CONDSTORE']) &&
                isset($this->_temp['lastmodsequids'][$mailbox])) {
                $ret['lastmodsequids'] = $this->utils->fromSequenceString($this->_temp['lastmodsequids'][$mailbox]);
            }
            $flags &= ~Horde_Imap_Client::STATUS_LASTMODSEQUIDS;
        }

        if (!$flags) {
            return $ret;
        }

        /* STATUS_PERMFLAGS requires a read/write mailbox. */
        if ($flags & Horde_Imap_Client::STATUS_PERMFLAGS) {
            $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_READWRITE);
        }

        $ret = array_merge($ret, $this->_status($mailbox, $flags));

        if ($this->_selected != $mailbox) {
            if (!isset($this->_temp['statuscache'])) {
                $this->_temp['statuscache'] = array();
            }
            $ptr = &$this->_temp['statuscache'];

            $ptr[$mailbox] = isset($ptr[$mailbox])
                ? array_merge($ptr[$mailbox], $ret)
                : $ret;
        }

        return $ret;
    }

    /**
     * Obtain status information for a mailbox.
     *
     * @param string $mailbox  The mailbox to query (UTF7-IMAP).
     * @param integer $flags   A bitmask of information requested from the
     *                         server.
     *
     * @return array  See self::status().
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _status($mailbox, $flags);

    /**
     * Perform a STATUS call on multiple mailboxes at the same time.
     *
     * This method leverages the LIST-EXTENDED and LIST-STATUS extensions on
     * the IMAP server to improve the efficiency of this operation.
     *
     * @param array $mailboxes  The mailboxes to query. Either in UTF7-IMAP
     *                          or UTF-8.
     * @param integer $flags    See self::status().
     *
     * @return array  An array with the keys as the mailbox names and the
     *                values as arrays with the requested keys (from the
     *                mask given in $flags).
     */
    public function statusMultiple($mailboxes,
                                   $flags = Horde_Imap_Client::STATUS_ALL)
    {
        $ret = null;

        if ($this->queryCapability('LIST-STATUS')) {
            try {
                $ret = array();
                foreach ($this->listMailboxes($mailboxes, Horde_Imap_Client::MBOX_ALL, array('status' => $flags)) as $val) {
                    if (isset($val['status'])) {
                        $ret[$val['mailbox']] = $val['status'];
                    }
                }
            } catch (Horde_Imap_Client_Exception $e) {
                $ret = null;
            }
        }

        if (is_null($ret)) {
            $ret = array();
            foreach ($mailboxes as $val) {
                try {
                    $ret[$val] = $this->status($val, $flags);
                } catch (Horde_Imap_Client_Exception $e) {}
            }
        }

        return $ret;
    }

    /**
     * Append message(s) to a mailbox.
     *
     * @param string $mailbox  The mailbox to append the message(s) to. Either
     *                         in UTF7-IMAP or UTF-8.
     * @param array $data      The message data to append, along with
     *                         additional options. An array of arrays with
     *                         each embedded array having the following
     *                         entries:
     * <pre>
     * 'data' - (mixed) The data to append. If a string or a stream resource,
     *          this will be used as the entire contents of a single message.
     *          If an array, will catenate all given parts into a single
     *          message. This array contains one or more arrays with two keys:
     *            't' - (string) Either 'url' or 'text'.
     *            'v' - (mixed) If 't' is 'url', this is the IMAP URL to the
     *                  message part to append. If 't' is 'text', this is
     *                  either a string or resource representation of the
     *                  message part data.
     *          DEFAULT: NONE (entry is MANDATORY)
     * 'flags' - (array) An array of flags/keywords to set on the appended
     *           message.
     *           DEFAULT: Only the '\Recent' flag is set.
     * 'internaldate' - (DateTime object) The internaldate to set for the
     *                  appended message.
     *                  DEFAULT: internaldate will be the same date as when
     *                  the message was appended.
     * </pre>
     * @param array $options  Additonal options:
     * <pre>
     * 'create' - (boolean) Try to create $mailbox if it does not exist?
     *             DEFAULT: No.
     * </pre>
     *
     * @return array  The UIDs of the appended messages.
     * @throws Horde_Imap_Client_Exception
     */
    public function append($mailbox, $data, $options = array())
    {
        $mailbox = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox);

        $ret = $this->_append($mailbox, $data, $options);
        unset($this->_temp['statuscache'][$mailbox]);

        if (is_array($ret)) {
            return $ret;
        }

        $uids = array();

        while (list(,$val) = each($data)) {
            if (is_string($data['data'])) {
                $text = $data;
            } elseif (is_resource($data['data'])) {
                $text = '';
                rewind($data['data']);
                while ($in = fread($data['data'], 1024)) {
                    $text .= $in;
                    if (preg_match("/\n\r*\n\r*/", $text)) {
                        break;
                    }
                }
            }

            $headers = Horde_Mime_Headers::parseHeaders($text);
            $msgid = $headers->getValue('message-id');

            if ($msgid) {
                $search_query = new Horde_Imap_Client_Search_Query();
                $search_query->headerText('Message-ID', $msgid);
                $uidsearch = $this->search($mailbox, $search_query);
                $uids[] = reset($uidsearch['match']);
            }
        }

        return $uids;
    }

    /**
     * Append message(s) to a mailbox.
     *
     * @param string $mailbox  The mailbox to append the message(s) to
     *                         (UTF7-IMAP).
     * @param array $data      The message data.
     * @param array $options   Additional options.
     *
     * @return mixed  An array of the UIDs of the appended messages (if server
     *                supports UIDPLUS extension) or true.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _append($mailbox, $data, $options);

    /**
     * Request a checkpoint of the currently selected mailbox (RFC 3501
     * [6.4.1]).
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function check()
    {
        // CHECK only useful if we are already authenticated.
        if ($this->_isAuthenticated) {
            $this->_check();
        }
    }

    /**
     * Request a checkpoint of the currently selected mailbox.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _check();

    /**
     * Close the connection to the currently selected mailbox, optionally
     * expunging all deleted messages (RFC 3501 [6.4.2]).
     *
     * @param array $options  Additional options:
     * <pre>
     * 'expunge' - (boolean) Expunge all messages flagged as deleted?
     *             DEFAULT: No
     * </pre>
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function close($options = array())
    {
        if (is_null($this->_selected)) {
            return;
        }

        /* If we are caching, search for deleted messages. */
        if (!empty($options['expunge']) &&
            $this->_initCache(true)) {
            $search_query = new Horde_Imap_Client_Search_Query();
            $search_query->flag('\\deleted', true);
            $search_res = $this->search($this->_selected, $search_query);
            $mbox = $this->_selected;
        } else {
            $search_res = null;
        }

        $this->_close($options);
        $this->_selected = null;
        $this->_mode = 0;

        if (!is_null($search_res)) {
            $this->cache->deleteMsgs($mbox, $search_res['match']);
        }
    }

    /**
     * Close the connection to the currently selected mailbox, optionally
     * expunging all deleted messages (RFC 3501 [6.4.2]).
     *
     * @param array $options  Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _close($options);

    /**
     * Expunge deleted messages from the given mailbox.
     *
     * @param string $mailbox  The mailbox to expunge. Either in UTF7-IMAP
     *                         or UTF-8.
     * @param array $options   Additional options:
     * <pre>
     * 'ids' - (array) A list of messages to expunge, but only if they
     *         are also flagged as deleted. By default, this array is
     *         assumed to contain UIDs (see 'sequence').
     *         DEFAULT: All messages marked as deleted will be expunged.
     * 'list' - (boolean) If true, returns the list of expunged messages.
     *          DEFAULT: false
     * 'sequence' - (boolean) If true, 'ids' is an array of sequence numbers.
     *              DEFAULT: 'sequence' is an array of UIDs.
     * </pre>
     *
     * @return array  If 'list' option is true, returns the list of
     *                expunged messages.
     * @throws Horde_Imap_Client_Exception
     */
    public function expunge($mailbox, $options = array())
    {
        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_READWRITE);
        return $this->_expunge($options);
    }

    /**
     * Expunge all deleted messages from the given mailbox.
     *
     * @param array $options  Additional options.
     *
     * @return array  If 'list' option is true, returns the list of
     *                expunged messages.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _expunge($options);

    /**
     * Search a mailbox.
     *
     * @param string $mailbox  The mailbox to search. Either in UTF7-IMAP
     *                         or UTF-8.
     * @param object $query    The search query (a
     *                         Horde_Imap_Client_Search_Query object).
     *                         Defaults to an ALL search.
     * @param array $options   Additional options:
     * <pre>
     * 'nocache' - (boolean) Don't cache the results.
     *             DEFAULT: false (results cached, if possible)
     * 'results' - (array) The data to return. Consists of zero or more of the
     *                     following flags:
     * Horde_Imap_Client::SORT_RESULTS_COUNT
     * Horde_Imap_Client::SORT_RESULTS_MATCH (DEFAULT)
     * Horde_Imap_Client::SORT_RESULTS_MAX
     * Horde_Imap_Client::SORT_RESULTS_MIN
     * Horde_Imap_Client::SORT_RESULTS_SAVE - (This option is currently meant
     *   for internal use only)
     * 'reverse' - (boolean) Sort the entire returned list of messages in
     *             reverse (i.e. descending) order.
     *             DEFAULT: Sorted in ascending order.
     * 'sequence' - (boolean) If true, returns an array of sequence numbers.
     *              DEFAULT: Returns an array of UIDs
     * 'sort' - (array) Sort the returned list of messages. Multiple sort
     *          criteria can be specified. The following sort criteria
     *          are available:
     * Horde_Imap_Client::SORT_ARRIVAL
     * Horde_Imap_Client::SORT_CC
     * Horde_Imap_Client::SORT_DATE
     * Horde_Imap_Client::SORT_FROM
     * Horde_Imap_Client::SORT_SEQUENCE
     * Horde_Imap_Client::SORT_SIZE
     * Horde_Imap_Client::SORT_SUBJECT
     * Horde_Imap_Client::SORT_TO
     * [On servers that support SORT=DISPLAY, these criteria are also
     *  available:]
     * Horde_Imap_Client::SORT_DISPLAYFROM
     * Horde_Imap_Client::SORT_DISPLAYTO
     *          Additionally, any sort criteria can be sorted in reverse order
     *          (instead of the default ascending order) by adding a
     *          Horde_Imap_Client::SORT_REVERSE element to the array directly
     *          before adding the sort element. Note that if you want the
     *          entire list to be sorted in reverse order, use the 'reverse'
     *          option instead. If this option is set, the 'results' option
     *          is ignored.
     *          DEFAULT: None.
     * </pre>
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'count' - (integer) The number of messages that match the search
     *           criteria.
     *           Always returned.
     * 'match' - OPTIONAL (array) The UIDs (default) or message sequence
     *           numbers (if 'sequence' is true) that match $criteria.
     *           Returned if 'sort' is false and
     *           Horde_Imap_Client::SORT_RESULTS_MATCH is set.
     * 'max' - (integer) The UID (default) or message sequence number (if
     *         'sequence is true) of the highest message that satisifies
     *         $criteria. Returns null if no matches found.
     *         Returned if Horde_Imap_Client::SORT_RESULTS_MAX is set.
     * 'min' - (integer) The UID (default) or message sequence number (if
     *         'sequence is true) of the lowest message that satisifies
     *         $criteria. Returns null if no matches found.
     *         Returned if Horde_Imap_Client::SORT_RESULTS_MIN is set.
     * 'modseq' - (integer) The highest mod-sequence for all messages being
     *            returned.
     *            Returned if 'sort' is false, the search query includes a
     *            modseq command, and the server supports the CONDSTORE IMAP
     *            extension.
     * 'save' - (boolean) Whether the search results were saved. This value is
     *          meant for internal use only. Returned if 'sort' is false and
     *          Horde_Imap_Client::SORT_RESULTS_SAVE is set.
     * 'sort' - (array) The sorted UIDs (default) or message sequence numbers
     *          (if 'sequence' is true) that match $criteria.
     *          Returned if 'sort' is true.
     * </pre>
     * @throws Horde_Imap_Client_Exception
     */
    public function search($mailbox, $query = null, $options = array())
    {
        if (empty($options['results'])) {
            $options['results'] = array(
                Horde_Imap_Client::SORT_RESULTS_MATCH,
                Horde_Imap_Client::SORT_RESULTS_COUNT
            );
        }

        // Default to an ALL search.
        if (is_null($query)) {
            $query = new Horde_Imap_Client_Search_Query();
        }

        $options['_query'] = $query->build($this->capability());

        /* Optimization - if query is just for a count of either RECENT or
         * ALL messages, we can send status information instead. Can't
         * optimize with unseen queries because we may cause an infinite loop
         * between here and the status() call. */
        if ((count($options['results']) == 1) &&
            (reset($options['results']) == Horde_Imap_Client::SORT_RESULTS_COUNT)) {
            switch ($options['_query']['query']) {
            case 'ALL':
                $ret = $this->status($this->_selected, Horde_Imap_Client::STATUS_MESSAGES);
                return array('count' => $ret['messages']);

            case 'RECENT':
                $ret = $this->status($this->_selected, Horde_Imap_Client::STATUS_RECENT);
                return array('count' => $ret['recent']);
            }
        }

        $type = empty($options['sort']) ? 'match' : 'sort';

        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_AUTO);

        /* Take advantage of search result caching.  If CONDSTORE available,
         * we can cache all queries and invalidate the cache when the MODSEQ
         * changes. If CONDSTORE not available, we can only store queries
         * that don't involve flags. We store results by hashing the options
         * array - the generated query is already added to '_query' key
         * above. */
        $cache = null;
        if (empty($options['nocache']) &&
            $this->_initCache(true) &&
            (isset($this->_init['enabled']['CONDSTORE']) ||
             !$query->flagSearch())) {
            $cache = $this->_getSearchCache('search', $mailbox, $options);
            if (isset($cache['data'])) {
                if (isset($cache['data'][$type])) {
                    $cache['data'][$type] = $this->utils->fromSequenceString($cache['data'][$type]);
                }
                return $cache['data'];
            }
        }

        $ret = $this->_search($query, $options);

        if (!empty($options['reverse'])) {
            $ret[$type] = array_reverse($ret[$type]);
        }

        if ($cache) {
            $save = $ret;
            if (isset($save[$type])) {
                $save[$type] = $this->utils->toSequenceString($ret[$type], array('nosort' => true));
            }
            $this->_setSearchCache($save, $cache);
        }

        return $ret;
    }

    /**
     * Search a mailbox.
     *
     * @param object $query   The search query.
     * @param array $options  Additional options. The '_query' key contains
     *                        the value of $query->build(). 'reverse' should
     *                        be ignored (handled in search()).
     *
     * @return array  An array of UIDs (default) or an array of message
     *                sequence numbers (if 'sequence' is true).
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _search($query, $options);

    /**
     * Set the comparator to use for searching/sorting (RFC 5255).
     *
     * @param string $comparator  The comparator string (see RFC 4790 [3.1] -
     *                            "collation-id" - for format). The reserved
     *                            string 'default' can be used to select
     *                            the default comparator.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function setComparator($comparator = null)
    {
        $comp = is_null($comparator) ? (empty($this->_params['comparator']) ? null : $this->_params['comparator']) : $comparator;
        if (is_null($comp)) {
            return;
        }

        $i18n = $this->queryCapability('I18NLEVEL');
        if (empty($i18n) || (max($i18n) < 2)) {
            throw new Horde_Imap_Client_Exception('The IMAP server does not support changing SEARCH/SORT comparators.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
        }

        $this->_setComparator($comp);
    }

    /**
     * Set the comparator to use for searching/sorting (RFC 5255).
     *
     * @param string $comparator  The comparator string (see RFC 4790 [3.1] -
     *                            "collation-id" - for format). The reserved
     *                            string 'default' can be used to select
     *                            the default comparator.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setComparator($comparator);

    /**
     * Get the comparator used for searching/sorting (RFC 5255).
     *
     * @return mixed  Null if the default comparator is being used, or an
     *                array of comparator information (see RFC 5255 [4.8]).
     * @throws Horde_Imap_Client_Exception
     */
    public function getComparator()
    {
        $i18n = $this->queryCapability('I18NLEVEL');
        if (empty($i18n) || (max($i18n) < 2)) {
            return null;
        }

        return $this->_getComparator();
    }

    /**
     * Get the comparator used for searching/sorting (RFC 5255).
     *
     * @return mixed  Null if the default comparator is being used, or an
     *                array of comparator information (see RFC 5255 [4.8]).
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getComparator();

    /**
     * Thread sort a given list of messages (RFC 5256).
     *
     * @param string $mailbox  The mailbox to search. Either in UTF7-IMAP
     *                         or UTF-8.
     * @param array $options   Additional options:
     * <pre>
     * 'criteria' - (mixed) The following thread criteria are available:
     *              Horde_Imap_Client::THREAD_ORDEREDSUBJECT,
     *              Horde_Imap_Client::THREAD_REFERENCES, and
     *              Horde_Imap_Client::THREAD_REFS. Additionally, other
     *              algorithms can be explicitly specified by passing the IMAP
     *              thread algorithm in as a string.
     *              DEFAULT: Horde_Imap_Client::THREAD_ORDEREDSUBJECT
     * 'search' - (object) The search query (a
     *            Horde_Imap_Client_Search_Query object).
     *            DEFAULT: All messages in mailbox included in thread sort.
     * 'sequence' - (boolean) If true, each message is stored and referred to
     *              by its message sequence number.
     *              DEFAULT: Stored/referred to by UID.
     * </pre>
     *
     * @return Horde_Imap_Client_Thread  A Horde_Imap_Client_Thread object.
     * @throws Horde_Imap_Client_Exception
     */
    public function thread($mailbox, $options = array())
    {
        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_AUTO);

        /* Take advantage of search result caching.  If CONDSTORE available,
         * we can cache all queries and invalidate the cache when the MODSEQ
         * changes. If CONDSTORE not available, we can only store queries
         * that don't involve flags. See search() for similar caching. */
        $cache = null;
        if ($this->_initCache(true) &&
            (isset($this->_init['enabled']['CONDSTORE']) ||
             empty($options['search']) ||
             !$options['search']->flagSearch())) {
            $cache = $this->_getSearchCache('thread', $mailbox, $options);
            if (isset($cache['data'])) {
                return $cache['data'];
            }
        }

        $ob = new Horde_Imap_Client_Thread($this->_thread($options), empty($options['sequence']) ? 'uid' : 'sequence');

        if ($cache) {
            $this->_setSearchCache($ob, $cache);
        }

        return $ob;
    }

    /**
     * Thread sort a given list of messages (RFC 5256).
     *
     * @param array $options  Additional options. See thread().
     *
     * @return array  An array with the following values, one per message,
     *                with the key being either the UID (default) or the
     *                message sequence number (if 'sequence' is true). Values
     *                of each entry:
     * <pre>
     * 'b' (base) - (integer) [OPTIONAL] The ID of the base message. Is not
     *              set, this is the only message in the thread.
     *              DEFAULT: Only message in thread
     * 'l' (level) - (integer) [OPTIONAL] The thread level of this
     *               message (1 = base).
     *               DEFAULT: 0
     * 's' (subthread) - (boolean) [OPTIONAL] Are there more messages in this
     *                   subthread?
     *                   DEFAULT: No
     * </pre>
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _thread($options);

    /**
     * Fetch message data (see RFC 3501 [6.4.5]).
     *
     * @param string $mailbox  The mailbox to fetch messages from. Either in
     *                         UTF7-IMAP or UTF-8.
     * @param array $criteria  The fetch criteria. Contains the following:
     * <pre>
     * Key: Horde_Imap_Client::FETCH_FULLMSG
     *   Desc: Returns the full text of the message.
     *         ONLY ONE of these entries should be defined.
     *   Value: (array) The following options are available:
     *     'length' - (integer) If 'start' is defined, the length of the
     *                substring to return.
     *                DEFAULT: The entire text is returned.
     *     'peek' - (boolean) If set, does not set the '\Seen' flag on the
     *              message.
     *              DEFAULT: The seen flag is set.
     *     'start' - (integer) If a portion of the full text is desired to be
     *               returned, the starting position is identified here.
     *               DEFAULT: The entire text is returned.
     *     'stream' - (boolean) If true, returns a stream resource with the
     *                message data instead of a string.
     *   Return key: 'fullmsg'
     *   Return format: (mixed) The full text of the entire message (or the
     *                  portion of the text delineated by the 'start'/'length'
     *                  parameters). If the 'stream' parameter is set, returns
     *                  a stream resource.
     *
     * Key: Horde_Imap_Client::FETCH_HEADERTEXT
     *   Desc: Returns the header text. Header text is defined only for the
     *         base RFC 2822 message or message/rfc822 parts. Attempting to
     *         retrieve the body text from other parts will result in a
     *         thrown exception.
     *         MORE THAN ONE of these entries can be defined. Each entry will
     *         be a separate array contained in the value field.
     *         Each entry should have a unique 'id' value.
     *   Value: (array) One array for each request. Each array may contain
     *          the following options:
     *     'id' - (string) The MIME ID to obtain the header text for.
     *            DEFAULT: The header text for the entire message (MIME ID: 0)
     *            will be returned.
     *     'length' - (integer) If 'start' is defined, the length of the
     *                substring to return.
     *                DEFAULT: The entire text is returned.
     *     'parse' - (boolean) If true, and the Horde MIME library is
     *               available, parse the header text into a
     *               Horde_Mime_Headers object.
     *               DEFAULT: The full header text is returned.
     *     'peek' - (boolean) If set, does not set the '\Seen' flag on the
     *              message.
     *              DEFAULT: The seen flag is set.
     *     'start' - (integer) If a portion of the full text is desired to be
     *               returned, the starting position is identified here.
     *               DEFAULT: The entire text is returned.
     *   Return key: 'headertext'
     *   Return format: (array) An array of header text entries. Keys are
     *                  the 'id'. If 'parse' is true, values are
     *                  Horde_Mime_Headers objects. Otherwise, values are the
     *                  raw text of the header (or the portion of the text
     *                  delineated by the 'start' & 'length' parameters).
     *
     * Key: Horde_Imap_Client::FETCH_BODYTEXT
     *   Desc: Returns the body text. Body text is defined only for the
     *         base RFC 2822 message or message/rfc822 parts. Attempting to
     *         retrieve the body text from other parts will result in a
     *         thrown exception.
     *         MORE THAN ONE of these entries can be defined. Each entry will
     *         be a separate array contained in the value field.
     *         Each entry should have a unique 'id' value.
     *   Value: (array) One array for each request. Each array may contain
     *          the following options:
     *     'id' - (string) The MIME ID to obtain the body text for.
     *            DEFAULT: The body text for the entire message (MIME ID: 0)
     *            will be returned.
     *     'length' - (integer) If 'start' is defined, the length of the
     *                substring to return.
     *                DEFAULT: The entire text is returned.
     *     'peek' - (boolean) If set, does not set the '\Seen' flag on the
     *              message.
     *              DEFAULT: The seen flag is set.
     *     'start' - (integer) If a portion of the full text is desired to be
     *               returned, the starting position is identified here.
     *               DEFAULT: The entire text is returned.
     *     'stream' - (boolean) If true, returns a stream resource with the
     *                message data instead of a string.
     *   Return key: 'bodytext'
     *   Return format: (array) An array of body text entries. Keys are the
     *                  the 'id', values are the message body text strings
     *                  (or the portion of the text delineated by the
     *                  'start'/'length' parameters). If the 'stream'
     *                  parameter is set, returns a stream resource.
     *
     * Key: Horde_Imap_Client::FETCH_MIMEHEADER
     *   Desc: Returns the MIME header text. MIME header text is defined only
     *         for non RFC 2822 messages and non message/rfc822 parts.
     *         Attempting to retrieve the MIME header from other parts will
     *         result in a thrown exception.
     *         MORE THAN ONE of these entries can be defined. Each entry will
     *         be a separate array contained in the value field.
     *         Each entry should have a unique 'id' value.
     *   Value: (array) One array for each request. Each array may contain
     *          the following options:
     *     'id' - (string) The MIME ID to obtain the MIME header text for.
     *            DEFAULT: NONE
     *     'length' - (integer) If 'start' is defined, the length of the
     *                substring to return.
     *                DEFAULT: The entire text is returned.
     *     'peek' - (boolean) If set, does not set the '\Seen' flag on the
     *              message.
     *              DEFAULT: The seen flag is set.
     *     'start' - (integer) If a portion of the full text is desired to be
     *               returned, the starting position is identified here.
     *               DEFAULT: The entire text is returned.
     *   Return key: 'mimeheader'
     *   Return format: (array) An array of MIME header text entries. Keys are
     *                  the 'id', values are the MIME header text strings
     *                  (or the portion of the text delineated by the
     *                  'start'/'length' parameters).
     *
     * Key: Horde_Imap_Client::FETCH_BODYPART
     *   Desc: Returns the body part data for a given MIME ID.
     *         MORE THAN ONE of these entries can be defined. Each entry will
     *         be a separate array contained in the value field.
     *         Each entry should have a unique 'id' value.
     *   Value: (array) One array for each request. Each array may contain
     *          the following options:
     *     'decode' - (boolean) Attempt to server-side decode the bodypart
     *                data if it is MIME transfer encoded. If it can be done,
     *                the 'bodypartdecode' key will be set with one of two
     *                values: '8bit' or 'binary'.
     *                DEFAULT: The raw data.
     *     'id' - (string) The MIME ID to obtain the body part text for.
     *            DEFAULT: NONE
     *     'length' - (integer) If 'start' is defined, the length of the
     *                substring to return.
     *                DEFAULT: The entire data is returned.
     *     'peek' - (boolean) If set, does not set the '\Seen' flag on the
     *              message.
     *              DEFAULT: The seen flag is set.
     *     'start' - (integer) If a portion of the full data is desired to be
     *               returned, the starting position is identified here.
     *               DEFAULT: The entire data is returned.
     *     'stream' - (boolean) If true, returns a stream resource with the
     *                message data instead of a string.
     *   Return key: 'bodypart' (and possibly 'bodypartdecode')
     *   Return format: (array) An array of body part data entries. Keys are
     *                  the 'id', values are the body part data (or the
     *                  portion of the data delineated by the 'start'/'length'
     *                  parameters). If the 'stream' parameter is set, returns
     *                  a stream resource.
     *
     * Key: Horde_Imap_Client::FETCH_BODYPARTSIZE
     *   Desc: Returns the decoded body part size for a given MIME ID.
     *         MORE THAN ONE of these entries can be defined. Each entry will
     *         be a separate array contained in the value field.
     *         Each entry should have a unique 'id' value.
     *   Value: (array) One array for each request. Each array may contain
     *          the following options:
     *     'id' - (string) The MIME ID to obtain the body part size for.
     *            DEFAULT: NONE
     *   Return key: 'bodypartsize' (if supported by server)
     *   Return format: (integer) The body part size in bytes. If the server
     *                  does not support the functionality, 'bodypartsize'
     *                  will not be set.
     *
     * Key: Horde_Imap_Client::FETCH_HEADERS
     *   Desc: Returns RFC 2822 header text that matches a search string.
     *         This header search work only with the base RFC 2822 message or
     *         message/rfc822 parts.
     *         MORE THAN ONE of these entries can be defined. Each entry will
     *         be a separate array contained in the value field.
     *         Each entry should have a unique 'label' value.
     *   Value: (array) One array for each request. Each array may contain
     *          the following options:
     *     'cache' - (boolean) If true, and 'peek' is also true, will cache
     *               the result of this call.
     *               DEFAULT: false
     *     'headers' - (array) The headers to search for (case-insensitive).
     *                 DEFAULT: NONE (MANDATORY)
     *     'id' - (string) The MIME ID to search.
     *            DEFAULT: The base message part (MIME ID: 0)
     *     'label' - (string) A unique label associated with this particular
     *               search. This is how the results are stored.
     *               DEFAULT: NONE (MANDATORY entry or exception will be
     *               thrown)
     *     'length' - (integer) If 'start' is defined, the length of the
     *                substring to return.
     *                DEFAULT: The entire text is returned.
     *     'notsearch' - (boolean) Do a 'NOT' search on the headers.
     *                   DEFAULT: false
     *     'parse' - (boolean) If true, and the Horde_Mime library is
     *               available, parse the returned headers into a
     *               Horde_Mime_Headers object.
     *               DEFAULT: The full header text is returned.
     *     'peek' - (boolean) If set, does not set the '\Seen' flag on the
     *              message.
     *              DEFAULT: The seen flag is set.
     *     'start' - (integer) If a portion of the full text is desired to be
     *               returned, the starting position is identified here.
     *               DEFAULT: The entire text is returned.
     *   Return key: 'headers'
     *   Return format: (array) Keys are the 'label'. If 'parse' is false,
     *                  values are the matched header text. If 'parse' is true,
     *                  values are Horde_Mime_Headers objects. Both returns
     *                  are subject to the search result being truncated due
     *                  to the 'start'/'length' parameters.
     *
     * Key: Horde_Imap_Client::FETCH_STRUCTURE
     *   Desc: Returns MIME structure information
     *         ONLY ONE of these entries should be defined per fetch request.
     *   Value: (array) The following options are available:
     *     'noext' - (boolean) Don't return information on extensions
     *               DEFAULT: Will return information on extensions
     *     'parse' - (boolean) If true, and the Horde_Mime library is
     *               available, parse the returned structure into a
     *               Horde_Mime_Part object.
     *               DEFAULT: The array representation is returned.
     *   Return key: 'structure' [CACHEABLE]
     *   Return format: (mixed) If 'parse' is true, a Horde_Mime_Part object.
     *                          Else, an array with the following information:
     *
     *     'type' - (string) The MIME type
     *     'subtype' - (string) The MIME subtype
     *
     *     The returned array MAY contain the following information:
     *     'disposition' - (string) The disposition type of the part (e.g.
     *                     'attachment', 'inline').
     *     'dparameters' - (array) Attribute/value pairs from the part's
     *                     Content-Disposition header.
     *     'language' - (array) A list of body language values.
     *     'location' - (string) The body content URI.
     *
     *     Depending on the MIME type of the part, the array will also contain
     *     further information. If labeled as [OPTIONAL], the array MAY
     *     contain this information, but only if 'noext' is false and the
     *     server returned the requested information. Else, the value is not
     *     set.
     *
     *     multipart/* parts:
     *     ==================
     *     'parts' - (array) An array of subparts (follows the same format as
     *               the base structure array).
     *     'parameters' - [OPTIONAL] (array) Attribute/value pairs from the
     *                    part's Content-Type header.
     *
     *     All other parts:
     *     ================
     *     'parameters' - (array) Attribute/value pairs from the part's
     *                    Content-Type header.
     *     'id' - (string) The part's Content-ID value.
     *     'description' - (string) The part's Content-Description value.
     *     'encoding' - (string) The part's Content-Transfer-Encoding value.
     *     'size' - (integer) - The part's size in bytes.
     *     'envelope' - [ONLY message/rfc822] (array) See 'envelope' response.
     *     'structure' - [ONLY message/rfc822] (array) See 'structure'
     *                   response.
     *     'lines' - [ONLY message/rfc822 and text/*] (integer) The size of
     *               the body in text lines.
     *     'md5' - [OPTIONAL] (string) The part's MD5 value.
     *
     * Key: Horde_Imap_Client::FETCH_ENVELOPE
     *   Desc: Envelope header data
     *         ONLY ONE of these entries should be defined per fetch request.
     *   Value: NONE
     *   Return key: 'envelope' [CACHEABLE]
     *   Return format: (array) This array has 9 elements: 'date', 'subject',
     *     'from', 'sender', 'reply-to', 'to', 'cc', 'bcc', 'in-reply-to', and
     *     'message-id'. For 'date', 'subject', 'in-reply-to', and
     *     'message-id', the values will be a string or null if it doesn't
     *     exist. For the other keys, the value will be an array of arrays (or
     *     an empty array if the header does not exist). Each of these
     *     underlying arrays corresponds to a single address and contains 4
     *     keys: 'personal', 'adl', 'mailbox', and 'host'. These keys will
     *     only be set if the server returned information.
     *
     * Key: Horde_Imap_Client::FETCH_FLAGS
     *   Desc: Flags set for the message
     *         ONLY ONE of these entries should be defined per fetch request.
     *   Value: NONE
     *   Return key: 'flags' [CACHEABLE - if CONSTORE IMAP extension is
     *                        supported on the server]
     *   Return format: (array) Each flag will be in a separate array entry.
     *     The flags will be entirely in lowercase.
     *
     * Key: Horde_Imap_Client::FETCH_DATE
     *   Desc: The internal (IMAP) date of the message
     *         ONLY ONE of these entries should be defined per fetch request.
     *   Value: NONE
     *   Return key: 'date' [CACHEABLE]
     *   Return format: (DateTime object) Returns a PHP DateTime object.
     *
     * Key: Horde_Imap_Client::FETCH_SIZE
     *   Desc: The size (in bytes) of the message
     *         ONLY ONE of these entries should be defined per fetch request.
     *   Value: NONE
     *   Return key: 'size' [CACHEABLE]
     *   Return format: (integer) The size of the message.
     *
     * Key: Horde_Imap_Client::FETCH_UID
     *   Desc: The Unique ID of the message.
     *         ONLY ONE of these entries should be defined per fetch request.
     *   Value: NONE
     *   Returned key: 'uid'
     *   Return format: (integer) The unique ID of the message.
     *
     * Key: Horde_Imap_Client::FETCH_SEQ
     *   Desc: The sequence number of the message.
     *         ONLY ONE of these entries should be defined per fetch request.
     *   Value: NONE
     *   Return key: 'seq'
     *   Return format: (integer) The sequence number of the message.
     *
     * Key: Horde_Imap_Client::FETCH_MODSEQ
     *   Desc: The mod-sequence value for the message.
     *         The server must support the CONDSTORE IMAP extension to use
     *         this criteria. Additionally, the mailbox must support mod-
     *         sequences or an exception will be thrown.
     *         ONLY ONE of these entries should be defined per fetch request.
     *   Value: NONE
     *   Returned key: 'modseq'
     *   Return format: (integer) The mod-sequence value of the message, or
     *                  undefined if the server does not support CONDSTORE.
     * </pre>
     * @param array $options    Additional options:
     * <pre>
     * 'changedsince' - (integer) Only return messages that have a
     *                  mod-sequence larger than this value. This option
     *                  requires the CONDSTORE IMAP extension (if not present,
     *                  this value is ignored). Additionally, the mailbox
     *                  must support mod-sequences or an exception will be
     *                  thrown. If valid, this option implicity adds the
     *                  Horde_Imap_Client::FETCH_MODSEQ fetch criteria to
     *                  the fetch command.
     *                  DEFAULT: Mod-sequence values are ignored.
     * 'ids' - (array) A list of messages to fetch data from.
     *         DEFAULT: All messages in $mailbox will be fetched.
     * 'sequence' - (boolean) If true, 'ids' is an array of sequence numbers.
     *              DEFAULT: 'ids' is an array of UIDs.
     * 'vanished' - (boolean) Only return messages from the UID set parameter
     *              that have been expunged and whose associated mod-sequence
     *              is larger than the specified mod-sequence. This option
     *              requires the QRESYNC IMAP extension, requires
     *              'changedsince' to be set, and requires 'sequence' to
     *              be false.
     *              DEFAULT: Vanished search ignored.
     * </pre>
     *
     * @return array  An array of fetch results. The array consists of
     *                keys that correspond to 'ids', and values that
     *                contain the array of fetched information as requested
     *                in criteria.
     * @throws Horde_Imap_Client_Exception
     */
    public function fetch($mailbox, $criteria, $options = array())
    {
        $cache_array = $get_fields = $header_cache = $new_criteria = $ret = array();
        $headerpeek = false;
        $headertext = null;
        $qresync = isset($this->_init['enabled']['QRESYNC']);
        $seq = !empty($options['sequence']);

        /* Make sure 'ids' is defined. */
        if (!isset($options['ids'])) {
            $options['ids'] = array();
        }

        /* The 'vanished' modifier requires QRESYNC, 'changedsince', and
         * !'sequence'. */
        if (!empty($options['vanished']) &&
            (!$qresync ||
             $seq ||
             empty($options['changedsince']))) {
            throw new Horde_Imap_Client_Exception('The vanished FETCH modifier is missing a pre-requisite.');
        }

        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_AUTO);

        $cache_avail = $this->_initCache(true);
        $cf = $cache_avail
            ? $this->_params['cache']['fields']
            : array();

        /* The 'changedsince' modifier implicitly adds the MODSEQ FETCH item.
         * (RFC 4551 [3.3.1]). A UID SEARCH will always return UID
         * information (RFC 3501 [6.4.8]). Don't add to criteria because it
         * simply creates a longer FETCH command. */
        if (!empty($cf)) {
            /* We need the UIDVALIDITY for the current mailbox. */
            $status_res = $this->status($this->_selected, Horde_Imap_Client::STATUS_HIGHESTMODSEQ | Horde_Imap_Client::STATUS_UIDVALIDITY);

            /* If using cache, we store by UID so we need to return UIDs. */
            if ($seq) {
                $criteria[Horde_Imap_Client::FETCH_UID] = true;
            }
        }

        /* Determine if caching is available and if anything in $criteria is
         * cacheable. Do some sanity checking on criteria also. */
        foreach ($criteria as $k => $v) {
            $cache_field = null;

            switch ($k) {
            case Horde_Imap_Client::FETCH_STRUCTURE:
                /* Don't cache if 'noext' is present. It will probably be a
                 * rare event anyway. */
                if (empty($v['noext']) && isset($cf[$k])) {
                    /* Structure can be cached two ways - via Horde_Mime_Part
                     * or by internal array format. */
                    $cache_field = empty($v['parse'])
                        ? 'HICstructa'
                        : 'HICstructm';
                    $fetch_field = 'structure';
                }
                break;

            case Horde_Imap_Client::FETCH_BODYPARTSIZE:
                if (!$this->queryCapability('BINARY')) {
                    unset($criteria[$k]);
                }
                break;

            case Horde_Imap_Client::FETCH_ENVELOPE:
                if (isset($cf[$k])) {
                    $cache_field = 'HICenv';
                    $fetch_field = 'envelope';
                }
                break;

            case Horde_Imap_Client::FETCH_FLAGS:
                if (isset($cf[$k])) {
                    /* QRESYNC would have already done syncing on mailbox
                     * open, so no need to do again. */
                    if (!$qresync) {
                        /* Grab all flags updated since the cached modseq
                         * val. */
                        $metadata = $this->cache->getMetaData($this->_selected, $status_res['uidvalidity'], array('HICmodseq'));
                        if (isset($metadata['HICmodseq']) &&
                            ($metadata['HICmodseq'] != $status_res['highestmodseq'])) {
                            $uids = $this->cache->get($this->_selected, array(), array(), $status_res['uidvalidity']);
                            if (!empty($uids)) {
                                $this->_fetch(array(Horde_Imap_Client::FETCH_FLAGS => true), array('changedsince' => $metadata['HICmodseq'], 'ids' => $uids));
                            }
                            $this->_updateMetaData($this->_selected, array('HICmodseq' => $status_res['highestmodseq']), $status_res['uidvalidity']);
                        }
                    }

                    $cache_field = 'HICflags';
                    $fetch_field = 'flags';
                }
                break;

            case Horde_Imap_Client::FETCH_DATE:
                if (isset($cf[$k])) {
                    $cache_field = 'HICdate';
                    $fetch_field = 'date';
                }
                break;

            case Horde_Imap_Client::FETCH_SIZE:
                if (isset($cf[$k])) {
                    $cache_field = 'HICsize';
                    $fetch_field = 'size';
                }
                break;

            case Horde_Imap_Client::FETCH_MODSEQ:
                if (!isset($this->_init['enabled']['CONDSTORE'])) {
                    unset($criteria[$k]);
                }
                break;

            case Horde_Imap_Client::FETCH_HEADERTEXT:
                // Caching for this access only - and only base header is
                // cached.
                foreach ($v as $k2 => $v2) {
                    if (!empty($v['peek'])) {
                        $headerpeek = true;
                        if (!isset($v2['id']) || ($v2['id'] === 0)) {
                            $headertext = $k2;
                            break;
                        }
                    }
                }
                break;

            case Horde_Imap_Client::FETCH_HEADERS:
                $this->_temp['headers_caching'] = array();

                /* Only cache if directly requested. */
                if (isset($cf[$k]) && $cache_avail) {
                    $fetch_field = 'headers';

                    foreach ($v as $key => $val) {
                        if (!empty($val['cache']) &&
                            !empty($val['peek'])) {
                            $cache_field = 'HIChdrs';
                            ksort($val);
                            $header_cache[] = array(
                                'f' => hash('md5', serialize($val)),
                                'k' => $key,
                                'l' => $val['label']
                            );
                        }
                    }
                }
                break;
            }

            if (!is_null($cache_field)) {
                $cache_array[$k] = array(
                    'c' => $cache_field,
                    'f' => $fetch_field
                );
                $get_fields[] = $cache_field;
            }
        }

        /* If nothing is cacheable, we can do a straight search. */
        if (empty($cache_array) && $headerpeek) {
            return $this->_fetch($criteria, $options);
        }

        /* If given sequence numbers, we need to switch to UIDs for caching
         * purposes. Also, we need UID #'s now if searching the entire
         * mailbox. However, if we are doing a changedsince since we need to
         * do that component of the search first to determine exactly what
         * UIDs we need to fetch. */
        if (!empty($options['changedsince'])) {
            if (!($res = $this->_fetch(array(Horde_Imap_Client::FETCH_UID => 1), $options))) {
                return array();
            }
            $options['ids'] = array_keys($res);
        }

        if ($seq || empty($options['ids'])) {
            $res_seq = $this->_getSeqUIDLookup(empty($options['ids']) ? null : $options['ids'], $seq);
            $uids = $res_seq['uids'];
        } else {
            $uids = $options['ids'];
        }

        /* Get the cached values. */
        if (!empty($cache_array)) {
            $data = $this->cache->get($this->_selected, $uids, $get_fields, $status_res['uidvalidity']);
        }

        // Build a list of what we still need.
        foreach ($uids as $val) {
            $crit = $criteria;
            $id = $seq ? $res_seq['lookup'][$val] : $val;
            $ret[$id] = array('uid' => $id);

            foreach ($cache_array as $key => $cval) {
                switch ($key) {
                case Horde_Imap_Client::FETCH_HEADERS:
                    /* HEADERS caching. */
                    foreach ($header_cache as $hval) {
                        if (isset($data[$val][$cval['c']][$hval['f']])) {
                            $ret[$id][$cval['f']][$hval['l']] = $data[$val][$cval['c']][$hval['f']];
                            unset($crit[$key][$hval['k']]);
                            if (empty($crit[$key])) {
                                unset($crit[$key]);
                            }
                        } else {
                            $this->_temp['headers_caching'][$hval['l']] = $hval['f'];
                        }
                    }
                    break;

                default:
                    /* Retrieved from cache so store in return array. */
                    if (isset($data[$val][$cval['c']])) {
                        $ret[$id][$cval['f']] = $data[$val][$cval['c']];
                        unset($crit[$key]);
                    }
                    break;
                }
            }


            /* HEADERTEXT caching for this access only. */
            if (!is_null($headertext) &&
                isset($this->_temp['headertext'][$val])) {
                $ret[$id]['headertext'][0] = empty($crit[Horde_Imap_Client::FETCH_HEADERTEXT][$headertext]['parse'])
                    ? $this->_temp['headertext'][$val]
                    : Horde_Mime_Headers::parseHeaders($this->_temp['headertext'][$val]);

                if (count($crit[Horde_Imap_Client::FETCH_HEADERTEXT]) == 1) {
                    unset($crit[Horde_Imap_Client::FETCH_HEADERTEXT]);
                } else {
                    unset($crit[Horde_Imap_Client::FETCH_HEADERTEXT][$headertext]);
                }
            }

            if (!$seq) {
                unset($crit[Horde_Imap_Client::FETCH_UID]);
            }

            if (!empty($crit)) {
                $sig = hash('md5', serialize(array_values($crit)));
                if (isset($new_criteria[$sig])) {
                    $new_criteria[$sig]['i'][] = $id;
                } else {
                    $new_criteria[$sig] = array('c' => $crit, 'i' => array($id));
                }
            }
        }

        if (!empty($new_criteria)) {
            $opts = $options;
            foreach ($new_criteria as $val) {
                $opts['ids'] = $val['i'];
                $fetch_res = $this->_fetch($val['c'], $opts);

                reset($fetch_res);
                while (list($k, $v) = each($fetch_res)) {
                    reset($v);
                    while (list($k2, $v2) = each($v)) {
                        switch ($k2) {
                        case 'headertext':
                            foreach ($val['c'][Horde_Imap_Client::FETCH_HEADERTEXT] as $hkey => $hval) {
                                if (!is_null($headertext) &&
                                    ($headertext == $hkey)) {
                                    /* Store headertext internally as the raw
                                     * text; can convert to parsed format later
                                     * if needed. */
                                    $this->_temp['headertext'][$k] = $v2[$hkey];
                                }

                                if (!empty($hval['parse'])) {
                                    $id = isset($hval['id']) ? $hval['id'] : 0;
                                    $v2[$id] = Horde_Mime_Headers::parseHeaders($v2[$id]);
                                }
                            }
                        }

                        $ret[$k][$k2] = $v2;
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Fetch message data.
     *
     * @param array $criteria  The fetch criteria. Function must not handle
     *                         'parse' param to FETCH_HEADERTEXT.
     * @param array $options   Additional options.
     *
     * @return array  See self::fetch().
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _fetch($criteria, $options);

    /**
     * Store message flag data (see RFC 3501 [6.4.6]).
     *
     * @param string $mailbox  The mailbox containing the messages to modify.
     *                         Either in UTF7-IMAP or UTF-8.
     * @param array $options   Additional options:
     * <pre>
     * 'add' - (array) An array of flags to add.
     *         DEFAULT: No flags added.
     * 'ids' - (array) The list of messages to modify.
     *         DEFAULT: All messages in $mailbox will be modified.
     * 'remove' - (array) An array of flags to remove.
     *            DEFAULT: No flags removed.
     * 'replace' - (array) Replace the current flags with this set
     *             of flags. Overrides both the 'add' and 'remove' options.
     *             DEFAULT: No replace is performed.
     * 'sequence' - (boolean) If true, 'ids' is an array of sequence numbers.
     *              DEFAULT: 'ids' is an array of UIDs.
     * 'unchangedsince' - (integer) Only changes flags if the mod-sequence ID
     *                    of the message is equal or less than this value.
     *                    Requires the CONDSTORE IMAP extension on the server.
     *                    Also requires the mailbox to support mod-sequences.
     *                    Will throw an exception if either condition is not
     *                    met.
     *                    DEFAULT: mod-sequence is ignored when applying
     *                             changes
     * </pre>
     *
     * @return array  If 'unchangedsince' is set, this is a list of UIDs or
     *                sequence numbers (if 'sequence' is true) that failed
     *                the 'unchangedsince' test.  Else, an empty array.
     * @throws Horde_Imap_Client_Exception
     */
    public function store($mailbox, $options = array())
    {
        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_READWRITE);

        if (!empty($options['unchangedsince']) &&
            !isset($this->_init['enabled']['CONDSTORE'])) {
            throw new Horde_Imap_Client_Exception('Server does not support the CONDSTORE extension.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
        }

        return $this->_store($options);
    }

    /**
     * Store message flag data.
     *
     * @param array $options  Additional options.
     *
     * @return array  See self::store().
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _store($options);

    /**
     * Copy messages to another mailbox.
     *
     * @param string $source   The source mailbox. Either in UTF7-IMAP
     *                         or UTF-8.
     * @param string $dest     The destination mailbox. Either in UTF7-IMAP
     *                         or UTF-8.
     * @param array $options   Additional options:
     * <pre>
     * 'create' - (boolean) Try to create $dest if it does not exist?
     *            DEFAULT: No.
     * 'ids' - (array) The list of messages to copy.
     *         DEFAULT: All messages in $mailbox will be copied.
     * 'move' - (boolean) If true, delete the original messages.
     *          DEFAULT: Original messages are not deleted.
     * 'sequence' - (boolean) If true, 'ids' is an array of sequence numbers.
     *              DEFAULT: 'ids' is an array of UIDs.
     * </pre>
     *
     * @return mixed  An array mapping old UIDs (keys) to new UIDs (values) on
     *                success (if the IMAP server and/or driver support the
     *                UIDPLUS extension) or true.
     * @throws Horde_Imap_Client_Exception
     */
    public function copy($source, $dest, $options = array())
    {
        $this->openMailbox($source, empty($options['move']) ? Horde_Imap_Client::OPEN_AUTO : Horde_Imap_Client::OPEN_READWRITE);
        return $this->_copy(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($dest), $options);
    }

    /**
     * Copy messages to another mailbox.
     *
     * @param string $dest    The destination mailbox (UTF7-IMAP).
     * @param array $options  Additional options.
     *
     * @return mixed  An array mapping old UIDs (keys) to new UIDs (values) on
     *                success (if the IMAP server and/or driver support the
     *                UIDPLUS extension) or true.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _copy($dest, $options);

    /**
     * Set quota limits. The server must support the IMAP QUOTA extension
     * (RFC 2087).
     *
     * @param string $root    The quota root. Either in UTF7-IMAP or UTF-8.
     * @param array $options  Additional options:
     * <pre>
     * 'messages' - (integer) The limit to set on the number of messages
     *              allowed.
     *              DEFAULT: No limit set.
     * 'storage' - (integer) The limit (in units of 1 KB) to set for the
     *             storage size.
     *             DEFAULT: No limit set.
     * </pre>
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function setQuota($root, $options = array())
    {
        if (!$this->queryCapability('QUOTA')) {
            throw new Horde_Imap_Client_Exception('Server does not support the QUOTA extension.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
        }

        if (isset($options['messages']) || isset($options['storage'])) {
            $this->_setQuota(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($root), $options);
        }
    }

    /**
     * Set quota limits.
     *
     * @param string $root    The quota root (UTF7-IMAP).
     * @param array $options  Additional options.
     *
     * @return boolean  True on success.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setQuota($root, $options);

    /**
     * Get quota limits. The server must support the IMAP QUOTA extension
     * (RFC 2087).
     *
     * @param string $root  The quota root. Either in UTF7-IMAP or UTF-8.
     *
     * @return mixed  An array with these possible keys: 'messages' and
     *                'storage'; each key holds an array with 2 values:
     *                'limit' and 'usage'.
     * @throws Horde_Imap_Client_Exception
     */
    public function getQuota($root)
    {
        if (!$this->queryCapability('QUOTA')) {
            throw new Horde_Imap_Client_Exception('Server does not support the QUOTA extension.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
        }

        return $this->_getQuota(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($root));
    }

    /**
     * Get quota limits.
     *
     * @param string $root  The quota root (UTF7-IMAP).
     *
     * @return mixed  An array with these possible keys: 'messages' and
     *                'storage'; each key holds an array with 2 values:
     *                'limit' and 'usage'.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getQuota($root);

    /**
     * Get quota limits for a mailbox. The server must support the IMAP QUOTA
     * extension (RFC 2087).
     *
     * @param string $mailbox  A mailbox. Either in UTF7-IMAP or UTF-8.
     *
     * @return mixed  An array with the keys being the quota roots. Each key
     *                holds an array with two possible keys: 'messages' and
     *                'storage'; each of these keys holds an array with 2
     *                values: 'limit' and 'usage'.
     * @throws Horde_Imap_Client_Exception
     */
    public function getQuotaRoot($mailbox)
    {
        if (!$this->queryCapability('QUOTA')) {
            throw new Horde_Imap_Client_Exception('Server does not support the QUOTA extension.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
        }

        return $this->_getQuotaRoot(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox));
    }

    /**
     * Get quota limits for a mailbox.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @return mixed  An array with the keys being the quota roots. Each key
     *                holds an array with two possible keys: 'messages' and
     *                'storage'; each of these keys holds an array with 2
     *                values: 'limit' and 'usage'.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getQuotaRoot($mailbox);

    /**
     * Get the ACL rights for a given mailbox. The server must support the
     * IMAP ACL extension (RFC 2086/4314).
     *
     * @param string $mailbox  A mailbox. Either in UTF7-IMAP or UTF-8.
     *
     * @return array  An array with identifiers as the keys and an array of
     *                rights as the values.
     * @throws Horde_Imap_Client_Exception
     */
    public function getACL($mailbox)
    {
        return $this->_getACL(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox));
    }

    /**
     * Get ACL rights for a given mailbox.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @return array  An array with identifiers as the keys and an array of
     *                rights as the values.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getACL($mailbox);

    /**
     * Set ACL rights for a given mailbox/identifier.
     *
     * @param string $mailbox     A mailbox. Either in UTF7-IMAP or UTF-8.
     * @param string $identifier  The identifier to alter. Either in UTF7-IMAP
     *                            or UTF-8.
     * @param array $options      Additional options:
     * <pre>
     * 'remove' - (boolean) If true, removes all rights for $identifier.
     *            DEFAULT: Rights in 'rights' are added.
     * 'rights' - (string) The rights to alter.
     *            DEFAULT: No rights are altered.
     * </pre>
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function setACL($mailbox, $identifier, $options)
    {
        if (!$this->queryCapability('ACL')) {
            throw new Horde_Imap_Client_Exception('Server does not support the ACL extension.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
        }

        return $this->_setACL(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox), Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($identifier), $options);
    }

    /**
     * Set ACL rights for a given mailbox/identifier.
     *
     * @param string $mailbox     A mailbox (UTF7-IMAP).
     * @param string $identifier  The identifier to alter (UTF7-IMAP).
     * @param array $options      Additional options.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setACL($mailbox, $identifier, $options);

    /**
     * List the ACL rights for a given mailbox/identifier. The server must
     * support the IMAP ACL extension (RFC 2086/4314).
     *
     * @param string $mailbox     A mailbox. Either in UTF7-IMAP or UTF-8.
     * @param string $identifier  The identifier to query. Either in UTF7-IMAP
     *                            or UTF-8.
     *
     * @return array  An array with two elements: 'required' (a list of
     *                required rights) and 'optional' (a list of rights the
     *                identifier can be granted in the mailbox; these rights
     *                may be grouped together to indicate that they are tied
     *                to each other).
     * @throws Horde_Imap_Client_Exception
     */
    public function listACLRights($mailbox, $identifier)
    {
        if (!$this->queryCapability('ACL')) {
            throw new Horde_Imap_Client_Exception('Server does not support the ACL extension.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
        }

        return $this->_listACLRights(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox), Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($identifier));
    }

    /**
     * Get ACL rights for a given mailbox/identifier.
     *
     * @param string $mailbox     A mailbox (UTF7-IMAP).
     * @param string $identifier  The identifier to query (UTF7-IMAP).
     *
     * @return array  An array of rights (keys: 'required' and 'optional').
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _listACLRights($mailbox, $identifier);

    /**
     * Get the ACL rights for the current user for a given mailbox. The
     * server must support the IMAP ACL extension (RFC 2086/4314).
     *
     * @param string $mailbox  A mailbox. Either in UTF7-IMAP or UTF-8.
     *
     * @return array  An array of rights.
     * @throws Horde_Imap_Client_Exception
     */
    public function getMyACLRights($mailbox)
    {
        if (!$this->queryCapability('ACL')) {
            throw new Horde_Imap_Client_Exception('Server does not support the ACL extension.', Horde_Imap_Client_Exception::NOSUPPORTIMAPEXT);
        }

        return $this->_getMyACLRights(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox));
    }

    /**
     * Get the ACL rights for the current user for a given mailbox.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     *
     * @return array  An array of rights.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getMyACLRights($mailbox);

    /**
     * Get metadata for a given mailbox. The server must support either the
     * IMAP METADATA extension (RFC 5464) or the ANNOTATEMORE extension
     * (http://ietfreport.isoc.org/idref/draft-daboo-imap-annotatemore/).
     *
     * @param string $mailbox  A mailbox. Either in UTF7-IMAP or UTF-8.
     * @param array $entries   The entries to fetch.
     * @param array $options   Additional options:
     * <pre>
     * 'depth' - (string) Either "0", "1" or "infinity". Returns only the
     *           given value ("0"), only values one level below the specified
     *           value (1) or all entries below the specified value
     *           ("infinity").
     * 'maxsize' - (integer) The maximal size the returned values may have.
     *             DEFAULT: No maximal size.
     *           DEFAULT: 0
     * </pre>
     *
     * @return array  An array with metadata names as the keys and metadata
     *                values as the values.
     * @throws Horde_Imap_Client_Exception
     */
    public function getMetadata($mailbox, $entries, $options = array())
    {
        if (!is_array($entries)) {
            $entries = array($entries);
        }

        return $this->_getMetadata(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox), array_map(array('Horde_Imap_Client_Utf7imap', 'Utf8ToUtf7Imap'), $entries), $options);
    }

    /**
     * Get metadata for a given mailbox.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     * @param array $entries   The entries to fetch.
     * @param array $options   Additional options.
     *
     * @return array  An array with metadata names as the keys and metadata
     *                values as the values.
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getMetadata($mailbox, $entries, $options);

    /**
     * Set metadata for a given mailbox/identifier.
     *
     * @param string $mailbox  A mailbox. Either in UTF7-IMAP or UTF-8. If
     *                         empty, sets a server annotation.
     * @param array $data      A set of data values. The metadata values
     *                         corresponding to the keys of the array will
     *                         be set to the values in the array.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function setMetadata($mailbox, $data)
    {
        $this->_setMetadata(Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mailbox), $data);
    }

    /**
     * Set metadata for a given mailbox/identifier.
     *
     * @param string $mailbox  A mailbox (UTF7-IMAP).
     * @param array $data      A set of data values. The metadata values
     *                         corresponding to the keys of the array will
     *                         be set to the values in the array.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setMetadata($mailbox, $data);

    /* Public utility functions. */

    /**
     * Returns a unique identifier for the current mailbox status.
     *
     * @param string $mailbox  A mailbox. Either in UTF7-IMAP or UTF-8.
     * @param array $addl      Additional cache info to add to the cache ID
     *                         string.
     *
     * @return string  The cache ID string, which will change when the
     *                 composition of the mailbox changes. The uidvalidity
     *                 will always be the first element, and will be delimited
     *                 by the '|' character.
     * @throws Horde_Imap_Client_Exception
     */
    public function getCacheId($mailbox, $addl = array())
    {
        $query = Horde_Imap_Client::STATUS_UIDVALIDITY;

        /* Use MODSEQ as cache ID if CONDSTORE extension is available. */
        if (isset($this->_init['enabled']['CONDSTORE'])) {
            $query |= Horde_Imap_Client::STATUS_HIGHESTMODSEQ;
        } else {
            $query |= Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_UIDNEXT;
        }

        $status = $this->status($mailbox, $query);

        if ($query & Horde_Imap_Client::STATUS_HIGHESTMODSEQ) {
            $parts = array(
                'V' . $status['uidvalidity'],
                'H' . $status['highestmodseq']
            );
        } else {
            if (empty($status['uidnext'])) {
                /* UIDNEXT is not strictly required on mailbox open. If it is
                 * not available, use the last UID in the mailbox instead. */
                $this->_temp['nocache'] = true;
                $search_res = $this->_getSeqUIDLookup(array($status['messages']), true);
                unset($this->_temp['nocache']);
                $status['uidnext'] = reset($search_res['uids']);
            }

            $parts = array(
                'V' . $status['uidvalidity'],
                'U' . $status['uidnext'],
                'M' . $status['messages']
            );
        }

        return implode('|', array_merge($parts, $addl));
    }

    /**
     * Parses a cacheID created by getCacheId().
     *
     * @param string $id  The cache ID.
     *
     * @return array  An array with the following information:
     * <pre>
     * 'highestmodseq' - (integer)
     * 'messages' - (integer)
     * 'uidnext' - (integer)
     * 'uidvalidity' - (integer) Always present
     * </pre>
     */
    public function parseCacheId($id)
    {
        $data = array(
            'H' => 'highestmodseq',
            'M' => 'messages',
            'U' => 'uidnext',
            'V' => 'uidvalidity'
        );
        $info = array();

        foreach (explode('|', $id) as $part) {
            if (isset($data[$part[0]])) {
                $info[$data[$part[0]]] = intval(substr($part, 1));
            }
        }

        return $info;
    }

    /**
     * Parses a client command array to create a server command string.
     *
     * @param string $out         The unprocessed command string.
     * @param array $query        An array with the following format:
     * <pre>
     * (array)
     *   * Array with keys 't' and 'v'
     *     + 't' key = IMAP data type (Horde_Imap_Client::DATA_* constants)
     *     + 'v' key = Data value
     *   * Array with only values
     *     + Treated as a parenthesized list
     * (null) Ignored
     * (resource) Treated as literal data
     * (string) Output as-is (raw)
     * </pre>
     * @param callback $callback  A callback function to use if literal data
     *                            is found. Two arguments are passed: the
     *                            command string (as built so far) and the
     *                            literal data. The return value should be the
     *                            new value for the current command string.
     *
     * @return string  The command string.
     */
    public function parseCommandArray($query, $out = '', $callback = null)
    {
        foreach ($query as $val) {
            if (is_null($val)) {
                continue;
            }

            if (is_array($val)) {
                if (isset($val['t'])) {
                    if ($val['t'] == Horde_Imap_Client::DATA_NUMBER) {
                        $out .= intval($val['v']);
                    } elseif (($val['t'] != Horde_Imap_Client::DATA_ATOM) &&
                              preg_match('/[\x80-\xff\n\r]/', $val['v'])) {
                        $out = is_null($callback)
                            /* This is technically not correct - 8-bit
                             * characters can not be sent in quoted text.
                             * However, this is the only valid fallback here -
                             * thus the need for a callback function to
                             * correctly handle. */
                            ? $out . $this->utils->escape($val['v'], true)
                            : call_user_func_array($callback, array($out, $val['v']));
                    } else {
                        switch ($val['t']) {
                        case Horde_Imap_Client::DATA_ASTRING:
                        case Horde_Imap_Client::DATA_MAILBOX:
                            /* Only requires quoting if an atom-special is
                             * present (besides resp-specials). */
                            $out .= $this->utils->escape($val['v'], preg_match('/[\x00-\x1f\x7f\(\)\{\s%\*"\\\\]/', $val['v']));
                            break;


                        case Horde_Imap_Client::DATA_ATOM:
                            $out .= $val['v'];
                            break;

                        case Horde_Imap_Client::DATA_STRING:
                            /* IMAP strings MUST be quoted. */
                            $out .= $this->utils->escape($val['v'], true);
                            break;

                        case Horde_Imap_Client::DATA_DATETIME:
                            $out .= '"' . $val['v'] . '"';
                            break;

                        case Horde_Imap_Client::DATA_LISTMAILBOX:
                            $out .= $this->utils->escape($val['v'], preg_match('/[\x00-\x1f\x7f\(\)\{\s"\\\\]/', $val['v']));
                            break;

                        case Horde_Imap_Client::DATA_NSTRING:
                            $out .= strlen($val['v'])
                                ? $this->utils->escape($val['v'], true)
                                : 'NIL';
                            break;
                        }
                    }
                } else {
                    $out = rtrim($this->parseCommandArray($val, $out . '(', $callback)) . ')';
                }

                $out .= ' ';
            } elseif (is_resource($val)) {
                /* Resource indicates literal data. Absolutely nothing we can
                 * do without a callback function here. */
                if (!is_null($callback)) {
                    $out = call_user_func_array($callback, array($out, $val)) . ' ';
                }
            } else {
                $out .= $val . ' ';
            }
        }

        return $out;
    }

    /**
     * Given an IMAP body section string, fetches the corresponding part.
     *
     * @param string $mailbox  The IMAP mailbox name.
     * @param integer $uid     The IMAP UID.
     * @param string $section  The IMAP section string.
     *
     * @return resource  The section contents in a stream.
     * @throws Horde_Imap_Client_Exception
     */
    public function fetchFromSectionString($mailbox, $uid, $section = null)
    {
        $section = trim($section);

        // BODY[]
        if (!strlen($section)) {
            $fetch = $this->fetch($mailbox, array(
                Horde_Imap_Client::FETCH_FULLMSG => array(
                    'peek' => true,
                    'stream' => true
                )
            ), array('ids' => array($uid)));
            return $fetch[$uid]['fullmsg'];
        }

        // BODY[<#.>HEADER.FIELDS<.NOT>()]
        if (($pos = stripos($section, 'HEADER.FIELDS')) !== false) {
            $hdr_pos = strpos($section, '(');
            $cmd = substr($section, 0, $hdr_pos);

            $fetch = $this->fetch($mailbox, array(
                Horde_Imap_Client::FETCH_HEADERS => array(
                    array(
                        'headers' => explode(' ', substr($section, $hdr_pos + 1, strrpos($section, ')') - $hdr_pos)),
                        'id' => ($pos ? substr($section, 0, $pos - 1) : 0),
                        'label' => 'section',
                        'notsearch' => (stripos($cmd, '.NOT') !== false),
                        'peek' => true
                    )
                )
            ), array('ids' => array($uid)));

            $stream = fopen('php://temp', 'w+');
            fwrite($stream, $fetch[$uid]['headers']['section']);
            return $stream;
        }

        // BODY[#]
        if (is_numeric(substr($section, -1))) {
            $fetch = $this->fetch($mailbox, array(
                Horde_Imap_Client::FETCH_BODYPART => array(
                    array(
                        'id' => $section,
                        'peek' => true,
                        'stream' => true
                    )
                )
            ), array('ids' => array($uid)));
            return $fetch[$uid]['bodypart'][$section];
        }

        // BODY[<#.>HEADER]
        if (($pos = stripos($section, 'HEADER')) !== false) {
            $id = ($pos ? substr($section, 0, $pos - 1) : 0);
            $fetch = $this->fetch($mailbox, array(
                Horde_Imap_Client::FETCH_HEADERTEXT => array(
                    array(
                        'id' => $id,
                        'peek' => true
                    )
                )
            ), array('ids' => array($uid)));

            $stream = fopen('php://temp', 'w+');
            fwrite($stream, $fetch[$uid]['headertext'][$id]);
            return $stream;
        }

        // BODY[<#.>TEXT]
        if (($pos = stripos($section, 'TEXT')) !== false) {
            $id = ($pos ? substr($section, 0, $pos - 1) : 0);
            $fetch = $this->fetch($mailbox, array(
                Horde_Imap_Client::FETCH_BODYTEXT => array(
                    array(
                        'id' => $id,
                        'peek' => true,
                        'stream' => true
                    )
                )
            ), array('ids' => array($uid)));
            return $fetch[$uid]['bodytext'][$id];
        }

        // BODY[<#.>MIMEHEADER]
        if (($pos = stripos($section, 'MIME')) !== false) {
            $id = ($pos ? substr($section, 0, $pos - 1) : 0);
            $fetch = $this->fetch($mailbox, array(
                Horde_Imap_Client::FETCH_MIMEHEADER => array(
                    array(
                        'id' => $id,
                        'peek' => true
                    )
                )
            ), array('ids' => array($uid)));

            $stream = fopen('php://temp', 'w+');
            fwrite($stream, $fetch[$uid]['mimeheader'][$id]);
            return $stream;
        }

        return null;
    }

    /**
     * Determines if the given charset is valid for search-related queries.
     *
     * @param string $charset  The query charset.
     *
     * @return boolean  True if server supports this charset.
     */
    public function validSearchCharset($charset)
    {
        if (!isset($this->_init['enabled']['s_charset'][$charset])) {
            $support = null;

            switch ($charset) {
            case 'US-ASCII';
                /* US-ASCII is always supported (RFC 3501 [6.4.4]). */
                $support = true;
                break;

            case 'UTF-8':
                /* SORT (RFC 5266) & ESORT (RFC 5267) require UTF-8
                 * support. */
                if ($this->queryCapability('SORT') ||
                    $this->queryCapability('ESORT')) {
                    $support = true;
                }
                break;
            }

            /* Use a dummy search query and search for BADCHARSET
             * response. */
            if (is_null($support)) {
                $query = new Horde_Imap_Client_Search_Query();
                $query->charset($charset);
                $query->text('a');
                try {
                    $this->search('INBOX', $query);
                    $support = true;
                } catch (Horde_Imap_Client_Exception $e) {
                    /* BADCHARSET is only a MAYBE return - but there is no
                     * other way of determining charset support. */
                    $support = ($e->getCode() != Horde_Imap_Client_Exception::BADCHARSET);
                }
            }

            $this->_init['enabled']['s_charset'][$charset] = $support;
        }

        return $this->_init['enabled']['s_charset'][$charset];
    }

    /* Private utility functions. */

    /**
     * Returns UIDs for an ALL search, or for a sequence number -> UID lookup.
     *
     * @param mixed $ids    If null, return all UIDs for the mailbox. If an
     *                      array, only look up these values.
     * @param boolean $seq  Are $ids sequence numbers?
     *
     * @return array  An array with 2 possible entries:
     * <pre>
     * 'lookup' - (array) If $seqs is not empty, the mapping of sequence
     *            numbers (keys) to UIDs (values).
     * 'uids' - (array) The list of UIDs.
     * </pre>
     */
    protected function _getSeqUIDLookup($ids, $seq)
    {
        if (is_null($ids)) {
            $search = null;
        } else {
            $search = new Horde_Imap_Client_Search_Query();
            $search->sequence($ids, $seq);
        }
        $res = $this->search($this->_selected, $search, array('sort' => array(Horde_Imap_Client::SORT_SEQUENCE)));
        $ret = array('uids' => $res['sort']);
        if ($seq) {
            if (!$res['count']) {
                $ret['lookup'] = array();
            } else {
                if (empty($ids)) {
                    $ids = range(1, $res['count']);
                } else {
                    sort($ids, SORT_NUMERIC);
                }
                $ret['lookup'] = array_combine($ret['uids'], $ids);
            }
        }

        return $ret;
    }

    /**
     * Store FETCH data in cache.
     *
     * @param array $data     The data array.
     * @param array $options  Additional options:
     * <pre>
     * 'mailbox' - (string) The mailbox to update.
     *             DEFAULT: The selected mailbox.
     * 'seq' - (boolean) Is data stored with sequence numbers?
     *         DEFAULT: Data stored with UIDs.
     * 'uidvalid' - (integer) The UID Validity number.
     *              DEFAULT: UIDVALIDITY discovered via a status() call.
     * </pre>
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _updateCache($data, $options = array())
    {
        $mailbox = empty($options['mailbox'])
            ? $this->_selected
            : $options['mailbox'];

        if (!$this->_initCache(empty($options['mailbox']))) {
            return;
        }

        if (!empty($options['seq'])) {
            $seq_res = $this->_getSeqUIDLookup(array_keys($data));
        }

        $cf = $this->_params['cache']['fields'];
        $tocache = array();

        $status_flags = 0;
        if (isset($this->_init['enabled']['CONDSTORE'])) {
            $status_flags |= Horde_Imap_Client::STATUS_HIGHESTMODSEQ;
        }
        if (empty($options['uidvalid'])) {
            $status_flags |= Horde_Imap_Client::STATUS_UIDVALIDITY;
        }

        $status_res = $this->status($mailbox, $status_flags);

        $highestmodseq = isset($status_res['highestmodseq'])
            ? array($status_res['highestmodseq'])
            : array();
        $uidvalid = isset($status_res['uidvalidity'])
            ? $status_res['uidvalidity']
            : $options['uidvalid'];

        reset($data);
        while (list($k, $v) = each($data)) {
            $tmp = array();
            $id = empty($options['seq']) ? $k : $seq_res['lookup'][$k];

            reset($v);
            while (list($label, $val) = each($v)) {
                switch ($label) {
                case 'structure':
                    if (isset($cf[Horde_Imap_Client::FETCH_STRUCTURE])) {
                        if (is_array($val)) {
                            $tmp['HICstructa'] = $val;
                        } else {
                            $tmp['HICstructm'] = clone $val;
                        }
                    }
                    break;

                case 'envelope':
                    if (isset($cf[Horde_Imap_Client::FETCH_ENVELOPE])) {
                        $tmp['HICenv'] = $val;
                    }
                    break;

                case 'flags':
                    if (isset($cf[Horde_Imap_Client::FETCH_FLAGS])) {
                        /* A FLAGS FETCH can only occur if we are in the
                         * mailbox. So either HIGHESTMODSEQ has already been
                         * updated or the flag FETCHs will provide the new
                         * HIGHESTMODSEQ value.  In either case, we are
                         * guaranteed that all cache information is correctly
                         * updated (in the former case, we reached here via
                         * an 'changedsince' FETCH and in the latter case, we
                         * are in EXAMINE/SELECT mode and will catch all flag
                         * changes). */
                        if (isset($v['modseq'])) {
                            $highestmodseq[] = $v['modseq'];
                        }
                        $tmp['HICflags'] = $val;
                    }
                    break;

                case 'date':
                    if (isset($cf[Horde_Imap_Client::FETCH_DATE])) {
                        $tmp['HICdate'] = $val;
                    }
                    break;

                case 'size':
                    if (isset($cf[Horde_Imap_Client::FETCH_SIZE])) {
                        $tmp['HICsize'] = $val;
                    }
                    break;

                case 'headers':
                    reset($val);
                    if (isset($this->_temp['headers_caching'][key($val)])) {
                        $tmp['HIChdrs'][$this->_temp['headers_caching'][key($val)]] = is_array($val)
                            ? current($val)
                            : clone current($val);
                    }
                    break;
                }
            }

            if (!empty($tmp)) {
                $tocache[$id] = $tmp;
            }
        }

        $this->cache->set($mailbox, $tocache, $uidvalid);

        if (!empty($highestmodseq)) {
            $modseq = max($highestmodseq);
            $metadata = $this->cache->getMetaData($mailbox, $uidvalid, array('HICmodseq'));
            if (!isset($metadata['HICmodseq']) ||
                ($metadata['HICmodseq'] != $modseq)) {
                $this->_temp['lastmodseq'][$mailbox] = isset($metadata['HICmodseq']) ? $metadata['HICmodseq'] : 0;
                if (count($tocache)) {
                    $this->_temp['lastmodsequids'][$mailbox] = $this->utils->toSequenceString(array_keys($tocache), array('nosort' => true));
                }
                $this->_updateMetaData($mailbox, array('HICmodseq' => $modseq), $uidvalid);
            }
        }
    }

    /**
     * Retrieve data from the search cache.
     *
     * @param string $type     The cache type ('search' or 'thread').
     * @param string $mailbox  The mailbox to update.
     * @param array $options   The options array of the calling function.
     *
     * @return array  If retrieved, data will be in key 'data'.  If not, array
     *                will contain state needed for self::_setSearchCache().
     */
    protected function _getSearchCache($type, $mailbox, $options)
    {
        ksort($options);
        $cache = hash('md5', $type . serialize($options));

        $status = $this->status($mailbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
        $metadata = $this->cache->getMetaData($mailbox, $status['uidvalidity'], array('HICsearch'));

        $cacheid = $this->getCacheId($mailbox);
        if (isset($metadata['HICsearch']['cacheid']) &&
            ($metadata['HICsearch']['cacheid'] != $cacheid)) {
            $metadata['HICsearch'] = array();
            if ($this->_debug) {
                fwrite($this->_debug, sprintf("Horde_Imap_Client: Expired search results from cache (mailbox: %s)\n", $mailbox));
            }
        }

        if (isset($metadata['HICsearch'][$cache])) {
            if ($this->_debug) {
                fwrite($this->_debug, sprintf("Horde_Imap_Client: Retrieved %s results from cache (mailbox: %s; id: %s)\n", $type, $mailbox, $cache));
            }
            return array('data' => unserialize($metadata['HICsearch'][$cache]));
        }

        $metadata['HICsearch']['cacheid'] = $cacheid;

        return array(
            'id' => $cache,
            'mailbox' => $mailbox,
            'metadata' => $metadata,
            'type' => $type
        );
    }

    /**
     * Set data in the search cache.
     *
     * @param mixed $data   The cache data to store.
     * @param array $cache  The data object returned from
     *                      self::_getSearchCache().
     */
    protected function _setSearchCache($data, $cache)
    {
        $cache['metadata']['HICsearch'][$cache['id']] = serialize($data);

        $this->_updateMetaData($cache['mailbox'], $cache['metadata']);

        if ($this->_debug) {
            fwrite($this->_debug, sprintf("Horde_Imap_Client: Saved %s results to cache (mailbox: %s; id: %s)\n", $cache['type'], $cache['mailbox'], $cache['id']));
        }
    }

    /**
     * Updates metadata for a mailbox.
     *
     * @param string $mailbox    Mailbox to update.
     * @param string $data       The data to update.
     * @param integer $uidvalid  The uidvalidity of the mailbox.  If not set,
     *                           do a status call to grab it.
     */
    protected function _updateMetaData($mailbox, $data, $uidvalid = null)
    {
        if (is_null($uidvalid)) {
            $status = $this->status($mailbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
            $uidvalid = $status['uidvalidity'];
        }
        $this->cache->setMetaData($mailbox, $uidvalid, $data);
    }

    /**
     * Prepares append message data for insertion into the IMAP command
     * string.
     *
     * @param mixed $data       Either a resource or a string.
     * @param resource $stream  The stream to append to. If not given, will
     *                          append to new stream.
     *
     * @param resource  A stream containing the message data.
     */
    protected function _prepareAppendData($data = null, $stream = null)
    {
        if (is_null($stream)) {
            $stream = fopen('php://temp', 'w+');
            stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
            stream_filter_append($stream, 'horde_eol', STREAM_FILTER_WRITE);
        }

        if (!is_null($data)) {
            if (is_resource($data)) {
                rewind($data);
                stream_copy_to_stream($data, $stream);
            } else {
                fwrite($stream, $data);
            }
        }

        return $stream;
    }

    /**
     * Builds a stream from CATENATE input to append().
     *
     * @param array $data  See append() - array input for the 'data' key to
     *                     the $data parameter.
     *
     * @return resource  The data combined into a single stream.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _buildCatenateData($data)
    {
        $stream = $this->_prepareAppendData();

        foreach (array_keys($data) as $key2) {
            switch ($data[$key2]['t']) {
            case 'text':
                $this->_prepareAppendData($data[$key2]['v'], $stream);
                break;

            case 'url':
                $part = null;
                $url = $this->utils->parseUrl($data[$key2]['v']);

                if (isset($url['mailbox']) &&
                    isset($url['uid'])) {
                    try {
                        $status_res = isset($url['uidvalidity'])
                            ? $this->status($url['mailbox'], Horde_Imap_Client::STATUS_UIDVALIDITY)
                            : null;

                        if (is_null($status_res) ||
                            ($status_res['uidvalidity'] == $url['uidvalidity'])) {
                            $part = $this->fetchFromSectionString($url['mailbox'], $url['uid'], isset($url['section']) ? $url['section'] : null);
                        }
                    } catch (Horde_Imap_Client_Exception $e) {}
                }

                if (is_null($part)) {
                    throw new Horde_Imap_Client_Exception('Bad IMAP URL given in CATENATE data.', Horde_Imap_Client_Exception::CATENATE_BADURL);
                } else {
                    $this->_prepareAppendData($part, $stream);
                }
                break;
            }
        }

        return $stream;
    }

}
