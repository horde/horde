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
class Vilma_MailboxDriver_Maildrop extends Vilma_MailboxDriver
{
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
        if (empty($this->_params['system_user'])) {
            throw new Vilma_Exception(_("No 'system_user' parameter specified to maildrop driver."));
        }

        $shell = sprintf('sudo -u %s maildirmake %s',
                         escapeshellarg($this->_params['system_user']),
                         escapeshellarg($this->_getMailboxDir($user, $domain)));
        exec($shell);
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
    }

    /**
     * Checks whether a mailbox exists and is set up properly.
     *
     * @param string $user    The name of the mailbox to check.
     * @param string $domain  The mailbox' domain.
     *
     * @return boolean  True if the mailbox exists.
     * @throws Vilma_Exception if the mailbox doesn't exist or a parameter is
     *                         missing
     */
    public function checkMailbox($user, $domain)
    {
        $dir = $this->_getMailboxDir($user, $domain);

        if (!is_dir($dir)) {
            throw new Vilma_Exception(sprintf(_("Maildrop directory \"%s\" does not exist."), $dir));
        }

        return true;
    }

    /**
     * @throws Vilma_Exception
     */
    protected function _getMailboxDir($user, $domain)
    {
        if (empty($this->_params['mail_dir_base'])) {
            throw new Vilma_Exception(_("No 'mail_dir_base' parameter specified to maildrop driver."));
        }

        $dir = $this->_params['mail_dir_base'];
        if (!empty($this->_params['usedomain'])) {
            $dir .= '/' . $domain;
        }

        return $dir . '/' . $user;
    }
}
