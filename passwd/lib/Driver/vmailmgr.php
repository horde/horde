<?php
/**
 * The vmailmgr class attempts to change a user's password on a local vmailmgr
 * daemon
 *
 * $Horde: passwd/lib/Driver/vmailmgr.php,v 1.11.2.7 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Marco Kaiser <bate@php.net>
 * @since   Passwd 2.2
 * @package Passwd
 */
 class Passwd_Driver_vmailmgr extends Passwd_Driver {

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
            return PEAR::raiseError('vmail.inc not found ! (' . $this->_params['vmailinc'] . ')');
        }

        $_splitted = explode('@', $username);
        $_username = $_splitted[0];
        $_domain = $_splitted[1];
        $_returnChange = vchpass($_domain, $old_password, $_username, $new_password);

        if ($_returnChange[0]) {
            return PEAR::raiseError(_("Incorrect old password."));
        }

        return true;
    }

}
