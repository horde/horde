<?php
/**
 * The Passwd_expectpecl class provides an PECL expect implementation of the
 * Passwd system.
 *
 * $Horde: passwd/lib/Driver/expectpecl.php,v 1.5.2.1 2008/10/09 17:12:04 jan Exp $
 *
 * Copyright 2006-2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Duck <duck@obala.net>
 * @since   Passwd 3.1
 * @package Passwd
 */
class Passwd_Driver_expectpecl extends Passwd_Driver {

    /**
     * Expect connection handle.
     *
     * @var resource
     */
    var $_stream;

    /**
     * Handles expect communication.
     *
     * @param string $expect  String to expect
     * @param string $error   Error message
     *
     * @return boolean|PEAR_Error  True on success, PEAR_Error on failure
     */
    function ctl($expect, $error)
    {
        $cases = array(array(0 => $expect,
                             1 => 'ok',
                             2 => EXP_REGEXP));

        $result = expect_expectl($this->_stream, $cases);

        switch ($result) {
        case EXP_EOF:
            return PEAR::raiseError(_("End of file."));
            break;
        case EXP_TIMEOUT:
            return PEAR::raiseError(_("Time out."));    
            break;
        case EXP_FULLBUFFER:
            return PEAR::raiseError(_("Full buffer."));
            break;
        case 'ok':
            return true;
            break;
        default:
            return PEAR::raiseError($error);
            break;
        }
    }

    /**
     * Changes the users password by executing an expect script.
     *
     * @param string $user          User ID.
     * @param string $old_password  Old password.
     * @param string $new_password  New password.
     *
     * @return boolean  True on success, false or error message on error.
     */
    function changePassword($user, $old_password, $new_password)
    {
        if (!Util::loadExtension('expect')) {
            return PEAR::raiseError(sprintf(_("%s extension cannot be loaded!"), 'expect'));
        }

        // Set up parameters
        if (isset($this->_params['timeout'])) {
            ini_set('expect.timeout', $this->_params['timeout']);
        }
        if (isset($this->_params['loguser'])) {
            ini_set('expect.loguser', $this->_params['loguser']);
        }
        if (isset($this->_params['logfile'])) {
            ini_set('expect.logfile', $this->_params['logfile']);
        }

        // Open connection
        $call = sprintf('ssh %s@%s %s',
                        $user,
                        $this->_params['host'],
                        $this->_params['program']);
        if (!($this->_stream = expect_popen($call))) {
            return PEAR::raiseError(_("Unable to open expect stream!"));
        }

        // Log in
        $result = $this->ctl('(P|p)assword.*',
                             _("Could not login to system (no password prompt)"));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Send login password
        fwrite($this->_stream, "$old_password\n");

        // Expect old password prompt
        $result = $this->ctl('((O|o)ld|login|current).* (P|p)assword.*',
                             _("Could not start passwd program (no old password prompt)"));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Send old password
        fwrite($this->_stream, "$old_password\n");

        // Expect new password prompt
        $result = $this->ctl('(N|n)ew.* (P|p)assword.*',
                             _("Could not change password (bad old password?)"));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Send new password
        fwrite($this->_stream, "$new_password\n");

        // Expect reenter password prompt
        $result = $this->ctl("((R|r)e-*enter.*(P|p)assword|Retype new( UNIX)? password|(V|v)erification|(V|v)erify|(A|a)gain).*",
                           _("New password not valid (too short, bad password, too similar, ...)"));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Send new password
        fwrite($this->_stream, "$new_password\n");

        // Expect successfully message
        $result = $this->ctl('((P|p)assword.* changed|successfully)',
                             _("Could not change password."));
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return $result;
    }
}
