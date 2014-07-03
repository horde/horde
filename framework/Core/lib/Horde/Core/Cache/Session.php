<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Cache data in session, offloading the data to the cache storage backend
 * when the data becomes too large.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Core_Cache_Session extends Horde_Cache_Storage_Base
{
    /* Suffix to add to storage_key to produce stored key. */
    const STORED_KEY = '_s';

    /**
     * The list of keys stored in the cache backend.
     *
     * @var array
     */
    protected $_stored = array();

    /**
     * @param array $params  Configuration parameters:
     *   - app: (string) Application to store session data under.
     *   - cache: (Horde_Cache_Storage_Backend) [REQUIRED] The backend cache
     *            storage driver used to store large entries.
     *   - maxsize: (integer) The maximum size of the data to store in the
     *              session (0 to always store in session).
     *   - storage_key: (string) The storage key to save the session data
     *                  under.
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['cache'])) {
            throw new InvalidArgumentException('Missing cache parameter.');
        }

        parent::__construct(array_merge(
            array(
                'app' => 'horde',
                'maxsize' => 5000,
                'storage_key' => 'sess_cache'
            ),
            $params
        ));

        if ($params['cache'] instanceof Horde_Cache_Storage_Null) {
            $this->_params['maxsize'] = 0;
        }
    }

    /**
     */
    protected function _initOb()
    {
        global $session;

        $this->_stored = $session->get(
            $this->_params['app'],
            $this->_params['storage_key'] . self::STORED_KEY,
            $session::TYPE_ARRAY
        );
    }

    /**
     * @param integer $lifetime  Ignored in this driver.
     */
    public function get($key, $lifetime = 0)
    {
        global $session;

        return isset($this->_stored[$key])
            ? $this->_params['cache']->get($this->_getCid($key, false), 0)
            : $session->get($this->_params['app'], $this->_getCid($key, true));
    }

    /**
     * @param integer $lifetime  Ignored in this driver.
     */
    public function set($key, $data, $lifetime = 0)
    {
        global $session;

        if ($this->_params['maxsize'] &&
            (strlen($data) > $this->_params['maxsize'])) {
            $this->_params['cache']->set($this->_getCid($key, false), $data);
            $this->_stored[$key] = 1;
            $this->_saveStored();
            $session->remove($this->_params['app'], $key);
        } else {
            $session->set(
                $this->_params['app'],
                $this->_getCid($key, true),
                $data
            );
            if (isset($this->_stored[$key])) {
                unset($this->_stored[$key]);
                $this->_saveStored();
                $this->_params['cache']->expire($key);
            }
        }
    }

    /**
     * @param integer $lifetime  Ignored in this driver.
     */
    public function exists($key, $lifetime = 0)
    {
        return ($this->get($key) !== false);
    }

    /**
     */
    public function expire($key)
    {
        global $session;

        $session->remove($this->_params['app'], $key);
        if (isset($this->_stored[$key])) {
            unset($this->_stored[$key]);
            $this->_saveStored();
            $this->_params['cache']->expire($key);
        }
    }

    /**
     */
    public function clear()
    {
        global $session;

        $session->remove(
            $this->_params['app'],
            $this->_params['storage_key'] . '/'
        );

        foreach (array_keys($this->_stored) as $key) {
            $this->_params['cache']->expire($key);
        }
        $this->_stored = array();
        $this->_saveStored();
    }

    /**
     */
    protected function _getCid($key, $in_session)
    {
        global $session;

        if ($in_session) {
            return $this->_params['storage_key'] . '/' . $key;
        }

        return implode('|', array(
            $this->_params['app'],
            $session->getToken(),
            $key
        ));
    }

    /**
     * Save stored list to the session.
     */
    protected function _saveStored()
    {
        global $session;

        $session->set(
            $this->_params['app'],
            $this->_params['storage_key'] . self::STORED_KEY,
            $this->_stored
        );
    }

}
