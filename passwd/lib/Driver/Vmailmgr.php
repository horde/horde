<?php
/**
 * The vmailmgr class attempts to change a user's password on a local vmailmgr
 * daemon
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Marco Kaiser <bate@php.net>
 * @package Passwd
 */
class Passwd_Driver_Vmailmgr extends Passwd_Driver
{
    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($username, $old_password, $new_password)
    {
        if (isset($this->_params['vmailinc']) &&
            is_readable($this->_params['vmailinc'])) {
            include $this->_params['vmailinc'];
        } else {
            throw new Passwd_Exception('vmail.inc not found! (' . $this->_params['vmailinc'] . ')');
        }

        list($username, $domain) = explode('@', $username);
        $returnChange = vchpass($domain, $old_password, $username, $new_password);

        if ($returnChange[0]) {
            throw new Passwd_Exception(_("Incorrect old password."));
        }
    }
}
