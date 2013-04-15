<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 *
 * @property integer $begin  The timestamp when this session began (0 if
 *                           session is not active).
 * @property integer $modified  The timestamp when this session was last
 *                              changed or refreshed.
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
 * @property integer $begin  The timestamp when this session began (0 if
 *                           session is not active).
 */
class Horde_Session
{
    /* Class constants. */
    const BEGIN = '_b';
    const DATA = '_d';
    const MODIFIED = '_m';
    const PRUNE = '_p';

    const TYPE_ARRAY = 1;
    const TYPE_OBJECT = 2;

    const NOT_SERIALIZED = 0;
    const IS_SERIALIZED = 1;

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
        case 'modified':
            return $this->_data[self::MODIFIED];
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
        global $conf;

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
            0,
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
        $this->sessionHandler = $GLOBALS['injector']->createInstance('Horde_SessionHandler');

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
        if (!isset($this->_data[self::MODIFIED])) {
            $this->_data[self::BEGIN] = $curr_time;

            /* Last modification time of session.
             * This will cause the check below to always return true
             * (time() >= 0) and will set the initial value. */
            $this->_data[self::MODIFIED] = 0;
        }

        /* Determine if we need to force write the session to avoid a
         * session timeout, even though the session is unchanged.
         * Theory: On initial login, set the current time plus half of the
         * max lifetime in the session.  Then check this timestamp before
         * saving. If we exceed, force a write of the session and set a
         * new timestamp. Why half the maxlifetime?  It guarantees that if
         * we are accessing the server via a periodic mechanism (think
         * folder refreshing in IMP) that we will catch this refresh. 
         * We make sure to avoid refreshing a timeouted session. */
        $last_modify = $this->_data[self::MODIFIED];
        $timeout = ini_get('session.gc_maxlifetime');
        if ($curr_time >= $last_modify) {
            if ($last_modify == 0 || $curr_time <= $last_modify + $timeout) {
                $this->_data[self::MODIFIED] = intval($curr_time + ($timeout / 2));
                $this->sessionHandler->changed = true;
            }
        }
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
        session_destroy();
        $this->_cleansession = true;
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
        return isset($this->_data[$app][self::NOT_SERIALIZED . $name]) ||
               isset($this->_data[$app][self::IS_SERIALIZED . $name]);
    }

    /**
     * Get the value of a session variable.
     *
     * @param string $app    Application name.
     * @param string $name   Session variable name.
     * @param integer $mask  One of:
     *   - self::TYPE_ARRAY - Return an array value.
     *   - self::TYPE_OBJECT - Return an object value.
     *
     * @return mixed  The value or null if the value doesn't exist.
     */
    public function get($app, $name, $mask = 0)
    {
        global $injector;

        if (isset($this->_data[$app][self::NOT_SERIALIZED . $name])) {
            return $this->_data[$app][self::NOT_SERIALIZED . $name];
        } elseif (isset($this->_data[$app][self::IS_SERIALIZED . $name])) {
            return @unserialize(
                $injector->getInstance('Horde_Compress_Fast')->decompress($this->_data[$app][self::IS_SERIALIZED . $name])
            );
        }

        if ($subkeys = $this->_subkeys($app, $name)) {
            $ret = array();
            foreach ($subkeys as $k => $v) {
                $ret[$k] = $this->get($app, $v, $mask);
            }
            return $ret;
        }

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
     *   - self::TYPE_ARRAY - Force save as an array value.
     *   - self::TYPE_OBJECT - Force save as an object value.
     */
    public function set($app, $name, $value, $mask = 0)
    {
        global $injector;

        if ($this->_readonly) {
            return;
        }

        /* Each particular piece of session data is generally not used on any
         * given page load.  Thus, for arrays and objects, it is beneficial to
         * always convert to string representations so that the object/array
         * does not need to be rebuilt every time the session is reloaded. */
        if (is_object($value) || ($mask & self::TYPE_OBJECT) ||
            is_array($value) || ($mask & self::TYPE_ARRAY)) {
            $this->_data[$app][self::IS_SERIALIZED . $name] = $injector->getInstance('Horde_Compress_Fast')->compress(serialize($value));
            unset($this->_data[$app][self::NOT_SERIALIZED . $name]);
        } else {
            $this->_data[$app][self::NOT_SERIALIZED . $name] = $value;
            unset($this->_data[$app][self::IS_SERIALIZED . $name]);
        }

        $this->sessionHandler->changed = true;
    }

    /**
     * Remove session key(s).
     *
     * @param string $app    Application name.
     * @param string $name   Session variable name.
     */
    public function remove($app, $name = null)
    {
        if ($this->_readonly) {
            return;
        }

        if (!isset($this->_data[$app])) {
            return;
        }

        if (is_null($name)) {
            unset($this->_data[$app]);
        } elseif ($this->exists($app, $name)) {
            unset(
                $this->_data[$app][self::NOT_SERIALIZED . $name],
                $this->_data[$app][self::IS_SERIALIZED . $name],
                $this->_data[self::PRUNE][$this->_getKey($app, $name)]
            );
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
                if (strpos($k, $name) === 1) {
                    $ret[substr($k, strlen($name) + 1)] = substr($k, 1);
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

    /* Session object storage. */

    /**
     * Store an arbitrary piece of data in the session.
     *
     * @param mixed $data     Data to save.
     * @param boolean $prune  Is data pruneable?
     * @param string $id      ID to use (otherwise, is autogenerated).
     *
     * @return string  The session storage id (used to retrieve session data).
     */
    public function store($data, $prune = true, $id = null)
    {
        $id = is_null($id)
            ? strval(new Horde_Support_Randomid())
            : $this->_getStoreId($id);

        $this->set(self::DATA, $id, $data);

        if ($prune) {
            $ptr = &$this->_data[self::PRUNE];
            unset($ptr[$id]);
            $ptr[$id] = 1;
            if (count($ptr) > $this->maxStore) {
                array_shift($ptr);
            }
        }

        return $this->_getKey(self::DATA, $id);
    }

    /**
     * Retrieve data from the session data store (created via store()).
     *
     * @param string $id  The session data ID.
     *
     * @return mixed  The session data value.
     */
    public function retrieve($id)
    {
        return $this->get(self::DATA, $this->_getStoreId($id));
    }

    /**
     * Purge data from the session data store (created via store()).
     *
     * @param string $id  The session data ID.
     */
    public function purge($id)
    {
        $this->remove(self::DATA, $this->_getStoreId($id));
    }

    /**
     * Returns the base storage ID.
     *
     * @param string $id  The session data ID.
     *
     * @return string  The base storage ID (without prefix).
     */
    private function _getStoreId($id)
    {
        $id = trim($id);

        if (strpos($id, self::DATA) === 0) {
            $id = substr($id, strlen(self::DATA) + 1);
        }

        return $id;
    }

}
