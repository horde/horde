<?php
/**
 * The Folks_Auth_Folks class provides a sql implementation of the Horde
 * authentication system with use of folks app.
 *
 * $Horde$
 *
 * Copyright 2008 Obala d.o.o. (http://www.obala.si/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Duck <duck@obala.net>
 */
class Folks_Auth_Folks extends Horde_Auth_Application
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $capabilities = array(
        'resetpassword' => true
    );

    /**
     * Constructs a new Application authentication object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $params['app'] = 'folks';
        parent::__construct($params);
    }

    /**
     * Returns the URI of the login screen for this authentication object.
     *
     * @param string $app  The application to use.
     * @param string $url  The URL to redirect to after login.
     *
     * @return string  The login screen URI.
     */
    public function getLoginScreen($app = 'folks', $url = '')
    {
        $webroot = $GLOBALS['registry']->get('webroot', 'folks');
        if ($webroot instanceof PEAR_Error) {
            return $webroot;
        }

        $login = Horde::url($webroot . '/login.php', true);
        if (!empty($url)) {
            $login = Horde_Util::addParameter($login, 'url', $url);
        }
        return $login;
    }

    /**
     * Checks if $userId exists in the system.
     *
     * @param string $userId User ID for which to check
     *
     * @return boolean  Whether or not $userId already exists.
     */
    public function exists($userId)
    {
        return $GLOBALS['registry']->callByPackage('folks',
                                                    'userExists',
                                                    array('userId' => $userId));
    }

    /**
     * Resets a user's password. Used for example when the user does not
     * remember the existing password.
     *
     * @param string $userId  The user id for which to reset the password.
     *
     * @return string  The new password on success.
     * @throws Horde_Exception
     */
    public function resetPassword($userId)
    {
        /* Get a new random password. */
        $password = Horde_Auth::genRandomPassword();

        /* Process. */
        $fileroot = $GLOBALS['registry']->get('fileroot', 'folks');
        if ($fileroot instanceof PEAR_Error) {
            throw new Horde_Exception($fileroot);
        }

        require_once $fileroot . '/lib/base.php';
        $result = $GLOBALS['folks_driver']->changePassword($password, $userId);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception($result);
        }

        return $password;
    }

    /**
     * Automatic authentication: Finds out if the client matches an allowed IP
     * block.
     *
     * @return boolean
     * @throws Horde_Exception
     */
    protected function _transparent()
    {
        if (!isset($_COOKIE['folks_login_code']) ||
            !isset($_COOKIE['folks_login_user'])) {
            return false;
        }

        $fileroot = $GLOBALS['registry']->get('webroot', 'folks');
        if ($fileroot instanceof PEAR_Error) {
            throw new Horde_Exception($fileroot);
        }

        require_once $fileroot . '/lib/base.php';
        if ($_COOKIE['folks_login_code'] != $GLOBALS['folks_driver']->getCookie($_COOKIE['folks_login_user'])) {
            return false;
        }

        if ($this->setAuth($_COOKIE['folks_login_user'], array('transparent' => 1, 'password' => null))) {
            $GLOBALS['folks_driver']->resetOnlineUsers();
            return true;
        }

        return false;
    }

    /**
    function transparent()
    {
        if (!isset($_COOKIE['folks_login_code']) ||
            !isset($_COOKIE['folks_login_user'])) {
            return false;
        }

        $conn = mysql_connect($GLOBALS['conf']['sql']['hostspec'],
                                $GLOBALS['conf']['sql']['username'],
                                $GLOBALS['conf']['sql']['password']);

        $query = 'SELECT user_password FROM '
                    . $GLOBALS['conf']['sql']['database']
                    . '.folks_users WHERE user_uid = "'
                    . $_COOKIE['folks_login_user'] . '"';

        $result = mysql_query($query);
        $r = mysql_fetch_assoc($result);

        if (mysql_num_rows($result) == 0) {
            return false;
        }

        require_once $GLOBALS['registry']->get('fileroot', 'folks') . '/lib/Folks.php';
        $key = date('m') . $r['user_password'];

        if ($_COOKIE['folks_login_code'] != Folks::encodeString($_COOKIE['folks_login_user'], $key)) {
            return false;
        }

        if ($this->setAuth($_COOKIE['folks_login_user'], array('transparent' => 1, 'password' => null))) {

            $sql = 'REPLACE INTO ' . $GLOBALS['conf']['sql']['database']
                    . '.folks_online SET user_uid="'
                    . $_COOKIE['folks_login_user']
                    . '", ip_address="' . $_SERVER["REMOTE_ADDR"]
                    . '", time_last_click="' . $_SERVER['REQUEST_TIME'] . '"';
            mysql_unbuffered_query($sql, $conn);

            return true;

        } else {
            return false;
        }
    }
    */
}
