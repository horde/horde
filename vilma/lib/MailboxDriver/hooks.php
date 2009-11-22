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

class Vilma_MailboxDriver_hooks extends Vilma_MailboxDriver {

    function Vilma_MailboxDriver_hooks($params)
    {
        Horde::loadConfiguration('hooks.php', null, 'vilma');
    }


    function checkMailbox($user, $domain)
    {
        if (function_exists('_vilma_hook_checkMailbox')) {
            return call_user_func('_vilma_hook_checkMailbox', $user, $domain);
        } else {
            return true;
        }
    }

    function createMailbox($user, $domain)
    {
        if (function_exists('_vilma_hook_createMailbox')) {
            return call_user_func('_vilma_hook_createMailbox', $user, $domain);
        } else {
            return true;
        }
    }

    function deleteMailbox($user, $domain)
    {
        if (function_exists('_vilma_hook_deleteMailbox')) {
            return call_user_func('_vilma_hook_deleteMailbox', $user, $domain);
        } else {
            return true;
        }
    }

}
