<?php
/**
 * Folks application API.
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/folks/LICENSE.
 *
 * @package Folks
 */
class Folks_Application extends Horde_Registry_Application
{
    public $version = 'H4 (0.1-git)';

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        return Folks::getMenu();
    }

    /**
     * Authenticate a givern user
     *
     * @param string $userID       Username
     * @param array  $credentials  Array of criedentials (password requied)
     *
     * @return boolean  Whether Folks authentication was successful.
     */
    public function authAuthenticate($userID, $credentials)
    {
        require_once dirname(__FILE__) . '/base.php';

        $result = $GLOBALS['folks_driver']->comparePassword($userID, $credentials['password']);
        if ($result !== true) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

    /**
     * Tries to transparently authenticate
     *
     * @param Horde_Auth_Application $auth_ob  The authentication object.
     *
     * @return boolean  Whether transparent login is supported.
     * @throws Horde_Auth_Exception
     */
    public function authTransparent($auth_ob)
    {
        if (empty($_COOKIE['folks_login_code']) ||
            empty($_COOKIE['folks_login_user'])) {
            return false;
        }

        require_once dirname(__FILE__) . '/base.php';
        $GLOBALS['folks_driver'] = Folks_Driver::factory();
        if ($_COOKIE['folks_login_code'] == $GLOBALS['folks_driver']->getCookie($_COOKIE['folks_login_user'])) {
            $GLOBALS['registry']->setAuth($_COOKIE['folks_login_user']);
            $auth_ob->setCredential('userId', $_COOKIE['folks_login_user']);
            $GLOBALS['folks_driver']->resetOnlineUsers();
            return true;
        }  else {
            return false;
        }
    }

    /**
     * Check if a user exists
     *
     * @param string $userID       Username
     *
     * @return boolean  True if user exists
     */
    public function authUserExists($userId)
    {
        require_once dirname(__FILE__) . '/base.php';

        return $GLOBALS['folks_driver']->userExists($userId);
    }

    /**
     * Lists all users in the system.
     *
     * @return array  The array of userIds, or a PEAR_Error object on failure.
     */
    public function authUserList()
    {
        require_once dirname(__FILE__) . '/base.php';

        $users = array();
        foreach ($GLOBALS['folks_driver']->getUsers() as $user) {
            $users[] = $user['user_uid'];
        }

        return $users;
    }

    /**
     * Adds a set of authentication credentials.
     *
     * @param string $userId  The userId to add.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Exception
     */
    public function authAddUser($userId, $credentials)
    {
        require_once dirname(__FILE__) . '/base.php';

        $result = $GLOBALS['folks_driver']->addUser($userId, $credentials);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception_Prior($result);
        }
    }

    /**
     * Reset a user's password. Used for example when the user does not
     * remember the existing password.
     *
     * @param string $userId  The user id for which to reset the password.
     *
     * @return string  The new password on success.
     * @throws Horde_Auth_Exception
     */
    public function authResetPassword($userId)
    {
        /* Get a new random password. */
        $password = Horde_Auth::genRandomPassword();

        /* Update password in DB. */
        require_once dirname(__FILE__) . '/base.php';
        $result = $GLOBALS['folks_driver']->changePassword($password, $userId);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Auth_Exception($result);
        }

        return $password;
    }

    /**
     * Deletes a set of authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @return boolean  True on success or a PEAR_Error object on failure.
     */
    public function authRemoveUser($userId)
    {
        require_once dirname(__FILE__) . '/base.php';

        return $GLOBALS['folks_driver']->deleteUser($userId);
    }

    /**
     * Deletes a user and its data
     *
     * @param string $userId  The userId to delete.
     *
     * @return boolean  True on success or a PEAR_Error object on failure.
     */
    public function removeUserData($user = null)
    {
        return $this->authRemoveUser($user);
    }

}
