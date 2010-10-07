<?php
/**
 * A Horde_Injector based Horde_Auth_Imap:: factory.
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
 * A Horde_Injector based Horde_Auth_Imap:: factory.
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
class IMP_Injector_Factory_AuthImap
{
    /**
     * Return the Horde_Auth_Imap:: instance that uses IMP configuration.
     *
     * @return Horde_Auth_Imap  The singleton instance.
     * @throws IMP_Exception
     */
    public function create(Horde_Injector $injector)
    {
        $params = $GLOBALS['registry']->callByPackage('imp', 'server');
        if (is_null($params)) {
            throw new IMP_Exception('No server parameters found.');
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

}
