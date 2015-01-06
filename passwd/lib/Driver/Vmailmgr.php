<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * Changes a password on a local vmailmgr daemon.
 *
 * @author    Marco Kaiser <bate@php.net>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Vmailmgr extends Passwd_Driver
{
    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        if (isset($this->_params['vmailinc']) &&
            is_readable($this->_params['vmailinc'])) {
            include $this->_params['vmailinc'];
        } else {
            throw new Passwd_Exception('vmail.inc not found! (' . $this->_params['vmailinc'] . ')');
        }

        list($user, $domain) = explode('@', $user);
        $res = vchpass($domain, $oldpass, $user, $newpass);

        if ($res[0]) {
            throw new Passwd_Exception(_("Incorrect old password."));
        }
    }
}
