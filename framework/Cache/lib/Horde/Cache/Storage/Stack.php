<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */

/**
 * Driver that loops through a given list of storage drivers to search for a
 * cached value. Allows for use of caching backends on top of persistent
 * backends.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */
class Horde_Cache_Storage_Stack extends Horde_Cache_Storage_Base
{
    /**
     * Stack of cache drivers.
     *
     * @var string
     */
    protected $_stack = array();

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     *   - stack: (array) [REQUIRED] An array of storage instances to loop
     *            through, in order of priority. The last entry is considered
     *            the 'master' driver, for purposes of writes.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['stack'])) {
            throw new InvalidArgumentException('Missing stack parameter.');
        }

        parent::__construct($params);
    }

    /**
     */
    protected function _initOb()
    {
        $this->_stack = array_values($this->_params['stack']);
    }

    /**
     */
    public function get($key, $lifetime = 0)
    {
        foreach ($this->_stack as $val) {
            if (($result = $val->get($key, $lifetime)) !== false) {
                return $result;
            }
        }

        return false;
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
        /* Do writes in *reverse* order, since a failure on the master should
         * not allow writes on the other backends. */
        foreach (array_reverse($this->_stack) as $k => $v) {
            if (($result = $v->set($key, $data, $lifetime)) === false) {
                if ($k === 0) {
                    return;
                }

                /* Invalidate cache if write failed. */
                $val->expire($k);
            }
        }
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        foreach ($this->_stack as $val) {
            if (($result = $val->exists($key, $lifetime)) === true) {
                break;
            }
        }

        return $result;
    }

    /**
     */
    public function expire($key)
    {
        foreach ($this->_stack as $val) {
            $success = $val->expire($key);
        }

        /* Success is reported from last (master) expire() call. */
        return $success;
    }

    /**
     */
    public function clear()
    {
        foreach ($this->_stack as $val) {
            try {
                $val->clear();
                $ex = null;
            } catch (Horde_Cache_Exception $e) {
                $ex = $e;
            }
        }

        if ($ex) {
            throw $ex;
        }
    }

}
