<?php
/**
 * Functions required to start a Gollem session.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Max Kalika <max@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Gollem
 */
class Gollem_Session {

    /**
     * Take information posted from a login attempt and try setting up
     * an initial Gollem session. Handle Horde authentication, if
     * required, and only do enough work to see if the user can log
     * in. This function should only be called once, when the user first logs
     * into Gollem.
     *
     * Creates the $gollem session variable with the following entries:
     * 'backend_key' --  The current backend
     * 'be_list'     --  The cached list of available backends
     * 'selectlist'  --  Stores file selections from the API call
     *
     * Each backend is stored by its name in the 'backends' array.  Each
     * backend contains the following entries:
     * 'attributes'  --  See config/backends.php
     * 'autologin'   --  Whether this backend supports autologin
     * 'clipboard'   --  The clipboard for the current backend
     * 'createhome'  --  See config/backends.php
     * 'dir'         --  The current directory
     * 'driver'      --  See config/backends.php
     * 'filter'      --  See config/backends.php
     * 'home'        --  The user's home directory
     * 'hordeauth'   --  See config/backends.php
     * 'hostspec'    --  See config/backends.php
     * 'label'       --  The label to use
     * 'name'        --  See config/backends.php
     * 'params'      --  See config/backends.php
     * 'preferred'   --  See config/backends.php
     * 'root'        --  The root directory
     *
     * @param string $key   The backend key to initialize.
     * @param string $user  The username to use for authentication.
     * @param string $pass  The password to use for authentication.
     * @param array $args   Any additional parameters the backend needs.
     *
     * @return boolean  True on success, false on failure.
     */
    function createSession($key, $user = null, $pass = null, $args = array())
    {
        global $conf;

        /* Make sure we have a key and that it is valid. */
        if (empty($key) || (substr($key, 0, 1) == '_')) {
            return false;
        }

        /* We might need to override some of the defaults with
         * environment-wide settings. Do NOT use the global $backends
         * variable as it may not exist. */
        require GOLLEM_BASE . '/config/backends.php';
        if (empty($backends[$key])) {
            $entry = sprintf('Invalid server key from client [%s]', $_SERVER['REMOTE_ADDR']);
            Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_INFO);
            return false;
        }

        /* Create gollem session object if it doesn't already exist. */
        if (!isset($_SESSION['gollem'])) {
            $_SESSION['gollem'] = array();
            $_SESSION['gollem']['backends'] = array();
            $_SESSION['gollem']['selectlist'] = array();
        }
        $_SESSION['gollem']['backends'][$key] = $backends[$key];
        $GLOBALS['gollem_be'] = &$_SESSION['gollem']['backends'][$key];
        $ptr = &$_SESSION['gollem']['backends'][$key];
        $ptr['params'] = array_merge($ptr['params'], $args);

        /* Set the current backend as active. */
        $_SESSION['gollem']['backend_key'] = $key;

        /* Set username now. Don't set the current username if the backend
         * already has a username defined. */
        if (empty($ptr['params']['username'])) {
            $ptr['params']['username'] = ($user === null) ? Horde_Auth::getBareAuth() : $user;
        }

        /* Set password now. The password should always be encrypted within
         * the session. */
        if (!empty($ptr['params']['password'])) {
            $pass = $ptr['params']['password'];
        }
        if ($pass === null) {
            $ptr['params']['password'] = null;
        } else {
            $ptr['params']['password'] = Horde_Secret::write(Horde_Secret::getKey('gollem'), $pass);
        }

        /* Try to authenticate with the given information. */
        $auth_gollem = new Gollem_Auth();
        if ($auth_gollem->authenticate(null, null, true) !== true) {
            unset($_SESSION['gollem']['backends'][$key]);
            $_SESSION['gollem']['backend_key'] = null;
            return false;
        }

        // Make sure we have a 'root' parameter.
        if (empty($ptr['root'])) {
            $ptr['root'] = '/';
        }
        $ptr['root'] = Horde_Util::realPath($ptr['root']);

        // Make sure we have a 'home' parameter.
        if (empty($ptr['home'])) {
            $ptr['home'] = (!empty($ptr['params']['home'])) ? $ptr['params']['home'] : $GLOBALS['gollem_vfs']->getCurrentDirectory();
            if (empty($ptr['home'])) {
                $ptr['home'] = $ptr['root'];
            }
        }

        // Make sure the home parameter lives under root if it is a relative
        // directory.
        if (strpos($ptr['home'], '/') !== 0) {
            $ptr['home'] = $ptr['root'] . '/' . $ptr['home'];
        }
        $ptr['home'] = Horde_Util::realPath($ptr['home']);
        $ptr['dir'] = $ptr['home'];

        // Verify that home is below root.
        if (!Gollem::verifyDir($ptr['home'])) {
            $error_msg = 'Backend Configuration Error: Home directory not below root.';
            $auth_gollem->gollemSetAuthErrorMsg($error_msg);
            Horde::logMessage(PEAR::raiseError($error_msg), __FILE__, __LINE__, PEAR_LOG_ERR);
            unset($_SESSION['gollem']['backends'][$key]);
            $_SESSION['gollem']['backend_key'] = null;
            return false;
        }

        /* Create the home directory if it doesn't already exist. */
        if (($ptr['home'] != '/') && !empty($ptr['createhome'])) {
            $pos = strrpos($ptr['home'], '/');
            $cr_dir = substr($ptr['home'], 0, $pos);
            $cr_file = substr($ptr['home'], $pos + 1);
            if (!$GLOBALS['gollem_vfs']->exists($cr_dir, $cr_file)) {
                $res = Gollem::createFolder($cr_dir, $cr_file);
                if (is_a($res, 'PEAR_Error')) {
                    $error_msg = 'Backend Configuration Error: Could not create home directory ' . $ptr['home'] . '.';
                    $auth_gollem->gollemSetAuthErrorMsg($error_msg);
                    Horde::logMessage(PEAR::raiseError($error_msg), __FILE__, __LINE__, PEAR_LOG_ERR);
                    unset($_SESSION['gollem']['backends'][$key]);
                    $_SESSION['gollem']['backend_key'] = null;
                    return false;
                }
            }
        }

        /* Does this driver support autologin? */
        $ptr['autologin'] = Gollem::canAutoLogin(true);

        /* Cache the backend_list in the session. */
        if (empty($_SESSION['gollem']['be_list'])) {
            Gollem::loadBackendList();
            $_SESSION['gollem']['be_list'] = $GLOBALS['gollem_backends'];
        }

        /* Initialize clipboard. */
        if (!isset($_SESSION['gollem']['clipboard'])) {
            $_SESSION['gollem']['clipboard'] = array();
        }

        /* Call Gollem::changeDir() to make sure the label is set. */
        Gollem::changeDir();

        return true;
    }

    /**
     * Change the currently active backend.
     *
     * @param string $key  The ID of the backend to set as active.
     */
    function changeBackend($key)
    {
        $_SESSION['gollem']['backend_key'] = $key;
        $GLOBALS['gollem_be'] = &$_SESSION['gollem']['backends'][$key];
    }

}
