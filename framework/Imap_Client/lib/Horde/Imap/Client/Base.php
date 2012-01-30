<?php
/**
 * An abstracted API interface to IMAP backends supporting the IMAP4rev1
 * protocol (RFC 3501).
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 *
 * @property Horde_Imap_Client_Utils $utils  A Utils object.
 */
abstract class Horde_Imap_Client_Base implements Serializable
{
    /* Serialized version. */
    const VERSION = 1;

    /* Cache names for miscellaneous data. */
    const CACHE_MODSEQ = 'HICmodseq';
    const CACHE_SEARCH = 'HICsearch';

    /**
     * The Horde_Imap_Client_Cache object.
     *
     * @var Horde_Imap_Client_Cache
     */
    public $cache = null;

    /**
     * The list of fetch fields that can be cached, and their cache names.
     *
     * @var array
     */
    public $cacheFields = array(
        Horde_Imap_Client::FETCH_ENVELOPE => 'HICenv',
        Horde_Imap_Client::FETCH_FLAGS => 'HICflags',
        Horde_Imap_Client::FETCH_HEADERS => 'HIChdrs',
        Horde_Imap_Client::FETCH_IMAPDATE => 'HICdate',
        Horde_Imap_Client::FETCH_SIZE => 'HICsize',
        Horde_Imap_Client::FETCH_STRUCTURE => 'HICstruct'
    );

    /**
     * Has the internal configuration changed?
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * The debug stream.
     *
     * @var resource
     */
    protected $_debug = null;

    /**
     * The fetch data object type to return.
     *
     * @var string
     */
    protected $_fetchDataClass = 'Horde_Imap_Client_Data_Fetch';

    /**
     * Cached server data.
     *
     * @var array
     */
    protected $_init = array(
        'enabled' => array(),
        'namespace' => array(),
        's_charset' => array()
    );

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
     * The current mailbox selection mode.
     *
     * @var integer
     */
    protected $_mode = 0;

    /**
     * Hash containing connection parameters.
     * This hash never changes.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The currently selected mailbox.
     *
     * @var Horde_Imap_Client_Mailbox
     */
    protected $_selected = null;

    /**
     * Temp array (destroyed at end of process).
     *
     * @var array
     */
    protected $_temp = array();

    /**
     * The Horde_Imap_Client_Utils object.
     *
     * @var Horde_Imap_Client_Utils
     */
    protected $_utils;

    /**
     * The utils class to use.
     *
     * @var string
     */
    protected $_utilsClass = 'Horde_Imap_Client_Utils';

    /**
     * Constructor.
     *
     * @see Horde_Imap_Client::factory()
     *
     * @param array $params  A hash containing configuration parameters.
     *                       See Horde_Imap_Client::factory().
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['username']) || !isset($params['password'])) {
            throw new InvalidArgumentException('Horde_Imap_Client requires a username and password.');
        }

        // Default values.
        $params = array_merge(array(
            'encryptKey' => null,
            'hostspec' => 'localhost',
            'log' => null,
            'port' => ((isset($params['secure']) && ($params['secure'] == 'ssl')) ? 993 : 143),
            'secure' => false,
            'timeout' => 30
        ), array_filter($params));

        if (empty($params['cache'])) {
            $params['cache'] = array('fields' => array());
        } elseif (empty($params['cache']['fields'])) {
            $params['cache']['fields'] = $this->cacheFields;
        } else {
            $params['cache']['fields'] = array_flip($params['cache']['fields']);
        }

        if (empty($params['cache']['fetch_ignore'])) {
            $params['cache']['fetch_ignore'] = array();
        }

        $this->_params = $params;

        // Encrypt password.
        try {
            $encrypt_key = $this->_getEncryptKey();
            if (strlen($encrypt_key)) {
                $secret = new Horde_Secret();
                $this->_params['password'] = $secret->write($encrypt_key, $this->_params['password']);
                $this->_params['_passencrypt'] = true;
            }
        } catch (Horde_Imap_Client_Exception $e) {}

        $this->changed = true;

        $this->_initOb();
    }

    /**
     * Get encryption key.
     *
     * @return string  The encryption key.
     */
    protected function _getEncryptKey()
    {
        if (is_callable($this->_params['encryptKey'])) {
            return call_user_func($this->_params['encryptKey']);
        }

        throw new InvalidArgumentException('encryptKey parameter is not a valid callback.');
    }

    /**
     * Exception wrapper - logs an error message before (optionally) throwing
     * exception.
     *
     * Server debug information, if present, will be stored in the 'details'
     * property of the exception object.
     *
     * @param mixed $msg            Error message/error object. If an array,
     *                              the first entry is used as the exception
     *                              message and the second entry is taken
     *                              to be server debug information.
     * @param integer|string $code  Error code. If string, will convert from
     *                              the Exception constant of the same name.
     *                              If 'NO_SUPPORT', throws a non-supported
     *                              extension exception.
     * @param boolean $logonly      If true, log only and don't throw
     *                              exception.
     *
     * @throws Horde_Imap_Client_Exception
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    protected function _exception($msg, $code = 0, $logonly = false)
    {
        if (is_array($msg)) {
            $details = $msg[1];
            $msg = $msg[0];
        } else {
            $details = null;
        }

        if (is_integer($code)) {
            $e = new Horde_Imap_Client_Exception($msg, $code);
        } elseif ($code == 'NO_SUPPORT') {
            $e = new Horde_Imap_Client_Exception_NoSupportExtension($msg);
        } else {
            $e = new Horde_Imap_Client_Exception($msg, constant('Horde_Imap_Client_Exception::' . $code));
        }

        if (!is_null($details)) {
            $e->details = $details;
        }

        if (is_callable($this->_params['log'])) {
            call_user_func($this->_params['log'], $e);
        }

        if (!$logonly) {
            throw $e;
        }
    }

    /**
     * Do initialization tasks.
     */
    protected function _initOb()
    {
        if (!empty($this->_params['debug'])) {
            if (is_resource($this->_params['debug'])) {
                $this->_debug = $this->_params['debug'];
            } else {
                $this->_debug = @fopen($this->_params['debug'], 'a');
            }
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->logout();

        /* Close debugging output. */
        if (is_resource($this->_debug)) {
            fflush($this->_debug);
            fclose($this->_debug);
            $this->_debug = null;
        }
    }

    /**
     */
    public function serialize()
    {
        return serialize(array(
            'i' => $this->_init,
            'p' => $this->_params,
            'v' => self::VERSION
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data['v']) ||
            ($data['v'] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_init = $data['i'];
        $this->_params = $data['p'];

        $this->_initOb();
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'utils':
            if (!isset($this->_utils)) {
                $this->_utils = new $this->_utilsClass();
            }
            return $this->_utils;
        }
    }

    /**
     * Set an initialization value.
     *
     * @param string $key  The initialization key.
     * @param mixed $val   The cached value. If null, removes the key.
     */
    public function _setInit($key, $val = null)
    {
        if (is_null($val)) {
            unset($this->_init[$key]);
        } else {
            switch ($key) {
            case 'capability':
                if (!empty($this->_params['capability_ignore'])) {
                    if ($this->_debug &&
                        ($ignored = array_intersect_key($val, array_flip($this->_params['capability_ignore'])))) {
                        $this->writeDebug(sprintf("IGNORING these IMAP capabilities: %s\n", implode(', ', array_keys($ignored))), Horde_Imap_Client::DEBUG_INFO);
                    }
                    $val = array_diff_key($val, array_flip($this->_params['capability_ignore']));
                }
                break;
            }

            $this->_init[$key] = $val;
        }
        $this->changed = true;
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
     */
    protected function _initCache($current = false)
    {
        if (empty($this->_params['cache']['fields']) ||
            !empty($this->_temp['nocache'])) {
            return false;
        }

        if (is_null($this->cache)) {
            try {
                $this->cache = new Horde_Imap_Client_Cache(array_merge($this->getParam('cache'), array(
                    'baseob' => $this,
                    'debug' => (bool)$this->_debug
                )));
            } catch (InvalidArgumentException $e) {
                return false;
            }
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
     * Update the list of ignored mailboxes for caching FETCH data.
     *
     * @param array $mboxes  The list of mailboxes to ignore.
     */
    public function fetchCacheIgnore(array $mboxes)
    {
        $this->_params['cache']['fetch_ignore'] = $mboxes;
        $this->changed = true;
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
        /* Passwords may be stored encrypted. */
        if (($key == 'password') && !empty($this->_params['_passencrypt'])) {
            try {
                $secret = new Horde_Secret();
                return $secret->read($this->_getEncryptKey(), $this->_params['password']);
            } catch (Exception $e) {
                return null;
            }
        }

        return isset($this->_params[$key])
            ? $this->_params[$key]
            : null;
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
     * Returns the correct IDs object for use with this driver.
     *
     * @param mixed $ids         See self::add().
     * @param boolean $sequence  Are $ids message sequence numbers?
     *
     * @return Horde_Imap_Client_Ids  The IDs object.
     */
    public function getIdsOb($ids = null, $sequence = false)
    {
        return new Horde_Imap_Client_Ids($ids, $sequence);
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

        if (!isset($this->_init['capability'][$capability])) {
            return false;
        }

        /* Check for capability requirements. */
        if (isset(Horde_Imap_Client::$capability_deps[$capability])) {
            foreach (Horde_Imap_Client::$capability_deps[$capability] as $val) {
                if (!$this->queryCapability($val)) {
                    return false;
                }
            }
        }

        return $this->_init['capability'][$capability];
    }

    /**
     * Get CAPABILITY information from the IMAP server.
     *
     * @return array  The capability array.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function capability()
    {
        if (!isset($this->_init['capability'])) {
            $this->_setInit('capability', $this->_capability());
        }

        return $this->_init['capability'];
    }

    /**
     * Get CAPABILITY information from the IMAP server.
     *
     * @return array  The capability array.
     *
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
     *                           the server. The namespaces must be UTF-8
     *                           strings.
     *
     * @return array  An array of namespace information with the name as the
     *                key (UTF7-IMAP) and the following values:
     * <ul>
     *  <li>delimiter: (string) The namespace delimiter.</li>
     *  <li>hidden: (boolean) Is this a hidden namespace?</li>
     *  <li>name: (string) The namespace name (UTF7-IMAP).</li>
     *  <li>
     *   translation: (string) Returns the translated name of the namespace
     *   (UTF-8). Requires RFC 5255 and a previous call to setLanguage().
     *  </li>
     *  <li>
     *   type: (integer) The namespace type. Either:
     *   <ul>
     *    <li>Horde_Imap_Client::NS_PERSONAL</li>
     *    <li>Horde_Imap_Client::NS_OTHER</li>
     *    <li>Horde_Imap_Client::NS_SHARED</li>
     *   </ul>
     *  </li>
     * </ul>
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getNamespaces(array $additional = array())
    {
        $this->login();

        $sig = hash('md5', serialize($additional));

        if (isset($this->_init['namespace'][$sig])) {
            return $this->_init['namespace'][$sig];
        }

        $ns = $this->_getNamespaces();

        foreach (array_map(array('Horde_Imap_Client_Utf7imap', 'Utf8ToUtf7Imap'), $additional) as $val) {
            /* Skip namespaces if we have already auto-detected them. Also,
             * hidden namespaces cannot be empty. */
            if (!strlen($val) || isset($ns[$val->utf7imap])) {
                continue;
            }

            $mbox = $this->listMailboxes($val, Horde_Imap_Client::MBOX_ALL, array('delimiter' => true, 'utf8' => true));
            $first = reset($mbox);

            if ($first && ($first['mailbox'] == $val)) {
                $ns[$val->utf7imap] = array(
                    'delimiter' => $first['delimiter'],
                    'hidden' => true,
                    'name' => $val->utf7imap,
                    'translation' => '',
                    'type' => Horde_Imap_Client::NS_SHARED
                );
            }
        }

        if (empty($ns)) {
            /* This accurately determines the namespace information of the
             * base namespace if the NAMESPACE command is not supported.
             * See: RFC 3501 [6.3.8] */
            $mbox = $this->listMailboxes('', Horde_Imap_Client::MBOX_ALL, array('delimiter' => true, 'utf8' => true));
            $first = reset($mbox);
            $ns[''] = array(
                'delimiter' => $first['delimiter'],
                'hidden' => false,
                'name' => '',
                'translation' => '',
                'type' => Horde_Imap_Client::NS_PERSONAL
            );
        }

        $this->_setInit('namespace', array_merge($this->_init['namespace'], array($sig => $ns)));

        return $ns;
    }

    /**
     * Get the NAMESPACE information from the IMAP server.
     *
     * @return array  An array of namespace information. See getNamespaces()
     *                for format.
     *
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
                } catch (Horde_Imap_Client_Exception_NoSupportExtension $e) {
                    // Ignore if server doesn't support ID extension.
                }
            }

            if (!empty($this->_params['comparator'])) {
                try {
                    $this->setComparator();
                } catch (Horde_Imap_Client_Exception_NoSupportExtension $e) {
                    // Ignore if server doesn't support I18NLEVEL=2
                }
            }

            /* Check for ability to cache flags here. */
            if (!isset($this->_init['enabled']['CONDSTORE'])) {
                unset($this->_params['cache']['fields'][Horde_Imap_Client::FETCH_FLAGS]);
            }
        }

        $this->_isAuthenticated = true;
    }

    /**
     * Login to the IMAP server.
     *
     * @return boolean  Return true if global login tasks should be run.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _login();

    /**
     * Logout from the IMAP server (see RFC 3501 [6.1.3]).
     */
    public function logout()
    {
        if ($this->_isAuthenticated) {
            $this->_logout();
            $this->_isAuthenticated = false;
        }
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
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function sendID($info = null)
    {
        if (!$this->queryCapability('ID')) {
            $this->_exception('The IMAP server does not support the ID extension.', 'NO_SUPPORT');
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
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getID()
    {
        if (!$this->queryCapability('ID')) {
            $this->_exception('The IMAP server does not support the ID extension.', 'NO_SUPPORT');
        }

        return $this->_getID();
    }

    /**
     * Return ID information from the IMAP server (RFC 2971).
     *
     * @return array  An array of information returned, with the keys as the
     *                'field' and the values as the 'value'.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getID();

    /**
     * Sets the preferred language for server response messages (RFC 5255).
     *
     * @param array $langs  Overrides the value of the 'lang' param and sends
     *                      this list of preferred languages instead. The
     *                      special string 'i-default' can be used to restore
     *                      the language to the server default.
     *
     * @return string  The language accepted by the server, or null if the
     *                 default language is used.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function setLanguage($langs = null)
    {
        $lang = null;

        if ($this->queryCapability('LANGUAGE')) {
            $lang = is_null($langs)
                ? (empty($this->_params['lang']) ? null : $this->_params['lang'])
                : $langs;
        }

        return is_null($lang)
            ? null
            : $this->_setLanguage($lang);
    }

    /**
     * Sets the preferred language for server response messages (RFC 5255).
     *
     * @param array $langs  The preferred list of languages.
     *
     * @return string  The language accepted by the server, or null if the
     *                 default language is used.
     *
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
     *
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
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getLanguage($list);

    /**
     * Open a mailbox.
     *
     * @param mixed $mailbox  The mailbox to open. Either a
     *                        Horde_Imap_Client_Mailbox object (as of 1.2.0)
     *                        or a string (UTF-8).
     * @param integer $mode   The access mode. Either
     *   - Horde_Imap_Client::OPEN_READONLY
     *   - Horde_Imap_Client::OPEN_READWRITE
     *   - Horde_Imap_Client::OPEN_AUTO
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function openMailbox($mailbox, $mode = Horde_Imap_Client::OPEN_AUTO)
    {
        $this->login();

        $change = false;
        $mailbox = Horde_Imap_Client_Mailbox::get($mailbox, null);

        if ($mode == Horde_Imap_Client::OPEN_AUTO) {
            if (is_null($this->_selected) ||
                !$mailbox->equals($this->_selected)) {
                $mode = Horde_Imap_Client::OPEN_READONLY;
                $change = true;
            }
        } elseif (is_null($this->_selected) ||
                  !$mailbox->equals($this->_selected) ||
                  ($mode != $this->_mode)) {
            $change = true;
        }

        if ($change) {
            $this->_openMailbox($mailbox, $mode);
            $this->_selected = $mailbox;
            $this->_mode = $mode;
            unset($this->_temp['statuscache'][strval($mailbox)]);
        }
    }

    /**
     * Open a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to open.
     * @param integer $mode                       The access mode.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _openMailbox(Horde_Imap_Client_Mailbox $mailbox,
                                             $mode);

    /**
     * Return the currently opened mailbox and access mode.
     *
     * @param array $options  Additional options:
     *   - utf8: (boolean) True if 'mailbox' should be in UTF-8 [DEPRECATED].
     *           DEFAULT: 'mailbox' returned in UTF7-IMAP.
     *
     * @return mixed  Null if no mailbox selected, or an array with two
     *                elements:
     *   - mailbox: (mixed) If 'utf8' is true, returns a
     *              Horde_Imap_Client_Mailbox object. Otherwise, returns a
     *              string (UTF7-IMAP; DEPRECATED).
     *   - mode: (integer) Current mode.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function currentMailbox()
    {
        return is_null($this->_selected)
            ? null
            : array(
                'mailbox' => (empty($options['utf8']) ? $this->_selected->utf7imap : clone($this->_selected)),
                'mode' => $this->_mode
            );
    }

    /**
     * Create a mailbox.
     *
     * @param mixed $mailbox  The mailbox to create. Either a
     *                        Horde_Imap_Client_Mailbox object (as of 1.2.0)
     *                        or a string (UTF-8).
     * @param array $opts     Additional options:
     *   - special_use: (array) An array of special-use flags to mark the
     *                  mailbox with. The server MUST support RFC 6154.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function createMailbox($mailbox, array $opts = array())
    {
        $this->login();

        if (!$this->queryCapability('CREATE-SPECIAL-USE')) {
            unset($opts['special_use']);
        }

        $this->_createMailbox(Horde_Imap_Client_Mailbox::get($mailbox, null), $opts);
    }

    /**
     * Create a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to create.
     * @param array $opts                         Additional options. See
     *                                            createMailbox().
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _createMailbox(Horde_Imap_Client_Mailbox $mailbox,
                                               $opts);

    /**
     * Delete a mailbox.
     *
     * @param mixed $mailbox  The mailbox to delete. Either a
     *                        Horde_Imap_Client_Mailbox object (as of 1.2.0)
     *                        or a string (UTF-8).
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function deleteMailbox($mailbox)
    {
        $this->login();

        $mailbox = Horde_Imap_Client_Mailbox::get($mailbox, null);

        $this->_deleteMailbox($mailbox);

        /* Delete mailbox caches. */
        if ($this->_initCache()) {
            $this->cache->deleteMailbox($mailbox);
        }
        unset($this->_temp['statuscache'][strval($mailbox)]);

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
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to delete.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _deleteMailbox(Horde_Imap_Client_Mailbox $mailbox);

    /**
     * Rename a mailbox.
     *
     * @param mixed $old  The old mailbox name. Either a
     *                    Horde_Imap_Client_Mailbox object (as of 1.2.0) or a
     *                    string (UTF-8).
     * @param mixed $new  The new mailbox name. Either a
     *                    Horde_Imap_Client_Mailbox object (as of 1.2.0) or a
     *                    string (UTF-8).
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function renameMailbox($old, $new)
    {
        // Login will be handled by first listMailboxes() call.

        $old = Horde_Imap_Client_Mailbox::get($old, null);
        $new = Horde_Imap_Client_Mailbox::get($new, null);

        /* Check if old mailbox(es) were subscribed to. */
        $base = $this->listMailboxes($old, Horde_Imap_Client::MBOX_SUBSCRIBED, array('delimiter' => true, 'utf8' => true));
        if (empty($base)) {
            $base = $this->listMailboxes($old, Horde_Imap_Client::MBOX_ALL, array('delimiter' => true, 'utf8' => true));
            $base = reset($base);
            $subscribed = array();
        } else {
            $base = reset($base);
            $subscribed = array($base['mailbox']);
        }

        $all_mboxes = array($base['mailbox']);
        if (strlen($base['delimiter'])) {
            $all_mboxes = array_merge($all_mboxes, $this->listMailboxes($old . $base['delimiter'] . '*', Horde_Imap_Client::MBOX_ALL, array('flat' => true, 'utf8' => true)));
            $subscribed = array_merge($subscribed, $this->listMailboxes($old . $base['delimiter'] . '*', Horde_Imap_Client::MBOX_SUBSCRIBED, array('flat' => true, 'utf8' => true)));
        }

        $this->_renameMailbox($old, $new);

        /* Delete mailbox caches. */
        foreach ($all_mboxes as $val) {
            if ($this->_initCache()) {
                $this->cache->deleteMailbox($val);
            }
            unset($this->_temp['statuscache'][strval($val)]);
        }

        foreach ($subscribed as $val) {
            /* Clean up subscription information. */
            try {
                $this->subscribeMailbox($val, false);
                $this->subscribeMailbox(new Horde_Imap_Client_Mailbox(substr_replace($val, $new, 0, strlen($old))));
            } catch (Horde_Imap_Client_Exception $e) {
                // Ignore failed unsubscribe request
            }
        }
    }

    /**
     * Rename a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $old  The old mailbox name.
     * @param Horde_Imap_Client_Mailbox $new  The new mailbox name.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _renameMailbox(Horde_Imap_Client_Mailbox$old,
                                               Horde_Imap_Client_Mailbox $new);

    /**
     * Manage subscription status for a mailbox.
     *
     * @param mixed $mailbox      The mailbox to [un]subscribe to. Either a
     *                            Horde_Imap_Client_Mailbox object (as of
     *                            1.2.0) or a string (UTF-8).
     * @param boolean $subscribe  True to subscribe, false to unsubscribe.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function subscribeMailbox($mailbox, $subscribe = true)
    {
        $this->login();
        $this->_subscribeMailbox(Horde_Imap_Client_Mailbox::get($mailbox, null), (bool)$subscribe);
    }

    /**
     * Manage subscription status for a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to [un]subscribe
     *                                            to.
     * @param boolean $subscribe                  True to subscribe, false to
     *                                            unsubscribe.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _subscribeMailbox(Horde_Imap_Client_Mailbox $mailbox,
                                                  $subscribe);

    /**
     * Obtain a list of mailboxes matching a pattern.
     *
     * @param mixed $pattern   The mailbox search pattern(s) (see RFC 3501
     *                         [6.3.8] for the format). A UTF-8 string or an
     *                         array of strings.
     * @param integer $mode    Which mailboxes to return.  Either:
     *   - Horde_Imap_Client::MBOX_SUBSCRIBED
     *   - Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS
     *   - Horde_Imap_Client::MBOX_UNSUBSCRIBED
     *   - Horde_Imap_Client::MBOX_ALL
     * @param array $options   Additional options:
     * <ul>
     *  <li>
     *   attributes: (boolean) If true, return attribute information under
     *   the 'attributes' key.
     *   DEFAULT: Do not return this information.
     *  </li>
     *  <li>
     *   children: (boolean) Tell server to return children attribute
     *   information (\HasChildren, \HasNoChildren). Requires the
     *   LIST-EXTENDED extension to guarantee this information is returned.
     *   Server MAY return this attribute without this option, or if the
     *   CHILDREN extension is available, but it is not guaranteed.
     *   DEFAULT: false
     *  </li>
     *  <li>
     *   delimiter: (boolean) If true, return delimiter information under the
     *   'delimiter' key.
     *   DEFAULT: Do not return this information.
     *  </li>
     *  <li>
     *   flat: (boolean) If true, return a flat list of mailbox names only.
     *   Overrides both the 'attributes' and 'delimiter' options.
     *   DEFAULT: Do not return flat list.
     *  </li>
     *  <li>
     *   recursivematch: (boolean) Force the server to return information
     *   about parent mailboxes that don't match other selection options, but
     *   have some submailboxes that do. Information about children is
     *   returned in the CHILDINFO extended data item ('extended'). Requires
     *   the LIST-EXTENDED extension.
     *   DEFAULT: false
     *  </li>
     *  <li>
     *   remote: (boolean) Tell server to return mailboxes that reside on
     *   another server. Requires the LIST-EXTENDED extension.
     *   DEFAULT: false
     *  </li>
     *  <li>
     *   special_use: (boolean) Tell server to return special-use attribute
     *   information (\Drafts, \Flagged, \Junk, \Sent, \Trash, \All,
     *   \Archive). Server must support the SPECIAL-USE return option for this
     *   setting to have any effect. Server MAY return this attribute without
     *   this option.
     *   DEFAULT: false
     *  <li>
     *   status: (integer) Tell server to return status information. The
     *   value is a bitmask that may contain any of:
     *   <ul>
     *    <li>Horde_Imap_Client::STATUS_MESSAGES</li>
     *    <li>Horde_Imap_Client::STATUS_RECENT</li>
     *    <li>Horde_Imap_Client::STATUS_UIDNEXT</li>
     *    <li>Horde_Imap_Client::STATUS_UIDVALIDITY</li>
     *    <li>Horde_Imap_Client::STATUS_UNSEEN</li>
     *    <li>Horde_Imap_Client::STATUS_HIGHESTMODSEQ</li>
     *   </ul>
     *           Requires the LIST-STATUS extension.
     *           DEFAULT: 0
     *  </li>
     *  <li>
     *   sort: (boolean) If true, return a sorted list of mailboxes?
     *   DEFAULT: Do not sort the list.
     *  </li>
     *  <li>
     *   sort_delimiter: (string) If 'sort' is true, this is the delimiter
     *   used to sort the mailboxes.
     *   DEFAULT: '.'
     *  </li>
     *  <li>
     *   utf8: (boolean) True to return mailbox names in UTF-8.
     *   DEFAULT: Names are returned in UTF7-IMAP.
     *  </li>
     * </ul>
     *
     * @return array  If 'flat' option is true, the array values are a list
     *                of Horde_Imap_Client_Mailbox objects (if the 'utf8'
     *                parameter is true) or a list of UTF7-IMAP strings.
     *                Otherwise, the array values are arrays with these keys:
     *   - attributes: (array) List of lower-cased attributes [only if
     *                 'attributes' option is true].
     *   - delimiter: (string) The delimiter for the mailbox [only if
     *                'delimiter' option is true].
     *   - extended: (TODO) TODO [only if 'recursivematch' option is true and
     *               LIST-EXTENDED extension is supported on the server].
     *   - mailbox: (mixed) The mailbox. A Horde_Imap_Client_Mailbox object if
     *              the 'utf8' parameter is true, or a UTF7-IMAP string.
     *   - status: (array) See status() [only if 'status' option is true and
     *             LIST-STATUS extension is supported on the server].
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function listMailboxes($pattern, $mode = Horde_Imap_Client::MBOX_ALL,
                                  $options = array())
    {
        $this->login();

        if (!is_array($pattern)) {
            $pattern = array($pattern);
        }

        if (isset($options['special_use']) &&
            !$this->queryCapability('SPECIAL-USE')) {
            unset($options['special_use']);
        }

        $ret = $this->_listMailboxes(
            array_map(array('Horde_Imap_Client_Utf7imap', 'Utf8ToUtf7Imap'), $pattern, array_fill(0, count($pattern), null)),
            $mode,
            $options
        );

        if (!empty($options['sort'])) {
            Horde_Imap_Client_Sort::sortMailboxes($ret, array('delimiter' => empty($options['sort_delimiter']) ? '.' : $options['sort_delimiter'], 'index' => false, 'keysort' => empty($options['flat'])));
        }

        return $ret;
    }

    /**
     * Obtain a list of mailboxes matching a pattern.
     *
     * @param array $pattern  The mailbox search patterns (UTF7-IMAP strings).
     * @param integer $mode   Which mailboxes to return.
     * @param array $options  Additional options.
     *
     * @return array  See listMailboxes().
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _listMailboxes($pattern, $mode, $options);

    /**
     * Obtain status information for a mailbox.
     *
     * @param mixed $mailbox  The mailbox to query. Either a
     *                        Horde_Imap_Client_Mailbox object (as of 1.2.0)
     *                        or a string (UTF-8).
     * @param integer $flags  A bitmask of information requested from the
     *                        server. Allowed flags:
     * <ul>
     *  <li>
     *   Horde_Imap_Client::STATUS_MESSAGES
     *   <ul>
     *    <li>
     *     Return key: messages
     *    </li>
     *    <li>
     *     Return format: (integer) The number of messages in the mailbox.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_RECENT
     *   <ul>
     *    <li>
     *     Return key: recent
     *    </li>
     *    <li>
     *     Return format: (integer) The number of messages with the \Recent
     *     flag set
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_UIDNEXT
     *   <ul>
     *    <li>
     *     Return key: uidnext
     *    </li>
     *    <li>
     *     Return format: (integer) The next UID to be assigned in the
     *     mailbox.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_UIDVALIDITY
     *   <ul>
     *    <li>
     *     Return key: uidvalidity
     *    </li>
     *    <li>
     *     Return format: (integer) The unique identifier validity of the
     *     mailbox.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_UNSEEN
     *   <ul>
     *    <li>
     *     Return key: unseen
     *    </li>
     *    <li>
     *     Return format: (integer) The number of messages which do not have
     *     the \Seen flag set.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_FIRSTUNSEEN
     *   <ul>
     *    <li>
     *     Return key: firstunseen
     *    </li>
     *    <li>
     *     Return format: (integer) The sequence number of the first unseen
     *     message in the mailbox.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_FLAGS
     *   <ul>
     *    <li>
     *     Return key: flags
     *    </li>
     *    <li>
     *     Return format: (array) The list of defined flags in the mailbox
     *     (all flags are in lowercase).
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_PERMFLAGS
     *   <ul>
     *    <li>
     *     Return key: permflags
     *    </li>
     *    <li>
     *     Return format: (array) The list of flags that a client can change
     *     permanently (all flags are in lowercase).
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_HIGHESTMODSEQ
     *   <ul>
     *    <li>
     *     Return key: highestmodseq
     *    </li>
     *    <li>
     *     Return format: (integer) If the server supports the CONDSTORE
     *     IMAP extension, this will be the highest mod-sequence value of all
     *     messages in the mailbox. Else 0 if CONDSTORE not available or the
     *     mailbox does not support mod-sequences.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_LASTMODSEQ
     *   <ul>
     *    <li>
     *     Return key: lastmodseq
     *    </li>
     *    <li>
     *     Return format: (integer) If the server supports the CONDSTORE
     *     IMAP extension, this will be the cached mod-sequence value of the
     *     mailbox when it was first opened if HIGHESTMODSEQ changed. Else 0
     *     if CONDSTORE not available, the mailbox does not support
     *     mod-sequences, or the mod-sequence did not change.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_LASTMODSEQUIDS
     *   <ul>
     *    <li>
     *     Return key: lastmodsequids
     *    </li>
     *    <li>
     *     Return format: (array) If the server supports the QRESYNC IMAP
     *     extension, this will be the list of UIDs changed in the mailbox
     *     when it was first opened if HIGHESTMODSEQ changed. Else an empty
     *     array if QRESYNC not available, the mailbox does not support
     *     mod-sequences, or the mod-sequence did not change.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_UIDNOTSTICKY
     *   <ul>
     *    <li>
     *     Return key: uidnotsticky
     *    </li>
     *    <li>
     *     Return format: (boolean) If the server supports the UIDPLUS IMAP
     *     extension, and the queried mailbox does not support persistent
     *     UIDs, this value will be true. In all other cases, this value will
     *     be false.
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   Horde_Imap_Client::STATUS_ALL (DEFAULT)
     *   <ul>
     *    <li>
     *     Shortcut to return 'messages', 'recent', 'uidnext', 'uidvalidity',
     *     and 'unseen' values.
     *    </li>
     *   </ul>
     *  </li>
     * </ul>
     *
     * @return array  An array with the requested keys (see above).
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function status($mailbox, $flags = Horde_Imap_Client::STATUS_ALL)
    {
        $this->login();

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

        $mailbox = Horde_Imap_Client_Mailbox::get($mailbox, null);
        $ret = array();

        /* Check for cached information. */
        if ($mailbox->equals($this->_selected) &&
            isset($this->_temp['statuscache'][strval($mailbox)])) {
            $ptr = &$this->_temp['statuscache'][strval($mailbox)];

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
                isset($this->_temp['lastmodseq'][strval($mailbox)])) {
                $ret['lastmodseq'] = $this->_temp['lastmodseq'][strval($mailbox)];
            }
            $flags &= ~Horde_Imap_Client::STATUS_LASTMODSEQ;
        }

        if ($flags & Horde_Imap_Client::STATUS_LASTMODSEQUIDS) {
            $ret['lastmodsequids'] = array();
            if (isset($this->_init['enabled']['CONDSTORE']) &&
                isset($this->_temp['lastmodsequids'][strval($mailbox)])) {
                $ret['lastmodsequids'] = $this->utils->fromSequenceString($this->_temp['lastmodsequids'][strval($mailbox)]);
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

        if (!$mailbox->equals($this->_selected)) {
            if (!isset($this->_temp['statuscache'])) {
                $this->_temp['statuscache'] = array();
            }
            $ptr = &$this->_temp['statuscache'];

            $ptr[strval($mailbox)] = isset($ptr[strval($mailbox)])
                ? array_merge($ptr[strval($mailbox)], $ret)
                : $ret;
        }

        return $ret;
    }

    /**
     * Obtain status information for a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to query.
     * @param integer $flags                      A bitmask of information
     *                                            requested from the server.
     *
     * @return array  See status().
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _status(Horde_Imap_Client_Mailbox $mailbox,
                                        $flags);

    /**
     * Perform a STATUS call on multiple mailboxes at the same time.
     *
     * This method leverages the LIST-EXTENDED and LIST-STATUS extensions on
     * the IMAP server to improve the efficiency of this operation.
     *
     * @param array $mailboxes  The mailboxes to query. Either
     *                          Horde_Imap_Client_Mailbox objects (as of
     *                          1.2.0), strings (UTF-8), or a combination of
     *                          the two.
     * @param integer $flags    See status().
     * @param array $opts       Additional options:
     *   - sort: (boolean) If true, sort the list of mailboxes?
     *           DEFAULT: Do not sort the list.
     *   - sort_delimiter: (string) If 'sort' is true, this is the delimiter
     *                     used to sort the mailboxes.
     *                     DEFAULT: '.'
     *
     * @return array  An array with the keys as the mailbox names (UTF7-IMAP)
     *                and the values as arrays with the requested keys (from
     *                the mask given in $flags).
     */
    public function statusMultiple($mailboxes,
                                   $flags = Horde_Imap_Client::STATUS_ALL,
                                   array $opts = array())
    {
        if (empty($mailboxes)) {
            return array();
        }

        $this->login();

        $opts = array_merge(array(
            'sort' => false,
            'sort_delimiter' => '.'
        ), $opts);
        $ret = null;

        /* Optimization: If there is one mailbox in list, and we are already
         * in that mailbox, we should just do a straight STATUS call. */
        if ($this->queryCapability('LIST-STATUS') &&
            ((count($mailboxes) != 1) ||
            !Horde_Imap_Client_Mailbox::get(reset($mailboxes), null)->equals($this->_selected))) {
            try {
                $ret = array();
                foreach ($this->listMailboxes($mailboxes, Horde_Imap_Client::MBOX_ALL, array_merge($opts, array('status' => $flags, 'utf8' => true))) as $val) {
                    if (isset($val['status'])) {
                        $ret[$val['mailbox']->utf7imap] = $val['status'];
                    }
                }
            } catch (Horde_Imap_Client_Exception $e) {
                $ret = null;
            }
        }

        if (is_null($ret)) {
            $ret = array();
            foreach ($mailboxes as $val) {
                $val = Horde_Imap_Client_Mailbox::get($val, null);
                try {
                    $ret[$val->utf7imap] = $this->status($val, $flags);
                } catch (Horde_Imap_Client_Exception $e) {}
            }

            if ($opts['sort']) {
                Horde_Imap_Client_Sort::sortMailboxes($ret, array(
                    'delimiter' => $opts['sort_delimiter'],
                    'keysort' => true
                ));
            }
        }

        return $ret;
    }

    /**
     * Append message(s) to a mailbox.
     *
     * @param mixed $mailbox  The mailbox to append the message(s) to. Either
     *                        a Horde_Imap_Client_Mailbox object (as of 1.2.0)
     *                        or a string (UTF-8).
     * @param array $data     The message data to append, along with
     *                        additional options. An array of arrays with
     *                        each embedded array having the following
     *                        entries:
     * <ul>
     *  <li>
     *   data: (mixed) The data to append. If a string or a stream resource,
     *   this will be used as the entire contents of a single message. If an
     *   array, will catenate all given parts into a single message. This
     *   array contains one or more arrays with two keys:
     *   <ul>
     *    <li>
     *     t: (string) Either 'url' or 'text'.
     *    </li>
     *    <li>
     *     v: (mixed) If 't' is 'url', this is the IMAP URL to the message
     *     part to append. If 't' is 'text', this is either a string or
     *     resource representation of the message part data.
     *     DEFAULT: NONE (entry is MANDATORY)
     *    </li>
     *   </ul>
     *  </li>
     *  <li>
     *   flags: (array) An array of flags/keywords to set on the appended
     *   message.
     *   DEFAULT: Only the \Recent flag is set.
     *  </li>
     *  <li>
     *   internaldate: (DateTime) The internaldate to set for the appended
     *   message.
     *   DEFAULT: internaldate will be the same date as when the message was
     *   appended.
     *  </li>
     * </ul>
     * @param array $options  Additonal options:
     *   - create: (boolean) Try to create $mailbox if it does not exist?
     *             DEFAULT: No.
     *
     * @return Horde_Imap_Client_Ids  The UIDs of the appended messages.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function append($mailbox, $data, $options = array())
    {
        $this->login();

        $mailbox = Horde_Imap_Client_Mailbox::get($mailbox, null);

        $ret = $this->_append($mailbox, $data, $options);
        unset($this->_temp['statuscache'][strval($mailbox)]);

        if ($ret instanceof Horde_Imap_Client_Ids) {
            return $ret;
        }

        $uids = $this->getIdsOb();

        while (list(,$val) = each($data)) {
            if (is_string($data['data'])) {
                $text = $data;
            } elseif (is_resource($data['data'])) {
                $text = '';
                rewind($data['data']);
                while (!feof($data['data'])) {
                    $text .= fread($data['data'], 512);
                    if (preg_match("/\n\r{2,}/", $text)) {
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
                $uids->add($uidsearch['match']);
            }
        }

        return $uids;
    }

    /**
     * Append message(s) to a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  The mailbox to append the
     *                                            message(s) to.
     * @param array $data                         The message data.
     * @param array $options                      Additional options.
     *
     * @return mixed  A Horde_Imap_Client_Ids object containing the UIDs of
     *                the appended messages (if server supports UIDPLUS
     *                extension) or true.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _append(Horde_Imap_Client_Mailbox $mailbox,
                                        $data, $options);

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
     *   - expunge: (boolean) Expunge all messages flagged as deleted?
     *              DEFAULT: No
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function close($options = array())
    {
        // This check catches the non-logged in case.
        if (is_null($this->_selected)) {
            return;
        }

        /* If we are caching, search for deleted messages. */
        if (!empty($options['expunge']) &&
            $this->_initCache(true)) {
            $search_query = new Horde_Imap_Client_Search_Query();
            $search_query->flag(Horde_Imap_Client::FLAG_DELETED, true);
            $search_res = $this->search($this->_selected, $search_query);
            $mbox = $this->_selected;
        } else {
            $search_res = null;
        }

        $this->_close($options);
        $this->_selected = null;
        $this->_mode = 0;

        if (!is_null($search_res)) {
            $this->_deleteMsgs($mbox, $search_res['match']->ids);
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
     * @param mixed $mailbox  The mailbox to expunge. Either a
     *                        Horde_Imap_Client_Mailbox object (as of 1.2.0)
     *                        or a string (UTF-8).
     * @param array $options  Additional options:
     *   - ids: (Horde_Imap_Client_Ids) A list of messages to expunge, but
     *          only if they are also flagged as deleted.
     *          DEFAULT: All messages marked as deleted will be expunged.
     *   - list: (boolean) If true, returns the list of expunged messages.
     *           DEFAULT: false
     *
     * @return Horde_Imap_Client_Ids  If 'list' option is true, returns the
     *                                list of expunged messages.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function expunge($mailbox, $options = array())
    {
        // Open mailbox call will handle the login.
        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_READWRITE);

        if (empty($options['ids'])) {
            $options['ids'] = $this->getIdsOb(Horde_Imap_Client_Ids::ALL);
        } elseif ($options['ids']->isEmpty()) {
            return array();
        }

        return $this->_expunge($options);
    }

    /**
     * Expunge all deleted messages from the given mailbox.
     *
     * @param array $options  Additional options.
     *
     * @return Horde_Imap_Client_Ids  If 'list' option is true, returns the
     *                                list of expunged messages.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _expunge($options);

    /**
     * Search a mailbox.
     *
     * @param mixed $mailbox                         The mailbox to search.
     *                                               Either a
     *                                               Horde_Imap_Client_Mailbox
     *                                               object (as of 1.2.0) or a
     *                                               string (UTF-8).
     * @param Horde_Imap_Client_Search_Query $query  The search query.
     *                                               Defaults to an ALL
     *                                               search.
     * @param array $options                         Additional options:
     * <ul>
     *  <li>
     *   nocache: (boolean) Don't cache the results.
     *   DEFAULT: false (results cached, if possible)
     *  </li>
     *  <li>
     *   partial: (mixed) The range of results to return (message sequence
     *   numbers).
     *   DEFAULT: All messages are returned.
     *  </li>
     *  <li>
     *   results: (array) The data to return. Consists of zero or more of
     *   the following flags:
     *   <ul>
     *    <li>Horde_Imap_Client::SEARCH_RESULTS_COUNT</li>
     *    <li>Horde_Imap_Client::SEARCH_RESULTS_MATCH (DEFAULT)</li>
     *    <li>Horde_Imap_Client::SEARCH_RESULTS_MAX</li>
     *    <li>Horde_Imap_Client::SEARCH_RESULTS_MIN</li>
     *    <li>
     *     Horde_Imap_Client::SEARCH_RESULTS_SAVE (This option is currently
     *     meant for internal use only)
     *    </li>
     *    <li>Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY</li>
     *   </ul>
     *  </li>
     *  <li>
     *   sequence: (boolean) If true, returns an array of sequence numbers.
     *   DEFAULT: Returns an array of UIDs
     *  </li>
     *  <li>
     *   sort: (array) Sort the returned list of messages. Multiple sort
     *   criteria can be specified. Any sort criteria can be sorted in reverse
     *   order (instead of the default ascending order) by adding a
     *   Horde_Imap_Client::SORT_REVERSE element to the array directly before
     *   adding the sort element. The following sort criteria are available:
     *   <ul>
     *    <li>Horde_Imap_Client::SORT_ARRIVAL</li>
     *    <li>Horde_Imap_Client::SORT_CC</li>
     *    <li>Horde_Imap_Client::SORT_DATE</li>
     *    <li>Horde_Imap_Client::SORT_FROM</li>
     *    <li>Horde_Imap_Client::SORT_SEQUENCE</li>
     *    <li>Horde_Imap_Client::SORT_SIZE</li>
     *    <li>Horde_Imap_Client::SORT_SUBJECT</li>
     *    <li>Horde_Imap_Client::SORT_TO</li>
     *    <li>
     *     [On servers that support SORT=DISPLAY, these criteria are also
     *     available:]
     *     <ul>
     *      <li>Horde_Imap_Client::SORT_DISPLAYFROM</li>
     *      <li>Horde_Imap_Client::SORT_DISPLAYTO</li>
     *     </ul>
     *    </li>
     *    <li>
     *     [On servers that support SEARCH=FUZZY, this criteria is also
     *     available:]
     *     <ul>
     *      <li>Horde_Imap_Client::SORT_RELEVANCY</li>
     *     </ul>
     *    </li>
     *   </ul>
     *  </li>
     * </ul>
     *
     * @return array  An array with the following keys:
     *   - count: (integer) The number of messages that match the search
     *            criteria. Always returned.
     *   - match: (Horde_Imap_Client_Ids) The IDs that match $criteria, sorted
     *            if the 'sort' modifier was set. Returned if
     *            Horde_Imap_Client::SEARCH_RESULTS_MATCH is set.
     *   - max: (integer) The UID (default) or message sequence number (if
     *          'sequence' is true) of the highest message that satisifies
     *          $criteria. Returns null if no matches found. Returned if
     *          Horde_Imap_Client::SEARCH_RESULTS_MAX is set.
     *   - min: (integer) The UID (default) or message sequence number (if
     *          'sequence' is true) of the lowest message that satisifies
     *          $criteria. Returns null if no matches found. Returned if
     *          Horde_Imap_Client::SEARCH_RESULTS_MIN is set.
     *   - modseq: (integer) The highest mod-sequence for all messages being
     *            returned. Returned if 'sort' is false, the search query
     *            includes a MODSEQ command, and the server supports the
     *            CONDSTORE IMAP extension.
     *   - relevancy: (array) The list of relevancy scores. Returned if
     *                Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY is set and
     *                the server supports FUZZY search matching.
     *   - save: (boolean) Whether the search results were saved. This value
     *           is meant for internal use only. Returned if 'sort' is false
     *           and Horde_Imap_Client::SEARCH_RESULTS_SAVE is set.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function search($mailbox, $query = null, $options = array())
    {
        $this->login();

        if (empty($options['results'])) {
            $options['results'] = array(
                Horde_Imap_Client::SEARCH_RESULTS_MATCH,
                Horde_Imap_Client::SEARCH_RESULTS_COUNT
            );
        }

        // Default to an ALL search.
        if (is_null($query)) {
            $query = new Horde_Imap_Client_Search_Query();
        }

        $options['_query'] = $query->build($this->capability());

        /* RFC 6203: MUST NOT request relevancy results if we are not using
         * FUZZY searching. */
        if (in_array(Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY, $options['results']) &&
            !in_array('SEARCH=FUZZY', $options['_query']['exts_used'])) {
            throw new InvalidArgumentException('Cannot specify RELEVANCY results if not doing a FUZZY search.');
        }

        /* Optimization - if query is just for a count of either RECENT or
         * ALL messages, we can send status information instead. Can't
         * optimize with unseen queries because we may cause an infinite loop
         * between here and the status() call. */
        if ((count($options['results']) == 1) &&
            (reset($options['results']) == Horde_Imap_Client::SEARCH_RESULTS_COUNT)) {
            switch ($options['_query']['query']) {
            case 'ALL':
                $ret = $this->status($this->_selected, Horde_Imap_Client::STATUS_MESSAGES);
                return array('count' => $ret['messages']);

            case 'RECENT':
                $ret = $this->status($this->_selected, Horde_Imap_Client::STATUS_RECENT);
                return array('count' => $ret['recent']);
            }
        }

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
            $cache = $this->_getSearchCache('search', $this->_selected, $options);
            if (is_array($cache)) {
                if (isset($cache['data']['match'])) {
                    $cache['data']['match'] = $this->getIdsOb($cache['data']['match']);
                }
                return $cache['data'];
            }
        }

        /* Optimization: Catch when there are no messages in a mailbox. */
        $status_res = $this->status($this->_selected, Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_HIGHESTMODSEQ);
        if ($status_res['messages'] ||
            in_array(Horde_Imap_Client::SEARCH_RESULTS_SAVE, $options['results'])) {
            $ret = $this->_search($query, $options);
        } else {
            $ret = array(
                'count' => 0
            );

            foreach ($options['results'] as $val) {
                switch ($val) {
                case Horde_Imap_Client::SEARCH_RESULTS_MATCH:
                    $ret['match'] = $this->getIdsOb();
                    break;

                case Horde_Imap_Client::SEARCH_RESULTS_MAX:
                    $ret['max'] = null;
                    break;

                case Horde_Imap_Client::SEARCH_RESULTS_MIN:
                    $ret['min'] = null;
                    break;

                case Horde_Imap_Client::SEARCH_RESULTS_MIN:
                    if (isset($status_res['highestmodseq'])) {
                        $ret['modseq'] = $status_res['highestmodseq'];
                    }
                    break;

                case Horde_Imap_Client::SEARCH_RESULTS_RELEVANCY:
                    $ret['relevancy'] = array();
                    break;
                }
            }
        }

        if ($cache) {
            $save = $ret;
            if (isset($save['match'])) {
                $save['match'] = $this->utils->toSequenceString($ret['match'], array('nosort' => true));
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
     *                        the value of $query->build().
     *
     * @return Horde_Imap_Client_Ids  An array of IDs.
     *
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
     * @throws Horde_Imap_Client_Exception_NoSupportExtension
     */
    public function setComparator($comparator = null)
    {
        $comp = is_null($comparator)
            ? (empty($this->_params['comparator']) ? null : $this->_params['comparator'])
            : $comparator;
        if (is_null($comp)) {
            return;
        }

        $this->login();

        $i18n = $this->queryCapability('I18NLEVEL');
        if (empty($i18n) || (max($i18n) < 2)) {
            $this->_exception('The IMAP server does not support changing SEARCH/SORT comparators.', 'NO_SUPPORT');
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
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getComparator()
    {
        $this->login();

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
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getComparator();

    /**
     * Thread sort a given list of messages (RFC 5256).
     *
     * @param mixed $mailbox  The mailbox to query. Either a
     *                        Horde_Imap_Client_Mailbox object (as of 1.2.0)
     *                        or a string (UTF-8).
     * @param array $options  Additional options:
     * <ul>
     *  <li>
     *   criteria: (mixed) The following thread criteria are available:
     *   <ul>
     *    <li>Horde_Imap_Client::THREAD_ORDEREDSUBJECT</li>
     *    <li>Horde_Imap_Client::THREAD_REFERENCES</li>
     *    <li>Horde_Imap_Client::THREAD_REFS</li>
     *    <li>
     *     Other algorithms can be explicitly specified by passing the IMAP
     *     thread algorithm in as a string value.
     *    </li>
     *   </ul>
     *   DEFAULT: Horde_Imap_Client::THREAD_ORDEREDSUBJECT
     *  </li>
     *  <li>
     *   search: (Horde_Imap_Client_Search_Query) The search query.
     *   DEFAULT: All messages in mailbox included in thread sort.
     *  </li>
     *  <li>
     *   sequence: (boolean) If true, each message is stored and referred to
     *   by its message sequence number.
     *   DEFAULT: Stored/referred to by UID.
     *  </li>
     * </ul>
     *
     * @return Horde_Imap_Client_Data_Thread  A thread data object.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function thread($mailbox, $options = array())
    {
        // Open mailbox call will handle the login.
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
            $cache = $this->_getSearchCache('thread', $this->_selected, $options);
            if (is_array($cache)) {
                if ($cache['data'] instanceof Horde_Imap_Client_Data_Thread) {
                    return $cache['data'];
                }
                $cache = $cache['id'];
            }
        }

        $status_res = $this->status($this->_selected, Horde_Imap_Client::STATUS_MESSAGES);

        $ob = new Horde_Imap_Client_Data_Thread($status_res['messages'] ? $this->_thread($options) : array(), empty($options['sequence']) ? 'uid' : 'sequence');

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
     *   - b (base): (integer) [OPTIONAL] The ID of the base message. If not
     *               set, this is the only message in the thread.
     *               DEFAULT: Only message in thread
     *   - l (level): (integer) [OPTIONAL] The thread level of this
     *                message (1 = base).
     *                DEFAULT: 0
     *   - s (subthread): (boolean) [OPTIONAL] Are there more messages in this
     *                    subthread?
     *                    DEFAULT: No
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _thread($options);

    /**
     * Fetch message data (see RFC 3501 [6.4.5]).
     *
     * @param mixed $mailbox                        The mailbox to search.
     *                                              Either a
     *                                              Horde_Imap_Client_Mailbox
     *                                              object (as of 1.2.0) or a
     *                                              string (UTF-8).
     * @param Horde_Imap_Client_Fetch_Query $query  Fetch query object.
     * @param array $options                        Additional options:
     *   - changedsince: (integer) Only return messages that have a
     *                   mod-sequence larger than this value. This option
     *                   requires the CONDSTORE IMAP extension (if not present,
     *                   this value is ignored). Additionally, the mailbox
     *                   must support mod-sequences or an exception will be
     *                   thrown. If valid, this option implicity adds the
     *                   mod-sequence fetch criteria to the fetch command.
     *                   DEFAULT: Mod-sequence values are ignored.
     *   - fetch_res: (array) A partial results array to have fetch results
     *                added to.
     *   - ids: (Horde_Imap_Client_Ids) A list of messages to fetch data from.
     *          DEFAULT: All messages in $mailbox will be fetched.
     *   - vanished: (boolean) Only return messages from the UID set parameter
     *               that have been expunged and whose associated mod-sequence
     *               is larger than the specified mod-sequence. This option
     *               requires the QRESYNC IMAP extension and requires
     *               'changedsince' to be set, and requires 'ids' to be UIDs.
     *               DEFAULT: Vanished search ignored.
     *
     * @return array  An array of fetch results. The array consists of
     *                keys that correspond to 'ids', and values that
     *                contain Horde_Imap_Query_Data_Fetch objects.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function fetch($mailbox, $query, $options = array())
    {
        $this->login();

        $query = clone $query;

        $cache_array = $header_cache = $new_query = array();
        $qresync = isset($this->_init['enabled']['QRESYNC']);
        $res_seq = null;

        if (empty($options['ids'])) {
            $options['ids'] = $this->getIdsOb(empty($options['fetch_res']) ? Horde_Imap_Client_Ids::ALL : array_keys($options['fetch_res']));
            if ($options['ids']->isEmpty()) {
                return array();
            }
        } elseif ($options['ids']->isEmpty()) {
            return array();
        } elseif ($options['ids']->search_res &&
                  !$this->queryCapability('SEARCHRES')) {
            /* SEARCHRES requires server support. */
            $this->_exception('Server does not support saved searches.', 'NO_SUPPORT');
        }

        /* The 'vanished' modifier requires QRESYNC, 'changedsince', and IDs
         * that are not sequence numbers. */
        if (!empty($options['vanished'])) {
            if (!$qresync) {
                $this->_exception('Server does not support the QRESYNC extension.', 'NO_SUPPORT');
            } elseif ($options['ids']->sequence) {
                throw new InvalidArgumentException('The vanished FETCH modifier requires UIDs.');
            } elseif (empty($options['changedsince'])) {
                throw new InvalidArgumentException('The vanished FETCH modifier requires the changedsince parameter.');
            }
        }

        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_AUTO);

        $cf = $this->_initCache(true)
            ? $this->_params['cache']['fields']
            : array();

        if (!empty($cf)) {
            /* We need the UIDVALIDITY for the current mailbox. */
            $status_res = $this->status($this->_selected, Horde_Imap_Client::STATUS_HIGHESTMODSEQ | Horde_Imap_Client::STATUS_UIDVALIDITY);

            /* If using cache, we store by UID so we need to return UIDs. */
            $query->uid();
        }

        if ($query->contains(Horde_Imap_Client::FETCH_MODSEQ) &&
            !isset($this->_init['enabled']['CONDSTORE'])) {
            unset($query[$k]);
        }

        /* Determine if caching is available and if anything in $query is
         * cacheable.
         * TODO: Re-add base headertext caching. */
        foreach ($cf as $k => $v) {
            if (isset($query[$k])) {
                switch ($k) {
                case Horde_Imap_Client::FETCH_ENVELOPE:
                case Horde_Imap_Client::FETCH_IMAPDATE:
                case Horde_Imap_Client::FETCH_SIZE:
                case Horde_Imap_Client::FETCH_STRUCTURE:
                    $cache_array[$k] = $v;
                    break;

                case Horde_Imap_Client::FETCH_FLAGS:
                    /* QRESYNC would have already done syncing on mailbox
                     * open, so no need to do again. Only can cache if MODSEQ
                     * is available in the mailbox. */
                    if (!$qresync && !empty($status_res['highestmodseq'])) {
                        /* Grab all flags updated since the cached modseq
                         * val. */
                        $metadata = $this->cache->getMetaData($this->_selected, $status_res['uidvalidity'], array(self::CACHE_MODSEQ));
                        if (isset($metadata[self::CACHE_MODSEQ]) &&
                            ($metadata[self::CACHE_MODSEQ] != $status_res['highestmodseq'])) {
                            $uids = $this->cache->get($this->_selected, array(), array(), $status_res['uidvalidity']);
                            if (!empty($uids)) {
                                $flag_query = new Horde_Imap_Client_Fetch_Query();
                                $flag_query->flags();

                                /* Update flags in cache. */
                                $this->_fetch($flag_query, array(), array(
                                    'changedsince' => $metadata[self::CACHE_MODSEQ],
                                    'ids' => $this->getIdsOb($uids)
                                ));
                            }
                            $this->_updateMetaData($this->_selected, array(self::CACHE_MODSEQ => $status_res['highestmodseq']), $status_res['uidvalidity']);
                        }
                    }

                    $cache_array[$k] = $v;
                    break;

                case Horde_Imap_Client::FETCH_HEADERS:
                    $this->_temp['headers_caching'] = array();

                    foreach ($query[$k] as $key => $val) {
                        /* Only cache if directly requested.  Iterate through
                         * requests to ensure at least one can be cached. */
                        if (!empty($val['cache']) && !empty($val['peek'])) {
                            $cache_array[$k] = $v;
                            ksort($val);
                            $header_cache[$key] = hash('md5', serialize($val));
                        }
                    }
                    break;
                }
            }
        }

        /* Build the default fetch entries. */
        if (empty($options['fetch_res'])) {
            $fetch_ob = new $this->_fetchDataClass();
            $ret = array();

            $res_seq = $this->_getSeqUidLookup($options['ids']);
            $ids = $options['ids']->sequence
                ? array_keys($res_seq['lookup'])
                : $res_seq['uids'];

            foreach ($ids as $val) {
                $ret[$val] = clone $fetch_ob;
            }
        } else {
            $ret = &$options['fetch_res'];
        }

        /* If nothing is cacheable, we can do a straight search. */
        if (empty($cache_array)) {
            $ret = $this->_fetch($query, $ret, $options);
            foreach ($ret as $key => $val) {
                if ($val->isDefault()) {
                    unset($ret[$key]);
                }
            }
            return $ret;
        }

        /* If doing a changedsince/vanished search, limit the UIDs now. */
        if (!empty($options['changedsince'])) {
            $changed_query = new Horde_Imap_Client_Fetch_Query();
            if (empty($options['vanished']) && $options['ids']->sequence) {
                $changed_query->seq();
            } else {
                $changed_query->uid();
            }

            $cs_res = $this->_fetch($changed_query, array(), array(
                'changedsince' => $options['changedsince'],
                'ids' => $options['ids'],
                'vanished' => !empty($options['vanished'])
            ));

            if (!empty($options['vanished'])) {
                return $cs_res;
            }

            $ret = array_intersect_key($ret, $cs_res);
            if (empty($ret)) {
                return $ret;
            }

            $options['ids'] = $this->getIdsOb(array_keys($ret), $options['ids']->sequence);
        }

        /* Need Seq -> UID lookup if we haven't already grabbed it. */
        if (is_null($res_seq)) {
            $res_seq = $this->_getSeqUidLookup($options['ids']);
        }

        /* Get the cached values. */
        $data = $this->cache->get($this->_selected, $res_seq['uids']->ids, array_values($cache_array), $status_res['uidvalidity']);

        /* Build a list of what we still need. */
        foreach (array_keys($ret) as $val) {
            $crit = clone $query;

            if ($options['ids']->sequence) {
                $uid = $res_seq['lookup'][$val];
                unset($crit[Horde_Imap_Client::FETCH_SEQ]);
            } else {
                $uid = $val;
            }

            /* UID will be added into the results object below. */
            unset($crit[Horde_Imap_Client::FETCH_UID]);

            foreach ($cache_array as $key => $cid) {
                switch ($key) {
                case Horde_Imap_Client::FETCH_ENVELOPE:
                    if (isset($data[$uid][$cid]) &&
                        ($data[$uid][$cid] instanceof Horde_Imap_Client_Data_Envelope)) {
                        $ret[$val]->setEnvelope($data[$uid][$cid]);
                        unset($crit[$key]);
                    }
                    break;

                case Horde_Imap_Client::FETCH_FLAGS:
                    if (isset($data[$uid][$cid]) &&
                        is_array($data[$uid][$cid])) {
                        $ret[$val]->setFlags($data[$uid][$cid]);
                        unset($crit[$key]);
                    }
                    break;

                case Horde_Imap_Client::FETCH_HEADERS:
                    /* HEADERS caching. */
                    foreach ($header_cache as $hkey => $hval) {
                        if (isset($data[$uid][$cid][$hval])) {
                            /* We have found a cached entry with the same MD5
                             * sum. */
                            $ret[$val]->setHeaders($hkey, $data[$uid][$cid][$hval]);
                            $crit->remove($key, $hkey);
                        } else {
                            $this->_temp['headers_caching'][$hkey] = $hval;
                        }
                    }
                    break;

                case Horde_Imap_Client::FETCH_IMAPDATE:
                    if (isset($data[$uid][$cid]) &&
                        ($data[$uid][$cid] instanceof Horde_Imap_Client_DateTime)) {
                        $ret[$val]->setImapDate($data[$uid][$cid]);
                        unset($crit[$key]);
                    }
                    break;

                case Horde_Imap_Client::FETCH_SIZE:
                    if (isset($data[$uid][$cid])) {
                        $ret[$val]->setSize($data[$uid][$cid]);
                        unset($crit[$key]);
                    }
                    break;

                case Horde_Imap_Client::FETCH_STRUCTURE:
                    if (isset($data[$uid][$cid]) &&
                        ($data[$uid][$cid] instanceof Horde_Mime_Part)) {
                        $ret[$val]->setStructure($data[$uid][$cid]);
                        unset($crit[$key]);
                    }
                    break;
                }
            }

            if (count($crit)) {
                $sig = $crit->hash();
                if (isset($new_query[$sig])) {
                    $new_query[$sig]['i']->add($val);
                } else {
                    $new_query[$sig] = array(
                        'c' => $crit,
                        'i' => $this->getIdsOb($val, $options['ids']->sequence)
                    );
                }
            }
        }

        foreach ($new_query as $val) {
            $ret = $this->_fetch($val['c'], $ret, array_merge($options, array(
                'ids' => $val['i']
            )));
        }

        foreach ($ret as $key => $val) {
            if ($val->isDefault() && !empty($new_query)) {
                /* If $new_query is empty, this means that the fetch requested
                 * was for UIDs only. Need to add that info below. */
                unset($ret[$key]);
            } elseif ($options['ids']->sequence) {
                $ret[$key]->setSeq($key);
                $ret[$key]->setUid($res_seq['lookup'][$key]);
            } else {
                $ret[$key]->setUid($key);
            }
        }

        return $ret;
    }

    /**
     * Fetch message data.
     *
     * @param Horde_Imap_Client_Fetch_Query $query  Fetch query object.
     * @param array $results                        Partial results.
     * @param array $options                        Additional options.
     *
     * @return array  An array of fetch results. The array consists of
     *                keys that correspond to 'ids', and values that
     *                contain Horde_Imap_Query_Data_Fetch objects.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _fetch($query, $results, $options);

    /**
     * Store message flag data (see RFC 3501 [6.4.6]).
     *
     * @param mixed $mailbox  The mailbox containing the messages to modify.
     *                        Either a Horde_Imap_Client_Mailbox object (as of
     *                        1.2.0) or a string (UTF-8).
     * @param array $options  Additional options:
     *   - add: (array) An array of flags to add.
     *          DEFAULT: No flags added.
     *   - ids: (Horde_Imap_Client_Ids) The list of messages to modify.
     *          DEFAULT: All messages in $mailbox will be modified.
     *   - remove: (array) An array of flags to remove.
     *             DEFAULT: No flags removed.
     *   - replace: (array) Replace the current flags with this set
     *              of flags. Overrides both the 'add' and 'remove' options.
     *              DEFAULT: No replace is performed.
     *   - unchangedsince: (integer) Only changes flags if the mod-sequence ID
     *                     of the message is equal or less than this value.
     *                     Requires the CONDSTORE IMAP extension on the server.
     *                     Also requires the mailbox to support mod-sequences.
     *                     Will throw an exception if either condition is not
     *                     met.
     *                     DEFAULT: mod-sequence is ignored when applying
     *                              changes
     *
     * @return Horde_Imap_Client_Ids  A Horde_Imap_Client_Ids object
     *                                containing the list of IDs that failed
     *                                the 'unchangedsince' test.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function store($mailbox, $options = array())
    {
        // Open mailbox call will handle the login.
        $this->openMailbox($mailbox, Horde_Imap_Client::OPEN_READWRITE);

        /* SEARCHRES requires server support. */
        if (empty($options['ids'])) {
            $options['ids'] = $this->getIdsOb(Horde_Imap_Client_Ids::ALL);
        } elseif ($options['ids']->isEmpty()) {
            return $this->getIdsOb();
        } elseif ($options['ids']->search_res &&
                  !$this->queryCapability('SEARCHRES')) {
            $this->_exception('Server does not support saved searches.', 'NO_SUPPORT');
        }

        if (!empty($options['unchangedsince']) &&
            !isset($this->_init['enabled']['CONDSTORE'])) {
            $this->_exception('Server does not support the CONDSTORE extension.', 'NO_SUPPORT');
        }

        return $this->_store($options);
    }

    /**
     * Store message flag data.
     *
     * @param array $options  Additional options.
     *
     * @return Horde_Imap_Client_Ids  A Horde_Imap_Client_Ids object
     *                                containing the list of IDs that failed
     *                                the 'unchangedsince' test.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _store($options);

    /**
     * Copy messages to another mailbox.
     *
     * @param mixed $source   The source mailbox. Either a
     *                        Horde_Imap_Client_Mailbox object (as of 1.2.0)
     *                        or a string (UTF-8).
     * @param mixed $dest     The destination mailbox. Either a
     *                        Horde_Imap_Client_Mailbox object (as of 1.2.0)
     *                        or a string (UTF-8).
     * @param array $options  Additional options:
     *   - create: (boolean) Try to create $dest if it does not exist?
     *             DEFAULT: No.
     *   - ids: (Horde_Imap_Client_Ids) The list of messages to copy.
     *          DEFAULT: All messages in $mailbox will be copied.
     *   - move: (boolean) If true, delete the original messages.
     *           DEFAULT: Original messages are not deleted.
     *
     * @return mixed  An array mapping old UIDs (keys) to new UIDs (values) on
     *                success (if the IMAP server and/or driver support the
     *                UIDPLUS extension) or true.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function copy($source, $dest, $options = array())
    {
        // Open mailbox call will handle the login.
        $this->openMailbox($source, empty($options['move']) ? Horde_Imap_Client::OPEN_AUTO : Horde_Imap_Client::OPEN_READWRITE);

        /* SEARCHRES requires server support. */
        if (empty($options['ids'])) {
            $options['ids'] = $this->getIdsOb(Horde_Imap_Client_Ids::ALL);
        } elseif ($options['ids']->isEmpty()) {
            return array();
        } elseif ($options['ids']->search_res &&
                  !$this->queryCapability('SEARCHRES')) {
            $this->_exception('Server does not support saved searches.', 'NO_SUPPORT');
        }

        return $this->_copy(Horde_Imap_Client_Mailbox::get($dest, null), $options);
    }

    /**
     * Copy messages to another mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $dest  The destination mailbox.
     * @param array $options                   Additional options.
     *
     * @return mixed  An array mapping old UIDs (keys) to new UIDs (values) on
     *                success (if the IMAP server and/or driver support the
     *                UIDPLUS extension) or true.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _copy(Horde_Imap_Client_Mailbox $dest,
                                      $options);

    /**
     * Set quota limits. The server must support the IMAP QUOTA extension
     * (RFC 2087).
     *
     * @param mixed $root     The quota root. Either a
     *                        Horde_Imap_Client_Mailbox object (as of 1.2.0)
     *                        or a string (UTF-8).
     * @param array $options  Additional options:
     *   - messages: (integer) The limit to set on the number of messages
     *               allowed.
     *               DEFAULT: No limit set.
     *   - storage: (integer) The limit (in units of 1 KB) to set for the
     *              storage size.
     *              DEFAULT: No limit set.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function setQuota($root, $options = array())
    {
        $this->login();

        if (!$this->queryCapability('QUOTA')) {
            $this->_exception('Server does not support the QUOTA extension.', 'NO_SUPPORT');
        }

        if (isset($options['messages']) || isset($options['storage'])) {
            $this->_setQuota(Horde_Imap_Client_Mailbox::get($root, null), $options);
        }
    }

    /**
     * Set quota limits.
     *
     * @param Horde_Imap_Client_Mailbox $root  The quota root.
     * @param array $options                   Additional options.
     *
     * @return boolean  True on success.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setQuota(Horde_Imap_Client_Mailbox $root,
                                          $options);

    /**
     * Get quota limits. The server must support the IMAP QUOTA extension
     * (RFC 2087).
     *
     * @param mixed $root  The quota root. Either a Horde_Imap_Client_Mailbox
     *                     object (as of 1.2.0) or a string (UTF-8).
     *
     * @return mixed  An array with these possible keys: 'messages' and
     *                'storage'; each key holds an array with 2 values:
     *                'limit' and 'usage'.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getQuota($root)
    {
        $this->login();

        if (!$this->queryCapability('QUOTA')) {
            $this->_exception('Server does not support the QUOTA extension.', 'NO_SUPPORT');
        }

        return $this->_getQuota(Horde_Imap_Client_Mailbox::get($root, null));
    }

    /**
     * Get quota limits.
     *
     * @param Horde_Imap_Client_Mailbox $root  The quota root.
     *
     * @return mixed  An array with these possible keys: 'messages' and
     *                'storage'; each key holds an array with 2 values:
     *                'limit' and 'usage'.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getQuota(Horde_Imap_Client_Mailbox $root);

    /**
     * Get quota limits for a mailbox. The server must support the IMAP QUOTA
     * extension (RFC 2087).
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object (as of 1.2.0) or a string (UTF-8).
     *
     * @return mixed  An array with the keys being the quota roots. Each key
     *                holds an array with two possible keys: 'messages' and
     *                'storage'; each of these keys holds an array with 2
     *                values: 'limit' and 'usage'.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getQuotaRoot($mailbox)
    {
        $this->login();

        if (!$this->queryCapability('QUOTA')) {
            $this->_exception('Server does not support the QUOTA extension.', 'NO_SUPPORT');
        }

        return $this->_getQuotaRoot(Horde_Imap_Client_Mailbox::get($mailbox, null));
    }

    /**
     * Get quota limits for a mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     *
     * @return mixed  An array with the keys being the quota roots. Each key
     *                holds an array with two possible keys: 'messages' and
     *                'storage'; each of these keys holds an array with 2
     *                values: 'limit' and 'usage'.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getQuotaRoot(Horde_Imap_Client_Mailbox $mailbox);

    /**
     * Get the ACL rights for a given mailbox. The server must support the
     * IMAP ACL extension (RFC 2086/4314).
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object (as of 1.2.0) or a string (UTF-8).
     *
     * @return array  An array with identifiers as the keys and
     *                Horde_Imap_Client_Data_Acl objects as the values.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getACL($mailbox)
    {
        $this->login();
        return $this->_getACL(Horde_Imap_Client_Mailbox::get($mailbox, null));
    }

    /**
     * Get ACL rights for a given mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     *
     * @return array  An array with identifiers as the keys and
     *                Horde_Imap_Client_Data_Acl objects as the values.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getACL(Horde_Imap_Client_Mailbox $mailbox);

    /**
     * Set ACL rights for a given mailbox/identifier.
     *
     * @param mixed $mailbox      A mailbox. Either a Horde_Imap_Client_Mailbox
     *                            object (as of 1.2.0) or a string (UTF-8).
     * @param string $identifier  The identifier to alter (UTF-8).
     * @param array $options      Additional options:
     *   - remove: (boolean) If true, removes rights for $identifier.
     *             DEFAULT: false
     *   - rights: (string) The rights to alter.
     *             DEFAULT: If 'remove' is true, removes all rights. If
     *                      'remove' is false, no rights are altered.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function setACL($mailbox, $identifier, $options)
    {
        $this->login();

        if (!$this->queryCapability('ACL')) {
            $this->_exception('Server does not support the ACL extension.', 'NO_SUPPORT');
        }

        if (!empty($options['rights'])) {
            $acl = ($options['rights'] instanceof Horde_Imap_Client_Data_Acl)
                ? $options['rights']
                : new Horde_Imap_Client_Data_Acl(strval($options['rights']));

            $options['rights'] =
                (empty($options['remove']) ? '+' : '-') .
                $acl->getString($this->queryCapability('RIGHTS') ? Horde_Imap_Client_Data_AclCommon::RFC_4314 : Horde_Imap_Client_Data_AclCommon::RFC_2086);
        }

        if (empty($options['rights']) && empty($options['remove'])) {
            return;
        }

        return $this->_setACL(
            Horde_Imap_Client_Mailbox::get($mailbox, null),
            Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($identifier, true),
            $options
        );
    }

    /**
     * Set ACL rights for a given mailbox/identifier.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     * @param string $identifier                  The identifier to alter
     *                                            (UTF7-IMAP).
     * @param array $options                      Additional options. 'rights'
     *                                            contains the string of
     *                                            rights to set on the server.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setACL(Horde_Imap_Client_Mailbox $mailbox,
                                        $identifier, $options);

    /**
     * List the ACL rights for a given mailbox/identifier. The server must
     * support the IMAP ACL extension (RFC 2086/4314).
     *
     * @param mixed $mailbox      A mailbox. Either a Horde_Imap_Client_Mailbox
     *                            object (as of 1.2.0) or a string (UTF-8).
     * @param string $identifier  The identifier to query (UTF-8).
     *
     * @return Horde_Imap_Client_Data_AclRights  An ACL data rights object.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function listACLRights($mailbox, $identifier)
    {
        $this->login();

        if (!$this->queryCapability('ACL')) {
            $this->_exception('Server does not support the ACL extension.', 'NO_SUPPORT');
        }

        return $this->_listACLRights(
            Horde_Imap_Client_Mailbox::get($mailbox, null),
            Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($identifier, true)
        );
    }

    /**
     * Get ACL rights for a given mailbox/identifier.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     * @param string $identifier                  The identifier to query
     *                                            (UTF7-IMAP).
     *
     * @return Horde_Imap_Client_Data_AclRights  An ACL data rights object.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _listACLRights(Horde_Imap_Client_Mailbox $mailbox,
                                               $identifier);

    /**
     * Get the ACL rights for the current user for a given mailbox. The
     * server must support the IMAP ACL extension (RFC 2086/4314).
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object (as of 1.2.0) or a string (UTF-8).
     *
     * @return Horde_Imap_Client_Data_Acl  An ACL data object.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getMyACLRights($mailbox)
    {
        $this->login();

        if (!$this->queryCapability('ACL')) {
            $this->_exception('Server does not support the ACL extension.', 'NO_SUPPORT');
        }

        return $this->_getMyACLRights(Horde_Imap_Client_Mailbox::get($mailbox, null));
    }

    /**
     * Get the ACL rights for the current user for a given mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     *
     * @return Horde_Imap_Client_Data_Acl  An ACL data object.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getMyACLRights(Horde_Imap_Client_Mailbox $mailbox);

    /**
     * Return master list of ACL rights available on the server.
     *
     * @return array  A list of ACL rights.
     */
    public function allAclRights()
    {
        $this->login();

        $rights = array(
            Horde_Imap_Client::ACL_LOOKUP,
            Horde_Imap_Client::ACL_READ,
            Horde_Imap_Client::ACL_SEEN,
            Horde_Imap_Client::ACL_WRITE,
            Horde_Imap_Client::ACL_INSERT,
            Horde_Imap_Client::ACL_POST,
            Horde_Imap_Client::ACL_ADMINISTER
        );

        if ($capability = $this->queryCapability('RIGHTS')) {
            // Add rights defined in CAPABILITY string (RFC 4314).
            return array_merge($rights, str_split(reset($capability)));
        }

        // Add RFC 2086 rights (DEPRECATED)
        return array_merge($rights, array(
            Horde_Imap_Client::ACL_CREATE,
            Horde_Imap_Client::ACL_DELETE
        ));
    }

    /**
     * Get metadata for a given mailbox. The server must support either the
     * IMAP METADATA extension (RFC 5464) or the ANNOTATEMORE extension
     * (http://ietfreport.isoc.org/idref/draft-daboo-imap-annotatemore/).
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object (as of 1.2.0) or a string (UTF-8).
     * @param array $entries  The entries to fetch (UTF-8 strings).
     * @param array $options  Additional options:
     *   - depth: (string) Either "0", "1" or "infinity". Returns only the
     *            given value (0), only values one level below the specified
     *            value (1) or all entries below the specified value
     *            (infinity).
     *   - maxsize: (integer) The maximal size the returned values may have.
     *              DEFAULT: No maximal size.
     *
     * @return array  An array with metadata names as the keys and metadata
     *                values as the values. If 'maxsize' is set, and entries
     *                exist on the server larger than this size, the size will
     *                be returned in the key '*longentries'.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getMetadata($mailbox, $entries, $options = array())
    {
        $this->login();

        if (!is_array($entries)) {
            $entries = array($entries);
        }

        return $this->_getMetadata(Horde_Imap_Client_Mailbox::get($mailbox, null), array_map(array('Horde_Imap_Client_Utf7imap', 'Utf8ToUtf7Imap'), $entries, array_fill(0, count($entries), null)), $options);
    }

    /**
     * Get metadata for a given mailbox.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     * @param array $entries                      The entries to fetch
     *                                            (UTF7-IMAP strings).
     * @param array $options                      Additional options.
     *
     * @return array  An array with metadata names as the keys and metadata
     *                values as the values.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _getMetadata(Horde_Imap_Client_Mailbox $mailbox,
                                             $entries, $options);

    /**
     * Set metadata for a given mailbox/identifier.
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object (as of 1.2.0) or a string (UTF-8). If
     *                        empty, sets a server annotation.
     * @param array $data     A set of data values. The metadata values
     *                        corresponding to the keys of the array will
     *                        be set to the values in the array.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function setMetadata($mailbox, $data)
    {
        $this->login();
        $this->_setMetadata(Horde_Imap_Client_Mailbox::get($mailbox, null), $data);
    }

    /**
     * Set metadata for a given mailbox/identifier.
     *
     * @param Horde_Imap_Client_Mailbox $mailbox  A mailbox.
     * @param array $data                         A set of data values. See
     *                                            setMetaData() for format.
     *
     * @throws Horde_Imap_Client_Exception
     */
    abstract protected function _setMetadata(Horde_Imap_Client_Mailbox $mailbox,
                                             $data);

    /* Public utility functions. */

    /**
     * Returns a unique identifier for the current mailbox status.
     *
     * @param mixed $mailbox  A mailbox. Either a Horde_Imap_Client_Mailbox
     *                        object (as of 1.2.0) or a string (UTF-8).
     * @param array $addl     Additional cache info to add to the cache ID
     *                        string.
     *
     * @return string  The cache ID string, which will change when the
     *                 composition of the mailbox changes. The uidvalidity
     *                 will always be the first element, and will be delimited
     *                 by the '|' character.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function getCacheId($mailbox, $addl = array())
    {
        $query = Horde_Imap_Client::STATUS_UIDVALIDITY | Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_UIDNEXT;

        /* Use MODSEQ as cache ID if CONDSTORE extension is available. */
        if (isset($this->_init['enabled']['CONDSTORE'])) {
            $query |= Horde_Imap_Client::STATUS_HIGHESTMODSEQ;
        }

        $status = $this->status($mailbox, $query);

        if (!empty($status['highestmodseq'])) {
            $parts = array(
                'V' . $status['uidvalidity'],
                'H' . $status['highestmodseq']
            );
        } else {
            if (empty($status['uidnext'])) {
                /* UIDNEXT is not strictly required on mailbox open. If it is
                 * not available, use the last UID + 1 in the mailbox instead
                 * (or 0 if mailbox is empty). */
                if (empty($status['messages'])) {
                    $status['uidnext'] = 0;
                } else {
                    $this->_temp['nocache'] = true;
                    $search_res = $this->_getSeqUidLookup($this->getIdsOb($status['messages'], true));
                    unset($this->_temp['nocache']);
                    $uids = $search_res['uids']->ids;
                    $status['uidnext'] = intval(end($uids)) + 1;
                }
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
     *   - highestmodseq: (integer)
     *   - messages: (integer)
     *   - uidnext: (integer)
     *   - uidvalidity: (integer) Always present
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
     * @deprecated
     * @see Horde_Imap_Client_Utils#parseCommandArray()
     */
    public function parseCommandArray($query, $callback = null, $out = '')
    {
        return $this->parseCommandArray($query, $callback, $out);
    }

    /**
     * Given an IMAP body section string, fetches the corresponding part.
     *
     * @param mixed $mailbox   A mailbox. Either a Horde_Imap_Client_Mailbox
     *                         object (as of 1.2.0) or a string (UTF-8).
     * @param integer $uid     The IMAP UID.
     * @param string $section  The IMAP section string.
     *
     * @return resource  The section contents in a stream. Returns null if
     *                   the part could not be found.
     *
     * @throws Horde_Imap_Client_Exception
     */
    public function fetchFromSectionString($mailbox, $uid, $section = null)
    {
        $ids_ob = $this->getIdsOb($uid);
        $section = trim($section);

        // BODY[]
        if (!strlen($section)) {
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->fullText(array(
                'peek' => true
            ));

            $fetch = $this->fetch($mailbox, $query, array('ids' => $ids_ob));
            return $fetch[$uid]->getFullMsg(true);
        }

        // BODY[<#.>HEADER.FIELDS<.NOT>()]
        if (($pos = stripos($section, 'HEADER.FIELDS')) !== false) {
            $hdr_pos = strpos($section, '(');
            $cmd = substr($section, 0, $hdr_pos);

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->headers(
                'section',
                explode(' ', substr($section, $hdr_pos + 1, strrpos($section, ')') - $hdr_pos)),
                array(
                    'id' => ($pos ? substr($section, 0, $pos - 1) : 0),
                    'notsearch' => (stripos($cmd, '.NOT') !== false),
                    'peek' => true
                )
            );

            $fetch = $this->fetch($mailbox, $query, array('ids' => $ids_ob));
            return $fetch[$uid]->getHeaders('section', Horde_Imap_Client_Data_Fetch::HEADER_STREAM);
        }

        // BODY[#]
        if (is_numeric(substr($section, -1))) {
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->bodyPart($section, array(
                'peek' => true
            ));

            $fetch = $this->fetch($mailbox, $query, array('ids' => $ids_ob));
            return $fetch[$uid]->getBodyPart($section, true);
        }

        // BODY[<#.>HEADER]
        if (($pos = stripos($section, 'HEADER')) !== false) {
            $id = $pos
                ? substr($section, 0, $pos - 1)
                : 0;

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->headerText(array(
                'id' => $id,
                'peek' => true
            ));

            $fetch = $this->fetch($mailbox, $query, array('ids' => $ids_ob));
            return $fetch[$uid]->getHeaderText($id, Horde_Imap_Client_Data_Fetch::HEADER_STREAM);
        }

        // BODY[<#.>TEXT]
        if (($pos = stripos($section, 'TEXT')) !== false) {
            $id = $pos
                ? substr($section, 0, $pos - 1)
                : 0;

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->bodyText(array(
                'id' => $id,
                'peek' => true
            ));

            $fetch = $this->fetch($mailbox, $query, array('ids' => $ids_ob));
            return $fetch[$uid]->getBodyText($id, true);
        }

        // BODY[<#.>MIMEHEADER]
        if (($pos = stripos($section, 'MIME')) !== false) {
            $id = $pos
                ? substr($section, 0, $pos - 1)
                : 0;

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->mimeHeader($id, array(
                'peek' => true
            ));

            $fetch = $this->fetch($mailbox, $query, array('ids' => $ids_ob));
            return $fetch[$uid]->getMimeHeader($id, Horde_Imap_Client_Data_Fetch::HEADER_STREAM);
        }

        return null;
    }

    /**
     * Determines if the given charset is valid for search-related queries.
     * This check pertains just to the basic IMAP SEARCH command.
     *
     * @param string $charset  The query charset.
     *
     * @return boolean  True if server supports this charset.
     */
    public function validSearchCharset($charset)
    {
        $charset = strtoupper($charset);

        if (!isset($this->_init['s_charset'][$charset])) {
            $support = null;

            switch ($charset) {
            case 'US-ASCII';
                /* US-ASCII is always supported (RFC 3501 [6.4.4]). */
                $support = true;
                break;
            }

            /* Use a dummy search query and search for BADCHARSET
             * response. */
            if (is_null($support)) {
                $query = new Horde_Imap_Client_Search_Query();
                $query->charset($charset);
                $query->ids($this->getIdsOb(1, true));
                $query->text('a');
                try {
                    $this->search('INBOX', $query, array(
                        'sequence' => true
                    ));
                    $support = true;
                } catch (Horde_Imap_Client_Exception $e) {
                    /* BADCHARSET is only a MAY return - but there is no
                     * other way of determining charset support. */
                    $support = ($e->getCode() != Horde_Imap_Client_Exception::BADCHARSET);
                }
            }

            $s_charset = $this->_init['s_charset'];
            $s_charset[$charset] = $support;
            $this->_setInit('s_charset', $s_charset);
        }

        return $this->_init['s_charset'][$charset];
    }

    /**
     * Output debug information.
     *
     * @param string $msg    Debug line.
     * @param string $type   The message type. One of the following:
     *   - Horde_Imap_Client::DEBUG_RAW: None (output raw message)
     *   - Horde_Imap_Client::DEBUG_CLIENT: Client command
     *   - Horde_Imap_Client::DEBUG_INFO: Informational message
     *   - Horde_Imap_Client::DEBUG_SERVER: Server command
     */
    public function writeDebug($msg, $type = Horde_Imap_Client::DEBUG_RAW)
    {
        if (!$this->_debug) {
            return;
        }

        $pre = '';

        if ($type) {
            $new_time = microtime(true);
            if (isset($this->_temp['debug_time'])) {
                if (($diff = ($new_time - $this->_temp['debug_time'])) > Horde_Imap_Client::SLOW_COMMAND) {
                    fwrite($this->_debug, '>> Slow IMAP Command: ' . round($diff, 3) . " seconds\n");
                }
            } else {
                fwrite($this->_debug,
                    str_repeat('-', 30) . "\n" .
                    '>> Timestamp: ' . date('r') . "\n"
                );
            }

            $this->_temp['debug_time'] = $new_time;

            switch ($type) {
            case Horde_Imap_Client::DEBUG_CLIENT:
                $pre .= 'C: ';
                break;

            case Horde_Imap_Client::DEBUG_INFO:
                $pre .= '>> ';
                break;

            case Horde_Imap_Client::DEBUG_SERVER:
                $pre .= 'S: ';
                break;
            }
        }

        fwrite($this->_debug, $pre . $msg);
    }

    /* Private utility functions. */

    /**
     * Returns UIDs for an ALL search, or for a sequence number -> UID lookup.
     *
     * @param Horde_Imap_Client_Ids $ids  The IDs to lookup.
     * @param boolean $reverse            Perform reverse lookup (UID ->
     *                                    Sequence number) if needed.
     *
     * @return array  An array with 2 possible entries:
     *   - lookup: (array) The mapping of sequence numbers [keys] to UIDs
     *             [values]. Calculated if $reverse is true or $ids are
     *             sequence numbers.
     *   - uids: (Horde_Imap_Client_Ids) The list of UIDs.
     */
    protected function _getSeqUidLookup(Horde_Imap_Client_Ids $ids,
                                        $reverse = false)
    {
        $ret = array('lookup' => array());

        if (count($ids) && !$ids->sequence && !$reverse) {
            $ret['uids'] = $ids;
            return $ret;
        }

        if ($ids->all || $ids->search_res) {
            $search = null;
        } else {
            $search = new Horde_Imap_Client_Search_Query();
            $search->ids($ids);
        }

        $res = $this->search($this->_selected, $search, array(
            'sequence' => !$ids->all && !$ids->sequence,
            'sort' => array(Horde_Imap_Client::SORT_SEQUENCE)
        ));

        if ($res['count']) {
            $ret['uids'] = ($ids->all || $ids->sequence)
                ? $res['match']
                : $ids;

            if ($ids->all) {
                $seq = range(1, $res['count']);
            } else {
                $seq = $ids->sequence
                    ? $ids->ids
                    : $res['match']->ids;
                sort($seq, SORT_NUMERIC);
            }
            $ret['lookup'] = array_combine($seq, $ret['uids']->ids);
        }

        return $ret;
    }

    /**
     * Store FETCH data in cache.
     *
     * @param array $data     The data array.
     * @param array $options  Additional options:
     *   - fields: (array) Only update these cache fields.
     *             DEFAULT: Update all cache fields.
     *   - mailbox: (Horde_Imap_Client_Mailbox) The mailbox to update.
     *              DEFAULT: The selected mailbox.
     *   - seq: (boolean) Is data stored with sequence numbers?
     *          DEFAULT: Data stored with UIDs.
     *   - uidvalid: (integer) The UID Validity number.
     *               DEFAULT: UIDVALIDITY discovered via a status() call.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _updateCache($data, array $options = array())
    {
        $mailbox = empty($options['mailbox'])
            ? $this->_selected
            : $options['mailbox'];

        if (!$this->_initCache(empty($options['mailbox']))) {
            return;
        }

        if (in_array(strval($mailbox), $this->_params['cache']['fetch_ignore'])) {
            $this->writeDebug(sprintf("IGNORING cached FETCH data (mailbox: %s)\n", $mailbox), Horde_Imap_Client::DEBUG_INFO);
            return;
        }

        $seq_res = empty($options['seq'])
            ? null
            : $this->_getSeqUidLookup($this->getIdsOb(array_keys($data), true));

        $cf = empty($options['fields'])
            ? $this->_params['cache']['fields']
            : array_intersect_key($this->_params['cache']['fields'], array_flip($options['fields']));
        $tocache = array();

        $status_flags = 0;
        if (isset($this->_init['enabled']['CONDSTORE'])) {
            $status_flags |= Horde_Imap_Client::STATUS_HIGHESTMODSEQ;
        }
        if (empty($options['uidvalid'])) {
            $status_flags |= Horde_Imap_Client::STATUS_UIDVALIDITY;
        }

        $status_res = $this->status($mailbox, $status_flags);

        $highestmodseq = empty($status_res['highestmodseq'])
            ? null
            : array($status_res['highestmodseq']);
        $uidvalid = isset($status_res['uidvalidity'])
            ? $status_res['uidvalidity']
            : $options['uidvalid'];

        reset($data);
        while (list($k, $v) = each($data)) {
            $tmp = array();

            foreach ($cf as $key => $val) {
                if ($v->exists($key)) {
                    switch ($key) {
                    case Horde_Imap_Client::FETCH_ENVELOPE:
                        $tmp[$val] = $v->getEnvelope();
                        break;

                    case Horde_Imap_Client::FETCH_FLAGS:
                        /* A FLAGS FETCH can only occur if we are in the
                         * mailbox. So either HIGHESTMODSEQ has already been
                         * updated or the flag FETCHs will provide the new
                         * HIGHESTMODSEQ value.  In either case, we are
                         * guaranteed that all cache information is correctly
                         * updated (in the former case, we reached here via
                         * a 'changedsince' FETCH and in the latter case, we
                         * are in EXAMINE/SELECT mode and will catch all flag
                         * changes).
                         * Ignore flag caching if MODSEQs not available. */
                        if ($highestmodseq) {
                            if ($modseq = $v->getModSeq()) {
                                $highestmodseq[] = $modseq;
                            }
                            $tmp[$val] = $v->getFlags();
                        }
                        break;

                    case Horde_Imap_Client::FETCH_HEADERS:
                        foreach ($this->_temp['headers_caching'] as $label => $hash) {
                            if ($hdr = $v->getHeaders($label)) {
                                $tmp[$val][$hash] = $hdr;
                            }
                        }
                        break;

                    case Horde_Imap_Client::FETCH_IMAPDATE:
                        $tmp[$val] = $v->getImapDate();
                        break;

                    case Horde_Imap_Client::FETCH_SIZE:
                        $tmp[$val] = $v->getSize();
                        break;

                    case Horde_Imap_Client::FETCH_STRUCTURE:
                        $tmp[$val] = clone $v->getStructure();
                        break;
                    }
                }
            }

            if (!empty($tmp)) {
                $tocache[is_null($seq_res) ? $k : $seq_res['lookup'][$k]] = $tmp;
            }
        }

        $this->cache->set($mailbox, $tocache, $uidvalid);

        if (!empty($highestmodseq)) {
            $modseq = max($highestmodseq);
            $metadata = $this->cache->getMetaData($mailbox, $uidvalid, array(self::CACHE_MODSEQ));
            if (!isset($metadata[self::CACHE_MODSEQ]) ||
                ($metadata[self::CACHE_MODSEQ] != $modseq)) {
                    $this->_temp['lastmodseq'][strval($mailbox)] = isset($metadata[self::CACHE_MODSEQ])
                        ? $metadata[self::CACHE_MODSEQ]
                        : 0;
                if (count($tocache)) {
                    $this->_temp['lastmodsequids'][strval($mailbox)] = $this->utils->toSequenceString(array_keys($tocache), array('nosort' => true));
                }
                $this->_updateMetaData($mailbox, array(self::CACHE_MODSEQ => $modseq), $uidvalid);
            }
        }
    }

    /**
     * Moves cache entries from one mailbox to another.
     *
     * @param string $from      The source mailbox (UTF7-IMAP).
     * @param string $to        The destination mailbox (UTF7-IMAP).
     * @param array $map        Mapping of source UIDs (keys) to destination
     *                          UIDs (values).
     * @param string $uidvalid  UIDVALIDITY of destination mailbox.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _moveCache($from, $to, $map, $uidvalid = null)
    {
        if (!$this->_initCache()) {
            return;
        }

        if (in_array($to, $this->_params['cache']['fetch_ignore'])) {
            $this->writeDebug(sprintf("IGNORING moving cached FETCH data (%s => %s)\n", $from, $to), Horde_Imap_Client::DEBUG_INFO);
            return;
        }

        if (is_null($uidvalid)) {
            $status_res = $this->status($to, Horde_Imap_Client::STATUS_UIDVALIDITY);
            $uidvalid = $status_res['uidvalidity'];
        }

        $old_data = $this->cache->get($from, array_keys($map), null);
        $new_data = array();

        foreach ($map as $key => $val) {
            if (!empty($old_data[$key])) {
                $new_data[$val] = $old_data[$key];
            }
        }

        if (!empty($new_data)) {
            $this->cache->set($to, $new_data, $uidvalid);
        }
    }

    /**
     * Delete messages in the cache.
     *
     * @param string $mailbox  An IMAP mailbox string.
     * @param array $uids      The list of message UIDs to delete.
     *
     * @throws Horde_Imap_Client_Exception
     */
    protected function _deleteMsgs($mailbox, $uids)
    {
        $this->cache->deleteMsgs($mailbox, $uids);
    }

    /**
     * Retrieve data from the search cache.
     *
     * @param string $type     The cache type ('search' or 'thread').
     * @param string $mailbox  The mailbox to update.
     * @param array $options   The options array of the calling function.
     *
     * @return mixed  If retrieved, array is returned with data in key 'data'
     *                and the search ID in key 'id'.
     *                If caching is available, returns cacheid string.
     *                Returns null if caching is not available.
     */
    protected function _getSearchCache($type, $mailbox, $options)
    {
        ksort($options);
        $cache = hash('md5', $type . serialize($options));

        $search_id = $mailbox . $cache;
        $status = $this->status($mailbox, Horde_Imap_Client::STATUS_UIDVALIDITY);
        $metadata = $this->cache->getMetaData($mailbox, $status['uidvalidity'], array(self::CACHE_SEARCH));

        $cacheid = $this->getCacheId($mailbox);
        if (isset($metadata[self::CACHE_SEARCH]['cacheid']) &&
            ($metadata[self::CACHE_SEARCH]['cacheid'] != $cacheid)) {
            $metadata[self::CACHE_SEARCH] = array();
            if ($this->_debug &&
                !isset($this->_temp['searchcacheexpire'][strval($mailbox)])) {
                $this->writeDebug(sprintf("Expired search results from cache (mailbox: %s)\n", $mailbox), Horde_Imap_Client::DEBUG_INFO);
                $this->_temp['searchcacheexpire'][strval($mailbox)] = true;
            }
        } elseif (isset($metadata[self::CACHE_SEARCH][$cache])) {
            $this->writeDebug(sprintf("Retrieved %s results from cache (mailbox: %s; id: %s)\n", $type, $mailbox, $cache), Horde_Imap_Client::DEBUG_INFO);

            return array(
                'data' => unserialize($metadata[self::CACHE_SEARCH][$cache]),
                'id' => $search_id
            );
        }

        $metadata[self::CACHE_SEARCH]['cacheid'] = $cacheid;

        $this->_temp['searchcache'][$search_id] = array(
            'id' => $cache,
            'mailbox' => $mailbox,
            'metadata' => $metadata,
            'type' => $type
        );

        return $search_id;
    }

    /**
     * Set data in the search cache.
     *
     * @param mixed $data  The cache data to store.
     * @param string $sid  The search ID returned from _getSearchCache().
     */
    protected function _setSearchCache($data, $sid)
    {
        $cache = &$this->_temp['searchcache'][$sid];
        $cache['metadata'][self::CACHE_SEARCH][$cache['id']] = serialize($data);

        $this->_updateMetaData($cache['mailbox'], $cache['metadata']);

        if ($this->_debug) {
            $this->writeDebug(sprintf("Saved %s results to cache (mailbox: %s; id: %s)\n", $cache['type'], $cache['mailbox'], $cache['id']), Horde_Imap_Client::DEBUG_INFO);
            unset($this->_temp['searchcacheexpire'][strval($cache['mailbox'])]);
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
                $part = $exception = null;
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
                    } catch (Horde_Imap_Client_Exception $exception) {
                    }
                }

                if (is_null($part)) {
                    $message = 'Bad IMAP URL given in CATENATE data: ' . json_encode($url);
                    if ($exception) {
                        $message .= ' ' . $exception->getMessage();
                    }
                    throw new InvalidArgumentException($message);
                } else {
                    $this->_prepareAppendData($part, $stream);
                }
                break;
            }
        }

        return $stream;
    }

    /**
     * Parses human-readable response text for response codes.
     *
     * @param string $text  The response text.
     *
     * @return object  An object with the following properties:
     *   - code: (string) The response code, if it exists.
     *   - data: (string) The response code data, if it exists.
     *   - text: (string) The human-readable response text.
     */
    protected function _parseResponseText($text)
    {
        $ret = new stdClass;

        $text = trim($text);
        if ($text[0] == '[') {
            $pos = strpos($text, ' ', 2);
            $end_pos = strpos($text, ']', 2);
            if ($pos > $end_pos) {
                $ret->code = strtoupper(substr($text, 1, $end_pos - 1));
            } else {
                $ret->code = strtoupper(substr($text, 1, $pos - 1));
                $ret->data = substr($text, $pos + 1, $end_pos - $pos - 1);
            }
            $ret->text = trim(substr($text, $end_pos + 1));
        } else {
            $ret->text = $text;
        }

        return $ret;
    }

}
