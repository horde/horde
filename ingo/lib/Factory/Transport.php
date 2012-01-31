<?php
/**
 * A Horde_Injector:: based Ingo_Transport:: factory.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Ingo
 * @package  Ingo
 */

/**
 * A Horde_Injector:: based Ingo_Transport:: factory.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Ingo
 * @package  Ingo
 */
class Ingo_Factory_Transport extends Horde_Core_Factory_Injector
{
    /**
     * Return the Ingo_Transport instance.
     *
     * @return Ingo_Transport  The singleton instance.
     *
     * @throws Ingo_Exception
     */
    public function create(Horde_Injector $injector)
    {
        global $registry, $session;

        $params = $session->get('ingo', 'backend/params');

        // Set authentication parameters.
        if (($hordeauth = $session->get('ingo', 'backend/hordeauth')) ||
            !isset($params['username']) ||
            !isset($params['password'])) {
            $params['password'] = $registry->getAuthCredential('password');
            $params['username'] = $registry->getAuth(($hordeauth === 'full') ? null : 'bare');
        }

        $class = 'Ingo_Transport_' . ucfirst(basename($session->get('ingo', 'backend/transport')));
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Ingo_Exception(sprintf(_("Unable to load the transport driver \"%s.\""), $class));
    }

}
