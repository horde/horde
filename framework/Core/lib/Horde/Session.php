<?php
/**
 * The Horde_Session:: class provides a set of methods for handling the
 * administration and contents of the Horde session variable.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Session
{
    /* Class constants. */
    const DATA = '_d';
    const MODIFIED = '_m';
    const PRUNE = '_p';
    const SERIALIZED = '_s';

    const TYPE_ARRAY = 1;
    const TYPE_OBJECT = 2;

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
     * Indicate that a new session ID has been generated for this page load.
     *
     * @var boolean
     */
    private $_cleansession = false;

    /**
     * Use LZF compression?
     * We use LZF compression on arrays and objects. Compressing numbers and
     * most strings is not enought of an benefit for the overhead.
     *
     * @var boolean
     */
    private $_lzf = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_lzf = Horde_Util::extensionExists('lzf');

        /* Make sure global session variable is always initialized. */
        $_SESSION = array();
    }

    /**
     * Sets a custom session handler up, if there is one.
     *
     * @param boolean $start         Initiate the session?
     * @param string $cache_limiter  Override for the session cache limiter
     *                               value.
     *
     * @throws Horde_Exception
     */
    public function setup($start = true, $cache_limiter = null)
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

        session_set_cookie_params(
            $conf['session']['timeout'],
            $conf['cookie']['path'],
            $conf['cookie']['domain'],
            $conf['use_ssl'] == 1 ? 1 : 0
        );
        session_cache_limiter(is_null($cache_limiter) ? $conf['session']['cache_limiter'] : $cache_limiter);
        session_name(urlencode($conf['session']['name']));

        /* We want to create an instance here, not get, since we may be
         * destroying the previous instances in the page. */
        $this->sessionHandler = $GLOBALS['injector']->createInstance('Horde_SessionHandler');

        if ($start) {
            session_start();
            $this->_start();
        }
    }

    /**
     * Tasks to perform when starting a session.
     */
    private function _start()
    {
        /* Create internal data arrays. */
        if (!isset($_SESSION[self::MODIFIED])) {
            /* Last modification time of session.
             * This will cause the check below to always return true
             * (time() >= 0) and will set the initial value. */
            $_SESSION[self::MODIFIED] = 0;

            /* Is this key serialized? */
            $_SESSION[self::SERIALIZED] = array();
        }

        /* Determine if we need to force write the session to avoid a
         * session timeout, even though the session is unchanged.
         * Theory: On initial login, set the current time plus half of the
         * max lifetime in the session.  Then check this timestamp before
         * saving. If we exceed, force a write of the session and set a
         * new timestamp. Why half the maxlifetime?  It guarantees that if
         * we are accessing the server via a periodic mechanism (think
         * folder refreshing in IMP) that we will catch this refresh. */
        $curr_time = time();
        if ($curr_time >= $_SESSION[self::MODIFIED]) {
            $_SESSION[self::MODIFIED] = intval($curr_time + (ini_get('session.gc_maxlifetime') / 2));
            $this->sessionHandler->changed = true;
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
        $_SESSION = array();
        $this->_start();

        $this->_cleansession = true;

        return true;
    }

    /**
     * Close the current session.
     */
    public function close()
    {
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
        return isset($_SESSION[$app][$name]);
    }

    /**
     * Get the value of a session variable.
     *
     * @param string $app    Application name.
     * @param string $name   Session variable name.
     * @param integer $mask  One of:
     * <pre>
     * self::TYPE_ARRAY - Return an array value.
     * self::TYPE_OBJECT - Return an object value.
     * </pre>
     *
     * @return mixed  The value or null if the value doesn't exist.
     */
    public function get($app, $name, $mask = 0)
    {
        if (!$this->exists($app, $name)) {
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

            default:
                return null;
            }
        }

        $data = $_SESSION[$app][$name];
        $key = $this->_getKey($app, $name);

        if (!isset($_SESSION[self::SERIALIZED][$key])) {
            return $data;
        }

        if ($this->_lzf &&
            (($data = @lzf_decompress($data)) === false)) {
            $this->remove($app, $name);
            return $this->get($app, $name);
        }

        return @unserialize($data);
    }

    /**
     * Sets the value of a session variable.
     *
     * @param string $app    Application name.
     * @param string $name   Session variable name.
     * @param mixed $value   Session variable value.
     * <pre>
     * self::TYPE_ARRAY - Force save as an array value.
     * self::TYPE_OBJECT - Force save as an object value.
     * </pre>
     */
    public function set($app, $name, $value, $mask = 0)
    {
        $key = $this->_getKey($app, $name);

        /* Each particular piece of session data is generally not used on any
         * given page load.  Thus, for arrays and objects, it is beneficial to
         * always convert to string representations so that the object/array
         * does not need to be rebuilt every time the session is reloaded. */
        if (is_object($value) || ($mask & self::TYPE_OBJECT) ||
            is_array($value) || ($mask & self::TYPE_ARRAY)) {
            $value = serialize($value);
            if ($this->_lzf) {
                $value = lzf_compress($value);
            }
            $_SESSION[self::SERIALIZED][$key] = true;
        } else {
            unset($_SESSION[self::SERIALIZED][$key]);
        }

        $_SESSION[$app][$name] = $value;
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
        if (!isset($_SESSION[$app])) {
            return;
        }

        if (is_null($name)) {
            foreach (array_keys($_SESSION[$app]) as $key) {
                unset($_SESSION[self::SERIALIZED][$key]);
            }
            unset($_SESSION[$app]);
        } elseif (isset($_SESSION[$app][$name])) {
            $key = $this->_getKey($app, $name);
            unset(
                $_SESSION[$app][$name],
                $_SESSION[self::PRUNE][$key],
                $_SESSION[self::SERIALIZED][$key]
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
     * @param string $app    Application name.
     * @param string $name   Session variable name.
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
     * @param string $app    Application name.
     * @param string $name   Session variable name.
     *
     * @return array  Subkeyname (keys) and session variable name (values).
     */
    private function _subkeys($app, $name)
    {
        $ret = array();

        if ($name &&
            isset($_SESSION[$app]) &&
            ($name[strlen($name) - 1] == '/')) {
            foreach (array_keys($_SESSION[$app]) as $k) {
                if (strpos($k, $name) === 0) {
                    $ret[substr($k, strlen($name))] = $k;
                }
            }
        }

        return $ret;
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
            $ptr = &$_SESSION[self::PRUNE];
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
