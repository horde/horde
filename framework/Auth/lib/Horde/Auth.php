<?php
/**
 * The Horde_Auth:: class provides a common abstracted interface into the
 * various backends for the Horde authentication system.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Auth
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
                : '$1$' . substr(hash('md5', mt_rand()), 0, 8) . '$';

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

        foreach ($GLOBALS['registry']->listApps(array('notoolbar', 'hidden', 'active', 'admin')) as $app) {
            try {
                $GLOBALS['registry']->callByPackage($app, 'removeUserData', array($userId));
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
     * @param string $realm  The authentication realm to check.
     *
     * @return boolean  Whether or not the user is authenticated.
     */
    static public function isAuthenticated($realm = null)
    {
        if (isset($_SESSION['horde_auth']) &&
            !empty($_SESSION['horde_auth']['authenticated']) &&
            !empty($_SESSION['horde_auth']['userId']) &&
            ($_SESSION['horde_auth']['realm'] == $realm)) {
            if (!self::checkSessionIP()) {
                self::setAuthError(self::REASON_SESSIONIP);
                return false;
            } elseif (!self::checkBrowserString()) {
                self::setAuthError(self::REASON_BROWSER);
                return false;
            }
            return true;
        }

        // Try transparent authentication now.
        $auth = self::singleton($GLOBALS['conf']['auth']['driver']);
        if ($auth->hasCapability('transparent') && $auth->transparent()) {
            return self::isAuthenticated($realm);
        }

        return false;
    }

    /**
     * Returns the currently logged in user, if there is one.
     *
     * @return mixed  The userId of the current user, or false if no user is
     *                logged in.
     */
    static public function getAuth()
    {
        if (isset($_SESSION['horde_auth'])) {
            if (!empty($_SESSION['horde_auth']['authenticated']) &&
                !empty($_SESSION['horde_auth']['userId'])) {
                return $_SESSION['horde_auth']['userId'];
            }
        }

        return false;
    }

    /**
     * Return whether the authentication backend requested a password change.
     *
     * @return boolean Whether the backend requested a password change.
     */
    static public function isPasswordChangeRequested()
    {
        return (isset($_SESSION['horde_auth']) &&
                !empty($_SESSION['horde_auth']['authenticated']) &&
                !empty($_SESSION['horde_auth']['changeRequested']));
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
        if ($user) {
            $pos = strpos($user, '@');
            if ($pos !== false) {
                $user = substr($user, 0, $pos);
            }
        }

        return $user;
    }

    /**
     * Returns the domain of currently logged-in user (e.g., bob@example.com
     * would be returned as 'example.com').
     *
     * @return mixed  The domain suffix of the current user, or false.
     */
    static public function getAuthDomain()
    {
        if ($user = self::getAuth()) {
            $pos = strpos($user, '@');
            if ($pos !== false) {
                return substr($user, $pos + 1);
            }
        }

        return false;
    }

    /**
     * Returns the requested credential for the currently logged in user, if
     * present.
     *
     * @param string $credential  The credential to retrieve.
     *
     * @return mixed  The requested credential, or false if no user is
     *                logged in.
     */
    static public function getCredential($credential)
    {
        if (empty($_SESSION['horde_auth']) ||
            empty($_SESSION['horde_auth']['authenticated'])) {
            return false;
        }

        $credentials = Horde_Secret::read(Horde_Secret::getKey('auth'), $_SESSION['horde_auth']['credentials']);
        $credentials = @unserialize($credentials);

        if (is_array($credentials) &&
            isset($credentials[$credential])) {
            return $credentials[$credential];
        }

        return false;
    }

    /**
     * Sets the requested credential for the currently logged in user.
     *
     * @param string $credential  The credential to set.
     * @param string $value       The value to set the credential to.
     */
    static public function setCredential($credential, $value)
    {
        if (!empty($_SESSION['horde_auth']) &&
            !empty($_SESSION['horde_auth']['authenticated'])) {
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
     * @param string $realm       The authentication realm to use.
     * @param boolean $change     Whether to request that the user change
     *                            their password.
     */
    static public function setAuth($userId, $credentials, $realm = null,
                                   $change = false)
    {
        $userId = self::addHook(trim($userId));

        if (!empty($GLOBALS['conf']['hooks']['postauthenticate'])) {
            if (Horde::callHook('_horde_hook_postauthenticate', array($userId, $credentials, $realm), 'horde') === false) {
                if (self::getAuthError() != self::REASON_MESSAGE) {
                    self::setAuthError(self::REASON_FAILED);
                }
                return false;
            }
        }

        /* If we're already set with this userId, don't continue. */
        if (isset($_SESSION['horde_auth']['userId']) &&
            ($_SESSION['horde_auth']['userId'] == $userId)) {
            return true;
        }

        /* Clear any existing info. */
        self::clearAuth($realm);

        $credentials = Horde_Secret::write(Horde_Secret::getKey('auth'), serialize($credentials));

        if (!empty($realm)) {
            $userId .= '@' . $realm;
        }

        $browser = Horde_Browser::singleton();

        $_SESSION['horde_auth'] = array(
            'authenticated' => true,
            'browser' => $browser->getAgentString(),
            'changeRequested' => $change,
            'credentials' => $credentials,
            'realm' => $realm,
            'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
            'timestamp' => time(),
            'userId' => $userId
        );

        /* Reload preferences for the new user. */
        $GLOBALS['registry']->loadPrefs();
        Horde_Nls::setLang($GLOBALS['prefs']->getValue('language'));

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
            $response = $resolver->query($host, 'PTR');
            $ptrdname = $response ? $response->answer[0]->ptrdname : $host;
        } else {
            $ptrdname = @gethostbyaddr($host);
        }

        $last_login = array('time' => time(), 'host' => $ptrdname);
        $GLOBALS['prefs']->setValue('last_login', serialize($last_login));

        if ($change) {
            $GLOBALS['notification']->push(_("Your password has expired."),
                                           'horde.message');

            $auth = self::singleton($GLOBALS['conf']['auth']['driver']);
            if ($auth->hasCapability('update')) {
                /* A bit of a kludge.  URL is set from the login screen, but
                 * we aren't completely certain we got here from the login
                 * screen.  So any screen which calls setAuth() which has a
                 * url will end up going there.  Should be OK. */
                $url_param = Horde_Util::getFormData('url');

                if ($url_param) {
                    $url = Horde::url(Horde_Util::removeParameter($url_param, session_name()), true);
                    $return_to = $GLOBALS['registry']->get('webroot', 'horde') .  '/index.php';
                    $return_to = Horde_Util::addParameter($return_to, 'url', $url);
                } else {
                    $return_to = Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/index.php');
                }

                $url = Horde::applicationUrl('services/changepassword.php');
                $url = Horde_Util::addParameter($url, array('return_to' => $return_to), null, false);

                header('Location: ' . $url);
                exit;
            }
        }

        return true;
    }

    /**
     * Clears any authentication tokens in the current session.
     *
     * @param string $realm  The authentication realm to clear.
     */
    static public function clearAuth($realm = null)
    {
        if (!empty($realm) && isset($_SESSION['horde_auth'][$realm])) {
            $_SESSION['horde_auth'][$realm] = array('authenticated' => false);
        } elseif (isset($_SESSION['horde_auth'])) {
            $_SESSION['horde_auth'] = array('authenticated' => false);
        }

        /* Remove the user's cached preferences if they are present. */
        if (isset($GLOBALS['registry'])) {
            $GLOBALS['registry']->unloadPrefs();
        }
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
     * Applies a hook defined by the function _username_hook_frombackend() to
     * the given user name if this function exists and user hooks are enabled.
     *
     * This method should be called if a authentication backend's user name
     * needs to be converted to a (unique) Horde user name. The backend's user
     * name is what the user sees and uses, but internally we use the Horde
     * user name.
     *
     * @param string $userId  The authentication backend's user name.
     *
     * @return string  The internal Horde user name.
     */
    static public function addHook($userId)
    {
        return empty($GLOBALS['conf']['hooks']['username'])
            ? $userId
            : Horde::callHook('_username_hook_frombackend', array($userId));
    }

    /**
     * Applies a hook defined by the function _username_hook_tobackend() to
     * the given user name if this function exists and user hooks are enabled.
     *
     * This method should be called if a Horde user name needs to be converted
     * to an authentication backend's user name or displayed to the user. The
     * backend's user name is what the user sees and uses, but internally we
     * use the Horde user name.
     *
     * @param string $userId  The internal Horde user name.
     *
     * @return string  The authentication backend's user name.
     */
    static public function removeHook($userId)
    {
        return empty($GLOBALS['conf']['hooks']['username'])
            ? $userId
            : Horde::callHook('_username_hook_tobackend', array($userId));
    }

    /**
     * Returns the name of the authentication provider.
     *
     * @param string $driver  Used by recursive calls when untangling composite
     *                        auth.
     * @param array $params   Used by recursive calls when untangling composite
     *                        auth.
     *
     * @return string  The name of the driver currently providing
     *                 authentication.
     */
    static public function getProvider($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['auth']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('auth', is_array($driver) ? $driver[1] : $driver);
        }

        if ($driver == 'application') {
            return isset($params['app']) ? $params['app'] : 'application';
        } elseif ($driver == 'composite') {
            if (($login_driver = self::getDriverByParam('loginscreen_switch', $params)) &&
                !empty($params['drivers'][$login_driver])) {
                return self::getProvider($params['drivers'][$login_driver]['driver'],
                                         isset($params['drivers'][$login_driver]['params']) ? $params['drivers'][$login_driver]['params'] : null);
            }
            return 'composite';
        } else {
            return $driver;
        }
    }

    /**
     * Returns the logout reason.
     *
     * @return string One of the logout reasons (see the AUTH_LOGOUT_*
     *                constants for the valid reasons).  Returns null if there
     *                is no logout reason present.
     */
    static public function getLogoutReason()
    {
        return isset($GLOBALS['horde_auth']['logout']['type'])
            ? $GLOBALS['horde_auth']['logout']['type']
            : Horde_Util::getFormData(self::REASON_PARAM);
    }

    /**
     * Returns the status string to use for logout messages.
     *
     * @return string  The logout reason string.
     */
    static public function getLogoutReasonString()
    {
        switch (self::getLogoutReason()) {
        case self::REASON_SESSION:
            return sprintf(_("Your %s session has expired. Please login again."), $GLOBALS['registry']->get('name'));

        case self::REASON_SESSIONIP:
            return sprintf(_("Your Internet Address has changed since the beginning of your %s session. To protect your security, you must login again."), $GLOBALS['registry']->get('name'));

        case self::REASON_BROWSER:
            return sprintf(_("Your browser appears to have changed since the beginning of your %s session. To protect your security, you must login again."), $GLOBALS['registry']->get('name'));

        case self::REASON_LOGOUT:
            return _("You have been logged out.");

        case self::REASON_FAILED:
            return _("Login failed.");
            break;

        case self::REASON_BADLOGIN:
            return _("Login failed because your username or password was entered incorrectly.");
            break;

        case self::REASON_EXPIRED:
            return _("Your login has expired.");
            break;

        case self::REASON_MESSAGE:
            return isset($GLOBALS['horde_auth']['logout']['msg'])
                ? $GLOBALS['horde_auth']['logout']['msg']
                : Horde_Util::getFormData(self::REASON_MSG_PARAM);

        default:
            return '';
        }
    }

    /**
     * Generates the correct parameters to pass to the given logout URL.
     *
     * If no reason/msg is passed in, use the current global authentication
     * error message.
     *
     * @param string $url     The URL to redirect to.
     * @param string $reason  The reason for logout.
     * @param string $msg     If reason is self::REASON_MESSAGE, the message to
     *                        display to the user.
     *
     * @return string The formatted URL
     */
    static public function addLogoutParameters($url, $reason = null,
                                               $msg = null)
    {
        $params = array('horde_logout_token' => Horde::getRequestToken('horde.logout'));

        if (isset($GLOBALS['registry'])) {
            $params['app'] = $GLOBALS['registry']->getApp();
        }

        if (is_null($reason)) {
            $reason = self::getLogoutReason();
        }

        if ($reason) {
            $params[self::REASON_PARAM] = $reason;
            if ($reason == self::REASON_MESSAGE) {
                if (is_null($msg)) {
                    $msg = self::getLogoutReasonString();
                }
                $params[self::REASON_MSG_PARAM] = $msg;
            }
        }

        return Horde_Util::addParameter($url, $params, null, false);
    }

    /**
     * Reads session data to determine if it contains Horde authentication
     * credentials.
     *
     * @param string $session_data  The session data.
     * @param boolean $info         Return session information.  The following
     *                              information is returned: userid, realm,
     *                              timestamp, remote_addr, browser.
     *
     * @return array  An array of the user's sesion information if
     *                authenticated or false.  The following information is
     *                returned: userid, realm, timestamp, remote_addr, browser.
     */
    static public function readSessionData($session_data)
    {
        if (empty($session_data)) {
            return false;
        }

        $pos = strpos($session_data, 'horde_auth|');
        if ($pos === false) {
            return false;
        }

        $endpos = $pos + 7;
        $old_error = error_reporting(0);

        while ($endpos !== false) {
            $endpos = strpos($session_data, '|', $endpos);
            $data = unserialize(substr($session_data, $pos + 7, $endpos));
            if (is_array($data)) {
                error_reporting($old_error);
                if (empty($data['authenticated'])) {
                    return false;
                }
                return array(
                    'browser' => $data['browser'],
                    'realm' => $data['realm'],
                    'remote_addr' => $data['remote_addr'],
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
    public function setAuthError($type, $msg = null)
    {
        $GLOBALS['horde_auth']['logout'] = array(
            'msg' => $msg,
            'type' => $type
        );
    }

    /**
     * Returns the error type for an invalid authentication or false on error.
     *
     * @return mixed  Error type or false on error.
     */
    public function getAuthError()
    {
        return isset($GLOBALS['horde_auth']['logout']['type'])
            ? $GLOBALS['horde_auth']['logout']['type']
            : false;
    }

    /**
     * Returns the appropriate authentication driver, if any, selecting by the
     * specified parameter.
     *
     * @param string $name          The parameter name.
     * @param array $params         The parameter list.
     * @param string $driverparams  A list of parameters to pass to the driver.
     *
     * @return mixed Return value or called user func or null if unavailable
     */
    public function getDriverByParam($name, $params,
                                     $driverparams = array())
    {
        if (isset($params[$name]) &&
            function_exists($params[$name])) {
            return call_user_func_array($params[$name], $driverparams);
        }

        return null;
    }

    /**
     * Performs check on session to see if IP Address has changed since the
     * last access.
     *
     * @return boolean  True if IP Address is the same (or the check is
     *                  disabled), false if the address has changed.
     */
    static public function checkSessionIP()
    {
        return (empty($GLOBALS['conf']['auth']['checkip']) ||
                (isset($_SESSION['horde_auth']['remote_addr']) &&
                 ($_SESSION['horde_auth']['remote_addr'] == $_SERVER['REMOTE_ADDR'])));
    }

    /**
     * Performs check on session to see if browser string has changed since
     * the last access.
     *
     * @return boolean  True if browser string is the same, false if the
     *                  string has changed.
     */
    static public function checkBrowserString()
    {
        return (empty($GLOBALS['conf']['auth']['checkbrowser']) ||
                ($_SESSION['horde_auth']['browser'] == $GLOBALS['browser']->getAgentString()));
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
