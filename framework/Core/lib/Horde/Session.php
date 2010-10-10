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
     *
     * @param boolean $start  Initiate the session?
     */
    public function __construct($start = true)
    {
        $this->_lzf = Horde_Util::extensionExists('lzf');

        $this->setup($start);
    }

    /**
     * Sets a custom session handler up, if there is one.
     *
     * @param boolean $start  Initiate the session?
     *
     * @throws Horde_Exception
     */
    public function setup($start = true)
    {
        global $conf, $registry;

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
        session_cache_limiter(is_null($registry->initParams['session_cache_limiter']) ? $conf['session']['cache_limiter'] : $registry->initParams['session_cache_limiter']);
        session_name(urlencode($conf['session']['name']));

        /* We want to create an instance here, not get, since we may be
         * destroying the previous instances in the page. */
        $this->sessionHandler = $GLOBALS['injector']->createInstance('Horde_SessionHandler');

        if ($start) {
            session_start();

            /* Create internal data arrays. */
            if (!isset($_SESSION['_s'])) {
                /* Is this key serialized? */
                $_SESSION['_s'] = array();
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

        if (!isset($_SESSION['_s'][$ob->key])) {
            return $data;
        }

        if ($this->_lzf) {
            $data = lzf_decompress($data);
        }

        return ($_SESSION['_s'][$ob->key] == 's')
            ? @unserialize($data)
            : json_decode($data, true);
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        $ob = $this->_parseOffset($offset);

        /* Each particular piece of session data is generally not used on any
         * given page load.  Thus, for arrays ans objects, it is beneficial to
         * always convert to string representations so that the object/array
         * does not need to be rebuilt every time the session is reloaded. */
        if (is_object($value)) {
            $value = serialize($value);
            if ($this->_lzf) {
                $value = lzf_compress($value);
            }
            $_SESSION['_s'][$ob->key] = 's';
        } elseif (is_array($value)) {
            $value = json_encode($value);
            if ($this->_lzf) {
                $value = lzf_compress($value);
            }
            $_SESSION['_s'][$ob->key] = 'j';
        }

        $_SESSION[$ob->app][$ob->name] = $value;
    }

    /**
     */
    public function offsetUnset($offset)
    {
        $ob = $this->_parseOffset($offset);

        if (isset($_SESSION[$ob->app])) {
            if (!strlen($ob->key)) {
                foreach (array_keys($_SESSION[$ob->app]) as $key) {
                    unset($_SESSION['_s'][$key]);
                }
                unset($_SESSION[$ob->app]);
            } elseif (isset($_SESSION[$ob->app][$ob->name])) {
                unset($_SESSION[$ob->app][$ob->name], $_SESSION['_s'][$ob->key]);
            }
        }
    }

    /* ArrayAccess helper methods. */

    /**
     * Parses a session variable identifier.
     * Format:
     * <pre>
     * [app:]name[;default]
     *
     * app - Application name.
     *       DEFAULT: horde
     * default - Default value type to return if value doesn't exist.
     *           Valid types: array, object
     *           DEFAULT: none
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
