<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Cache storage implementation using Horde_Session.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010-2014 Horde LLC
 * @deprecated Use Horde_Prefs_Cache_HordeCache with the
 *             Horde_Core_Cache_Session driver instead.
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Core
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
