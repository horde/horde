<?php
/**
 * A mocked version of the storage class that stores data for the current
 * page access only.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Data
 */
class Horde_Data_Storage_Mock implements Horde_Data_Storage
{
    /**
     * Data storage.
     *
     * @var array
     */
    protected $_data = array();

    /**
     */
    public function get($key)
    {
        return $this->_data[$key];
    }

    /**
     */
    public function set($key, $value = null)
    {
        if (is_null($value)) {
            unset($this->_data[$key]);
        } else {
            $this->_data[$key] = $value;
        }
    }

    /**
     */
    public function exists($key)
    {
        return isset($this->_data[$key]);
    }

    /**
     */
    public function clear()
    {
        $this->_data = array();
    }

}
