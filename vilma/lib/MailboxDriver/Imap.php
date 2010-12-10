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
    /**
     * An IMAP client.
     *
     * @var Horde_Imap_Client_Base
     */
    protected $_imap;

    /**
     * Constructor.
     *
     * @param array $params  Any parameters needed for this driver.
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);
        $params = array('username' => $this->_params['admin_user'],
                        'password' => $this->_params['admin_password'],
                        'hostspec' => $this->_params['hostspec'],
                        'port'     => $this->_params['port']);
        $this->_imap = Horde_Imap_Client::factory('Socket', $params);
    }

    /**
     * Creates a new mailbox.
     *
     * @param string $user    The name of the mailbox to create.
     * @param string $domain  The name of the domain in which to create the
     *                        mailbox.
     *
     * @throws Vilma_Exception
     */
    public function createMailbox($user, $domain)
    {
        try {
            $this->_imap->createMailbox($this->_params['userhierarchy'] . $user . '@' . $domain);
        } catch (Exception $e) {
            throw new Vilma_Exception($e);
        }
    }

    /**
     * Deletes an existing mailbox.
     *
     * @todo
     *
     * @param string $user    The name of the mailbox to delete.
     * @param string $domain  The name of the domain in which to delete the
     *                        mailbox.
     *
     * @throws Vilma_Exception
     */
    public function deleteMailbox($user, $domain)
    {
        try {
            $this->_imap->deleteMailbox($this->_params['userhierarchy'] . $user . '@' . $domain);
        } catch (Exception $e) {
            throw new Vilma_Exception($e);
        }
    }

    /**
     * Checks whether a mailbox exists and is set up properly.
     *
     * @param string $user    The name of the mailbox to check.
     * @param string $domain  The mailbox' domain.
     *
     * @return boolean  True if the mailbox exists.
     * @throws Vilma_Exception if the mailbox doesn't exist.
     */
    public function checkMailbox($user, $domain)
    {
        if (!$this->_imap->listMailboxes($this->_params['userhierarchy'] . $user . '@' . $domain)) {
            throw new Vilma_Exception(sprintf(_("Mailbox \"%s\" does not exist."), $user . '@' . $domain));
        }
        return true;
    }
}
