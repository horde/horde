<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Cache
 * @package   Cache
 */

/**
 * A memory overlay for a cache backend. Caches results in PHP memory for the
 * current access so the underlying cache backend is not continually hit.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013-2015 Horde LLC
 * @deprecated Use Memory driver as first backend in stack driver instead.
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Cache
 * @package    Cache
 */
class Horde_Cache_Storage_Memoryoverlay extends Horde_Cache_Storage_Base
{
    /**
     * The memory cache.
     *
     * @var array
     */
    private $_cache = array();

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     *   - backend: (Horde_Cache_Storage_Base) [REQUIRED] The master storage
     *              backend.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['backend'])) {
            throw new InvalidArgumentException('Missing backend parameter.');
        }

        parent::__construct($params);
    }

    /**
     */
    public function get($key, $lifetime = 0)
    {
        if (!isset($this->_cache[$key])) {
            $this->_cache[$key] = $this->_params['backend']->get($key, $lifetime);
        }

        return $this->_cache[$key];
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
        $this->_cache[$key] = $data;
        $this->_params['backend']->set($key, $data, $lifetime);
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        return isset($this->_cache[$key])
            ? true
            : $this->_params['backend']->exists($key, $lifetime);
    }

    /**
     */
    public function expire($key)
    {
        unset($this->_cache[$key]);
        $this->_params['backend']->expire($key);
    }

    /**
     */
    public function clear()
    {
        $this->_cache = array();
        $this->_params['backend']->clear();
    }

}
