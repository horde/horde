<?php
/**
 * The Horde_Core_Auth_Ldap class provides Horde-specific code that
 * extends the base LDAP driver.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
class Horde_Core_Auth_Ldap extends Horde_Auth_Ldap
{
    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId      The user ID to add.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Auth_Exception
     */
    public function addUser($userId, $credentials)
    {
        list($userId, $credentials) = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create()->runHook($userId, $credentials, 'preauthenticate', 'admin');

        parent::addUser($userId, $credentials);
    }

    /**
     * Update a set of authentication credentials.
     *
     * @param string $oldID       The old user ID.
     * @param string $newID       The new user ID.
     * @param array $credentials  The new credentials
     *
     * @throws Horde_Auth_Exception
     */
    public function updateUser($oldID, $newID, $credentials)
    {
        $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();

        list($oldID, $old_credentials) = $auth->runHook($oldID, $credentials, 'preauthenticate', 'admin');
        if (isset($old_credentials['ldap'])) {
            list($newID, $new_credentials) = $auth->runHook($newID, $credentials, 'preauthenticate', 'admin');
            $olddn = $old_credentials['ldap']['dn'];
            $newdn = $new_credentials['ldap']['dn'];
        } else {
            $olddn = $newdn = null;
        }

        parent::updateUser($oldID, $newID, $credentials, $olddn, $newdn);
    }

    /**
     * Delete a set of authentication credentials.
     *
     * @param string $userId  The user ID to delete.
     *
     * @throws Horde_Auth_Exception
     */
    public function removeUser($userId)
    {
        list($userId, $credentials) = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create()->runHook($userId, array(), 'preauthenticate', 'admin');

        parent::removeUser($userId, isset($credentials['ldap']) ? $credentials['ldap']['dn'] : null);
    }

}
