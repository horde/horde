<?php
/**
 * The Horde_Auth:: class provides a common abstracted interface into the
 * various backends for the Horde authentication system.
 *
 * Horde authentication data is stored in the session in the 'horde_auth'
 * array key.  That key has the following structure:
 * <pre>
 * 'app' - (array) Application-specific authentication. Keys are the
 *         app names, values are an array of credentials.
 * 'browser' - (string) The remote browser string.
 * 'change' - (boolean) Is a password change requested?
 * 'credentials' - (array) The credentials needed for this driver.
 * 'driver' - (string) The driver used for base horde auth.
 * 'remoteAddr' - (string) The remote IP address of the user.
 * 'timestamp' - (integer) The login time.
 * 'userId' - (username) The horde username.
 * </pre>
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Horde_Auth
 */
class Horde_Auth
{
    /**
     * The parameter name for the logout reason.
     */
    const REASON_PARAM = 'logout_reason';

    /**
     * The parameter name for the logout message used with type
     * REASON_MESSAGE.
    */
    const REASON_MSG_PARAM = 'logout_msg';

    /**
     * The 'badlogin' reason.
     *
     * The following 'reasons' for the logout screen are recognized:
     * <pre>
     * REASON_BADLOGIN - Bad username and/or password
     * REASON_BROWSER - A browser change was detected
     * REASON_FAILED - Login failed
     * REASON_EXPIRED - Password has expired
     * REASON_LOGOUT - Logout due to user request
     * REASON_MESSAGE - Logout with custom message in REASON_MSG_PARAM
     * REASON_SESSION - Logout due to session expiration
     * REASON_SESSIONIP - Logout due to change of IP address during session
     * </pre>
     */
    const REASON_BADLOGIN = 1;
    const REASON_BROWSER = 2;
    const REASON_FAILED = 3;
    const REASON_EXPIRED = 4;
    const REASON_LOGOUT = 5;
    const REASON_MESSAGE = 6;
    const REASON_SESSION = 7;
    const REASON_SESSIONIP = 8;

    /**
     * 64 characters that are valid for APRMD5 passwords.
     */
    const APRMD5_VALID = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Characters used when generating a password.
     */
    const VOWELS = 'aeiouy';
    const CONSONANTS = 'bcdfghjklmnpqrstvwxz';
    const NUMBERS = '0123456789';

    /**
     * Singleton instances.
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     * The logout reason information.
     *
     * @var array
     */
    static protected $_reason = array();

    /**
     * Attempts to return a concrete Horde_Auth_Base instance based on
     * $driver.
     *
     * @param mixed $driver  The type of concrete Horde_Auth_Base subclass
     *                       to return.
     * @param array $params  A hash containing any additional configuration or
     *                       parameters a subclass might need.
     *
     * @return Horde_Auth_Base  The newly created concrete instance.
     * @throws Horde_Auth_Exception
     */
    static public function factory($driver, $params = null)
    {
        $driver = str_replace(' ', '_' , ucwords(str_replace('_', ' ', basename($driver))));
        if (empty($params)) {
            $params = Horde::getDriverConfig('auth', $driver);
        }

        $class = 'Horde_Auth_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Auth_Exception('Class definition of ' . $class . ' not found.');
    }

    /**
     * Attempts to return a reference to a concrete instance based on $driver.
     * It will only create a new instance if no instance with the same
     * parameters currently exists.
     *
     * This method must be invoked as: $var = Horde_Auth::singleton()
     *
     * @param mixed $driver  The type of concrete Horde_Auth_Base subclass
     *                       to return.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Auth_Base  The concrete reference.
     * @throws Horde_Auth_Exception
     */
    static public function singleton($driver, $params = array())
    {
        ksort($params);
        $signature = hash('md5', serialize(array($driver, $params)));

        if (!isset(self::$_instances[$signature])) {
            self::$_instances[$signature] = Horde_Auth::factory($driver, $params);
        }

        return self::$_instances[$signature];
    }

    /**
     * Formats a password using the current encryption.
     *
     * @param string $plaintext      The plaintext password to encrypt.
     * @param string $salt           The salt to use to encrypt the password.
     *                               If not present, a new salt will be
     *                               generated.
     * @param string $encryption     The kind of pasword encryption to use.
     *                               Defaults to md5-hex.
     * @param boolean $show_encrypt  Some password systems prepend the kind of
     *                               encryption to the crypted password ({SHA},
     *                               etc). Defaults to false.
     *
     * @return string  The encrypted password.
     */
    static public function getCryptedPassword($plaintext, $salt = '',
                                              $encryption = 'md5-hex',
                                              $show_encrypt = false)
    {
        /* Get the salt to use. */
        $salt = self::getSalt($encryption, $salt, $plaintext);

        /* Encrypt the password. */
        switch ($encryption) {
        case 'plain':
            return $plaintext;

        case 'msad':
            return Horde_String::convertCharset('"' . $plaintext . '"', 'ISO-8859-1', 'UTF-16LE');

        case 'sha':
            $encrypted = base64_encode(pack('H*', hash('sha1', $plaintext)));
            return $show_encrypt ? '{SHA}' . $encrypted : $encrypted;

        case 'crypt':
        case 'crypt-des':
        case 'crypt-md5':
        case 'crypt-blowfish':
            return ($show_encrypt ? '{crypt}' : '') . crypt($plaintext, $salt);

        case 'md5-base64':
            $encrypted = base64_encode(pack('H*', hash('md5', $plaintext)));
            return $show_encrypt ? '{MD5}' . $encrypted : $encrypted;

        case 'ssha':
            $encrypted = base64_encode(pack('H*', hash('sha1', $plaintext . $salt)) . $salt);
            return $show_encrypt ? '{SSHA}' . $encrypted : $encrypted;

        case 'smd5':
            $encrypted = base64_encode(pack('H*', hash('md5', $plaintext . $salt)) . $salt);
            return $show_encrypt ? '{SMD5}' . $encrypted : $encrypted;

        case 'aprmd5':
            $length = strlen($plaintext);
            $context = $plaintext . '$apr1$' . $salt;
            $binary = pack('H*', hash('md5', $plaintext . $salt . $plaintext));

            for ($i = $length; $i > 0; $i -= 16) {
                $context .= substr($binary, 0, ($i > 16 ? 16 : $i));
            }
            for ($i = $length; $i > 0; $i >>= 1) {
                $context .= ($i & 1) ? chr(0) : $plaintext[0];
            }

            $binary = pack('H*', hash('md5', $context));

            for ($i = 0; $i < 1000; ++$i) {
                $new = ($i & 1) ? $plaintext : substr($binary, 0, 16);
                if ($i % 3) {
                    $new .= $salt;
                }
                if ($i % 7) {
                    $new .= $plaintext;
                }
                $new .= ($i & 1) ? substr($binary, 0, 16) : $plaintext;
                $binary = pack('H*', hash('md5', $new));
            }

            $p = array();
            for ($i = 0; $i < 5; $i++) {
                $k = $i + 6;
                $j = $i + 12;
                if ($j == 16) {
                    $j = 5;
                }
                $p[] = self::_toAPRMD5((ord($binary[$i]) << 16) |
                                       (ord($binary[$k]) << 8) |
                                       (ord($binary[$j])),
                                       5);
            }

            return '$apr1$' . $salt . '$' . implode('', $p) . self::_toAPRMD5(ord($binary[11]), 3);

        case 'md5-hex':
        default:
            return ($show_encrypt) ? '{MD5}' . hash('md5', $plaintext) : hash('md5', $plaintext);
        }
    }

    /**
     * Returns a salt for the appropriate kind of password encryption.
     * Optionally takes a seed and a plaintext password, to extract the seed
     * of an existing password, or for encryption types that use the plaintext
     * in the generation of the salt.
     *
     * @param string $encryption  The kind of pasword encryption to use.
     *                            Defaults to md5-hex.
     * @param string $seed        The seed to get the salt from (probably a
     *                            previously generated password). Defaults to
     *                            generating a new seed.
     * @param string $plaintext   The plaintext password that we're generating
     *                            a salt for. Defaults to none.
     *
     * @return string  The generated or extracted salt.
     */
    static public function getSalt($encryption = 'md5-hex', $seed = '',
                                   $plaintext = '')
    {
        switch ($encryption) {
        case 'crypt':
        case 'crypt-des':
            return $seed
                ? substr(preg_replace('|^{crypt}|i', '', $seed), 0, 2)
                : substr(hash('md5', mt_rand()), 0, 2);

        case 'crypt-md5':
            return $seed
                ? substr(preg_replace('|^{crypt}|i', '', $seed), 0, 12)
                : '$1$' . base64_encode(hash('md5', sprintf('%08X%08X', mt_rand(), mt_rand()), true)) . '$';

        case 'crypt-blowfish':
            return $seed
                ? substr(preg_replace('|^{crypt}|i', '', $seed), 0, 16)
                : '$2$' . substr(hash('md5', mt_rand()), 0, 12) . '$';

        case 'ssha':
            return $seed
                ? substr(base64_decode(preg_replace('|^{SSHA}|i', '', $seed)), 20)
                : substr(pack('H*', sha1(substr(pack('h*', hash('md5', mt_rand())), 0, 8) . $plaintext)), 0, 4);

        case 'smd5':
            return $seed
                ? substr(base64_decode(preg_replace('|^{SMD5}|i', '', $seed)), 16)
                : substr(pack('H*', hash('md5', substr(pack('h*', hash('md5', mt_rand())), 0, 8) . $plaintext)), 0, 4);

        case 'aprmd5':
            if ($seed) {
                return substr(preg_replace('/^\$apr1\$(.{8}).*/', '\\1', $seed), 0, 8);
            } else {
                $salt = '';
                $valid = self::APRMD5_VALID;
                for ($i = 0; $i < 8; ++$i) {
                    $salt .= $valid[mt_rand(0, 63)];
                }
                return $salt;
            }

        default:
            return '';
        }
    }

    /**
     * Generates a random, hopefully pronounceable, password. This can be used
     * when resetting automatically a user's password.
     *
     * @return string A random password
     */
    static public function genRandomPassword()
    {
        /* Alternate consonant and vowel random chars with two random numbers
         * at the end. This should produce a fairly pronounceable password. */
        return substr(self::CONSONANTS, mt_rand(0, strlen(self::CONSONANTS) - 1), 1) .
            substr(self::VOWELS, mt_rand(0, strlen(self::VOWELS) - 1), 1) .
            substr(self::CONSONANTS, mt_rand(0, strlen(self::CONSONANTS) - 1), 1) .
            substr(self::VOWELS, mt_rand(0, strlen(self::VOWELS) - 1), 1) .
            substr(self::CONSONANTS, mt_rand(0, strlen(self::CONSONANTS) - 1), 1) .
            substr(self::NUMBERS, mt_rand(0, strlen(self::NUMBERS) - 1), 1) .
            substr(self::NUMBERS, mt_rand(0, strlen(self::NUMBERS) - 1), 1);
    }

    /**
     * Calls all applications' removeUser API methods.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Auth_Exception
     */
    static public function removeUserData($userId)
    {
        $errApps = array();
        $registry = Horde_Registry::singleton();

        foreach ($registry->listApps(array('notoolbar', 'hidden', 'active', 'admin')) as $app) {
            try {
                $registry->callByPackage($app, 'removeUserData', array($userId));
            } catch (Horde_Auth_Exception $e) {
                Horde::logMessage($e, __FILE__, __LINE__, PEAR_LOG_ERR);
                $errApps[] = $app;
            }
        }

        if (count($errApps)) {
            throw new Horde_Auth_Exception(sprintf(_("The following applications encountered errors removing user data: %s"), implode(', ', $errApps)));
        }
    }

    /**
     * Checks if there is a session with valid auth information. for the
     * specified user. If there isn't, but the configured Auth driver supports
     * transparent authentication, then we try that.
     *
     * @params array $options  Additional options:
     * <pre>
     * 'app' - (string) Check authentication for this app.
     *         DEFAULT: Checks horde-wide authentication.
     * </pre>
     *
     * @return boolean  Whether or not the user is authenticated.
     */
    static public function isAuthenticated($options = array())
    {
        $driver = empty($options['app'])
            ? $GLOBALS['conf']['auth']['driver']
            : $options['app'];
        $is_auth = self::getAuth();

        /* Check for cached authentication results. */
        if ($is_auth &&
            (($_SESSION['horde_auth']['driver'] == $driver) ||
             isset($_SESSION['horde_auth']['app'][$driver]))) {
            return self::checkExistingAuth();
        }

        /* Try transparent authentication. */
        $auth = empty($options['app'])
            ? self::singleton($driver)
            : self::singleton('application', array('app' => $driver));

        return $auth->transparent();
    }

    /**
     * Check existing auth for triggers that might invalidate it.
     *
     * @return boolean  Is existing auth valid?
     */
    static public function checkExistingAuth()
    {
        if (!empty($GLOBALS['conf']['auth']['checkip']) &&
            !empty($_SESSION['horde_auth']['remoteAddr']) &&
            ($_SESSION['horde_auth']['remoteAddr'] != $_SERVER['REMOTE_ADDR'])) {
            self::setAuthError(self::REASON_SESSIONIP);
            return false;
        }

        if (!empty($GLOBALS['conf']['auth']['checkbrowser'])) {
            $browser = Horde_Browser::singleton();
            if ($_SESSION['horde_auth']['browser'] != $browser->getAgentString()) {
                self::setAuthError(self::REASON_BROWSER);
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the currently logged in user, if there is one.
     *
     * @return mixed  The userId of the current user, or false if no user is
     *                logged in.
     */
    static public function getAuth()
    {
        return empty($_SESSION['horde_auth']['userId'])
            ? false
            : $_SESSION['horde_auth']['userId'];
    }

    /**
     * Handle authentication failures, redirecting to the login page
     * when appropriate.
     *
     * @param string $app         The app which failed authentication.
     * @param Horde_Exception $e  An exception thrown by
     *                            Horde_Registry::pushApp().
     *
     * @throws Horde_Exception
     */
    static public function authenticateFailure($app = 'horde', $e = null)
    {
        if (Horde_Cli::runningFromCLI()) {
            $cli = Horde_Cli::singleton();
            $cli->fatal(_("You are not authenticated."));
        }

        if (is_null($e)) {
            $params = array();
        } else {
            switch ($e->getCode()) {
            case Horde_Registry::PERMISSION_DENIED:
                $params = array('app' => $app, 'reason' => Horde_Auth::REASON_MESSAGE, 'msg' => $e->getMessage());
                break;

            case Horde_Registry::AUTH_FAILURE:
                $params = array('app' => $app);
                break;

            default:
                throw $e;
            }
        }

        header('Location: ' . self::getLogoutUrl($params));
        exit;
    }

    /**
     * Return a URL to the login screen, adding the necessary logout
     * parameters.
     * If no reason/msg is passed in, use the current global authentication
     * error message.
     *
     * @param array $options  Additional options:
     * <pre>
     * 'app' - (string) Authenticate to this application
     *         DEFAULT: Horde
     * 'msg' - (string) If reason is self::REASON_MESSAGE, the message
     *         to display to the user.
     *         DEFAULT: None
     * 'params' - (array) Additional params to add to the URL (not allowed:
     *            'app', 'horde_logout_token', 'msg', 'nosidebar', 'reason',
     *            'url').
     *            DEFAULT: None
     * 'reason' - (integer) The reason for logout
     *            DEFAULT: None
     * </pre>
     *
     * @return string The formatted URL
     */
    static public function getLogoutUrl($options = array())
    {
        $registry = Horde_Registry::singleton();

        if (!isset($options['reason'])) {
            $options['reason'] = self::getAuthError();
        }

        if (empty($options['app']) ||
            ($options['app'] == 'horde') ||
            ($options['reason'] == self::REASON_LOGOUT)) {
            $params = array(
                'horde_logout_token' => Horde::getRequestToken('horde.logout'),
                'nosidebar' => 1
            );
        } else {
            $params = array(
                'url' => Horde::selfUrl(true)
            );
        }

        if (isset($options['app'])) {
            $params['app'] = $options['app'];
        }

        if ($options['reason']) {
            $params[self::REASON_PARAM] = $options['reason'];
            if ($options['reason'] == self::REASON_MESSAGE) {
                $params[self::REASON_MSG_PARAM] = empty($options['msg'])
                    ? self::getAuthError(true)
                    : $options['msg'];
            }
        }

        return Horde_Util::addParameter($registry->get('webroot', 'horde') . '/login.php', $params, null, false);
    }

    /**
     * Return whether the authentication backend requested a password change.
     *
     * @return boolean Whether the backend requested a password change.
     */
    static public function passwordChangeRequested()
    {
        return !empty($_SESSION['horde_auth']['change']);
    }

    /**
     * Returns the curently logged-in user without any domain information
     * (e.g., bob@example.com would be returned as 'bob').
     *
     * @return mixed  The user ID of the current user, or false if no user
     *                is logged in.
     */
    static public function getBareAuth()
    {
        $user = self::getAuth();
        return ($user && (($pos = strpos($user, '@')) !== false))
            ? substr($user, 0, $pos)
            : $user;
    }

    /**
     * Returns the domain of currently logged-in user (e.g., bob@example.com
     * would be returned as 'example.com').
     *
     * @return mixed  The domain suffix of the current user, or false.
     */
    static public function getAuthDomain()
    {
        $user = self::getAuth();
        return ($user && (($pos = strpos($user, '@')) !== false))
            ? substr($user, $pos + 1)
            : false;
    }

    /**
     * Returns the requested credential for the currently logged in user, if
     * present.
     *
     * @param string $credential  The credential to retrieve.
     *
     * @return mixed  The requested credential, all credentials if $credential
     *                is null, or false if no user is logged in.
     */
    static public function getCredential($credential = null)
    {
        if (!self::getAuth()) {
            return false;
        }

        $credentials = Horde_Secret::read(Horde_Secret::getKey('auth'), $_SESSION['horde_auth']['credentials']);
        $credentials = @unserialize($credentials);

        return is_null($credential)
            ? $credentials
            : ((is_array($credentials) && isset($credentials[$credential]))
                   ? $credentials[$credential]
                   : false);
    }

    /**
     * Sets the requested credential for the currently logged in user.
     *
     * @param string $credential  The credential to set.
     * @param string $value       The value to set the credential to.
     */
    static public function setCredential($credential, $value)
    {
        if (self::getAuth()) {
            $credentials = @unserialize(Horde_Secret::read(Horde_Secret::getKey('auth'), $_SESSION['horde_auth']['credentials']));
            if (is_array($credentials)) {
                $credentials[$credential] = $value;
            } else {
                $credentials = array($credential => $value);
            }
            $_SESSION['horde_auth']['credentials'] = Horde_Secret::write(Horde_Secret::getKey('auth'), serialize($credentials));
        }
    }

    /**
     * Sets a variable in the session saying that authorization has succeeded,
     * note which userId was authorized, and note when the login took place.
     *
     * If a user name hook was defined in the configuration, it gets applied
     * to the $userId at this point.
     *
     * @param string $userId      The userId who has been authorized.
     * @param array $credentials  The credentials of the user.
     * @param array $options      Additional options:
     * <pre>
     * 'app' - (string) The app to set authentication credentials for.
     *         DEFAULT: Set horde authentication
     * 'change' - (boolean) Whether to request that the user change their
     *            password.
     *            DEFAULT: No
     * 'nologin' - (boolean) Don't do login tasks?
     *             DEFAULT: Perform login tasks
     * </pre>
     *
     * @return boolean  Whether authentication was successful.
     */
    static public function setAuth($userId, $credentials, $options = array())
    {
        $app = empty($options['app']) ? 'horde' : $options['app'];
        $userId = self::addHook(trim($userId));

        try {
            list($userId, $credentials) = self::runHook($userId, $credentials, $app, 'postauthenticate');
        } catch (Horde_Auth_Exception $e) {
            return false;
        }

        $app_array = array();
        if ($app != 'horde') {
            if (empty($_SESSION['horde_auth'])) {
                $app_array = array($app => true);
            } else {
                $_SESSION['horde_auth']['app'][$app] = true;
            }
        }

        /* If we're already set with this userId, don't continue. */
        if (self::getAuth() &&
            ($_SESSION['horde_auth']['userId'] == $userId)) {
            return true;
        }

        /* Clear any existing info. */
        self::clearAuth();

        $browser = Horde_Browser::singleton();

        $_SESSION['horde_auth'] = array(
            'app' => $app_array,
            'browser' => $browser->getAgentString(),
            'change' => !empty($options['change']),
            'credentials' => Horde_Secret::write(Horde_Secret::getKey('auth'), serialize($credentials)),
            'driver' => $GLOBALS['conf']['auth']['driver'],
            'remoteAddr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
            'timestamp' => time(),
            'userId' => $userId
        );

        /* Reload preferences for the new user. */
        $registry = Horde_Registry::singleton();
        $registry->loadPrefs();
        Horde_Nls::setLang($GLOBALS['prefs']->getValue('language'));

        if (!empty($options['nologin'])) {
            return true;
        }

        /* Fetch the user's last login time. */
        $old_login = @unserialize($GLOBALS['prefs']->getValue('last_login'));

        /* Display it, if we have a notification object and the
         * show_last_login preference is active. */
        if (isset($GLOBALS['notification']) &&
            $GLOBALS['prefs']->getValue('show_last_login')) {
            if (empty($old_login['time'])) {
                $GLOBALS['notification']->push(_("Last login: Never"), 'horde.message');
            } else {
                if (empty($old_login['host'])) {
                    $GLOBALS['notification']->push(sprintf(_("Last login: %s"), strftime('%c', $old_login['time'])), 'horde.message');
                } else {
                    $GLOBALS['notification']->push(sprintf(_("Last login: %s from %s"), strftime('%c', $old_login['time']), $old_login['host']), 'horde.message');
                }
            }
        }

        /* Set the user's last_login information. */
        $host = empty($_SERVER['HTTP_X_FORWARDED_FOR'])
            ? $_SERVER['REMOTE_ADDR']
            : $_SERVER['HTTP_X_FORWARDED_FOR'];

        if (class_exists('Net_DNS')) {
            $resolver = new Net_DNS_Resolver();
            $resolver->retry = isset($GLOBALS['conf']['dns']['retry']) ? $GLOBALS['conf']['dns']['retry'] : 1;
            $resolver->retrans = isset($GLOBALS['conf']['dns']['retrans']) ? $GLOBALS['conf']['dns']['retrans'] : 1;
            $ptrdname = $host;
            if ($response = $resolver->query($host, 'PTR')) {
                foreach ($response->answer as $val) {
                    if (isset($val->ptrdname)) {
                        $ptrdname = $val->ptrdname;
                        break;
                    }
                }
            }
        } else {
            $ptrdname = @gethostbyaddr($host);
        }

        $last_login = array('time' => time(), 'host' => $ptrdname);
        $GLOBALS['prefs']->setValue('last_login', serialize($last_login));

        return true;
    }

    /**
     * Clears any authentication tokens in the current session.
     */
    static public function clearAuth()
    {
        unset($_SESSION['horde_auth']);

        /* Remove the user's cached preferences if they are present. */
        $registry = Horde_Registry::singleton();
        $registry->unloadPrefs();
    }

    /**
     * Is the current user an administrator?
     *
     * @param string $permission  Allow users with this permission admin access
     *                            in the current context.
     * @param integer $permlevel  The level of permissions to check for
     *                            (PERMS_EDIT, PERMS_DELETE, etc). Defaults
     *                            to PERMS_EDIT.
     * @param string $user        The user to check. Defaults to
     *                            self::getAuth().
     *
     * @return boolean  Whether or not this is an admin user.
     */
    static public function isAdmin($permission = null, $permlevel = null,
                                   $user = null)
    {
        if (is_null($user)) {
            $user = self::getAuth();
        }

        if ($user &&
            @is_array($GLOBALS['conf']['auth']['admins']) &&
            in_array($user, $GLOBALS['conf']['auth']['admins'])) {
            return true;
        }

        if (!is_null($permission)) {
            if (is_null($permlevel)) {
                $permlevel = PERMS_EDIT;
            }
            return $GLOBALS['perms']->hasPermission($permission, $user, $permlevel);
        }

        return false;
    }

    /**
     * Applies the username_frombackend hook to the given user name.
     *
     * This method should be called if a authentication backend's user name
     * needs to be converted to a (unique) Horde user name. The backend's user
     * name is what the user sees and uses, but internally we use the Horde
     * user name.
     *
     * @param string $userId  The authentication backend's user name.
     *
     * @return string  The internal Horde user name.
     * @throws Horde_Exception
     */
    static public function addHook($userId)
    {
        try {
            return Horde::callHook('username_frombackend', array($userId));
        } catch (Horde_Exception_HookNotSet $e) {
            return $userId;
        }
    }

    /**
     * Applies the username_tobackend hook to the given user name.
     *
     * This method should be called if a Horde user name needs to be converted
     * to an authentication backend's user name or displayed to the user. The
     * backend's user name is what the user sees and uses, but internally we
     * use the Horde user name.
     *
     * @param string $userId  The internal Horde user name.
     *
     * @return string  The authentication backend's user name.
     * @throws Horde_Exception
     */
    static public function removeHook($userId)
    {
        try {
            return Horde::callHook('username_tobackend', array($userId));
        } catch (Horde_Exception_HookNotSet $e) {
            return $userId;
        }
    }

    /**
     * Runs the pre/post-authenticate hook and parses the result.
     *
     * @param string $userId      The userId who has been authorized.
     * @param array $credentials  The credentials of the user.
     * @param string $app         The app currently being authenticated.
     * @param string $type        Either 'preauthenticate' or
     *                            'postauthenticate'.
     *
     * @return array  Two element array, $userId and $credentials.
     * @throws Horde_Auth_Exception
     */
    static public function runHook($userId, $credentials, $app, $type)
    {
        $ret_array = array($userId, $credentials);

        try {
            $result = Horde::callHook($type, array($userId, $credentials), $app);
        } catch (Horde_Exception $e) {
            throw new Horde_Auth_Exception($e->getMessage());
        } catch (Horde_Exception_HookNotSet $e) {
            return $ret_array;
        }

        if ($result === false) {
            if (self::getAuthError() != self::REASON_MESSAGE) {
                self::setAuthError(self::REASON_FAILED);
            }
            throw new Horde_Auth_Exception($type . ' hook failed');
        }

        if (is_array($result)) {
            if (isset($result['userId'])) {
                $ret_array[0] = $result['userId'];
            }

            if (isset($result['credentials'])) {
                $ret_array[1] = $result['credentials'];
            }
        }

        return $ret_array;
    }

    /**
     * Returns the name of the authentication provider.
     *
     * @return string  The name of the driver currently providing
     *                 authentication, or false if not set.
     */
    static public function getProvider()
    {
        return empty($_SESSION['horde_auth']['driver'])
            ? false
            : $_SESSION['horde_auth']['driver'];
    }

    /**
     * Reads session data to determine if it contains Horde authentication
     * credentials.
     *
     * @param string $session_data  The session data.
     * @param boolean $info         Return session information.  The following
     *                              information is returned: userid,
     *                              timestamp, remoteAddr, browser.
     *
     * @return array  An array of the user's sesion information if
     *                authenticated or false.  The following information is
     *                returned: userid, timestamp, remoteAddr, browser.
     */
    static public function readSessionData($session_data)
    {
        if (empty($session_data) ||
            (($pos = strpos($session_data, 'horde_auth|')) === false)) {
            return false;
        }

        $endpos = $pos + 7;

        while ($endpos !== false) {
            $endpos = strpos($session_data, '|', $endpos);
            $data = @unserialize(substr($session_data, $pos + 7, $endpos));
            if (is_array($data)) {
                return empty($data)
                    ? false
                    : array(
                        'browser' => $data['browser'],
                        'remoteAddr' => $data['remoteAddr'],
                        'timestamp' => $data['timestamp'],
                        'userid' => $data['userId']
                    );
            }
            ++$endpos;
        }

        return false;
    }

    /**
     * Sets the error message for an invalid authentication.
     *
     * @param string $type  The type of error (self::REASON_* constant).
     * @param string $msg   The error message/reason for invalid
     *                      authentication.
     */
    static public function setAuthError($type, $msg = null)
    {
        self::$_reason = array(
            'msg' => $msg,
            'type' => $type
        );
    }

    /**
     * Returns the error type or message for an invalid authentication.
     *
     * @param boolean $msg  If true, returns the message string (if set).
     *
     * @return mixed  Error type, error message (if $msg is true) or false
     *                if entry doesn't exist.
     */
    static public function getAuthError($msg = false)
    {
        return isset(self::$_reason['type'])
            ? ($msg ? self::$_reason['msg'] : self::$_reason['type'])
            : false;
    }

    /**
     * Converts to allowed 64 characters for APRMD5 passwords.
     *
     * @param string $value   TODO
     * @param integer $count  TODO
     *
     * @return string  $value converted to the 64 MD5 characters.
     */
    static protected function _toAPRMD5($value, $count)
    {
        $aprmd5 = '';
        $count = abs($count);
        $valid = self::APRMD5_VALID;

        while (--$count) {
            $aprmd5 .= $valid[$value & 0x3f];
            $value >>= 6;
        }

        return $aprmd5;
    }

}
