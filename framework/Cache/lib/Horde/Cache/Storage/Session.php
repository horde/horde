<?php
/**
 * This class provides cache storage in a PHP session.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Cache
 */
class Horde_Cache_Storage_Session extends Horde_Cache_Storage_Base
{
    /**
     * Pointer to the session entry.
     *
     * @var array
     */
    protected $_sess;

    /**
     * Constructor.
     *
     * @param array $params  Optional parameters:
     * <pre>
     * 'session' - (string) Store session data in this entry.
     *             DEFAULT: 'horde_cache_session'
     * </pre>
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array(
            'sess_name' => 'horde_cache_session'
        ), $params);

        parent::__construct($params);

        if (!isset($_SESSION[$this->_params['sess_name']])) {
            $_SESSION[$this->_params['sess_name']] = array();
        }
        $this->_sess = &$_SESSION[$this->_params['sess_name']];
    }

    /**
     */
    public function get($key, $lifetime = 0)
    {
        return $this->exists($key, $lifetime)
            ? $this->_sess[$key]['d']
            : false;
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
        $this->_sess[$key] = array(
            'd' => $data,
            'l' => $lifetime
        );
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        if (isset($this->_sess[$key])) {
            /* 0 means no expire. */
            if (($lifetime == 0) ||
                ((time() - $lifetime) <= $this->_sess[$key]['l'])) {
                return true;
            }

            unset($this->_sess[$key]);
        }

        return false;
    }

    /**
     */
    public function expire($key)
    {
        if (isset($this->_sess[$key])) {
            unset($this->_sess[$key]);
            return true;
        }

        return false;
    }

}
