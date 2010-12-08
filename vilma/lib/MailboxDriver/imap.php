<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Vilma
 */
class Vilma_MailboxDriver_imap extends Vilma_MailboxDriver
{
    protected $_imap = null;

    protected function _connect()
    {
        if ($this->_imap) {
            return;
        }
        $params = array('username' => $this->_params['admin_user'],
                        'password' => $this->_params['admin_password'],
                        'hostspec' => $this->_params['hostspec'],
                        'port'     => $this->_params['port']);
        $this->_imap = Horde_Imap_Client::factory('Socket', $params);
    }

    public function checkMailbox($user, $domain)
    {
        $this->_connect();
        if (!$this->_imap->listMailboxes($this->_params['userhierarchy'] . $user . '@' . $domain)) {
            throw new Vilma_Exception(sprintf(_("Mailbox \"%s\" does not exist."), $user . '@' . $domain));
        }
    }

    public function createMailbox($user, $domain)
    {
        $this->_connect();
        $this->_imap->createMailbox($this->_params['userhierarchy'] . $user . '@' . $domain);
    }

    public function deleteMailbox($user, $domain)
    {
        $this->_connect();
        $this->_imap->deleteMailbox($this->_params['userhierarchy'] . $user . '@' . $domain);
    }

}
