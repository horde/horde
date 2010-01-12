<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Vilma
 */
class Vilma_MailboxDriver_imap extends Vilma_MailboxDriver {

    var $_imapAdmin = null;

    function _connect()
    {
        if (!is_null($this->_imapAdmin)) {
            return false;
        }

        // Catch c-client errors.
        register_shutdown_function('imap_errors');
        register_shutdown_function('imap_alerts');

        require_once 'Horde/IMAP/Admin.php';
        $admin = &new IMAP_Admin($this->_params);
        if (is_a($admin, 'PEAR_Error')) {
            return $admin;
        }

        $this->_imapAdmin = $admin;
        return true;
    }

    function checkMailbox($user, $domain)
    {
        $res = $this->_connect();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        if (!$this->_imapAdmin->mailboxExists($user . '@' . $domain)) {
            throw new Vilma_Exception(sprintf(_("Mailbox '%s@%s' does not exist."), $user, $domain));
        }
    }

    function createMailbox($user, $domain)
    {
        $res = $this->_connect();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        $mbox = $user . '@' . $domain;

        $res = $this->_imapAdmin->addMailbox($mbox);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        return true;
    }

    function deleteMailbox($user, $domain)
    {
        $res = $this->_connect();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        $res = $this->_imapAdmin->removeMailbox($user . '@' . $domain);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        return true;
    }

}
