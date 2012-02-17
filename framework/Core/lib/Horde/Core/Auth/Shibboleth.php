<?php
/**
 * The Horde_Core_Auth_Shibboleth class provides Horde-specific code that
 * extends the base Shibboleth driver.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
class Horde_Core_Auth_Shibboleth extends Horde_Auth_Shibboleth
{
    /**
     * Checks for triggers that may invalidate the current auth.
     * These triggers are independent of the credentials.
     *
     * @return boolean  True if the results of authenticate() are still valid.
     */
    public function validateAuth()
    {
        if (!empty($_SERVER[$this->getParam('username_header')]) &&
            ($this->_removeScope($_SERVER[$this->getParam('username_header')]) == $GLOBALS['registry']->getAuth('original'))) {
            return true;
        }

        // Consider this a session expiration.
        $this->setError(Horde_Auth::REASON_SESSION);

        Horde::logMessage('Shibboleth authentication expired for user ' . $GLOBALS['registry']->getAuth(), 'INFO');

        return false;
    }

}
