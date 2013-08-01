<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based Horde_Auth_Imap factory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_AuthImap extends Horde_Core_Factory_Injector
{
    /**
     * Return the Horde_Auth_Imap instance that uses IMP configuration.
     *
     * @return Horde_Auth_Imap  The singleton instance.
     * @throws IMP_Exception
     */
    public function create(Horde_Injector $injector)
    {
        global $injector, $registry;

        $admin = $injector->getInstance('IMP_Imap')->config->admin;
        if (!$admin) {
            throw new IMP_Exception('Admin access not enabled.');
        }

        $params = $registry->callByPackage('imp', 'server');
        if (is_null($params)) {
            throw new IMP_Exception('No server parameters found.');
        }

        $params_map = array(
            'password' => 'admin_password',
            'user' => 'admin_user',
            'userhierarchy' => 'userhierarchy'
        );

        foreach ($admin as $key => $val) {
            if (isset($params_map[$key])) {
                $params[$params_map[$key]] = $val;
            }
        }

        $params['default_user'] = $registry->getAuth();
        $params['logger'] = $injector->getInstance('Horde_Log_Logger');

        return Horde_Auth::factory('Imap', $params);
    }

}
