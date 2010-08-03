<?php
/**
 * Binder for Horde_Auth_Imap:: that uses IMP configuration.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Injector_Binder_AuthImap implements Horde_Injector_Binder
{
    /**
     * @throws IMP_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $params = $GLOBALS['registry']->callByPackage('imp', 'server');
        if (is_null($params)) {
            throw new IMP_Exception('No mail parameters found.');
        }

        $params = array_merge(
            $params,
            $_SESSION['imp']['imap']['admin']['params'],
            array(
                'default_user' => $GLOBALS['registry']->getAuth(),
                'logger' => $injector->getInstance('Horde_Log_Logger')
            )
        );

        if (isset($params['admin_password'])) {
            $secret = $injector->getInstance('Horde_Secret');
            $params['admin_password'] = $secret->read($secret->getKey('imp'), $params['admin_password']);
        }

        return Horde_Auth::factory('imap', $params);
    }

    /**
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
