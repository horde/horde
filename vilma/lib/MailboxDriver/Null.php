<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  David Cummings <davidcummings@acm.org>
 * @package Vilma
 */
class Vilma_MailboxDriver_Null extends Vilma_MailboxDriver
{
    /**
     * Creates a new mailbox.
     *
     * @param string $user    The name of the mailbox to create.
     * @param string $domain  The name of the domain in which to create the
     *                        mailbox.
     */
    public function createMailbox($user, $domain)
    {
    }

    /**
     * Deletes an existing mailbox.
     *
     * @param string $user    The name of the mailbox to delete.
     * @param string $domain  The name of the domain in which to delete the
     *                        mailbox.
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
     */
    public function checkMailbox($user, $domain)
    {
        return true;
    }
}
