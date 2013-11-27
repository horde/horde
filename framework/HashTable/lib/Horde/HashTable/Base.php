<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   HashTable
 */

/**
 * The Horde_HashTable class provides an API to interact with various hash
 * table implementations.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   HashTable
 *
 * @property-read boolean $locking  Does hash table provide locking? (@since
 *                                  1.1.0)
 * @property-read boolean $persistent  Does hash table provide persistent
 *                                     storage?
 * @property-write string $prefix  Set the hash key prefix.
 */
abstract class Horde_HashTable_Base implements ArrayAccess, Serializable
{
    /**
     * A list of items known not to exist.
     *
     * @var array
     */
    protected $_noexist = array();

    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params = array(
        'prefix' => 'hht_'
    );

    /**
     * Persistent storage provided by driver?
     *
     * @var boolean
     */
    protected $_persistent = false;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <pre>
     *   - logger: (Horde_Log_Logger) Logger object.
     *   - prefix: (string) Prefix to use for key storage.
     * </pre>
     *
     * @throws Horde_HashTable_Exception
     */
    public function __construct(array $params = array())
    {
        $this->_params = array_merge($this->_params, $params);
        $this->_init();
    }

    /**
     * Do initialization.
     *
     * @throws Horde_HashTable_Exception
     */
    protected function _init()
    {
    }

    /**
     */
    public function __set($name, $val)
    {
        switch ($name) {
        case 'prefix':
            $this->_params['prefix'] = $val;
            break;
        }
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'locking':
            return ($this instanceof Horde_HashTable_Lock);

        case 'persistent':
            return $this->_persistent;
        }
    }

    /**
     * Delete a key(s).
     *
     * @param mixed $keys  The key or an array of keys to delete.
     *
     * @return boolean  True on success.
     */
    public function delete($keys)
    {
        if (!is_array($keys)) {
            $keys = array($keys);
        }

        if ($todo = array_diff($keys, array_keys($this->_noexist))) {
            $to_delete = array_fill_keys(array_map(array($this, 'hkey'), $todo), $todo);
            if (!$this->_delete(array_keys($to_delete))) {
                return false;
            }

            $this->_noexist = array_merge($this->_noexist, array_fill_keys(array_values($todo), true));
        }

        return true;
    }

    /**
     * Delete keys.
     *
     * @param array $key  An array of keys to delete.
     *
     * @return boolean  True on success.
     */
    abstract protected function _delete($keys);

    /**
     * Do the keys exists?
     *
     * @param mixed $keys  The key or an array of keys.
     *
     * @return mixed  A boolean/array of booleans indicating existence (return
     *                type is the type of $keys).
     */
    public function exists($keys)
    {
        return $this->_getExists($keys, array($this, '_exists'));
    }

    /**
     * Get data associated with keys.
     *
     * @param array $keys  An array of keys.
     *
     * @return array  Existence check. Values are boolean true/false.
     */
    abstract protected function _exists($keys);

    /**
     * Get data associated with a key(s).
     *
     * @param mixed $keys  The key or an array of keys.
     *
     * @return mixed  The string/array on success (return type is the type of
     *                $keys); false value(s) on failure.
     */
    public function get($keys)
    {
        return $this->_getExists($keys, array($this, '_get'));
    }

    /**
     * Get data associated with keys.
     *
     * @param array $keys  An array of keys.
     *
     * @return array  The retrieved keys. Non-existent keys should return
     *                false as the value.
     */
    abstract protected function _get($keys);

    /**
     * Does a get/exists action on a set of keys.
     *
     * @param mixed $keys         The key or an array of keys.
     * @param callable $callback  The internal callback action.
     *
     * @return mixed  The results.
     */
    protected function _getExists($keys, $callback)
    {
        $out = $todo = array();

        if (!($ret_array = is_array($keys))) {
            $keys = array($keys);
        }

        foreach ($keys as $val) {
            if (isset($this->_noexist[$val])) {
                $out[$val] = false;
            } else {
                $todo[$this->hkey($val)] = $val;
            }
        }

        if (!empty($todo)) {
            foreach (call_user_func($callback, array_keys($todo)) as $key => $val) {
                if ($val === false) {
                    $this->_noexist[$todo[$key]] = true;
                }
                $out[$todo[$key]] = $val;
            }
        }

        return $ret_array
            ? $out
            : reset($out);
    }

    /**
     * Set the value of a key.
     *
     * @param string $key  The key.
     * @param string $val  The string to store.
     * @param array $opts  Additional options:
     * <pre>
     *   - replace: (boolean) Replace the value of key. If key doesn't exist,
     *              returns false.
     *              DEFAULT: false
     *   - timeout: (integer) Expiration time in seconds.
     *              DEFAULT: Doesn't expire.
     * </pre>
     *
     * @return boolean  True on success, false on error.
     */
    public function set($key, $val, array $opts = array())
    {
        if (!empty($opts['replace']) && isset($this->_noexist[$key])) {
            return false;
        }

        if ($this->_set($this->hkey($key), $val, $opts)) {
            unset($this->_noexist[$key]);
            return true;
        }

        return false;
    }

    /**
     * Set the value of a key.
     *
     * @param string $key  The key.
     * @param string $val  The string to store.
     * @param array $opts  Additional options (see set()).
     *
     * @return boolean  True on success.
     */
    abstract protected function _set($key, $val, $opts);

    /**
     * Clear all hash table entries.
     */
    abstract public function clear();

    /**
     * Add local prefix to beginning of key.
     *
     * @param string $key  Key name.
     *
     * @return string  Hash table key identifier.
     */
    public function hkey($key)
    {
        return $this->_params['prefix'] . $key;
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     */
    public function offsetUnset($offset)
    {
        return $this->delete($offset);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return serialize($this->_params);
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_params = @unserialize($data);
        $this->_init();
    }

}
