<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/* Determine the base directories. */
if (!defined('IMP_BASE')) {
    define('IMP_BASE', realpath(__DIR__ . '/..'));
}

if (!defined('HORDE_BASE')) {
    /* If Horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(IMP_BASE . '/config/horde.local.php')) {
        include IMP_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', realpath(IMP_BASE . '/..'));
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

/**
 * IMP application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with IMP through this API.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Application extends Horde_Registry_Application
{

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
    public $features = array(
        'dynamicView' => true,
        'minimalView' => true,
        'notificationHandler' => true,
        'smartmobileView' => true
    );

    /**
     */
    public $version = 'H5 (6.2.0-git)';

    /**
     * Server key used in logged out session.
     *
     * @var string
     */
    protected $_oldserver = null;

    /**
     */
    public function appInitFailure($e)
    {
        global $injector;

        switch ($e->getCode()) {
        case Horde_Registry::AUTH_FAILURE:
            $injector->getInstance('IMP_Factory_Compose')->create()->sessionExpireDraft($injector->getInstance('Horde_Variables'));
            break;
        }
    }

    /**
     */
    protected function _bootstrap()
    {
        global $injector;

        /* Add IMP-specific factories. */
        $factories = array(
            'IMP_AuthImap' => 'IMP_Factory_AuthImap',
            'IMP_Crypt_Pgp' => 'IMP_Factory_Pgp',
            'IMP_Crypt_Smime' => 'IMP_Factory_Smime',
            'IMP_Flags' => 'IMP_Factory_Flags',
            'IMP_Identity' => 'IMP_Factory_Identity',
            'IMP_Ftree' => 'IMP_Factory_Ftree',
            'IMP_Mail' => 'IMP_Factory_Mail',
            'IMP_Maillog' => 'IMP_Factory_Maillog',
            'IMP_Prefs_Sort' => 'IMP_Factory_PrefsSort',
            'IMP_Quota' => 'IMP_Factory_Quota',
            'IMP_Search' => 'IMP_Factory_Search',
            'IMP_Sentmail' => 'IMP_Factory_Sentmail'
        );

        foreach ($factories as $key => $val) {
            $injector->bindFactory($key, $val, 'create');
        }

        /* Methods only available if admin config is set for this
         * server/login. */
        if (empty($injector->getInstance('IMP_Factory_Imap')->create()->config->admin)) {
            $this->auth = array_diff($this->auth, array('add', 'list', 'remove'));
        }

        /* Set exception handler to handle uncaught
         * Horde_Imap_Client_Exceptions. */
        set_exception_handler(array($this, 'exceptionHandler'));
    }

    /**
     */
    protected function _authenticated()
    {
        IMP_Auth::authenticateCallback();
    }

    /**
     */
    protected function _init()
    {
        global $prefs, $registry;

        // Set default message character set.
        if ($registry->getAuth()) {
            if ($def_charset = $prefs->getValue('default_msg_charset')) {
                Horde_Mime_Part::$defaultCharset = $def_charset;
                Horde_Mime_Headers::$defaultCharset = $def_charset;
            }

            // Always use Windows-1252 in place of ISO-8859-1 for MIME
            // decoding.
            Horde_Mime::$decodeWindows1252 = true;
        }

        if (($registry->initialApp == 'imp') &&
            !empty($this->initParams['impmode']) &&
            ($this->initParams['impmode'] != $registry->getView())) {
            IMP_Auth::getInitialPage()->url->redirect();
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
        $this->_oldserver = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->server_key;
    }

    /**
     */
    public function getInitialPage()
    {
        return strval(IMP_Auth::getInitialPage()->url);
    }

    /* Horde permissions. */

    /**
     */
    public function perms()
    {
        return $GLOBALS['injector']->getInstance('IMP_Perms')->perms();
    }

    /**
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        return $GLOBALS['injector']->getInstance('IMP_Perms')->hasPermission($permission, $allowed, $opts);
    }

    /* Menu methods. */

    /**
     */
    public function menu($menu)
    {
        global $injector, $prefs, $registry, $session;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        if ($imp_imap->access(IMP_Imap::ACCESS_TRASH) &&
            $prefs->getValue('use_trash') &&
            $prefs->getValue('empty_trash_menu') &&
            ($trash = IMP_Mailbox::getPref(IMP_Mailbox::MBOX_TRASH)) &&
            ($trash->vtrash || $trash->access_expunge)) {
            $menu->addArray(array(
                'class' => '__noselection',
                'icon' => 'imp-empty-trash',
                'onclick' => 'return window.confirm(' . json_encode(_("Are you sure you wish to empty your trash mailbox?")) . ')',
                'text' => _("Empty _Trash"),
                'url' => $trash->url('mailbox')->add(array('actionID' => 'empty_mailbox', 'token' => $session->getToken()))
            ));
        }

        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS) &&
            $prefs->getValue('empty_spam_menu') &&
            ($spam = IMP_Mailbox::getPref(IMP_Mailbox::MBOX_SPAM)) &&
            $spam->access_expunge) {
            $menu->addArray(array(
                'class' => '__noselection',
                'icon' =>  'imp-empty-spam',
                'onclick' => 'return window.confirm(' . json_encode(_("Are you sure you wish to empty your spam mailbox?")) . ')',
                'text' => _("Empty _Spam"),
                'url' => $spam->url('mailbox')->add(array('actionID' => 'empty_mailbox', 'token' => $session->getToken()))
            ));
        }

        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $menu->addArray(array(
                'icon' => 'imp-folder',
                'text' => _("_Folders"),
                'url' => IMP_Basic_Folders::url()
            ));
        }

        if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            $menu->addArray(array(
                'icon' => 'imp-search',
                'text' =>_("_Search"),
                'url' => IMP_Basic_Search::url()
            ));
        }

        if ($prefs->getValue('filter_menuitem')) {
            $menu->addArray(array(
                'icon' => 'imp-filters',
                'text' => _("Fi_lters"),
                'url' => $registry->getServiceLink('prefs', 'imp')->add('group', 'filters')
            ));
        }
    }

    /**
     * Add additional items to the sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        global $injector;

        if (IMP_Compose::canCompose()) {
            $clink = new IMP_Compose_Link();
            $sidebar->addNewButton(_("_New Message"), $clink->link());
        }

        /* Folders. */
        if ($injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS)) {
            try {
                $tree = $injector
                    ->getInstance('Horde_Core_Factory_Tree')
                    ->create('imp_menu', 'Horde_Tree_Renderer_Sidebar', array('nosession' => true));

                $tree = $injector->getInstance('IMP_Ftree')->createTree($tree, array(
                    'iterator' => IMP_Ftree_IteratorFilter::create(IMP_Ftree_IteratorFilter::NO_REMOTE | IMP_Ftree_IteratorFilter::NO_VFOLDER | IMP_Ftree_IteratorFilter::UNSUB_PREF),
                    'open' => false,
                    'poll_info' => true
                ));
                $sidebar->containers['imp-menu'] = array(
                    'content' => $tree->getTree()
                );
            } catch (Exception $e) {}
        }
    }


    // Horde_Notification methods.

    /**
     * Modifies the global notification handler.
     *
     * @param Horde_Notification_Handler $handler  A notification handler.
     */
    public function setupNotification(Horde_Notification_Handler $handler)
    {
        $handler->addDecorator(new IMP_Notification_Handler_Decorator_ImapAlerts());
        $handler->addDecorator(new IMP_Notification_Handler_Decorator_NewmailNotify());
        $handler->addType('status', 'imp.*', 'IMP_Notification_Event_Status');
    }

    /* Horde_Core_Auth_Application methods. */

    /**
     */
    public function authLoginParams()
    {
        $params = array();

        if ($GLOBALS['conf']['server']['server_list'] == 'shown') {
            $server_list = array();
            $selected = is_null($this->_oldserver)
                ? $GLOBALS['injector']->getInstance('Horde_Variables')->get('imp_server_key', IMP_Auth::getAutoLoginServer())
                : $this->_oldserver;

            foreach (IMP_Imap::loadServerConfig() as $key => $val) {
                $server_list[$key] = array(
                    'name' => $val->name,
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
            'HordeLogin.server_key_error' => _("Please choose a mail server.")
        );

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
     *                            'imp_server_key', 'password'.
     */
    public function authAuthenticate($userId, $credentials)
    {
        if (isset($credentials['server'])) {
            $server = $credentials['server'];
        } else {
            $server = empty($credentials['imp_server_key'])
                ? IMP_Auth::getAutoLoginServer()
                : $credentials['imp_server_key'];
        }

        IMP_Auth::authenticate(array(
            'password' => $credentials['password'],
            'server' => $server,
            'userId' => $userId
        ));
    }

    /**
     */
    public function authTransparent($auth_ob)
    {
        return IMP_Auth::transparent($auth_ob);
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

    /* Topbar method. */

    /**
     */
    public function topbarCreate(Horde_Tree_Renderer_Base $tree,
                                 $parent = null, array $params = array())
    {
        global $injector, $registry;

        if (IMP_Compose::canCompose()) {
            $clink = new IMP_Compose_Link();
            $tree->addNode(array(
                'id' => strval($parent) . 'compose',
                'parent' => $parent,
                'label' => _("New Message"),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('compose.png'),
                    'url' => $clink->link()->setRaw(true)
                )
            ));
        }

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            $onclick = null;
            switch ($registry->getView()) {
            case $registry::VIEW_DYNAMIC:
                $url = Horde::url('dynamic.php', true)
                    ->add('page', 'mailbox')
                    ->setAnchor('search');
                $onclick = 'if (window.DimpBase) { DimpBase.go(\'search\') }';
                break;

            default:
                $url = IMP_Basic_Search::url(array('full' => true));
                break;
            }

            $tree->addNode(array(
                'id' => strval($parent) . 'search',
                'parent' => $parent,
                'label' => _("Search"),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('search.png'),
                    'url' => $url,
                    'onclick' => $onclick,
                )
            ));
        }
    }

    /* Language change callback. */

    /**
     */
    public function changeLanguage()
    {
        $GLOBALS['injector']->getInstance('IMP_Ftree')->init();
    }

    /* NoSQL methods. */

    /**
     */
    public function nosqlDrivers()
    {
        global $injector;

        $out = array();

        $ob = $injector->getInstance('IMP_Factory_Imap')->create()->config->cache_params;
        if ($ob['backend'] instanceof Horde_Imap_Client_Cache_Backend_Mongo) {
            $out[] = $ob['backend'];
        }

        $ob = $injector->getInstance('IMP_Sentmail');
        if ($ob instanceof IMP_Sentmail_Mongo) {
            $out[] = $ob;
        }

        return $out;
    }

    /* Download data. */

    /**
     * URL parameters:
     *   - actionID
     *
     * @throws IMP_Exception
     */
    public function download(Horde_Variables $vars)
    {
        global $injector, $registry;

        /* Check for an authenticated user. */
        if (!$registry->isAuthenticated(array('app' => 'imp'))) {
            $e = new IMP_Exception(_("User is not authenticated."));
            $e->logged = true;
            throw $e;
        }

        switch ($vars->actionID) {
        case 'download_all':
            $view_ob = new IMP_Contents_View(new IMP_Indices_Mailbox($vars));
            $view_ob->checkToken($vars);
            return $view_ob->downloadAll();

        case 'download_attach':
            $view_ob = new IMP_Contents_View(new IMP_Indices_Mailbox($vars));
            $view_ob->checkToken($vars);
            return $view_ob->downloadAttach($vars->id, $vars->zip);

        case 'download_mbox':
            $mlist = IMP_Mailbox::formFrom($vars->mbox_list);
            $mbox = $injector->getInstance('IMP_Mbox_Generate')->generate($mlist);
            $name = is_array($mlist)
                ? reset($mlist)
                : $mlist;

            switch ($vars->type) {
            case 'mbox':
                return array(
                    'data' => $mbox,
                    'name' => $name . '.mbox',
                    'type' => 'text/plain; charset=UTF-8'
                );

            case 'mboxzip':
                try {
                    $data = Horde_Compress::factory('Zip')->compress(array(
                        array(
                            'data' => $mbox,
                            'name' => $name . '.mbox'
                        )
                    ), array(
                        'stream' => true
                    ));
                    fclose($mbox);
                } catch (Horde_Exception $e) {
                    fclose($mbox);
                    throw $e;
                }

                return array(
                    'data' => $data,
                    'name' => $name . '.zip',
                    'type' => 'application/zip'
                );
            }
            break;

        case 'download_render':
            $view_ob = new IMP_Contents_View(new IMP_Indices_Mailbox($vars));
            $view_ob->checkToken($vars);
            return $view_ob->downloadRender($vars->id, $vars->mode, $vars->ctype);

        case 'save_message':
            $view_ob = new IMP_Contents_View(new IMP_Indices_Mailbox($vars));
            return $view_ob->saveMessage();
        }

        return array();
    }

    /* Exception handler. */

    /**
     */
    public function exceptionHandler(Exception $e)
    {
        if ($e instanceof Horde_Imap_Client_Exception) {
            $e = new Horde_Exception_AuthenticationFailure(
                $e->getMessage(),
                Horde_Auth::REASON_MESSAGE
            );
        }

        Horde_ErrorHandler::fatal($e);
    }

}
