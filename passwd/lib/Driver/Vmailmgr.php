<?php
/**
 * The vmailmgr class attempts to change a user's password on a local vmailmgr
 * daemon
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * WARNING: This driver has only formally been converted to Horde 4. 
 * No testing has been done. If this doesn't work, please file bugs at
 * bugs.horde.org
 * If you really need this to work reliably, think about sponsoring development
 *
 * @author  Marco Kaiser <bate@php.net>
 * @since   Passwd 2.2
 * @package Passwd
 */
 class Passwd_Driver_Vmailmgr extends Passwd_Driver {

    /**
     * Change the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new password to set.
     *
     * @return boolean  True or false based on success of the change.
     */
    function changePassword($username, $old_password, $new_password)
    {
        if (is_readable($this->_params['vmailinc']) && isset($this->_params['vmailinc'])) {
            @include($this->_params['vmailinc']);
        } else {
            throw new Passwd_Exception('vmail.inc not found ! (' . $this->_params['vmailinc'] . ')');
        }

        $_splitted = explode('@', $username);
        $_username = $_splitted[0];
        $_domain = $_splitted[1];
        $_returnChange = vchpass($_domain, $old_password, $_username, $new_password);

        if ($_returnChange[0]) {
            throw new Passwd_Exception(_("Incorrect old password."));
        }

        return true;
    }

}
