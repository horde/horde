<?php
/**
 * A Horde_Injector based factory for IMP's configuration of Horde_Mail::
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */

/**
 * A Horde_Injector based factory for IMP's configuration of Horde_Mail::
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */
class IMP_Factory_Mail extends Horde_Core_Factory_Injector
{
    /**
     * Return the Horde_Mail instance.
     *
     * @return Horde_Mail  The singleton instance.
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $params = $GLOBALS['session']->get('imp', 'smtp', Horde_Session::TYPE_ARRAY);

        /* If SMTP authentication has been requested, use either the username
         * and password provided in the configuration or populate the username
         * and password fields based on the current values for the user. Note
         * that we assume that the username and password values from the
         * current IMAP / POP3 connection are valid for SMTP authentication as
         * well. */
        if (!empty($params['auth'])) {
            $imap_ob = $injector->getInstance('IMP_Factory_Imap')->create();
            if (empty($params['username'])) {
                $params['username'] = $imap_ob->getParam('username');
            }
            if (empty($params['password'])) {
                $params['password'] = $imap_ob->getParam('password');
            }
        }

        $class = $this->_getDriverName($GLOBALS['conf']['mailer']['type'], 'Horde_Mail_Transport');
        return new $class($params);
    }

}
