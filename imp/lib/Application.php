<?php
/**
 * IMP application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with IMP through this API.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
     */
    public $ajaxView = true;

    /**
     */
    public $auth = array(
        'add',
        'authenticate',
        'list',
        'remove',
        'transparent'
    );

    /**
     */
    public $mobileView = true;

    /**
     */
    public $version = 'H4 (5.0-git)';

    /**
     * Cached values to add to the session after authentication.
     *
     * @var array
     */
    protected $_cacheSess = array();

    /**
     * Server key used in logged out session.
     *
     * @var string
     */
    protected $_oldserver = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        /* Methods only available if admin config is set for this
         * server/login. */
        if (!isset($GLOBALS['session']) ||
            !$GLOBALS['session']->get('imp', 'imap_admin')) {
            $this->auth = array_diff($this->auth, array('add', 'list', 'remove'));
        }
    }

    /**
     */
    public function appInitFailure($e)
    {
        if (($e->getCode() == Horde_Registry::AUTH_FAILURE) &&
            Horde_Util::getFormData('composeCache')) {
            $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create()->sessionExpireDraft(Horde_Variables::getDefaultVariables());
        }
    }

    /**
     */
    protected function _init()
    {
        /* Add IMP-specific factories. */
        $factories = array(
            'IMP_AuthImap' => 'IMP_Factory_AuthImap',
            'IMP_Crypt_Pgp' => 'IMP_Factory_Pgp',
            'IMP_Crypt_Smime' => 'IMP_Factory_Smime',
            'IMP_Flags' => 'IMP_Factory_Flags',
            'IMP_Identity' => 'IMP_Factory_Identity',
            'IMP_Imap_Tree' => 'IMP_Factory_Imaptree',
            'IMP_Mail' => 'IMP_Factory_Mail',
            'IMP_Quota' => 'IMP_Factory_Quota',
            'IMP_Search' => 'IMP_Factory_Search',
            'IMP_Sentmail' => 'IMP_Factory_Sentmail'
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
     */
    public function logout()
    {
        /* Clean up dangling IMP_Compose objects. */
        foreach (array_keys($GLOBALS['session']->get('imp', 'compose_cache', Horde_Session::TYPE_ARRAY)) as $key) {
            $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($key)->destroy('cancel');
        }

        /* No need to keep Tree object in cache - it will be recreated next
         * login. */
        if ($treeob = $GLOBALS['session']->get('imp', 'treeob')) {
            $GLOBALS['injector']->getInstance('Horde_Cache')->expire($treeob);
        }

        /* Grab the current server from the session to correctly populate
         * login form. */
        $this->_oldserver = $GLOBALS['session']->get('imp', 'server_key');
    }

    /* Horde permissions. */

    /**
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
     * @param array $opts         Additional options:
     *   - For 'max_recipients' and 'max_timelimit', 'value' is the number of
     *     recipients in the current message.
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        switch ($permission) {
        case 'create_folders':
            // No-op
            break;

        case 'max_folders':
            if (empty($opts['value'])) {
                return ($allowed >= count($GLOBALS['injector']->getInstance('IMP_Folder')->flist_IMP(array(), false)));
            }
            break;

        case 'max_recipients':
            if (isset($opts['value'])) {
                return ($allowed >= $opts['value']);
            }
            break;

        case 'max_timelimit':
            if (isset($opts['value'])) {
                $sentmail = $GLOBALS['injector']->getInstance('IMP_Sentmail');
                if (!($sentmail instanceof IMP_Sentmail_Base)) {
                    Horde::logMessage('The permission for the maximum number of recipients per time period has been enabled, but no backend for the sent-mail logging has been configured for IMP.', 'ERR');
                    return true;
                }

                try {
                    $opts['value'] += $sentmail->numberOfRecipients($GLOBALS['conf']['sentmail']['params']['limit_period'], true);
                } catch (IMP_Exception $e) {}

                return ($allowed >= $opts['value']);
            }
            break;
        }

        return (bool)$allowed;
    }

    /* Menu methods. */

    /**
     */
    public function menu($menu)
    {
        global $injector, $prefs, $registry;

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
                    !$injector->getInstance('IMP_Factory_Imap')->create()->isReadOnly($trash_folder)) {
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

        if ($injector->getInstance('IMP_Factory_Imap')->create()->allowFolders()) {
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
     */
    public function authLoginParams()
    {
        $params = array();

        if ($GLOBALS['conf']['server']['server_list'] == 'shown') {
            $servers = IMP_Imap::loadServerConfig();
            $server_list = array();
            $selected = is_null($this->_oldserver)
                ? Horde_Util::getFormData('imp_server_key', IMP_Auth::getAutoLoginServer())
                : $this->_oldserver;

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
     * @param array $credentials  Credentials of the user. Allowed keys:
     *                            'imp_select_view', 'imp_server_key',
     *                            'password'.
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
     * @param array $credentials  An array of login credentials. For IMP,
     *                            this must contain a password entry.
     */
    public function authAddUser($userId, $credentials)
    {
        try {
            $GLOBALS['injector']->getInstance('IMP_AuthImap')->addUser($userId, $credentials);
        } catch (Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     */
    public function authRemoveUser($userId)
    {
        try {
            $GLOBALS['injector']->getInstance('IMP_AuthImap')->removeUser($userId);
        } catch (Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     */
    public function authUserList()
    {
        try {
            return $GLOBALS['injector']->getInstance('IMP_AuthImap')->listUsers();
        } catch (Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /* Preferences display/handling methods. Code is contained in
     * IMP_Prefs_Ui so it doesn't have to be loaded on every page load. */

    /**
     */
    public function prefsInit($ui)
    {
        $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsInit($ui);
    }

    /**
     */
    public function prefsGroup($ui)
    {
        $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsGroup($ui);
    }

    /**
     */
    public function prefsSpecial($ui, $item)
    {
        return $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsSpecial($ui, $item);
    }

    /**
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        return $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsSpecialUpdate($ui, $item);
    }

    /**
     */
    public function prefsCallback($ui)
    {
        $GLOBALS['injector']->getInstance('IMP_Prefs_Ui')->prefsCallback($ui);
    }

    /**
     */
    public function configSpecialValues($what)
    {
        switch ($what) {
        case 'backends':
            $servers = Horde::loadConfiguration('backends.php', 'servers', 'imp');
            $result = array();
            foreach ($servers as $key => $server) {
                if ($key[0] != '_') {
                    $result[$key] = $server['name'];
                }
            }
            return $result;
        }
    }

    /* Sidebar method. */

    /**
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
