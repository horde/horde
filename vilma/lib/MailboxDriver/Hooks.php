<?php
/**
 * Copyright 2006-2007 Alkaloid Networks <http://www.alkaloid.net/>
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  Ben Klang <bklang@alkaloid.net>
 * @package Vilma
 */

class Vilma_MailboxDriver_Hooks extends Vilma_MailboxDriver
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
        try {
            return Horde::callHook('createMailbox', array($user, $domain), 'vilma');
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
            return Horde::callHook('deleteMailbox', array($user, $domain), 'vilma');
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
        try {
            return Horde::callHook('checkMailbox', array($user, $domain), 'vilma');
        } catch (Exception $e) {
            throw new Vilma_Exception($e);
        }
    }
}
