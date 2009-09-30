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
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
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
     *
     * @return boolean  True on success or a PEAR_Error object on failure.
     */
    public function authAddUser($userId)
    {
        require_once dirname(__FILE__) . '/base.php';

        return $GLOBALS['folks_driver']->addUser($userId);
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
