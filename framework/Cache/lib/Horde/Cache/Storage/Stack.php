<?php
/**
 * This class loops through a given list of storage drivers to search for a
 * cached value.  This driver allows for use of caching backends on top of
 * persistent backends.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
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
     * 'stack' - (array) [REQUIRED] An array of storage instances to loop
     *           through, in order of priority. The last entry is considered
     *           the 'master' driver, for purposes of writes.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['stack'])) {
            throw new InvalidArgumentException('Missing stack parameter.');
        }
        $this->_stack = $params['stack'];
        unset($params['stack']);

        parent::__construct($params);
    }

    /**
     */
    public function get($key, $lifetime = 0)
    {
        foreach ($this->_stack as $val) {
            $result = $val->get($key, $lifetime);
            if ($result !== false) {
                return $result;
            }
        }

        return false;
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
        /* Do writes in *reverse* order - it is OK if a write to one of the
         * non-master backends fails. */
        $master = true;

        foreach (array_reverse($this->_stack) as $val) {
            $result = $val->set($key, $data, $lifetime);
            if ($result === false) {
                if ($master) {
                    return;
                }

                /* Attempt to invalidate cache if write failed. */
                $val->expire($id);
            }
            $master = false;
        }
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        foreach ($this->_stack as $val) {
            $result = $val->exists($key, $lifetime);
            if ($result === true) {
                break;
            }
        }

        return $result;
    }

    /**
     */
    public function expire($key)
    {
        /* Only report success from master. */
        $master = $success = true;

        foreach (array_reverse($this->_stack) as $val) {
            $result = $val->expire($key);
            if ($master && ($result === false)) {
                $success = false;
            }
            $master = false;
        }

        return $success;
    }

    /**
     */
    public function clear()
    {
        /* Only report errors from master. */
        $exception = null;
        $master = true;

        foreach (array_reverse($this->_stack) as $val) {
            try {
                $val->clear();
            } catch (Horde_Cache_Exception $e) {
                if ($master) {
                    $exception = $e;
                }
            }
            $master = false;
        }

        if ($exception) {
            throw $exception;
        }
    }

}
