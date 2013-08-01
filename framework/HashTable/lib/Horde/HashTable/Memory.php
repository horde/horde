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
 * Implementation of HashTable within PHP memory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   HashTable
 */
class Horde_HashTable_Memory
extends Horde_HashTable_Base
implements Horde_HashTable_Lock
{
    /**
     * Data.
     *
     * @var array
     */
    protected $_data = array();

    /**
     */
    protected function _delete($keys)
    {
        foreach ($keys as $val) {
            unset($this->_data[$val]);
        }

        return true;
    }

    /**
     */
    protected function _exists($keys)
    {
        $curr = time();
        $out = array();

        foreach ($keys as $val) {
            if (isset($this->_data[$val]) &&
                (!isset($this->_data[$val]['l']) ||
                ($this->_data[$val]['l'] >= $curr))) {
                $out[$val] = true;
            } else {
                $out[$val] = false;
                $this->delete($val);
            }
        }

        return $out;
    }

    /**
     */
    protected function _get($keys)
    {
        $exists = $this->_exists($keys);
        $out = array();

        foreach ($keys as $val) {
            $out[$val] = $exists[$val]
                ? $this->_data[$val]['v']
                : false;
        }

        return $out;
    }

    /**
     */
    protected function _set($key, $val, $opts)
    {
        if (!empty($opts['replace'])) {
            $exists = $this->_exists(array($key));
            if (!$exists[$key]) {
                return false;
            }
        }

        $this->_data[$key] = array_filter(array(
            'l' => empty($opts['timeout']) ? null : (time() + $opts['timeout']),
            'v' => $val
        ));

        return true;
    }

    /**
     */
    public function clear()
    {
        $this->_data = array();
    }

    /**
     */
    public function lock($key)
    {
    }

    /**
     */
    public function unlock($key)
    {
    }

}
