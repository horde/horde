<?php
/**
 * The Gollem_Auth class provides authentication for Gollem.
 *
 * Copyright 2004-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Gollem
 */
class Gollem_Auth
{
    /**
     * Authenticate to the backend.
     *
     * @param array $credentials  An array of login credentials. If empty,
     *                            attempts to login to the cached session.
     * <pre>
     * 'password' - (string) The user password.
     * 'backend' - (string) The backend key to use (from backends.php).
     * 'userId' - (string) The username.
     * </pre>
     *
     * @return mixed  If authentication was successful, and no session
     *                exists, an array of data to add to the session.
     *                Otherwise returns false.
     * @throws Horde_Auth_Exception
     */
    static public function authenticate($credentials = array())
    {
        $result = false;

        // Do 'horde' authentication.
        $gollem_app = $GLOBALS['registry']->getApiInstance('gollem', 'application');
        if (!empty($gollem_app->initParams['authentication']) &&
            ($gollem_app->initParams['authentication'] == 'horde')) {
            if ($registry->getAuth()) {
                return $result;
            }
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        // Load backend.
        if (!isset($credentials['backend_key'])) {
            $credentials['backend_key'] = self::getPreferredBackend();
        }
        $backend = self::getBackend($credentials['backend_key']);

        // Check for hordeauth.
        if ((!isset($credentials['userId']) ||
             !isset($credentials['password'])) &&
            !$GLOBALS['session']->exists('gollem', 'backend_key') &&
            self::canAutoLogin($credentials['backend_key'])) {
            if (!empty($backend['hordeauth'])) {
                $credentials['userId'] = self::getAutologinID($credentials['backend_key']);
                $credentials['password'] = $GLOBALS['registry']->getAuthCredential('password');

            }
        }

        // Check for hardcoded backend credentials.
        if (!isset($credentials['userId']) &&
            !empty($backend['params']['username'])) {
            $credentials['userId'] = $backend['params']['username'];
        }
        if (!isset($credentials['password']) &&
            !empty($backend['params']['password'])) {
            $credentials['password'] = $backend['params']['password'];
        }

        if (!isset($credentials['userId']) ||
            !isset($credentials['password'])) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        try {
            $vfs = $GLOBALS['injector']
                ->getInstance('Gollem_Factory_Vfs')
                ->create($credentials['backend_key']);
            $params = array('username' => $credentials['userId'],
                            'password' => $credentials['password']);
            foreach (array_keys($backend['loginparams']) as $param) {
                if (isset($credentials[$param])) {
                    $backend['params'][$param] = $params[$param] = $credentials[$param];
                }
            }
            $vfs->setParams($params);
            $vfs->checkCredentials();
        } catch (Horde_Exception $e) {
            throw new Horde_Auth_Exception($e->getMessage(), Horde_Auth::REASON_MESSAGE);
        }

        // Set current backend.
        Gollem::$backend = &$backend;

        // Mark backend as authenticated.
        $backend['auth'] = true;

        // Save username in backend configuration.
        if (!isset($backend['params']['username'])) {
            $backend['params']['username'] = $credentials['userId'];
        }
        if (!isset($backend['params']['password'])) {
            $backend['params']['password'] = $credentials['password'];
        }

        // Make sure we have a 'root' parameter.
        if (empty($backend['root'])) {
            $backend['root'] = '/';
        }
        $backend['root'] = Horde_Util::realPath($backend['root']);

        // Make sure we have a 'home' parameter.
        if (empty($backend['home'])) {
            $backend['home'] = empty($backend['params']['home'])
                ? $vfs->getCurrentDirectory()
                : $backend['params']['home'];
            if (empty($backend['home'])) {
                $backend['home'] = $backend['root'];
            }
        }

        // Make sure the home parameter lives under root if it is a relative
        // directory.
        if (strpos($backend['home'], '/') !== 0) {
            $backend['home'] = $backend['root'] . '/' . $backend['home'];
        }
        $backend['home'] = Horde_Util::realPath($backend['home']);
        $backend['dir'] = $backend['home'];

        // Verify that home is below root.
        if (!Gollem::verifyDir($backend['home'])) {
            throw new Horde_Auth_Exception('Backend Configuration Error: Home directory not below root.', Horde_Auth::REASON_MESSAGE);
        }

        // Create the home directory if it doesn't already exist.
        if ($backend['home'] != '/' && !empty($backend['createhome'])) {
            $pos = strrpos($backend['home'], '/');
            $cr_dir = substr($backend['home'], 0, $pos);
            $cr_file = substr($backend['home'], $pos + 1);
            if (!$vfs->exists($cr_dir, $cr_file)) {
                try {
                    $res = Gollem::createFolder($cr_dir, $cr_file, $vfs);
                } catch (Gollem_Exception $e) {
                    throw new Horde_Auth_Exception('Backend Configuration Error: Could not create home directory ' . $backend['home'] . ': ' . $e->getMessage(), Horde_Auth::REASON_MESSAGE);
                }
            }
        }

        // Write the backend to the session.
        $backends = self::_getBackends();
        $backends[$credentials['backend_key']] = $backend;
        self::_setBackends($backends);

        return array('backend_key' => $credentials['backend_key']);
    }

    /**
     * Perform transparent authentication.
     *
     * @param Horde_Auth_Application $auth_ob  The authentication object.
     *
     * @return mixed  If authentication was successful, and no session
     *                exists, an array of data to add to the session.
     *                Otherwise returns false.
     */
    static public function transparent($auth_ob)
    {
        $credentials = $auth_ob->getCredential('credentials');

        if (empty($credentials['transparent'])) {
            /* Attempt hordeauth authentication. */
            $credentials = self::canAutoLogin();
            if ($credentials === false) {
                return false;
            }
        } else {
            /* It is possible that preauthenticate() set the credentials.
             * If so, use that information instead of hordeauth. */
            $credentials['userId'] = $auth_ob->getCredential('userId');
        }

        try {
            return self::authenticate($credentials);
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

    /**
     * Loads the Gollem backend configuration from backends.php.
     *
     * @param string $backend  Returns this labeled entry only.
     *
     * @return mixed  If $backend is set return this entry; else, return the
     *                entire backends array. Returns false on error.
     */
    static public function getBackend($backend = null)
    {
        if (!($backends = self::_getBackends())) {
            try {
                $backends = Horde::loadConfiguration('backends.php', 'backends', 'gollem');
                if (is_null($backends)) {
                    return false;
                }
            } catch (Horde_Exception $e) {
                Horde::log($e, 'ERR');
                return false;
            }

            foreach (array_keys($backends) as $key) {
                if (!empty($backends[$key]['disabled']) ||
                    !Gollem::checkPermissions('backend', Horde_Perms::SHOW, $key)) {
                    unset($backends[$key]);
                }
            }
            self::_setBackends($backends);
        }

        if (is_null($backend)) {
            return $backends;
        }

        /* Check for the existence of the backend in the config file. */
        if (empty($backends[$backend]) || !is_array($backends[$backend])) {
            $entry = sprintf('Invalid backend key "%s" from client [%s]',
                             $backend, $_SERVER['REMOTE_ADDR']);
            Horde::log($entry, 'ERR');
            return false;
        }

        return $backends[$backend];
    }

    /**
     * Get the current preferred backend key.
     *
     * @return string  The preferred backend key.
     */
    static public function getPreferredBackend()
    {
        if ($backend_key = $GLOBALS['session']->get('gollem', 'backend_key')) {
            return $backend_key;
        }

        /* Determine the preferred backend. */
        foreach (self::getBackend() as $key => $backend) {
            if (empty($backend_key) && (substr($key, 0, 1) != '_')) {
                $backend_key = $key;
            }
            if (empty($backend['preferred'])) {
                continue;
            }
            $preferred = is_array($backend['preferred'])
                ? $backend['preferred']
                : array($backend['preferred']);
            if (in_array($_SERVER['SERVER_NAME'], $preferred) ||
                in_array($_SERVER['HTTP_HOST'], $preferred)) {
                $backend_key = $key;
            }
        }

        return $backend_key;
    }

    /**
     * Get the authentication ID to use for autologins based on the value of
     * the 'hordeauth' parameter.
     *
     * @param string $backend  The backend to login to.
     *
     * @return string  The ID string to use for logins.
     */
    static public function getAutologinID($backend)
    {
        $config = self::getBackend($backend);
        return (!empty($config['hordeauth']) &&
                strcasecmp($config['hordeauth'], 'full') === 0)
            ? $GLOBALS['registry']->getAuth()
            : $GLOBALS['registry']->getAuth('bare');
    }

    /**
     * Can we log in without a login screen for the requested backend key?
     *
     * @param string $key  The backend to login to.
     *
     * @return array  The credentials needed to login ('userId', 'password',
     *                'backend') or false if autologin not available.
     */
    static public function canAutoLogin($key = null)
    {
        if (is_null($key)) {
            $key = self::getPreferredBackend();
        }

        if ($key &&
            $GLOBALS['registry']->getAuth() &&
            ($config = self::getBackend($key)) &&
            empty($config['loginparams']) &&
            !empty($config['hordeauth'])) {
            return array(
                'userId' => self::getAutologinID($key),
                'password' => $GLOBALS['registry']->getAuthCredential('password'),
                'backend_key' => $key
            );
        }

        return false;
    }

    /**
     * Change the currently active backend.
     *
     * @param string $key  The ID of the backend to set as active.
     */
    static public function changeBackend($key)
    {
        $GLOBALS['session']->set('gollem', 'backend_key', $key);
        Gollem::$backend = self::getBackend($key);
    }

    /**
     * Return stored backend list.
     *
     * @return array  Backend configuration list.
     */
    static protected function _getBackends()
    {
        global $session;

        if ($backends = $session->get('gollem', 'backends', $session::TYPE_ARRAY)) {
            $passwords = $session->get('gollem', 'backends_password', $session::TYPE_ARRAY);
            if ($passwords) {
                foreach ($passwords as $key => $val) {
                    $backends[$key]['params']['password'] = $val;
                }
            }
        }

        return $backends;
    }

    /**
     * Store backend list.
     *
     * @param array $backends  Backend configuration list.
     */
    static protected function _setBackends($backends)
    {
        global $session;

        $passwords = array();
        foreach ($backends as $key => $val) {
            if (isset($val['params']['password'])) {
                $passwords[$key] = $val['params']['password'];
                unset($backends[$key]['params']['password']);
            }
        }

        $session->set('gollem', 'backends', $backends);
        if (!empty($passwords)) {
            $session->set('gollem', 'backends_password', $passwords, $session::ENCRYPT);
        }
    }

}
