<?php
/**
 * Cache storage implementation using Horde_Session.
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
class Horde_Core_Prefs_Cache_Session extends Horde_Prefs_Cache_Base
{
    const SESS_KEY = 'prefs_cache/';

    /**
     */
    public function get($scope)
    {
        global $session;

        return $session->exists('horde', self::SESS_KEY . $this->_params['user'] . '/' . $scope)
            ? $session->get('horde', self::SESS_KEY . $this->_params['user'] . '/' . $scope)
            : false;
    }

    /**
     */
    public function store($scope_ob)
    {
        $GLOBALS['session']->set('horde', self::SESS_KEY . $this->_params['user'] . '/' . $scope_ob->scope, $scope_ob);
    }

    /**
     */
    public function remove($scope = null)
    {
        $GLOBALS['session']->remove('horde', self::SESS_KEY . $this->_params['user'] . '/' . strval($scope));
    }

}
