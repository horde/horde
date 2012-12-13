<?php
/**
 * A Horde_Injector:: based Ingo_Transport factory.
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
 * A Horde_Injector:: based Ingo_Transport factory.
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
class Ingo_Factory_Transport extends Horde_Core_Factory_Base
{
    /**
     * Returns a Ingo_Transport instance.
     *
     * @param string $transport  A transport driver name.
     *
     * @return Ingo_Transport  The Ingo_Transport instance.
     * @throws Ingo_Exception
     */
    public function create($transport)
    {
        global $registry, $session;

        $transport = strlen($transport)
            ? basename($transport)
            : 'null';

        /* Get authentication parameters. */
        try {
            $auth = Horde::callHook('transport_auth', array($transport), 'ingo');
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

        /* Sieve configuration only. */
        if (!isset($auth['euser']) && ($transport == 'timsieved')) {
            $auth['euser'] = Ingo::getUser(false);
        }

        $class = 'Ingo_Transport_' . ucfirst($transport);
        if (class_exists($class)) {
            return new $class(array_merge(
                $session->get('ingo', 'backend/params', Horde_Session::TYPE_ARRAY),
                $auth
            ));
        }

        throw new Ingo_Exception(sprintf(_("Unable to load the transport driver \"%s\"."), $class));
    }

}
