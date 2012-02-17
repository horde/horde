<?php
/**
 * A Horde_Injector based factory for the IMP_Quota object.
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
 * A Horde_Injector based factory for the IMP_Quota object.
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
class IMP_Factory_Quota extends Horde_Core_Factory_Injector
{
    /**
     * Return the IMP_Quota instance.
     *
     * @return IMP_Quota  The singleton instance.
     * @throws IMP_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $qparams = $GLOBALS['session']->get('imp', 'imap_quota');

        if (!isset($qparams['driver'])) {
            throw new IMP_Exception('Quota config missing driver parameter.');
        }
        $driver = $qparams['driver'];
        $params = isset($qparams['params'])
            ? $qparams['params']
            : array();

        /* If 'password' exists in params, it has been encrypted in the
         * session so we need to decrypt. */
        if (isset($params['password'])) {
            $secret = $injector->getInstance('Horde_Secret');
            $params['password'] = $secret->read($secret->getKey('imp'), $params['password']);
        }

        $imap_ob = $injector->getInstance('IMP_Factory_Imap')->create();

        switch (Horde_String::lower($driver)) {
        case 'imap':
            $params['imap_ob'] = $imap_ob;
            $params['mbox'] = IMP::$mailbox->search
                ? 'INBOX'
                : IMP::$mailbox;
            break;

        case 'sql':
            $params['db'] = $injector->getInstance('Horde_Core_Factory_Db')->create('imp', $params);
            break;
        }

        $params['username'] = $imap_ob->getParam('username');

        return IMP_Quota::factory($driver, $params);
    }

}
