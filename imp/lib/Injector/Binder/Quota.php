<?php
/**
 * Binder for IMP_Quota::.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Injector_Binder_Quota implements Horde_Injector_Binder
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        $driver = $_SESSION['imp']['imap']['quota']['driver'];
        $params = isset($_SESSION['imp']['imap']['quota']['params'])
            ? $_SESSION['imp']['imap']['quota']['params']
            : array();

        /* If 'password' exists in params, it has been encrypted in the
         * session so we need to decrypt. */
        if (isset($params['password'])) {
            $secret = $injector->getInstance('Horde_Secret');
            $params['password'] = $secret->read($secret->getKey('imp'), $params['password']);
        }

        return IMP_Quota::factory($driver, $params);
    }

    /**
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
