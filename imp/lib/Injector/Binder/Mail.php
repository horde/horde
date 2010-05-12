<?php
/**
 * Binder for IMP's configuration of Horde_Mail::.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Injector_Binder_Mail implements Horde_Injector_Binder
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        /* We don't actually want to alter the contents of the $conf['mailer']
         * array, so we make a copy of the current settings. We will apply our
         * modifications (if any) to the copy, instead. */
        $params = $GLOBALS['conf']['mailer']['params'];

        /* Force the SMTP host and port value to the current SMTP server if
         * one has been selected for this connection. */
        if (!empty($_SESSION['imp']['smtp'])) {
            $params = array_merge($params, $_SESSION['imp']['smtp']);
        }

        /* If SMTP authentication has been requested, use either the username
         * and password provided in the configuration or populate the username
         * and password fields based on the current values for the user. Note
         * that we assume that the username and password values from the
         * current IMAP / POP3 connection are valid for SMTP authentication as
         * well. */
        if (!empty($params['auth']) && empty($params['username'])) {
            $imap_ob = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();
            $params['username'] = $imap_ob->getParam('username');
            $params['password'] = $imap_ob->getParam('password');
        }

        return Horde_Mail::factory($GLOBALS['conf']['mailer']['type'], $params);
    }

    /**
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
