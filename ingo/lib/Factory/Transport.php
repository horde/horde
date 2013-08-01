<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Ingo
 * @package  Ingo
 */

/**
 * A Horde_Injector based Ingo_Transport factory.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @link     http://pear.horde.org/index.php?package=Ingo
 * @package  Ingo
 */
class Ingo_Factory_Transport extends Horde_Core_Factory_Base
{
    /**
     * Returns a Ingo_Transport instance.
     *
     * @param array $transport  A transport driver name and parameter hash.
     *
     * @return Ingo_Transport  The Ingo_Transport instance.
     * @throws Ingo_Exception
     */
    public function create(array $transport)
    {
        global $registry, $session;

        /* Get authentication parameters. */
        try {
            $auth = Horde::callHook('transport_auth', array($transport['driver']), 'ingo');
        } catch (Horde_Exception_HookNotSet $e) {
            $auth = null;
        }

        if (!is_array($auth)) {
            $auth = array();
        }

        if (!isset($auth['password'])) {
            $auth['password'] = $registry->getAuthCredential('password');
        }
        if (!isset($auth['username'])) {
            $auth['username'] = $registry->getAuth('bare');
        }
        if (!isset($auth['euser'])) {
            $auth['euser'] = Ingo::getUser(false);
        }

        $class = 'Ingo_Transport_' . ucfirst($transport['driver']);
        if (class_exists($class)) {
            return new $class(array_merge($auth, $transport['params']));
        }

        throw new Ingo_Exception(sprintf(_("Unable to load the transport driver \"%s\"."), $class));
    }

}
