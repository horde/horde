<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */

/**
 * Extends Horde's authentication backend for Sabre to support username hooks.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Dav_Auth extends Horde_Dav_Auth
{
    /**
     * Returns information about the currently logged in username.
     *
     * If nobody is currently logged in, this method should return null.
     *
     * @return string|null
     */
    public function getCurrentUser()
    {
        $user = $this->_auth->getCredential('userId');
        try {
            $user = $GLOBALS['injector']->getInstance('Horde_Core_Hooks')
                ->callHook('davusername', 'horde', array($user, false));
        } catch (Horde_Exception_HookNotSet $e) {
        }
        return $user;
    }
}
