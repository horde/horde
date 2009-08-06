<?php
/**
 * IMP application API.
 *
 * This file defines IMP's external API interface. Other applications
 * can interact with IMP through this API.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package IMP
 */
class IMP_Application extends Horde_Registry_Application
{
    /**
     * Does this application support a mobile view?
     *
     * @var boolean
     */
    public $mobileView = true;

    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (5.0-git)';

    /**
     * Constructor.
     */
    public function __construct()
    {
        /* Only available if admin config is set for this server/login. */
        if (empty($_SESSION['imp']['admin'])) {
            $this->disabled = array('authAddUser', 'authRemoveUser', 'authUserList');
        }
    }

    /**
     * Return login parameters used on the login page.
     *
     * @return array  TODO
     */
    public function authLoginParams()
    {
        $params = array();

        if ($GLOBALS['conf']['server']['server_list'] == 'shown') {
            $servers = IMP_Imap::loadServerConfig();
            $server_list = array();
            $selected = Horde_Util::getFormData('imp_server_key', IMP_Auth::getAutoLoginServer());
            foreach ($servers as $key => $val) {
                $server_list[$key] = array(
                    'name' => $val['name'],
                    'selected' => ($selected == $key)
                );
            }
            $params['imp_server_key'] = array(
                'label' => _("Server"),
                'type' => 'select',
                'value' => $server_list
            );
        }

        /* Show selection of alternate views. */
        if (!empty($GLOBALS['conf']['user']['select_view'])) {
            $views = array();
            if (!($view_cookie = Horde_Util::getFormData('imp_select_view'))) {
                if (isset($_COOKIE['default_imp_view'])) {
                    $view_cookie = $_COOKIE['default_imp_view'];
                } else {
                    $browser = Horde_Browser::singleton();
                    $view_cookie = $browser->isMobile() ? 'mimp' : 'imp';
                }
            }

            $params['imp_select_view'] = array(
                'label' => _("Mode"),
                'type' => 'select',
                'value' => array(
                    'imp' => array(
                        'name' => _("Traditional"),
                        'selected' => $view_cookie == 'imp'
                    ),
                    'dimp' => array(
                        'hidden' => true,
                        'name' => _("Dynamic")
                        // Dimp selected is handled by javascript (dimp_sel)
                    ),
                    'mimp' => array(
                        'name' => _("Minimalist"),
                        'selected' => $view_cookie == 'mimp'
                    )
                )
            );
        }

        return array(
            'js_code' => array(
                'ImpLogin.dimp_sel=' . intval($view_cookie == 'dimp'),
                'ImpLogin.server_key_error=' . Horde_Serialize::serialize(_("Please choose a mail server."), Horde_Serialize::JSON)
            ),
            'js_files' => array(
                array('login.js', 'imp')
            ),
            'params' => $params
        );
    }

    /**
     * Tries to authenticate with the mail server and create a mail session.
     *
     * @param string $userId      The username of the user.
     * @param array $credentials  Credentials of the user. Allowed keys:
     *                            'imp_select_view', 'imp_server_key',
     *                            'password'.
     *
     * @throws Horde_Auth_Exception
     */
    public function authAuthenticate($userId, $credentials)
    {
        $GLOBALS['imp_authentication'] = 'none';
        require_once dirname(__FILE__) . '/base.php';

        $new_session = IMP_Auth::authenticate(array(
            'password' => $credentials['password'],
            'server' => empty($credentials['imp_server_key']) ? IMP_Auth::getAutoLoginServer() : $credentials['imp_server_key'],
            'userId' => $userId
        ));

        if ($new_session) {
            $_SESSION['imp']['cache']['select_view'] = empty($credentials['imp_select_view'])
                ? ''
                : $credentials['imp_select_view'];

            /* Set the Horde ID, since it may have been altered by the 'realm'
             * setting. */
            $credentials['auth_ob']->setCredential('userId', $_SESSION['imp']['uniquser']);
        }
    }

    /**
     * Tries to transparently authenticate with the mail server and create a
     * mail session.
     *
     * @return boolean  Whether transparent login is supported.
     * @throws Horde_Auth_Exception
     */
    public function authTransparent()
    {
        /* Transparent auth is a bit goofy - we most likely have reached this
         * code from the pushApp() call in base.php already. As such, some of
         * the IMP init has not yet been done, so we need to do the necessary
         * init here or else things will fail in IMP_Auth. */
        $GLOBALS['imp_authentication'] = 'none';
        require_once dirname(__FILE__) . '/base.php';
        IMP::initialize();
        return IMP_Auth::transparent();
    }

    /**
     * Does necessary authentication tasks reliant on a full IMP environment.
     *
     * @throws Horde_Auth_Exception
     */
    public function authAuthenticateCallback()
    {
        if (Horde_Auth::getAuth()) {
            require_once dirname(__FILE__) . '/base.php';
            IMP_Auth::authenticateCallback();
        }
    }

    /**
     * Adds a user defined by authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  An array of login credentials. For IMAP,
     *                            this must contain a password entry.
     *
     * @throws Horde_Exception
     */
    public function authAddUser($userId, $credentials)
    {
        $params = $GLOBALS['registry']->callByPackage('imp', 'server');
        if (is_null($params)) {
            return;
        }

        $params = array_merge($params, $_SESSION['imp']['admin']['params']);
        if (isset($params['admin_password'])) {
            $params['admin_password'] = Horde_Secret::read(Horde_Secret::getKey('imp'), $params['admin_password']);
        }
        $auth = Horde_Auth::singleton('imap', $params);
        $auth->addUser($userId, $credentials);
    }

    /**
     * Deletes a user defined by authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Exception
     */
    public function authRemoveUser($userId)
    {
        $params = $GLOBALS['registry']->callByPackage('imp', 'server');
        if (is_null($params)) {
            return;
        }

        $params = array_merge($params, $_SESSION['imp']['admin']['params']);
        if (isset($params['admin_password'])) {
            $params['admin_password'] = Horde_Secret::read(Horde_Secret::getKey('imp'), $params['admin_password']);
        }
        $auth = Horde_Auth::singleton('imap', $params);
        $auth->removeUser($userId);
    }

    /**
     * Lists all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Exception
     */
    public function authUserList()
    {
        $params = $GLOBALS['registry']->callByPackage('imp', 'server');
        if (is_null($params)) {
            return;
        }

        $params = array_merge($params, $_SESSION['imp']['admin']['params']);
        if (isset($params['admin_password'])) {
            $params['admin_password'] = Horde_Secret::read(Horde_Secret::getKey('imp'), $params['admin_password']);
        }
        $auth = Horde_Auth::singleton('imap', $params);
        return $auth->listUsers();
    }

}
