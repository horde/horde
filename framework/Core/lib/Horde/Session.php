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
class Horde_Session implements ArrayAccess
{
    /* Class constants. */
    const DATA = '_d';
    const PRUNE = '_p';
    const SERIALIZED = '_s';

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

            /* Create internal data arrays. */
            if (!isset($_SESSION[self::SERIALIZED])) {
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
            if ($curr_time >= intval($this['horde:session_mod'])) {
                $this['horde:session_mod'] = $curr_time + (ini_get('session.gc_maxlifetime') / 2);
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

    /**
     * Return the list of subkeys for a master key.
     *
     * @param object $ob  See _parseOffset().
     *
     * @return array  Subkeyname (keys) and session variable name (values).
     */
    private function _subkeys($ob)
    {
        $ret = array();

        if (isset($_SESSION[$ob->app]) &&
            ($ob->name[strlen($ob->name) - 1] == '/')) {
            foreach (array_keys($_SESSION[$ob->app]) as $k) {
                if (strpos($k, $ob->name) === 0) {
                    $ret[substr($k, strlen($ob->name))] = $k;
                }
            }
        }

        return $ret;
    }

    /* Session object storage. */

    /**
     * Store an arbitrary piece of data in the session.
     * Equivalent to: $this[self::DATA . ':' . $id] = $value;
     *
     * @param mixed $data     Data to save.
     * @param boolean $prune  Is data pruneable?
     * @param string $id      ID to use (otherwise, is autogenerated).
     *
     * @return string  The session storage id (used to get session data).
     */
    public function store($data, $prune = true, $id = null)
    {
        if (is_null($id)) {
            $id = strval(new Horde_Support_Randomid());
        } else {
            $offset = $this->_parseOffset($id);
            $id = $offset->name;
        }

        $full_id = self::DATA . ':' . $id;
        $this->_offsetSet($this->_parseOffset($full_id), $data);

        if ($prune) {
            $ptr = &$_SESSION[self::PRUNE];
            unset($ptr[$id]);
            $ptr[$id] = 1;
            if (count($ptr) > $this->maxStore) {
                array_shift($ptr);
            }
        }

        return $full_id;
    }

    /**
     * Retrieve data from the session data store (created via store()).
     * Equivalent to: $value = $this[self::DATA . ':' . $id];
     *
     * @param string $id  The session data ID.
     *
     * @return mixed  The session data value.
     */
    public function retrieve($id)
    {
        $id = trim($id);

        if (strpos($id, self::DATA) !== 0) {
            $id = self::DATA . ':' . $id;
        }

        return $this[$id];
    }

    /**
     * Purge data from the session data store (created via store()).
     * Equivalent to: unset($this[self::DATA . ':' . $id]);
     *
     * @param string $id  The session data ID.
     */
    public function purge($id)
    {
        $id = trim($id);

        if (strpos($id, self::DATA) !== 0) {
            $id = self::DATA . ':' . $id;
        }

        unset($this[$id]);
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        $ob = $this->_parseOffset($offset);

        return isset($_SESSION[$ob->app][$ob->name]);
    }

    /**
     */
    public function offsetGet($offset)
    {
        $ob = $this->_parseOffset($offset);

        if (!isset($_SESSION[$ob->app][$ob->name])) {
            $subkeys = $this->_subkeys($ob);
            if (!empty($subkeys)) {
                $ret = array();
                foreach ($subkeys as $k => $v) {
                    $ret[$k] = $this[$v];
                }
                return $ret;
            }

            switch ($ob->type) {
            case 'array':
                return array();

            case 'object':
                return new stdClass;

            default:
                return null;
            }
        }

        $data = $_SESSION[$ob->app][$ob->name];

        if (!isset($_SESSION[self::SERIALIZED][$ob->key])) {
            return $data;
        }

        if ($this->_lzf &&
            (($data = @lzf_decompress($data)) === false)) {
            unset($this[$offset]);
            return $this[$offset];
        }

        return ($_SESSION[self::SERIALIZED][$ob->key] == 's')
            ? @unserialize($data)
            : json_decode($data, true);
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        $ob = $this->_parseOffset($offset);

        if ($ob->app == self::DATA) {
            $this->store($value, false, $ob->name);
        } else {
            $this->_offsetSet($ob, $value);
        }
    }

    /**
     * TODO
     */
    private function _offsetSet($ob, $value)
    {
        /* Each particular piece of session data is generally not used on any
         * given page load.  Thus, for arrays ans objects, it is beneficial to
         * always convert to string representations so that the object/array
         * does not need to be rebuilt every time the session is reloaded. */
        if (is_object($value) || ($ob->type == 'object')) {
            $value = serialize($value);
            if ($this->_lzf) {
                $value = lzf_compress($value);
            }
            $_SESSION[self::SERIALIZED][$ob->key] = 's';
        } elseif (is_array($value) || ($ob->type == 'array')) {
            $value = json_encode($value);
            if ($this->_lzf) {
                $value = lzf_compress($value);
            }
            $_SESSION[self::SERIALIZED][$ob->key] = 'j';
        }

        $_SESSION[$ob->app][$ob->name] = $value;
        $this->sessionHandler->changed = true;
    }

    /**
     */
    public function offsetUnset($offset)
    {
        $ob = $this->_parseOffset($offset);

        if (isset($_SESSION[$ob->app])) {
            if (!strlen($ob->name)) {
                foreach (array_keys($_SESSION[$ob->app]) as $key) {
                    unset($_SESSION[self::SERIALIZED][$key]);
                }
                unset($_SESSION[$ob->app]);
            } elseif (isset($_SESSION[$ob->app][$ob->name])) {
                unset(
                    $_SESSION[$ob->app][$ob->name],
                    $_SESSION[self::PRUNE][$ob->key],
                    $_SESSION[self::SERIALIZED][$ob->key]
                );
            } else {
                foreach ($this->_subkeys($ob) as $val) {
                    unset($this[$val]);
                }
            }
        }
    }

    /* ArrayAccess helper methods. */

    /**
     * Parses a session variable identifier.
     * Format:
     * <pre>
     * [app:]name[/subkey][;default]
     *
     * app - Application name.
     *       DEFAULT: horde
     * default - Default value type to return if value doesn't exist.
     *           Valid types: array, object
     *           DEFAULT: none
     * subkey - Indicate that this entry is a subkey of the master name key.
     *          Requesting a session key with a trailing '/' will retrieve all
     *          subkeys of the given master key.
     * </pre>
     *
     * @return object  Object with the following properties:
     * <pre>
     * app - Application name.
     * key - Offset key.
     * name - Variable name.
     * type - Variable type.
     * </pre>
     */
    private function _parseOffset($offset)
    {
        $ob = new stdClass;

        $parts = explode(':', $offset);
        if (isset($parts[1])) {
            $ob->app = $parts[0];
            $type = explode(';', $parts[1]);
        } else {
            $ob->app = 'horde';
            $type = explode(';', $parts[0]);
        }

        $ob->name = $type[0];
        $ob->key = $ob->app . ':' . $ob->name;
        $ob->type = isset($type[1])
            ? $type[1]
            : 'scalar';

        return $ob;
    }

}
