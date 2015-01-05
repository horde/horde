<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * The Horde driver attempts to change a user's password without
 * caring about the actual implementation.
 *
 * It relies on the current horde authentication mechanism's ability to update
 * the user.
 *
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Horde extends Passwd_Driver
{
    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        $auth = $this->_params['auth'];

        if (!$auth->hasCapability('update')) {
            throw new Passwd_Exception(_("The current horde configuration does not allow changing passwords."));
        }

        /* Check the provided old password. */
        try {
            if ($auth->authenticate($user, array('password' => $oldpass, false))) {
                /* Actually modify the password. */
                $auth->updateUser($user, $user, array(
                    'password' => $newpass
                ));
            } else {
                throw new Passwd_Exception(_("Incorrect old password."));
            }
        } catch (Horde_Auth_Exception $e) {
            throw new Passwd_Exception($e);
        }
    }

}
