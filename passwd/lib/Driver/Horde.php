<?php
/**
 * The Horde driver attempts to change a user's password without
 * caring about the actual implementation. It relies on the current horde
 * authentication mechanism's ability to update the user
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Ralf Lang <lang@b1-systems.de>
 * @package Passwd
 */
class Passwd_Driver_Horde extends Passwd_Driver {

    /**
     * Change the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return boolean  True or false based on success of the change.
     */
    public function changePassword($username,  $old_password, $new_password)
    {

        $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();

        if (!$auth->hasCapability('update')) {
            throw new Passwd_Exception(_("The current horde configuration does not allow changing passwords."));
        }

        /* Check the provided old password. */
        if ($auth->authenticate($username, array('password' => $old_password, false))) {
            /* Actually modify the password. */
            return $auth->updateUser($username, $username, array('password' => $new_password));
        } else {
            throw new Passwd_Exception(_("Incorrect old password."));
        }

    }

}
