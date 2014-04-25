<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */

/**
 * The Horde_Session class provides a set of methods for handling the
 * administration and contents of the Horde session variable.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 *
 * @property-read integer $begin  The timestamp when this session began (0 if
 *                                session is not active).
 * @property-read boolean $regenerate_due  True if session ID is due for
 *                                         regeneration (since 2.5.0).
 * @property-read integer $regenerate_interval  The regeneration interval
 *                                              (since 2.5.0).
 * @property-write array $session_data  Manually set session data (since
 *                                      2.5.0).
 */
class Horde_Session
{
    /* Class constants. */
    const BEGIN = '_b';
    const ENCRYPTED = '_e'; /* @since 2.7.0 */
    const MODIFIED = '_m'; /* @deprecated */
    const PRUNE = '_p';
    const REGENERATE = '_r'; /* @since 2.5.0 */

    const TYPE_ARRAY = 1;
    const TYPE_OBJECT = 2;
    const ENCRYPT = 4; /* @since 2.7.0 */

    const NOT_SERIALIZED = "\0";

    const NONCE_ID = 'session_nonce'; /* @since 2.11.0 */
    const TOKEN_ID = 'session_token';

    /**
     * Maximum size of the pruneable data store.
     *
     * @var integer
     */
    public $maxStore = 20;

    /**
     * The session handler object.
     *
     * @var Horde_SessionHandler
     */
    public $sessionHandler = null;

    /**
     * Indicates that the session is active (read/write).
     *
     * @var boolean
     */
    protected $_active = false;

    /**
     * Indicate that a new session ID has been generated for this page load.
     *
     * @var boolean
     */
    protected $_cleansession = false;

    /**
     * Pointer to the session data.
     *
     * @var array
     */
    protected $_data;

    /**
     * Indicates that session data is read-only.
     *
     * @var boolean
     */
    protected $_readonly = false;

    /**
     * On re-login, indicate whether we were previously authenticated.
     *
     * @var integer
     */
    protected $_relogin = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        /* Make sure global session variable is always initialized. */
        $_SESSION = array();
        $this->_data = &$_SESSION;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'begin':
            return ($this->_active || $this->_relogin)
                ? $this->_data[self::BEGIN]
                : 0;

        case 'regenerate_due':
            return (isset($this->_data[self::REGENERATE]) &&
                    (time() >= $this->_data[self::REGENERATE]));

        case 'regenerate_interval':
            // DEFAULT: 6 hours
            return 21600;
        }
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'session_data':
            $this->_data = &$value;
            break;
        }
    }

    /**
     * Sets a custom session handler up, if there is one.
     *
     * @param boolean $start         Initiate the session?
     * @param string $cache_limiter  Override for the session cache limiter
     *                               value.
     * @param string $session_id     The session ID to use.
     *
     * @throws Horde_Exception
     */
    public function setup($start = true, $cache_limiter = null,
                          $session_id = null)
    {
        global $conf, $injector;

        ini_set('url_rewriter.tags', 0);
        if (empty($conf['session']['use_only_cookies'])) {
            ini_set('session.use_only_cookies', 0);
        } else {
            ini_set('session.use_only_cookies', 1);
            if (!empty($conf['cookie']['domain']) &&
                (strpos($conf['server']['name'], '.') === false)) {
                throw new Horde_Exception('Session cookies will not work without a FQDN and with a non-empty cookie domain. Either use a fully qualified domain name like "http://www.example.com" instead of "http://example" only, or set the cookie domain in the Horde configuration to an empty value, or enable non-cookie (url-based) sessions in the Horde configuration.');
            }
        }

        if (!empty($conf['session']['timeout'])) {
            ini_set('session.gc_maxlifetime', $conf['session']['timeout']);
        }

        session_set_cookie_params(
            $conf['session']['timeout'],
            $conf['cookie']['path'],
            $conf['cookie']['domain'],
            $conf['use_ssl'] == 1 ? 1 : 0,
            true
        );
        session_cache_limiter(is_null($cache_limiter) ? $conf['session']['cache_limiter'] : $cache_limiter);
        session_name(urlencode($conf['session']['name']));
        if ($session_id) {
            session_id($session_id);
        }

        /* We want to create an instance here, not get, since we may be
         * destroying the previous instances in the page. */
        $this->sessionHandler = $injector->createInstance('Horde_SessionHandler');

        if ($start) {
            $this->start();
            $this->_start();
        }
    }

    /**
     * Starts the session.
     */
    public function start()
    {
        /* Limit session ID to 32 bytes. Session IDs are NOT cryptographically
         * secure hashes. Instead, they are nothing more than a way to
         * generate random strings. */
        ini_set('session.hash_function', 0);
        ini_set('session.hash_bits_per_character', 5);

        session_start();
        $this->_active = true;
        $this->_data = &$_SESSION;

        /* We have reopened a session. Check to make sure that authentication
         * status has not changed in the meantime. */
        if (!$this->_readonly &&
            !is_null($this->_relogin) &&
            (($GLOBALS['registry']->getAuth() !== false) !== $this->_relogin)) {
            Horde::log('Previous session attempted to be reopened after authentication status change. All session modifications will be ignored.', 'DEBUG');
            $this->_readonly = true;
        }
    }

    /**
     * Tasks to perform when starting a session.
     */
    private function _start()
    {
        $curr_time = time();

        /* Create internal data arrays. */
        if (!isset($this->_data[self::BEGIN])) {
            $this->_data[self::BEGIN] = $curr_time;
            $this->_data[self::REGENERATE] = $curr_time + $this->regenerate_interval;
        }
    }

    /**
     * Regenerate the session ID.
     *
     * @since 2.5.0
     */
    public function regenerate()
    {
        /* Load old encrypted data. */
        $encrypted = array();
        foreach ($this->_data[self::ENCRYPTED] as $app => $val) {
            foreach (array_keys($val) as $val2) {
                $encrypted[$app][$val2] = $this->get($app, $val2);
            }
        }

        session_regenerate_id(true);
        $this->_data[self::REGENERATE] = time() + $this->regenerate_interval;

        /* Store encrypted data, since secret key may have changed. */
        foreach ($encrypted as $app => $val) {
            foreach ($val as $key2 => $val2) {
                $this->set($app, $key2, $val2, self::ENCRYPTED);
            }
        }

        $this->sessionHandler->changed = true;
    }

    /**
     * Destroys any existing session on login and make sure to use a new
     * session ID, to avoid session fixation issues. Should be called before
     * checking a login.
     *
     * @return boolean  True if the session was cleaned.
     */
    public function clean()
    {
        if ($this->_cleansession) {
            return false;
        }

        // Make sure to force a completely new session ID and clear all
        // session data.
        session_regenerate_id(true);
        session_unset();
        $this->_data = array();
        $this->_start();

        $GLOBALS['injector']->getInstance('Horde_Secret')->setKey();

        $this->_cleansession = true;

        return true;
    }

    /**
     * Close the current session.
     */
    public function close()
    {
        $this->_active = false;
        $this->_relogin = ($GLOBALS['registry']->getAuth() !== false);
        session_write_close();
    }

    /**
     * Destroy session data.
     */
    public function destroy()
    {
        if (isset($_SESSION)) {
            session_destroy();
        }
        $this->_cleansession = true;
        $GLOBALS['injector']->getInstance('Horde_Secret')->clearKey();
    }

    /**
     * Is the current session active (read/write)?
     *
     * @return boolean  True if the current session is active.
     */
    public function isActive()
    {
        return $this->_active;
    }

    /* Session variable access. */

    /**
     * Does the session variable exist?
     *
     * @param string $app   Application name.
     * @param string $name  Session variable name.
     *
     * @return boolean  True if session variable exists.
     */
    public function exists($app, $name)
    {
        return isset($this->_data[$app][$name]);
    }

    /**
     * Get the value of a session variable.
     *
     * @param string $app    Application name.
     * @param string $name   Session variable name.
     * @param integer $mask  One of:
     *   - Horde_Session::TYPE_ARRAY - Return an array value.
     *   - Horde_Session::TYPE_OBJECT - Return an object value.
     *
     * @return mixed  The value or null if the value doesn't exist.
     */
    public function get($app, $name, $mask = 0)
    {
        global $injector;

        if ($this->exists($app, $name)) {
            $value = $this->_data[$app][$name];
            if (!is_string($value) || strlen($value) === 0) {
                return $value;
            } elseif ($value[0] === self::NOT_SERIALIZED) {
                return substr($value, 1);
            }

            if (isset($this->_data[self::ENCRYPTED][$app][$name])) {
                $secret = $injector->getInstance('Horde_Secret');
                $value = strval($secret->read($secret->getKey(), $value));
            }

            try {
                return $injector->getInstance('Horde_Pack')->unpack($value);
            } catch (Horde_Pack_Exception $e) {
                return null;
            }
        }

        if ($subkeys = $this->_subkeys($app, $name)) {
            $ret = array();
            foreach ($subkeys as $k => $v) {
                $ret[$k] = $this->get($app, $v, $mask);
            }
            return $ret;
        }

        /* @todo Deprecated. */
        if (strpos($name, self::DATA) === 0) {
            return $this->retrieve($name);
        }

        switch ($mask) {
        case self::TYPE_ARRAY:
            return array();

        case self::TYPE_OBJECT:
            return new stdClass;
        }

        return null;
    }

    /**
     * Sets the value of a session variable.
     *
     * @param string $app    Application name.
     * @param string $name   Session variable name.
     * @param mixed $value   Session variable value.
     * @param integer $mask  One of:
     *   - Horde_Session::TYPE_ARRAY: Force save as an array value.
     *   - Horde_Session::TYPE_OBJECT: Force save as an object value.
     *   - Horde_Session::ENCRYPT: Encrypt the value. (since 2.7.0)
     */
    public function set($app, $name, $value, $mask = 0)
    {
        global $injector;

        if ($this->_readonly) {
            return;
        }

        unset($this->_data[self::ENCRYPTED][$app][$name]);

        /* Each particular piece of session data is generally not used on any
         * given page load.  Thus, for arrays and objects, it is beneficial to
         * always convert to string representations so that the object/array
         * does not need to be rebuilt every time the session is reloaded.
         * For convenience, encrypted data is ALWAYS serialized, regardless
         * of whether it is already a string. */
        if (($mask & self::ENCRYPT) ||
            is_object($value) || ($mask & self::TYPE_OBJECT) ||
            is_array($value) || ($mask & self::TYPE_ARRAY)) {
            $opts = array('compress' => 0);
            if (is_object($value) || ($mask & self::TYPE_OBJECT)) {
                $opts['phpob'] = true;
            }
            $value = $injector->getInstance('Horde_Pack')->pack($value, $opts);

            if ($mask & self::ENCRYPT) {
                $secret = $injector->getInstance('Horde_Secret');
                $value = $secret->write($secret->getKey(), $value);
                $this->_data[self::ENCRYPTED][$app][$name] = true;
            }
        } elseif (is_string($value)) {
            $value = self::NOT_SERIALIZED . $value;
        }

        if (!$this->exists($app, $name) ||
            ($this->_data[$app][$name] !== $value)) {
            $this->_data[$app][$name] = $value;
            $this->sessionHandler->changed = true;
        }
    }

    /**
     * Remove session key(s).
     *
     * @param string $app   Application name.
     * @param string $name  Session variable name.
     */
    public function remove($app, $name = null)
    {
        if ($this->_readonly || !isset($this->_data[$app])) {
            return;
        }

        if (is_null($name)) {
            unset($this->_data[$app]);
            $this->sessionHandler->changed = true;
        } elseif ($this->exists($app, $name)) {
            unset(
                $this->_data[$app][$name],
                $this->_data[self::PRUNE][$this->_getKey($app, $name)]
            );
            $this->sessionHandler->changed = true;
        } else {
            foreach ($this->_subkeys($app, $name) as $val) {
                $this->remove($app, $val);
            }
        }
    }

    /**
     * Generates the unique storage key.
     *
     * @param string $app   Application name.
     * @param string $name  Session variable name.
     *
     * @return string  The unique storage key.
     */
    private function _getKey($app, $name)
    {
        return $app . ':' . $name;
    }

    /**
     * Return the list of subkeys for a master key.
     *
     * @param string $app   Application name.
     * @param string $name  Session variable name.
     *
     * @return array  Subkeyname (keys) and session variable name (values).
     */
    private function _subkeys($app, $name)
    {
        $ret = array();

        if ($name &&
            isset($this->_data[$app]) &&
            ($name[strlen($name) - 1] == '/')) {
            foreach (array_keys($this->_data[$app]) as $k) {
                if (strpos($k, $name) === 0) {
                    $ret[substr($k, strlen($name))] = $k;
                }
            }
        }

        return $ret;
    }

    /* Session tokens. */

    /**
     * Returns the session token.
     *
     * @return string  Session token.
     */
    public function getToken()
    {
        if ($token = $this->get('horde', self::TOKEN_ID)) {
            return $token;
        }

        $token = strval(new Horde_Support_Randomid());
        $this->set('horde', self::TOKEN_ID, $token);

        return $token;
    }

    /**
     * Checks the validity of the session token.
     *
     * @param string $token  Token to check.
     *
     * @throws Horde_Exception
     */
    public function checkToken($token)
    {
        if ($this->getToken() != $token) {
            throw new Horde_Exception('Invalid token!');
        }
    }

    /* Session nonces. */

    /**
     * Returns a single-use, session nonce.
     *
     * @since 2.11.0
     *
     * @return string  Session nonce.
     */
    public function getNonce()
    {
        $id = strval(new Horde_Support_Randomid());

        $nonces = $this->get('horde', self::NONCE_ID, self::TYPE_ARRAY);
        $nonces[] = $id;
        $this->set('horde', self::NONCE_ID, array_values($nonces));

        return $id;
    }

    /**
     * Checks the validity of the session nonce.
     *
     * @since 2.11.0
     *
     * @param string $nonce  Nonce to check.
     *
     * @throws Horde_Exception
     */
    public function checkNonce($nonce)
    {
        $nonces = $this->get('horde', self::NONCE_ID, self::TYPE_ARRAY);
        if (($pos = array_search($nonce, $nonces)) === false) {
            throw new Horde_Exception('Invalid token!');
        }
        unset($nonces[$pos]);
        $this->set('horde', self::NONCE_ID, array_values($nonces));
    }

    /* Session object storage. */

    /** @deprecated */
    const DATA = '_d';

    /**
     * @deprecated  Use Horde_Core_Cache_SessionObjects instead.
     */
    public function store($data, $prune = true, $id = null)
    {
        global $injector;

        if (is_null($id)) {
            $id = strval(new Horde_Support_Randomid());
        }

        $ob = new Horde_Core_Cache_SessionObjects();
        $ob->set($id, $injector->getInstance('Horde_Pack')->pack($data));

        return $id;
    }

    /**
     * @deprecated  Use Horde_Core_Cache_SessionObjects instead.
     */
    public function retrieve($id)
    {
        global $injector;

        $ob = new Horde_Core_Cache_SessionObjects();
        try {
            return $injector->getInstance('Horde_Pack')->unpack($ob->get($id));
        } catch (Horde_Pack_Exception $e) {}

        return null;
    }

    /**
     * @deprecated  Use Horde_Core_Cache_SessionObjects instead.
     */
    public function purge($id)
    {
        $ob = new Horde_Core_Cache_SessionObjects();
        return $ob->expire($id);
    }

}
