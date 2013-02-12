<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * The null driver provides a set of methods for handling the administration
 * and contents of the Horde session variable when the PHP session is not
 * desired. Needed so things like application authentication can work within a
 * single HTTP request when we don't need the overhead of a full PHP session.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Session_Null extends Horde_Session
{
    /**
     * Store session data internally.
     *
     * @var array
     */
    protected $_data = array();
    private $_cleansession = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Don't call default constructor.
    }

    /**
     */
    public function setup($start = true, $cache_limiter = null, $session_id = null)
    {
        global $conf;
        session_cache_limiter(is_null($cache_limiter) ? $conf['session']['cache_limiter'] : $cache_limiter);
        if ($start) {
            $this->start();
        }
    }

    /**
     */
    public function start()
    {
        // We must start a session to ensure that session_id() is available,
        // but since we don't actually need to write to it, close it at once
        // to avoid session lock issues.
        session_start();
        $this->_active = true;
        session_write_close();

        register_shutdown_function(array($this, 'destroy'));
    }

    /**
     */
    public function clean()
    {
        if ($this->_cleansession) {
            return false;
        }
        session_regenerate_id(true);
        $this->destroy();
        return true;
    }

    /**
     */
    public function close()
    {
        $this->_active = false;
    }

    /**
     */
    public function destroy()
    {
        session_unset();
        $this->_data = array();
        $this->_cleansession = true;
    }

    /* Session variable access. */

    /**
     */
    public function exists($app, $name)
    {
        return isset($this->_data[$app][self::NOT_SERIALIZED . $name]) ||
               isset($this->_data[$app][self::IS_SERIALIZED . $name]);
    }

    /**
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
