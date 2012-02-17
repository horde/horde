<?php
/**
 * Session cache implementation for the preferences system.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Prefs
 */
class Horde_Prefs_Cache_Session extends Horde_Prefs_Cache_Base
{
    /**
     * Session key.
     *
     * @var string
     */
    protected $_key;

    /**
     */
    public function __construct($user, array $params = array())
    {
        parent::__construct($user, $params);

        $this->_key = 'horde_prefs_cache_' . $this->_params['user'];
    }

    /**
     */
    public function get($scope)
    {
        return isset($_SESSION[$this->_key][$scope])
            ? $_SESSION[$this->_key][$scope]
            : false;
    }

    /**
     */
    public function store($scope_ob)
    {
        $_SESSION[$this->_key][$scope_ob->getScope()] = $scope_ob;
    }

    /**
     */
    public function remove($scope = null)
    {
        if (is_null($scope)) {
            unset($_SESSION[$this->_key]);
        } else {
            unset($_SESSION[$this->_key][$scope]);
        }
    }

}
