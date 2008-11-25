<?php
/**
 * The Auth_imp:: class provides an IMP implementation of the Horde
 * authentication system.
 *
 * Required parameters: NONE
 * Optional parameters: NONE
 *
 * Copyright 2003-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Auth
 */
class Auth_imp extends Auth
{
    /**
     * Find out if a set of login credentials are valid, and if
     * requested, mark the user as logged in in the current session.
     *
     * @param string $userID      The userID to check.
     * @param array $credentials  The credentials to check.
     * @param boolean $login      Whether to log the user in. If false, we'll
     *                            only test the credentials and won't modify
     *                            the current session.
     *
     * @return boolean  Whether or not the credentials are valid.
     */
    public function authenticate($userID = null, $credentials = array(),
                                 $login = false)
    {
        return parent::authenticate($userID, $credentials, $login && ($this->getProvider() == 'imp'));
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userID      The userID to check.
     * @param array $credentials  An array of login credentials.
     *
     * @return boolean  Whether or not the credentials are valid.
     */
    protected function _authenticate($userID, $credentials)
    {
        // Check for valid IMAP Client object.
        if (!$GLOBALS['imp_imap']->loadImapObject()) {
            // Attempt to create IMAP Client object
            $key = isset($credentials['server']) ? $credentials['server'] : IMP_Session::getAutoLoginServer();
            if (is_null($key) ||
                !isset($credentials['password']) ||
                !$GLOBALS['imp_imap']->createImapObject($userID, $credentials['password'], $key)) {
                IMP::loginLogMessage('failed', __FILE__, __LINE__);
                $this->_setAuthError(AUTH_REASON_FAILED);
                return false;
            }
        }

        try {
            $GLOBALS['imp_imap']->ob->login();
            return true;
        } catch (Horde_Imap_Client_Exception $e) {
            IMP::loginLogMessage($e->getMessage(), __FILE__, __LINE__);
            $this->_setAuthError(AUTH_REASON_BADLOGIN);
            return false;
        }
    }
}
