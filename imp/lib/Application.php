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
     * Cached values to add to the session after authentication.
     *
     * @var array
     */
    protected $_cacheSess = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        /* Methods only available if admin config is set for this
         * server/login. */
        if (!$GLOBALS['session']->get('imp', 'imap_admin')) {
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
            $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Compose')->create()->sessionExpireDraft(Horde_Variables::getDefaultVariables());
        }
    }

    /**
     * Initialization function.
     */
    protected function _init()
    {
        /* Add IMP-specific factories. */
        $factories = array(
            'IMP_AuthImap' => 'IMP_Injector_Factory_AuthImap',
            'IMP_Crypt_Pgp' => 'IMP_Injector_Factory_Pgp',
            'IMP_Crypt_Smime' => 'IMP_Injector_Factory_Smime',
            'IMP_Identity' => 'IMP_Injector_Factory_Identity',
            'IMP_Imap_Tree' => 'IMP_Injector_Factory_Imaptree',
            'IMP_Mail' => 'IMP_Injector_Factory_Mail',
            'IMP_Quota' => 'IMP_Injector_Factory_Quota',
            'IMP_Search' => 'IMP_Injector_Factory_Search',
            'IMP_Sentmail' => 'IMP_Injector_Factory_Sentmail'
        );

        foreach ($factories as $key => $val) {
            $GLOBALS['injector']->bindFactory($key, $val, 'create');
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
            $redirect = (!empty($this->initParams['impmode']) &&
                         ($this->initParams['impmode'] != 'dimp'));
            $GLOBALS['notification']->addType('status', 'dimp.*', 'IMP_Notification_Event_Status');
            break;

        case 'mimp':
            $redirect = (empty($this->initParams['impmode']) ||
                         ($this->initParams['impmode'] != 'mimp'));
            break;

        case 'mobile':
            $redirect = (!empty($this->initParams['impmode']) &&
                         ($this->initParams['impmode'] != 'mobile'));
            break;

        case 'imp':
            $redirect = (!empty($this->initParams['impmode']) &&
                         ($this->initParams['impmode'] != 'imp'));
            $GLOBALS['notification']->attach('audio');
            break;
        }

        if ($redirect && ($GLOBALS['registry']->initialApp == 'imp')) {
            IMP_Auth::getInitialPage(true)->redirect();
        }
    }

    /**
     * Tasks to perform at logout.
     */
    public function logout()
    {
        /* Clean up dangling IMP_Compose objects. */
        foreach (array_keys($GLOBALS['session']->get('imp', 'compose_cache', Horde_Session::TYPE_ARRAY)) as $key) {
            $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Compose')->create($key)->destroy('cancel');
        }

        /* No need to keep Tree object in cache - it will be recreated next
         * login. */
        if ($treeob = $GLOBALS['session']->get('imp', 'treeob')) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire($treeob);
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
            'create_folders' => array(
                'title' => _("Allow Folder Creation?"),
                'type' => 'boolean'
            ),
            'max_folders' => array(
                'title' => _("Maximum Number of Folders"),
                'type' => 'int'
            ),
            'max_recipients' => array(
                'title' => _("Maximum Number of Recipients per Message"),
                'type' => 'int'
            ),
            'max_timelimit' => array(
                'title' => _("Maximum Number of Recipients per Time Period"),
                'type' => 'int'
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
            $allowed = max($allowed);
            if (empty($opts['value'])) {
                return ($allowed > count($GLOBALS['injector']->getInstance('IMP_Folder')->flist_IMP(array(), false)));
            }
            break;

        case 'max_recipients':
        case 'max_timelimit':
            $allowed = max($allowed);
            break;
        }

        return $allowed;
    }

    /* Menu methods. */

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        global $conf, $injector, $prefs, $registry;

        $menu_mailbox_url = Horde::url('mailbox.php');

        $menu->addArray(array(
            'icon' => 'folders/inbox.png',
            'text' => _("_Inbox"),
            'url' => IMP::generateIMPUrl($menu_mailbox_url, 'INBOX')
        ));

        if ($GLOBALS['session']->get('imp', 'protocol') != 'pop') {
            if ($prefs->getValue('use_trash') &&
                ($trash_folder = $prefs->getValue('trash_folder')) &&
                $prefs->getValue('empty_trash_menu')) {
                $imp_search = $injector->getInstance('IMP_Search');
                $trash_folder = IMP::folderPref($trash_folder, true);

                if ($injector->getInstance('IMP_Search')->isVTrash($trash_folder) ||
                    !$injector->getInstance('IMP_Injector_Factory_Imap')->create()->isReadOnly($trash_folder)) {
                    $menu->addArray(array(
                        'class' => '__noselection',
                        'icon' => 'empty_trash.png',
                        'onclick' => 'return window.confirm(' . Horde_Serialize::serialize(_("Are you sure you wish to empty your trash folder?"), Horde_Serialize::JSON, 'UTF-8') . ')',
                        'text' => _("Empty _Trash"),
                        'url' => IMP::generateIMPUrl($menu_mailbox_url, $trash_folder)->add(array('actionID' => 'empty_mailbox', 'mailbox_token' => $injector->getInstance('Horde_Token')->get('imp.mailbox')))
                    ));
                }
            }

            $spam_folder = $prefs->getValue('spam_folder');
            if (!empty($spam_folder) &&
                $prefs->getValue('empty_spam_menu')) {
                $menu->addArray(array(
                    'class' => '__noselection',
                    'icon' =>  'empty_spam.png',
                    'onclick' => 'return window.confirm(' . Horde_Serialize::serialize(_("Are you sure you wish to empty your trash folder?"), Horde_Serialize::JSON, 'UTF-8') . ')',
                    'text' => _("Empty _Spam"),
                    'url' => IMP::generateIMPUrl($menu_mailbox_url, IMP::folderPref($spam_folder, true))->add(array('actionID' => 'empty_mailbox', 'mailbox_token' => $injector->getInstance('Horde_Token')->get('imp.mailbox')))
                ));
            }
        }

        if (IMP::canCompose()) {
            $menu->addArray(array(
                'icon' => 'compose.png',
                'text' => _("_New Message"),
                'url' => IMP::composeLink(array('mailbox' => IMP::$mailbox))
            ));
        }

        if ($conf['user']['allow_folders']) {
            $menu->addArray(array(
                'icon' => 'folders/folder.png',
                'text' => _("_Folders"),
                'url' => Horde::url('folders.php')->unique()
            ));
        }

        if ($GLOBALS['session']->get('imp', 'protocol') != 'pop') {
            $menu->addArray(array(
                'icon' => 'search.png',
                'text' =>_("_Search"),
                'url' => Horde::url('search.php')
            ));
        }

        if ($prefs->getValue('filter_menuitem')) {
            $menu->addArray(array(
                'icon' => 'filters.png',
                'text' => _("Fi_lters"),
                'url' => Horde::url('filterprefs.php')
            ));
        }
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
                    : ($GLOBALS['browser']->isMobile()
                       ? ($GLOBALS['browser']->getBrowser() == 'webkit'
                          ? 'mimp'
                          : 'mobile')
                       : 'imp');
            }

            $js_code['ImpLogin.pre_sel'] = $view_cookie;

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
                    ),
                    'mobile' => array(
                        'hidden' => true,
                        'name' => _("Mobile (Smartphone)")
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
     * Tries to authenticate with the mail server.
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
            $this->_cacheSess = array_merge($new_session, array(
                'select_view' => empty($credentials['imp_select_view']) ? '' : $credentials['imp_select_view']
            ));
        }
    }

    /**
     * Tries to transparently authenticate with the mail server and create a
     * mail session.
     *
     * @param Horde_Core_Auth_Application $auth_ob  The authentication object.
     *
     * @return boolean  Whether transparent login is supported.
     */
    public function authTransparent($auth_ob)
    {
        $this->init();

        if ($result = IMP_Auth::transparent($auth_ob)) {
            $this->_cacheSess = $result;
            return true;
        }

        return false;
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

            foreach ($this->_cacheSess as $key => $val) {
                $GLOBALS['session']->set('imp', $key, $val);
            }
            $this->_cacheSess = array();

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
     * Code to run on init when viewing prefs for this application.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsInit($ui)
    {
        $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsInit($ui);
    }

    /**
     * Determine active prefs when displaying a group.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsGroup($ui)
    {
        $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsGroup($ui);
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

        if ($GLOBALS['session']->get('imp', 'protocol') == 'pop') {
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
    }

    /* Helper methods. */

    /**
     * Run tasks when the mailbox list has changed.
     */
    public function mailboxesChanged()
    {
        $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->init();
    }

    /**
     * Callback, called from common-template-mobile.inc that sets up the jquery
     * mobile init hanler.
     */
    public function mobileInitCallback()
    {
        Horde::addScriptFile('mobile.js');
        require IMP_TEMPLATES . '/mobile/javascript_defs.php';

        /* Inline script. */
        Horde::addInlineScript(
          '$(window.document).bind("mobileinit", function() {
              $.mobile.page.prototype.options.backBtnText = "' . _("Back") .'";
              $.mobile.loadingMessage = "' . _("loading") . '";
           });'
        );
    }
}
