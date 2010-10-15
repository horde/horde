<?php
/**
 * Session storage cache driver (using Horde_Session) for the preferences
 * system.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Prefs_Cache_Session extends Horde_Prefs_Cache
{
    /**
     * Session key.
     *
     * @var string
     */
    protected $_key;

    /**
     */
    public function __construct($user)
    {
        parent::__construct($user);

        $this->_key = 'horde:prefs_' . $this->_user . '/';
    }

    /**
     */
    public function get($scope)
    {
        global $session;

        return isset($session[$this->_key . $scope])
            ? $session[$this->_key . $scope]
            : false;
    }

    /**
     */
    public function update($scope, $prefs)
    {
        if (($cached = $this->get($scope)) === false) {
            $cached = array();
        }
        $cached = array_merge($cached, $prefs);
        $GLOBALS['session'][$this->_key . $scope] = $cached;
    }

    /**
     */
    public function clear($scope = null, $pref = null)
    {
        global $session;

        if (is_null($scope)) {
            unset($session[$this->_key]);
        } elseif (is_null($pref)) {
            unset($session[$this->_key . $scope]);
        } elseif ((($cached = $this->get($scope)) !== false) &&
                  isset($cached[$pref])) {
            unset($cached[$pref]);
            $session[$this->_key . $scope] = $cached;
        }
    }

}
