<?php
/**
 * IMP application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with IMP through this API.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

/* Determine the base directories. */
if (!defined('IMP_BASE')) {
    define('IMP_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(IMP_BASE . '/config/horde.local.php')) {
        include IMP_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', IMP_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class IMP_Application extends Horde_Registry_Application
{
    /**
     * Does this application support an ajax view?
     *
     * @var boolean
     */
    public $ajaxView = true;

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
        /* Methods only available if admin config is set for this
         * server/login. */
        if (empty($_SESSION['imp']['imap']['admin'])) {
            $this->disabled = array_merge($this->disabled, array('authAddUser', 'authRemoveUser', 'authUserList'));
        }
    }

    /**
     * Application-specific code to run if application auth fails.
     *
     * @param Horde_Exception $e  The exception object.
     */
    public function appInitFailure($e)
    {
        if (($e->getCode() == Horde_Registry::AUTH_FAILURE) &&
            Horde_Util::getFormData('composeCache')) {
            $imp_compose = $GLOBALS['injector']->getInstance('IMP_Compose')->getOb()->sessionExpireDraft(Horde_Variables::getDefaultVariables());
        }
    }

    /**
     * Initialization function.
     */
    protected function _init()
    {
        /* Add IMP-specific binders. */
        $binders = array(
            'IMP_AuthImap' => 'IMP_Injector_Binder_AuthImap',
            'IMP_Compose' => 'IMP_Injector_Binder_Compose',
            'IMP_Contents' => 'IMP_Injector_Binder_Contents',
            'IMP_Crypt_Pgp' => 'IMP_Injector_Binder_Pgp',
            'IMP_Crypt_Smime' => 'IMP_Injector_Binder_Smime',
            'IMP_Identity' => 'IMP_Injector_Binder_Identity',
            'IMP_Imap' => 'IMP_Injector_Binder_Imap',
            'IMP_Imap_Tree' => 'IMP_Injector_Binder_Imaptree',
            'IMP_Mail' => 'IMP_Injector_Binder_Mail',
            'IMP_Mailbox' => 'IMP_Injector_Binder_Mailbox',
            'IMP_Mime_Viewer' => 'IMP_Injector_Binder_MimeViewer',
            'IMP_Quota' => 'IMP_Injector_Binder_Quota',
            'IMP_Search' => 'IMP_Injector_Binder_Search',
            'IMP_Sentmail' => 'IMP_Injector_Binder_Sentmail'
        );

        foreach ($binders as $key => $val) {
            $GLOBALS['injector']->addOndemandBinder($key, $val);
        }

        // Set default message character set.
        if ($def_charset = $GLOBALS['prefs']->getValue('default_msg_charset')) {
            Horde_Mime_Part::$defaultCharset = $def_charset;
            Horde_Mime_Headers::$defaultCharset = $def_charset;
        }

        IMP::setCurrentMailboxInfo();

        $GLOBALS['notification']->addDecorator(new IMP_Notification_Handler_Decorator_Imap());
        $GLOBALS['notification']->addType('status', 'imp.*', 'IMP_Notification_Event_Status');

        $redirect = false;

        switch (IMP::getViewMode()) {
        case 'dimp':
            $GLOBALS['notification']->addType('status', 'dimp.*', 'IMP_Notification_Event_Status');
            break;

        case 'mimp':
            $redirect = (empty($this->initParams['impmode']) ||
                         ($this->initParams['impmode'] != 'mimp'));
            break;

        case 'imp':
            $redirect = (!empty($this->initParams['impmode']) &&
                         ($this->initParams['impmode'] == 'dimp'));
            $GLOBALS['notification']->attach('audio');
            break;
        }

        if ($redirect && ($GLOBALS['registry']->initialApp == 'imp')) {
            IMP_Auth::getInitialPage(true)->redirect();
        }
    }

    /* Horde permissions. */

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        return array(
            'tree' => array(
                'imp' => array(
                     'create_folders' => false,
                     'max_folders' => false,
                     'max_recipients' => false,
                     'max_timelimit' => false,
                 ),
            ),
            'title' => array(
                'imp:create_folders' => _("Allow Folder Creation?"),
                'imp:max_folders' => _("Maximum Number of Folders"),
                'imp:max_recipients' => _("Maximum Number of Recipients per Message"),
                'imp:max_timelimit' => _("Maximum Number of Recipients per Time Period"),
            ),
            'type' => array(
                'imp:create_folders' => 'boolean',
                'imp:max_folders' => 'int',
                'imp:max_recipients' => 'int',
                'imp:max_timelimit' => 'int',
            )
        );
    }

    /**
     * Returns the specified permission for the given app permission.
     *
     * @param string $permission  The permission to check.
     * @param mixed $allowed      The allowed permissions.
     * @param array $opts         Additional options ('value').
     *
     * @return mixed  The value of the specified permission.
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        switch ($permission) {
        case 'create_folders':
            $allowed = (bool)count(array_filter($allowed));
            break;

        case 'max_folders':
        case 'max_recipients':
        case 'max_timelimit':
            $allowed = max($allowed);
            break;
        }

        return (($permission == 'max_folders') && empty($opts['value']))
            ? $allowed > count($GLOBALS['injector']->getInstance('IMP_Folder')->flist_IMP(array(), false))
            : $allowed;
    }

    /* Horde_Core_Auth_Application methods. */

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
        $js_code = array(
            'ImpLogin.server_key_error' => _("Please choose a mail server.")
        );
        if (!empty($GLOBALS['conf']['user']['select_view'])) {
            if (!($view_cookie = Horde_Util::getFormData('imp_select_view'))) {
                $view_cookie = isset($_COOKIE['default_imp_view'])
                    ? $_COOKIE['default_imp_view']
                    : ($GLOBALS['browser']->isMobile() ? 'mimp' : 'imp');
            }

            $js_code['-ImpLogin.dimp_sel'] = intval($view_cookie == 'dimp');

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
                        'name' => _("Mobile"),
                        'selected' => $view_cookie == 'mimp'
                    )
                )
            );
        }

        return array(
            'js_code' => $js_code,
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
        $this->init();

        $new_session = IMP_Auth::authenticate(array(
            'password' => $credentials['password'],
            'server' => empty($credentials['imp_server_key']) ? IMP_Auth::getAutoLoginServer() : $credentials['imp_server_key'],
            'userId' => $userId
        ));

        if ($new_session) {
            $_SESSION['imp']['cache']['select_view'] = empty($credentials['imp_select_view'])
                ? ''
                : $credentials['imp_select_view'];
        }
    }

    /**
     * Tries to transparently authenticate with the mail server and create a
     * mail session.
     *
     * @param Horde_Core_Auth_Application $auth_ob  The authentication object.
     *
     * @return boolean  Whether transparent login is supported.
     * @throws Horde_Auth_Exception
     */
    public function authTransparent($auth_ob)
    {
        $this->init();
        return IMP_Auth::transparent($auth_ob);
    }

    /**
     * Does necessary authentication tasks reliant on a full IMP environment.
     *
     * @throws Horde_Auth_Exception
     */
    public function authAuthenticateCallback()
    {
        if ($GLOBALS['registry']->getAuth()) {
            $this->init();
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
     * @throws Horde_Auth_Exception
     */
    public function authAddUser($userId, $credentials)
    {
        try {
            $GLOBALS['injector']->getInstance('IMP_AuthImap')->addUser($userId, $credentials);
        } catch (Horde_Exception $e) {
            throw new Horde_Auth_Exception($e);
        } catch (IMP_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * Deletes a user defined by authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Auth_Exception
     */
    public function authRemoveUser($userId)
    {
        try {
            $GLOBALS['injector']->getInstance('IMP_AuthImap')->removeUser($userId);
        } catch (Horde_Exception $e) {
            throw new Horde_Auth_Exception($e);
        } catch (IMP_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * Lists all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function authUserList()
    {
        try {
            return $GLOBALS['injector']->getInstance('IMP_AuthImap')->listUsers();
        } catch (Horde_Exception $e) {
            throw new Horde_Auth_Exception($e);
        } catch (IMP_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /* Preferences display/handling methods. Code is contained in
     * IMP_Prefs_Ui so it doesn't have to be loaded on every page load. */

    /**
     * Populate dynamically-generated preference values.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsEnum($ui)
    {
        $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsEnum($ui);
    }

    /**
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsInit($ui);
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the options page.
     */
    public function prefsSpecial($ui, $item)
    {
        return $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsSpecial($ui, $item);
    }

    /**
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        return $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsSpecialUpdate($ui, $item);
    }

    /**
     * Called when preferences are changed.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsCallback($ui)
    {
        $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsCallback($ui);
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu($ui)
    {
        return $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsMenu($ui);
    }

    /* Sidebar method. */

    /**
     * Add node(s) to the sidebar tree.
     *
     * @param Horde_Tree_Base $tree  Tree object.
     * @param string $parent         The current parent element.
     * @param array $params          Additional parameters.
     *
     * @throws Horde_Exception
     */
    public function sidebarCreate(Horde_Tree_Base $tree, $parent = null,
                                  array $params = array())
    {
        global $injector, $prefs, $registry;

        /* Run filters now */
        if ($prefs->getValue('filter_on_display')) {
            $injector->getInstance('IMP_Filter')->filter('INBOX');
        }

        $tree->addNode(
            strval($parent) . 'compose',
            $parent,
            _("New Message"),
            0,
            false,
            array(
                'icon' => Horde_Themes::img('compose.png'),
                'url' => IMP::composeLink()
            )
        );

        /* Add link to the search page. */
        $tree->addNode(
            strval($parent) . 'search',
            $parent,
            _("Search"),
            0,
            false,
            array(
                'icon' => Horde_Themes::img('search.png'),
                'url' => Horde::url('search.php')
            )
        );

        if ($_SESSION['imp']['protocol'] == 'pop') {
            return;
        }

        $name_url = Horde::url('mailbox.php');

        /* Initialize the IMP_Tree object. */
        $imaptree = $injector->getInstance('IMP_Imap_Tree');
        $imaptree->setIteratorFilter(IMP_Imap_Tree::FLIST_VFOLDER);
        $imaptree->createTree($tree, array(
            'parent' => $parent,
            'poll_info' => true
        ));

        /* We want to rewrite the parent node of the INBOX to include new mail
         * notification. */
        if (!($url = $registry->get('url', $parent))) {
            $url = (($registry->get('status', $parent) == 'heading') || !$registry->get('webroot'))
                ? null
                : $registry->getInitialPage($parent);
        }

        $node_params = array(
            'icon' => $registry->get('icon', $parent),
            'url' => $url
        );
        $name = $registry->get('name', $parent);

        if ($imaptree->unseen) {
            $node_params['icon'] = Horde_Themes::img('newmail.png');
            $name = sprintf('<strong>%s</strong> (%s)', $name, $imaptree->unseen);
        }

        $tree->addNode(
            strval($parent),
            $registry->get('menu_parent', $parent),
            $name,
            0,
            $imaptree->isOpen($parent),
            $node_params
        );
    }

    /* Language change callback. */

    /**
     * Performs tasks necessary when the language is changed during the
     * session.
     */
    public function changeLanguage()
    {
        $this->init();
        $this->mailboxesChanged();
        $GLOBALS['injector']->getInstance('IMP_Search')->init(true);
    }

    /* Helper methods. */

    /**
     * Run tasks when the mailbox list has changed.
     */
    public function mailboxesChanged()
    {
        $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->init();
    }

}
