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
 * @property-read boolean $persistent  Does hash table provide persistent
 *                                     storage?
 */
abstract class Horde_HashTable implements ArrayAccess, Serializable
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
    protected $_params = array();

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
    public function __get($name)
    {
        switch ($name) {
        case 'persistent':
            return $this->_persistent;
        }
    }

    /**
     * Delete a key.
     *
     * @param string $key  The key.
     *
     * @return boolean  True on success.
     */
    public function delete($key)
    {
        if (!isset($this->_noexist[$key]) && $this->_delete($key)) {
            $this->_noexist[$key] = true;
            return true;
        }

        return false;
    }

    /**
     * Delete a key.
     *
     * @param string $key  The key.
     *
     * @return boolean  True on success.
     */
    abstract protected function _delete($key);

    /**
     * Get data associated with a key(s).
     *
     * @param mixed $keys  The key or an array of keys.
     *
     * @return mixed  The string/array on success (return type is the type of
     *                $keys); false on failure.
     */
    public function get($keys)
    {
        $out = $todo = array();

        if (!($ret_array = is_array($keys))) {
            $keys = array($keys);
        }

        foreach ($keys as $val) {
            if (isset($this->_noexist[$key])) {
                $out[$val] = false;
            } else {
                $todo[] = $val;
            }
        }

        if (!empty($todo)) {
            $out = array_merge($out, $this->_get($todo));
        }

        return $ret_array
            ? $out
            : reset($out);
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
     * Set the value of a key.
     *
     * @param string $key  The key.
     * @param mixed $val   The data to store.
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

        if ($this->_set($key, $val, $opts)) {
            unset($this->_noexist[$key]);
            return true;
        }

        return false;
    }

    /**
     * Set the value of a key.
     *
     * @param string $key  The key.
     * @param mixed $val   The data to store.
     * @param array $opts  Additional options (see set()).
     *
     * @return boolean  True on success.
     */
    abstract protected function _set($key, $val, $opts);

    /**
     * Clear all hash table entries.
     *
     * @param string $prefix  The key prefix (if empty, this command may
     *                        delete entries from other systems using the
     *                        hash table).
     */
    public function clear($prefix = null)
    {
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        return ($this->get($offset) !== false);
    }

    /**
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     */
    public function offsetSet($offset)
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
