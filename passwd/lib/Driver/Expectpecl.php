<?php
/**
 * Copyright 2006-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2006-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * A PECL expect implementation of the Passwd system.
 *
 * Copyright 2006-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Duck <duck@obala.net>
 * @category  Horde
 * @copyright 2006-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Expectpecl extends Passwd_Driver
{
    /**
     * Expect connection handle.
     *
     * @var resource
     */
    protected $_stream;

    /**
     * Handles expect communication.
     *
     * @param string $expect  String to expect.
     * @param string $error   Error message.
     *
     * @throws Passwd_Exception
     */
    protected function _ctl($expect, $error)
    {
        $result = expect_expectl($this->_stream, array(
            array(
                0 => $expect,
                1 => 'ok',
                2 => EXP_REGEXP
            )
        ));

        switch ($result) {
        case EXP_EOF:
            throw new Passwd_Exception(_("End of file."));

        case EXP_TIMEOUT:
            throw new Passwd_Exception(_("Time out."));

        case EXP_FULLBUFFER:
            throw new Passwd_Exception(_("Full buffer."));

        case 'ok':
            return;

        default:
            throw new Passwd_Exception($error);
        }
    }

    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        if (!Horde_Util::loadExtension('expect')) {
            throw new Passwd_Exception(_("expect extension cannot be loaded"));
        }

        // Set up parameters
        foreach (array('logfile', 'loguser', 'timeout') as $val) {
            if (isset($this->_params[$val])) {
                ini_set('expect.' . $val, $this->_params[$val]);
            }
        }

        // Open connection
        $call = sprintf(
            'ssh %s@%s %s',
            $user,
            $this->_params['host'],
            $this->_params['program']
        );
        if (!($this->_stream = expect_popen($call))) {
            throw new Passwd_Exception(_("Unable to open expect stream"));
        }

        // Log in
        $this->_ctl(
            '(P|p)assword.*',
            _("Could not login to system (no password prompt)")
        );

        // Send login password
        fwrite($this->_stream, $oldpass . "\n");

        // Expect old password prompt
        $this->_ctl(
            '((O|o)ld|login|current).* (P|p)assword.*',
            _("Could not start passwd program (no old password prompt)")
        );

        // Send old password
        fwrite($this->_stream, $oldpass . "\n");

        // Expect new password prompt
        $this->_ctl(
            '(N|n)ew.* (P|p)assword.*',
            _("Could not change password (bad old password?)")
        );

        // Send new password
        fwrite($this->_stream, $newpass . "\n");

        // Expect reenter password prompt
        $this->_ctl(
            "((R|r)e-*enter.*(P|p)assword|Retype new( UNIX)? password|(V|v)erification|(V|v)erify|(A|a)gain).*",
            _("New password not valid (too short, bad password, too similar, ...)")
        );

        // Send new password
        fwrite($this->_stream, $newpass . "\n");

        // Expect successfully message
        $this->_ctl(
            '((P|p)assword.* changed|successfully)',
            _("Could not change password.")
        );
    }

}
