<?php

require_once dirname(__FILE__) . '/application.php';

/**
 * The Auth_folks class provides a sql implementation of the Horde
 * authentication system with use of folks app
 *
 * $Id: folks.php 930 2008-09-26 09:14:36Z duck $
 *
 * Copyright 2008 Obala d.o.o. (http://www.obala.si/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Duck <duck@obala.net>
 */
class Auth_folks extends Auth_application {

    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    var $capabilities = array('add'           => false,
                              'update'        => false,
                              'resetpassword' => true,
                              'remove'        => false,
                              'list'          => false,
                              'transparent'   => false);

    /**
     * Constructs a new Application authentication object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Auth_folks($params = array())
    {
        $this->_params = array('app' => 'folks');
    }

    /**
     * Returns the URI of the login screen for this authentication object.
     *
     * @access private
     *
     * @param string $app  The application to use.
     * @param string $url  The URL to redirect to after login.
     *
     * @return string  The login screen URI.
     */
    function _getLoginScreen($app = 'folks', $url = '')
    {
        $webroot = $GLOBALS['registry']->get('webroot', 'folks');
        if ($webroot instanceof PEAR_Error) {
            return $webroot;
        }

        $login = Horde::url($webroot . '/login.php', true);
        if (!empty($url)) {
            $login = Util::addParameter($login, 'url', $url);
        }
        return $login;
    }

    /**
     * Checks if $userId exists in the system.
     *
     * @abstract
     *
     * @param string $userId User ID for which to check
     *
     * @return boolean  Whether or not $userId already exists.
     */
    function exists($userId)
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
     * @return mixed  The new password on success or a PEAR_Error object on
     *                failure.
     */
    function resetPassword($userId)
    {
        /* Get a new random password. */
        $password = Auth::genRandomPassword();

        /* Process. */
        $fileroot = $GLOBALS['registry']->get('webroot', 'folks');
        if ($fileroot instanceof PEAR_Error) {
            return $fileroot;
        }

        require_once $fileroot . '/lib/base.php';
        $result = $GLOBALS['folks_driver']->changePassword($password, $userId);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        return $password;
    }

    /**
     * Automatic authentication: Finds out if the client matches an allowed IP
     * block.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    function transparent()
    {
        if (!isset($_COOKIE['folks_login_code']) ||
            !isset($_COOKIE['folks_login_user'])) {
            return false;
        }

        $fileroot = $GLOBALS['registry']->get('webroot', 'folks');
        if ($fileroot instanceof PEAR_Error) {
            return $fileroot;
        }

        require_once $fileroot . '/lib/base.php';
        if ($_COOKIE['folks_login_code'] != $GLOBALS['folks_driver']->getCookie($_COOKIE['folks_login_user'])) {
            return false;
        }

        if ($this->setAuth($_COOKIE['folks_login_user'], array('transparent' => 1, 'password' => null))) {
            $GLOBALS['folks_driver']->resetOnlineUsers();
            return true;
        } else {
            return false;
        }
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