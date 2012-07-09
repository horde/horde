<?php
/**
 * IMP application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with IMP through this API.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

/* Determine the base directories. */
if (!defined('IMP_BASE')) {
    define('IMP_BASE', __DIR__ . '/..');
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
    public $version = 'H5 (6.0.0beta1)';

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

        if (($e->getCode() == Horde_Registry::AUTH_FAILURE) &&
            $injector->getInstance('Horde_Variables')->composeCache) {
            $injector->getInstance('IMP_Factory_Compose')->create()->sessionExpireDraft($injector->getInstance('Horde_Variables'));
        }
    }

    /**
     */
    protected function _bootstrap()
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
            'IMP_Prefs_Sort' => 'IMP_Factory_PrefsSort',
            'IMP_Quota' => 'IMP_Factory_Quota',
            'IMP_Search' => 'IMP_Factory_Search',
            'IMP_Sentmail' => 'IMP_Factory_Sentmail'
        );

        foreach ($factories as $key => $val) {
            $GLOBALS['injector']->bindFactory($key, $val, 'create');
        }

        /* Methods only available if admin config is set for this
         * server/login. */
        if (!isset($GLOBALS['session']) ||
            !$GLOBALS['session']->get('imp', 'imap_admin')) {
            $this->auth = array_diff($this->auth, array('add', 'list', 'remove'));
        }
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
        $this->_oldserver = $GLOBALS['session']->get('imp', 'server_key');
    }

    /* Horde permissions. */

    /**
     */
    public function perms()
    {
        return array(
            'create_folders' => array(
                'title' => _("Allow mailbox creation?"),
                'type' => 'boolean'
            ),
            'max_folders' => array(
                'title' => _("Maximum Number of Mailboxes"),
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
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        if (is_array($allowed)) {
            $allowed = max($allowed);
        }
        return $allowed;
    }

    /* Menu methods. */

    /**
     */
    public function menu($menu)
    {
        global $injector, $prefs, $registry;

        $menu_mailbox_url = Horde::url('mailbox.php');
        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        if ($imp_imap->access(IMP_Imap::ACCESS_TRASH) &&
            $prefs->getValue('use_trash') &&
            $prefs->getValue('empty_trash_menu') &&
            ($trash = IMP_Mailbox::getPref('trash_folder')) &&
            ($trash->vtrash || $trash->access_expunge)) {
            $menu->addArray(array(
                'class' => '__noselection',
                'icon' => 'imp-empty-trash',
                'onclick' => 'return window.confirm(' . Horde_Serialize::serialize(_("Are you sure you wish to empty your trash mailbox?"), Horde_Serialize::JSON, 'UTF-8') . ')',
                'text' => _("Empty _Trash"),
                'url' => $trash->url($menu_mailbox_url)->add(array('actionID' => 'empty_mailbox', 'mailbox_token' => $injector->getInstance('Horde_Token')->get('imp.mailbox')))
            ));
        }

        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS) &&
            $prefs->getValue('empty_spam_menu') &&
            ($spam = IMP_Mailbox::getPref('spam_folder')) &&
            $spam->access_expunge) {
            $menu->addArray(array(
                'class' => '__noselection',
                'icon' =>  'imp-empty-spam',
                'onclick' => 'return window.confirm(' . Horde_Serialize::serialize(_("Are you sure you wish to empty your spam mailbox?"), Horde_Serialize::JSON, 'UTF-8') . ')',
                'text' => _("Empty _Spam"),
                'url' => $spam->url($menu_mailbox_url)->add(array('actionID' => 'empty_mailbox', 'mailbox_token' => $injector->getInstance('Horde_Token')->get('imp.mailbox')))
            ));
        }

        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $menu->addArray(array(
                'icon' => 'imp-folder',
                'text' => _("_Folders"),
                'url' => Horde::url('folders.php')->unique()
            ));
        }

        if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            $menu->addArray(array(
                'icon' => 'imp-search',
                'text' =>_("_Search"),
                'url' => Horde::url('search.php')
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
            $servers = IMP_Imap::loadServerConfig();
            $server_list = array();
            $selected = is_null($this->_oldserver)
                ? $GLOBALS['injector']->getInstance('Horde_Variables')->get('imp_server_key', IMP_Auth::getAutoLoginServer())
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

        $this->_addSessVars(IMP_Auth::authenticate(array(
            'password' => $credentials['password'],
            'server' => $server,
            'userId' => $userId
        )));
    }

    /**
     */
    public function authTransparent($auth_ob)
    {
        if ($result = IMP_Auth::transparent($auth_ob)) {
            $this->_addSessVars($result);
            return true;
        }

        return false;
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
    public function topbarCreate(Horde_Tree_Renderer_Base $tree, $parent = null,
                                 array $params = array())
    {
        global $injector, $registry;

        IMP_Mailbox::get('INBOX')->filterOnDisplay();

        if (IMP::canCompose()) {
            $tree->addNode(array(
                'id' => strval($parent) . 'compose',
                'parent' => $parent,
                'label' => _("New Message"),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('compose.png'),
                    'url' => IMP::composeLink()->setRaw(false)
                )
            ));
        }

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
            $tree->addNode(array(
                'id' => strval($parent) . 'search',
                'parent' => $parent,
                'label' => _("Search"),
                'expanded' => false,
                'params' => array(
                    'icon' => Horde_Themes::img('search.png'),
                    'url' => Horde::url('search.php')
                )
            ));
        }
    }

    /* Language change callback. */

    /**
     */
    public function changeLanguage()
    {
        $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->init();
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
            $view_ob = new IMP_Contents_View(IMP::mailbox(true), IMP::uid());
            return $view_ob->downloadAll();

        case 'download_attach':
            $view_ob = new IMP_Contents_View(IMP::mailbox(true), IMP::uid());
            return $view_ob->downloadAttach($vars->id, $vars->zip);

        case 'download_mbox':
            $mlist = IMP_Mailbox::formFrom($vars->mbox_list);
            $mbox = $injector->getInstance('IMP_Ui_Folder')->generateMbox($mlist);

            if ($vars->zip) {
                try {
                    $data = Horde_Compress::factory('Zip')->compress(array(
                        array(
                            'data' => $mbox,
                            'name' => reset($mlist) . '.mbox'
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
                    'name' => reset($mlist) . '.zip',
                    'type' => 'application/zip'
                );
            }

            return array(
                'data' => $mbox,
                'name' => reset($mlist) . '.mbox',
                'type' => 'text/plain; charset=UTF-8'
            );

        case 'download_render':
            $view_ob = new IMP_Contents_View(IMP::mailbox(true), IMP::uid());
            return $view_ob->downloadRender($vars->id, $vars->mode, $vars->ctype);

        case 'save_message':
            $view_ob = new IMP_Contents_View(IMP::mailbox(true), IMP::uid());
            return $view_ob->saveMessage();
        }

        return array();
    }

}
