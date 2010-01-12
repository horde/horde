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
class Vilma_MailboxDriver_null extends Vilma_MailboxDriver {

    public function checkMailbox($user, $domain)
    {
        return true;
    }

    public function createMailbox($user, $domain)
    {
        return true;
    }

    public function deleteMailbox($user, $domain)
    {
        return true;
    }

}
