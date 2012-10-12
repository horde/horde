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
    /**
     */
    public $auth = array(
        'add',
        'authenticate',
        'exists',
        'list',
        'remove',
        'resetpassword',
        'transparent'
    );

    /**
     */
    public $version = 'H5 (0.1-git)';

    /**
     */
    protected function _init()
    {
        $links = array(Folks::getUrlFor('feed', 'online') => _("Online users"));
        if ($GLOBALS['registry']->isAuthenticated()) {
            $links[Folks::getUrlFor('feed', 'friends')] = _("Online friends");
            $links[Folks::getUrlFor('feed', 'activity')] = _("Friends activity");
            $links[Folks::getUrlFor('feed', 'know')] = _("People you might know");
        }

        foreach ($links as $url => $label) {
            $GLOBALS['page_output']->addLinkTag(array(
                'href' => $url,
                'title' => $label
            ));
        }
    }

    /**
     */
    public function menu($menu)
    {
        $menu->add(self::getUrlFor('user', $GLOBALS['registry']->getAuth()), _("My profile"), 'myaccount.png');
        $menu->add(self::getUrlFor('list', 'friends'), _("Friends"), 'group.png');
        $menu->add(Horde::url('edit/edit.php'), _("Edit profile"), 'edit.png');
        $menu->add(Horde::url('services.php'), _("Services"), 'horde.png');
        $menu->add(Horde::url('search.php'), _("Search"), 'search.png');
        $menu->add(self::getUrlFor('list', 'online'), _("List"), 'group.png');
    }

    /**
     * @param array $credentials  Array of criedentials (password requied)
     */
    public function authAuthenticate($userID, $credentials)
    {
        require_once __DIR__ . '/base.php';

        $result = $GLOBALS['folks_driver']->comparePassword($userID, $credentials['password']);
        if ($result !== true) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

    /**
     */
    public function authTransparent($auth_ob)
    {
        if (empty($_COOKIE['folks_login_code']) ||
            empty($_COOKIE['folks_login_user'])) {
            return false;
        }

        require_once __DIR__ . '/base.php';
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
     */
    public function authUserExists($userId)
    {
        require_once __DIR__ . '/base.php';

        return $GLOBALS['folks_driver']->userExists($userId);
    }

    /**
     */
    public function authUserList()
    {
        require_once __DIR__ . '/base.php';

        $users = array();
        foreach ($GLOBALS['folks_driver']->getUsers() as $user) {
            $users[] = $user['user_uid'];
        }

        return $users;
    }

    /**
     */
    public function authAddUser($userId, $credentials)
    {
        require_once __DIR__ . '/base.php';

        $result = $GLOBALS['folks_driver']->addUser($userId, $credentials);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Exception_Wrapped($result);
        }
    }

    /**
     */
    public function authResetPassword($userId)
    {
        /* Get a new random password. */
        $password = Horde_Auth::genRandomPassword();

        /* Update password in DB. */
        require_once __DIR__ . '/base.php';
        $result = $GLOBALS['folks_driver']->changePassword($password, $userId);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Auth_Exception($result);
        }

        return $password;
    }

    /**
     */
    public function authRemoveUser($userId)
    {
        require_once __DIR__ . '/base.php';

        return $GLOBALS['folks_driver']->deleteUser($userId);
    }

    /**
     */
    public function removeUserData($user = null)
    {
        return $this->authRemoveUser($user);
    }

}
