<?php
/**
 * The Gollem_Auth:: class provides a Gollem implementation of the Horde
 * authentication system.
 *
 * Required parameters: None
 * Optional parameters: None
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package Gollem
 */
class Gollem_Auth
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
        // Check for for hordeauth.
        if (empty($_SESSION['gollem']['backend_key'])) {
            if (Gollem::canAutoLogin()) {
                $backend_key = Gollem::getPreferredBackend();

                $ptr = &$GLOBALS['gollem_backends'][$backend_key];
                if (!empty($ptr['hordeauth'])) {
                    $user = Gollem::getAutologinID($backend_key);
                    $pass = Horde_Auth::getCredential('password');

                    if (Gollem_Session::createSession($backend_key, $user, $pass)) {
                        $entry = sprintf('Login success for %s [%s] to {%s}',
                                         $user, $_SERVER['REMOTE_ADDR'],
                                         $backend_key);
                        Horde::logMessage($entry, __FILE__, __LINE__,
                                          PEAR_LOG_NOTICE);
                        return true;
                    }
                }
            }
        }

        if (empty($userID) &&
            !empty($GLOBALS['gollem_be']['params']['username'])) {
            $userID = $GLOBALS['gollem_be']['params']['username'];
        }

        if (empty($credentials) &&
            !empty($GLOBALS['gollem_be']['params']['password'])) {
            $credentials = array('password' => Horde_Secret::read(Horde_Secret::getKey('gollem'), $GLOBALS['gollem_be']['params']['password']));
        }

        $login = ($login && (Horde_Auth::getProvider() == 'gollem'));

        return parent::authenticate($userID, $credentials, $login);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userID      The userID to check.
     * @param array $credentials  An array of login credentials.
     *
     * @throw Horde_Exception
     */
    protected function _authenticate($userID, $credentials)
    {
        if (!(isset($_SESSION['gollem']) && is_array($_SESSION['gollem']))) {
            if (isset($GLOBALS['prefs'])) {
                $GLOBALS['prefs']->cleanup(true);
            }
            throw new Horde_Exception('', Horde_Auth::REASON_SESSION);
        }

        // Allocate a global VFS object
        $GLOBALS['gollem_vfs'] = Gollem::getVFSOb($_SESSION['gollem']['backend_key']);
        if (is_a($GLOBALS['gollem_vfs'], 'PEAR_Error')) {
            Horde::fatal($GLOBALS['gollem_vfs']);
        }

        $valid = $GLOBALS['gollem_vfs']->checkCredentials();
        if ($valid instanceof PEAR_Error) {
            $msg = $valid->getMessage();
            if (empty($msg)) {
                throw new Horde_Exception('', Horde_Auth::REASON_FAILED);
            }

            throw new Horde_Exception($msg);
        }
    }

}
