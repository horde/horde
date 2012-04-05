<?php
/**
 * The Horde_Session_Null:: class provides a set of methods for handling the
 * administration and contents of the Horde session variable when the PHP
 * session is not desired. Needed so things like application authentication can
 * work within a single HTTP request when we don't need the overhead of a
 * full PHP session.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Session_Null extends Horde_Session
{
    protected $_data = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        // We must start a session to ensure that session_id() is available.
        // Places such as Horde_Secret use it as an encryption key.
        session_start();
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
    }

    /**
     * Starts the session.
     */
    public function start()
    {
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
        $this->_data = array();
        return true;
    }

    /**
     * Close the current session.
     */
    public function close()
    {
        session_destroy();
    }

    /**
     * Destroy session data.
     */
    public function destroy()
    {
        $this->clean();
        session_destroy();
    }

    /**
     * Is the current session active (read/write)?
     *
     * @return boolean  True if the current session is active.
     */
    public function isActive()
    {
        return true;
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
        if (isset($this->_data[$app][self::NOT_SERIALIZED . $name])) {
            return $this->_data[$app][self::NOT_SERIALIZED . $name];
        } elseif (isset($this->_data[$app][self::IS_SERIALIZED . $name])) {
            $data = $this->_data[$app][self::IS_SERIALIZED . $name];
            return @unserialize($data);
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
        /* Each particular piece of session data is generally not used on any
         * given page load.  Thus, for arrays and objects, it is beneficial to
         * always convert to string representations so that the object/array
         * does not need to be rebuilt every time the session is reloaded. */
        if (is_object($value) || ($mask & self::TYPE_OBJECT) ||
            is_array($value) || ($mask & self::TYPE_ARRAY)) {
            $this->_data[$app][self::IS_SERIALIZED . $name] = serialize($value);
            unset($this->_data[$app][self::NOT_SERIALIZED . $name]);
        } else {
            $this->_data[$app][self::NOT_SERIALIZED . $name] = $value;
            unset($this->_data[$app][self::IS_SERIALIZED . $name]);
        }
    }

    /**
     * Remove session key(s).
     *
     * @param string $app    Application name.
     * @param string $name   Session variable name.
     */
    public function remove($app, $name = null)
    {
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
