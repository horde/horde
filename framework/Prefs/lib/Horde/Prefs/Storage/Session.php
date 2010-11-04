<?php
/**
 * Session storage driver for the preferences system.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Prefs
 */
class Horde_Prefs_Storage_Session extends Horde_Prefs_Storage
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

        $this->_key = 'horde_prefs_' . $this->_params['user'];
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
    public function store($prefs)
    {
        foreach ($prefs as $scope => $vals) {
            $_SESSION[$this->_key][$scope] = isset($_SESSION[$this->_key][$scope])
                ? array_merge($_SESSION[$this->_key][$scope], $vals)
                : array();
        }
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        if (is_null($scope)) {
            unset($_SESSION[$this->_key]);
        } elseif (is_null($pref)) {
            unset($_SESSION[$this->_key][$scope]);
        } else {
            unset($_SESSION[$this->_key][$scope][$pref]);
        }
    }

}
