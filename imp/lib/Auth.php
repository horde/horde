<?php
/**
 * Copyright 1999-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 1999-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class provides authentication for IMP.
 *
 * The following is the list of IMP session variables:
 *   - compose_ob: (array) Active compose objects.
 *   - file_upload: (integer) If file uploads are allowed, the max size.
 *   - filteravail: (boolean) Can we apply filters manually?
 *   - imap_ob: (IMP_Imap) The IMAP client object.
 *   - pgp: (array) Cached PGP passhprase values.
 *   - rteavail: (boolean) Is the HTML editor available?
 *   - search: (IMP_Search) The IMP_Search object.
 *   - smime: (array) Settings related to the S/MIME viewer.
 *   - showunsub: (boolean) Show unsusubscribed mailboxes on the folders
 *                screen.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jon Parise <jon@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 1999-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Auth
{
    /**
     * Authenticate to the mail server.
     *
     * @param array $credentials  An array of login credentials. If empty,
     *                            attempts to login to the cached session.
     *   - password: (string) The user password.
     *   - server: (string) The server key to use (from backends.php).
     *   - userId: (string) The username.
     *
     * @throws Horde_Auth_Exception
     */
    static public function authenticate($credentials = array())
    {
        global $injector, $registry;

        // Do 'horde' authentication.
        $imp_app = $registry->getApiInstance('imp', 'application');
        if (!empty($imp_app->initParams['authentication']) &&
            ($imp_app->initParams['authentication'] == 'horde')) {
            if ($registry->getAuth()) {
                return;
            }
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        if (!isset($credentials['server'])) {
            $credentials['server'] = self::getAutoLoginServer();
        }

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        // Check for valid IMAP Client object.
        if (!$imp_imap->init) {
            if (!isset($credentials['userId']) ||
                !isset($credentials['password'])) {
                throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
            }

            try {
                $imp_imap->createBaseImapObject($credentials['userId'], $credentials['password'], $credentials['server']);
            } catch (IMP_Imap_Exception $e) {
                self::_log(false, $imp_imap);
                throw $e->authException();
            }
        }

        try {
            $imp_imap->login();
        } catch (IMP_Imap_Exception $e) {
            self::_log(false, $imp_imap);
            throw $e->authException();
        }
    }

    /**
     * Perform transparent authentication.
     *
     * @param Horde_Auth_Application $auth_ob  The authentication object.
     *
     * @return boolean  True on successful transparent authentication.
     */
    static public function transparent($auth_ob)
    {
        $credentials = $auth_ob->getCredential('credentials');

        if (empty($credentials['transparent'])) {
            /* Attempt hordeauth authentication. */
            $credentials = self::_canAutoLogin();
            if ($credentials === false) {
                return false;
            }
        } else {
            /* It is possible that preauthenticate() set the credentials.
             * If so, use that information instead of hordeauth. */
            $credentials['userId'] = $auth_ob->getCredential('userId');
        }

        if (!isset($credentials['password']) ||
            !strlen($credentials['password'])) {
            return false;
        }

        try {
            self::authenticate($credentials);
        } catch (Horde_Auth_Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Log login related message.
     *
     * @param boolean $status    True on success, false on failure.
     * @param IMP_Imap $imap_ob  The IMP_Imap object to use.
     */
    static protected function _log($status, $imap_ob)
    {
        $msg = $status
            ? 'Login success'
            : 'FAILED LOGIN';
        $user = $imap_ob->getParam('username');

        if (($auth_id = $GLOBALS['registry']->getAuth()) &&
            ($user != $auth_id)) {
            $user .= ' (Horde user ' . $auth_id . ')';
        }

        Horde::log(
            sprintf(
                $msg . ' for %s (%s)%s to {%s}',
                $user,
                $_SERVER['REMOTE_ADDR'],
                empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? '' : ' (forwarded for [' . $_SERVER['HTTP_X_FORWARDED_FOR'] . '])',
                $imap_ob->url
            ),
            $status ? 'NOTICE' : 'INFO'
        );
    }

    /**
     * Returns the autologin server key.
     *
     * @return string  The server key, or null if none available.
     */
    static public function getAutoLoginServer()
    {
        if (($servers = IMP_Imap::loadServerConfig()) === false) {
            return null;
        }

        $server_key = null;
        foreach ($servers as $key => $val) {
            if (is_null($server_key) && (substr($key, 0, 1) != '_')) {
                $server_key = $key;
            }

            /* Determines if the given mail server is the "preferred" mail
             * server for this web server. This decision is based on the
             * global 'SERVER_NAME' and 'HTTP_HOST' server variables and the
             * contents of the 'preferred' field in the backend's config. */
            if (($preferred = $val->preferred) &&
                (in_array($_SERVER['SERVER_NAME'], $preferred) ||
                 in_array($_SERVER['HTTP_HOST'], $preferred))) {
                return $key;
            }
        }

        return $server_key;
    }

    /**
     * Returns whether we can log in without a login screen for $server_key.
     *
     * @param string $server_key  The server to check. Defaults to the
     *                            autologin server.
     * @param boolean $force      If true, check $server_key even if there is
     *                            more than one server available.
     *
     * @return array  The credentials needed to login ('userId', 'password',
     *                 'server') or false if autologin not available.
     */
    static protected function _canAutoLogin($server_key = null, $force = false)
    {
        global $injector, $registry;

        if (($servers = $injector->getInstance('IMP_Factory_Imap')->create()->loadServerConfig()) === false) {
            return false;
        }

        if (is_null($server_key) || !$force) {
            $auto_server = self::getAutoLoginServer();
            if (is_null($server_key)) {
                $server_key = $auto_server;
            }
        }

        if ((!empty($auto_server) || $force) &&
            $registry->getAuth() &&
            !empty($servers[$server_key]->hordeauth)) {
            return array(
                'userId' => $registry->getAuth((strcasecmp($servers[$server_key]->hordeauth, 'full') === 0) ? null : 'bare'),
                'password' => $registry->getAuthCredential('password'),
                'server' => $server_key
            );
        }

        return false;
    }

    /**
     * Perform post-login tasks. Session creation requires the full IMP
     * environment, which is not available until this callback.
     *
     * @throws Horde_Exception
     */
    static public function authenticateCallback()
    {
        global $browser, $injector, $session;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        /* Perform post-login tasks for IMAP object. */
        $imp_imap->doPostLoginTasks();

        /* Does the server allow file uploads? If yes, store the
         * value, in bytes, of the maximum file size. */
        $session->set('imp', 'file_upload', $browser->allowFileUploads());

        /* Is the HTML editor available? */
        $session->set('imp', 'rteavail', $injector->getInstance('Horde_Editor')->supportedByBrowser());

        self::_log(true, $imp_imap);
    }

}
