<?php
/**
 * A Horde_Injector based factory for IMP's configuration of Horde_Mail::
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */

/**
 * A Horde_Injector based factory for IMP's configuration of Horde_Mail::
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */
class IMP_Injector_Factory_Mail
{
    /**
     * Return the Horde_Mail instance.
     *
     * @return Horde_Mail  The singleton instance.
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        /* We don't actually want to alter the contents of the $conf['mailer']
         * array, so we make a copy of the current settings. We will apply our
         * modifications (if any) to the copy, instead. */
        $params = $GLOBALS['conf']['mailer']['params'];

        /* Force the SMTP host and port value to the current SMTP server if
         * one has been selected for this connection. */
        $params = array_merge($params, $GLOBALS['session']->get('imp', 'smtp', Horde_Session::TYPE_ARRAY));

        /* If SMTP authentication has been requested, use either the username
         * and password provided in the configuration or populate the username
         * and password fields based on the current values for the user. Note
         * that we assume that the username and password values from the
         * current IMAP / POP3 connection are valid for SMTP authentication as
         * well. */
        if (!empty($params['auth']) && empty($params['username'])) {
            $imap_ob = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create();
            $params['username'] = $imap_ob->getParam('username');
            $params['password'] = $imap_ob->getParam('password');
        }

        $transport = $GLOBALS['conf']['mailer']['type'];
        $class = 'Horde_Mail_Transport_' . ucfirst($transport);
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Unable to find class for transport ' . $transport);
    }

}
