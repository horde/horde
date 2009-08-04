<?php
/**
 * IMP external API interface.
 *
 * This file defines IMP's external API interface. Other applications
 * can interact with IMP through this API.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package IMP
 */
class IMP_Api extends Horde_Registry_Api
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
     * The services provided by this application.
     *
     * @var array
     */
    public $services = array(
        'perms' => array(
            'args' => array(),
            'type' => '{urn:horde}hashHash'
        ),

        'authCredentials' => array(
            'args' => array(),
            'type' => '{urn:horde}hashHash'
        ),

        'compose' => array(
            'args' => array(
                'args' => '{urn:horde}hash',
                'extra' => '{urn:horde}hash'
            ),
            'type' => 'string'
        ),

        'batchCompose' => array(
            'args' => array(
                'args' => '{urn:horde}hash',
                'extra' => '{urn:horde}hash'
            ),
            'type' => 'string'
        ),

        'folderlist' => array(
            'args' => array(),
            'type' => '{urn:horde}stringArray'
        ),

        'createFolder' => array(
            'args' => array('folder' => 'string'),
            'type' => 'string'
        ),

        'deleteMessages' => array(
            'args' => array(
                'mailbox' => 'string',
                'indices' => '{urn:horde}integerArray'
            ),
            'type' => 'integer'
        ),

        'copyMessages' => array(
            'args' => array(
                'mailbox' => 'string',
                'indices' => '{urn:horde}integerArray',
                'target' => 'string'
            ),
            'type' => 'boolean'
        ),

        'moveMessages' => array(
            'args' => array(
                'mailbox' => 'string',
                'indices' => '{urn:horde}integerArray',
                'target' => 'string'
            ),
            'type' => 'boolean'
        ),

        'flagMessages' => array(
            'args' => array(
                'mailbox' => 'string',
                'indices' => '{urn:horde}integerArray',
                'flags' => '{urn:horde}stringArray',
                'set' => 'boolean'
            ),
            'type' => 'boolean'
        ),

        'msgEnvelope' => array(
            'args' => array(
                'mailbox' => 'string',
                'indices' => '{urn:horde}integerArray'
            ),
            'type' => '{urn:horde}hashHash'
        ),

        'searchMailbox' => array(
            'args' => array(
                'mailbox' => 'string',
                'query' => 'object'
            ),
            'type' => '{urn:horde}integerArray'
        ),

        'mailboxCacheId' => array(
            'args' => array(
                'mailbox' => 'string'
            ),
            'type' => 'string'
        ),

        'server' => array(
            'args' => array(),
            'type' => '{urn:horde}hashHash'
        ),

        'favouriteRecipients' => array(
            'args' => array(
                'limit' => 'int'
            ),
            'type' => '{urn:horde}stringArray'
        ),

        'changeLanguage' => array(
            'args' => array()
        ),

        /* Cache display method. */
        'cacheOutput' => array(
            'args' => array(
                '{urn:horde}hashHash'
            ),
            'type' => '{urn:horde}hashHash'
        ),

        /* Horde_Auth_Application methods. */
        'authLoginParams' => array(
            'args' => array(),
            'checkperms' => false,
            'type' => '{urn:horde}hashHash'
        ),

        'authAuthenticate' => array(
            'args' => array(
                'userID' => 'string',
                'credentials' => '{urn:horde}hash',
                'params' => '{urn:horde}hash'
            ),
            'checkperms' => false,
            'type' => 'boolean'
        ),

        'authAuthenticateCallback' => array(
            'args' => array(),
            'checkperms' => false
        ),

        'authTransparent' => array(
            'args' => array(),
            'checkperms' => false,
            'type' => 'boolean'
        ),

        'authAddUser' => array(
            'args' => array(
                'userId' => 'string',
                'credentials' => '{urn:horde}stringArray'
            )
        ),

        'authRemoveUser' => array(
            'args' => array(
                'userId' => 'string'
            )
        ),

        'authUserList' => array(
            'type' => '{urn:horde}stringArray'
        ),

        /* Prefs_UI methods. */
        'prefsInit' => array(
            'args' => array()
        ),

        'prefsHandle' => array(
            'args' => array(
                'item' => 'string',
                'updated' => 'boolean'
            ),
            'type' => 'boolean'
        ),

        'prefsCallback' => array(
            'args' => array()
        ),

        'prefsMenu' => array(
            'args' => array(),
            'type' => 'object'
        ),

        'prefsStatus' => array(
            'args' => array()
        )
    );

    /**
     * Constructor.
     */
    public function __construct()
    {
        /* Only available if admin config is set for this server/login. */
        if (empty($_SESSION['imp']['admin'])) {
            unset($this->services['authAddUser'], $this->services['authRemoveUser'], $this->services['authUserList']);
        }
    }

    /* Horde-defined functions. */

    /**
     * Returns a list of available permissions.
     *
     * @return array  The permissions list.
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
     * Performs tasks necessary when the language is changed during the
     * session.
     */
    public function changeLanguage()
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            return;
        }

        $imp_folder = IMP_Folder::singleton();
        $imp_folder->clearFlistCache();
        $imaptree = IMP_Imap_Tree::singleton();
        $imaptree->init();
        $GLOBALS['imp_search']->initialize(true);
    }

    /**
     * Returns a list of authentication credentials, i.e. server settings that
     * can be specified by the user on the login screen.
     *
     * @return array  A hash with credentials, suited for the preferences
     *                interface.
     */
    public function authCredentials()
    {
        $app_name = $GLOBALS['registry']->get('name');

        $servers = IMP_Imap::loadServerConfig();
        $server_list = array();
        foreach ($servers as $key => $val) {
            $server_list[$key] = $val['name'];
        }
        reset($server_list);

        $credentials = array(
            'username' => array(
                'desc' => sprintf(_("%s for %s"), _("Username"), $app_name),
                'type' => 'text'
            ),
            'password' => array(
                'desc' => sprintf(_("%s for %s"), _("Password"), $app_name),
                'type' => 'password'
            ),
            'server' => array(
                'desc' => sprintf(_("%s for %s"), _("Server"), $app_name),
                'type' => 'enum',
                'enum' => $server_list,
                'value' => key($server_list)
            )
        );

        return $credentials;
    }

    /**
     * Application-specific cache output driver.
     *
     * @param array $params  A list of params needed (USED: 'id').
     *
     * @return array  See Horde::getCacheUrl().
     * @throws Horde_Exception
     */
    public function cacheOutput($params)
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            throw new Horde_Exception('No cache data available');
        }

        switch ($params['id']) {
        case 'fckeditor':
            return array(
                'data' =>
                    'FCKConfig.ToolbarSets["ImpToolbar"] = ' . $GLOBALS['prefs']->getValue('fckeditor_buttons') . ";\n" .
                    /* To more closely match "normal" textarea behavior, send
                     * send <BR> on enter instead of <P>. */
                    "FCKConfig.EnterMode = 'br';\n" .
                    'FCKConfig.ShiftEnterMode = \'p\';',
                'type' => 'text/javascript'
            );
        }
    }

    /**
     * Code to run when viewing prefs for this application.
     *
     * @param string $group  The prefGroup name.
     */
    public function prefsInit($group)
    {
        /* Add necessary javascript files here (so they are added to the
         * document HEAD). */
        switch ($group) {
        case 'flags':
            Horde::addScriptFile('colorpicker.js', 'horde', true);
            Horde::addScriptFile('flagmanagement.js', 'imp', true);

            Horde::addInlineScript(array(
                'ImpFlagmanagement.new_prompt = ' . Horde_Serialize::serialize(_("Please enter the label for the new flag:"), Horde_Serialize::JSON, Horde_Nls::getCharset()),
                'ImpFlagmanagement.confirm_delete = ' . Horde_Serialize::serialize(_("Are you sure you want to delete this flag?"), Horde_Serialize::JSON, Horde_Nls::getCharset())
            ));
            break;
        }
    }

    /**
     * TODO
     */
    public function prefsHandle($item, $updated)
    {
        switch ($item) {
        case 'sentmailselect':
            return $this->_prefsSentmailSelect($updated);

        case 'draftsselect':
            return $updated | $this->_prefsHandleFolders($updated, 'drafts_folder', 'drafts', 'drafts_new');

        case 'spamselect':
            return $updated | $this->_prefsHandleFolders($updated, 'spam_folder', 'spam', 'spam_new');

        case 'trashselect':
            return $this->_prefsTrashSelect($updated);

        case 'sourceselect':
            return $this->_prefsSourceSelect($updated);

        case 'initialpageselect':
            $this->_prefsInitialPageSelect();
            return true;

        case 'encryptselect':
            $this->_prefsEncryptSelect();
            return true;

        case 'defaultsearchselect':
            $this->_prefsDefaultSearchSelect();
            return true;

        case 'soundselect':
            return $GLOBALS['prefs']->setValue('nav_audio', Horde_Util::getFormData('nav_audio'));

        case 'flagmanagement':
            $this->_prefsFlagManagement();
            return false;
        }
    }

    /**
     * Do anything that we need to do as a result of certain preferences
     * changing.
     */
    public function prefsCallback()
    {
        global $prefs;

        /* Always check to make sure we have a valid trash folder if delete to
         * trash is active. */
        if (($prefs->isDirty('use_trash') || $prefs->isDirty('trash_folder')) &&
            $prefs->getValue('use_trash') &&
            !$prefs->getValue('trash_folder') &&
            !$prefs->getValue('use_vtrash')) {
                $GLOBALS['notification']->push(_("You have activated move to Trash but no Trash folder is defined. You will be unable to delete messages until you set a Trash folder in the preferences."), 'horde.warning');
            }

        if ($prefs->isDirty('use_vtrash') || $prefs->isDirty('use_vinbox')) {
            $imp_search = new IMP_Search();
            $imp_search->initialize(true);
        }

        if ($prefs->isDirty('subscribe') || $prefs->isDirty('tree_view')) {
            $imp_folder = IMP_Folder::singleton();
            $imp_folder->clearFlistCache();
            $imaptree = IMP_Imap_Tree::singleton();
            $imaptree->init();
        }

        if ($prefs->isDirty('mail_domain')) {
            $maildomain = preg_replace('/[^-\.a-z0-9]/i', '', $prefs->getValue('mail_domain'));
            $prefs->setValue('maildomain', $maildomain);
            if (!empty($maildomain)) {
                $_SESSION['imp']['maildomain'] = $maildomain;
            }
        }

        if ($prefs->isDirty('compose_popup')) {
            Horde::addInlineScript(array(
                'if (window.parent.frames.horde_menu) window.parent.frames.horde_menu.location.reload();'
            ));
        }
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return IMP::getMenu();
    }

    /**
     * Output notifications on the preferences page.
     */
    public function prefsStatus()
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
            if (IMP::getViewMode() == 'dimp') {
                Horde::addInlineScript(array(IMP_Dimp::notify(true)), 'dom');
                return;
            }
        } catch (Horde_Exception $e) {}

        IMP::status();
    }

    /**
     * TODO
     */
    protected function _prefsSentmailSelect($updated)
    {
        if (!$GLOBALS['conf']['user']['allow_folders'] ||
            $GLOBALS['prefs']->isLocked('sent_mail_folder')) {
            return $updated;
        }

        $sent_mail_folder = Horde_Util::getFormData('sent_mail_folder');
        $sent_mail_new = Horde_String::convertCharset(Horde_Util::getFormData('sent_mail_new'), Horde_Nls::getCharset(), 'UTF7-IMAP');
        $sent_mail_default = $GLOBALS['prefs']->getValue('sent_mail_folder');

        if (empty($sent_mail_folder) && !empty($sent_mail_new)) {
            $sent_mail_folder = IMP::appendNamespace($sent_mail_new);
        } elseif (($sent_mail_folder == '-1') && !empty($sent_mail_default)) {
            $sent_mail_folder = IMP::appendNamespace($sent_mail_default);
        }

        if (!empty($sent_mail_folder)) {
            $imp_folder = IMP_Folder::singleton();
            if (!$imp_folder->exists($sent_mail_folder)) {
                $imp_folder->create($sent_mail_folder, $GLOBALS['prefs']->getValue('subscribe'));
            }
        }
        $GLOBALS['identity']->setValue('sent_mail_folder', IMP::folderPref($sent_mail_folder, false));

        return true;
    }

    /**
     * TODO
     */
    protected function _prefsHandlefolders($updated, $pref, $folder, $new)
    {
        if (!$GLOBALS['conf']['user']['allow_folders']) {
            return $updated;
        }

        $folder = Horde_Util::getFormData($folder);
        if (isset($folder) && !$GLOBALS['prefs']->isLocked($pref)) {
            $new = Horde_String::convertCharset(Horde_Util::getFormData($new), Horde_Nls::getCharset(), 'UTF7-IMAP');
            if ($folder == IMP::PREF_NO_FOLDER) {
                $GLOBALS['prefs']->setValue($pref, '');
            } else {
                if (empty($folder) && !empty($new)) {
                    $folder = IMP::appendNamespace($new);
                    $imp_folder = IMP_Folder::singleton();
                    if (!$imp_folder->create($folder, $GLOBALS['prefs']->getValue('subscribe'))) {
                        $folder = null;
                    }
                }
                if (!empty($folder)) {
                    $GLOBALS['prefs']->setValue($pref, IMP::folderPref($folder, false));
                    return true;
                }
            }
        }

        return $updated;
    }

    /**
     * TODO
     */
    protected function _prefsTrashSelect($updated)
    {
        global $prefs;

        if (Horde_Util::getFormData('trash') == IMP::PREF_VTRASH) {
            if ($prefs->isLocked('use_vtrash')) {
                return false;
            }

            $prefs->setValue('use_vtrash', 1);
            $prefs->setValue('trash_folder', '');
        } else {
            if ($prefs->isLocked('trash_folder')) {
                return false;
            }

            $updated = $updated | $this->_prefsHandleFolders($updated, 'trash_folder', 'trash', 'trash_new');
            if ($updated) {
                $prefs->setValue('use_vtrash', 0);
                $prefs->setDirty('trash_folder', true);
            }
        }

        return $updated;
    }

    /**
     * TODO
     */
    protected function _prefsSourceSelect($updated)
    {
        $search_sources = Horde_Util::getFormData('search_sources');
        if (!is_null($search_sources)) {
            $GLOBALS['prefs']->setValue('search_sources', $search_sources);
            unset($_SESSION['imp']['cache']['ac_ajax']);
            $updated = true;
        }

        $search_fields_string = Horde_Util::getFormData('search_fields_string');
        if (!is_null($search_fields_string)) {
            $GLOBALS['prefs']->setValue('search_fields', $search_fields_string);
            $updated = true;
        }

        $add_source = Horde_Util::getFormData('add_source');
        if (!is_null($add_source)) {
            $GLOBALS['prefs']->setValue('add_source', $add_source);
            $updated = true;
        }

        return $updated;
    }

    /**
     * TODO
     */
    protected function _prefsInitialPageSelect()
    {
        $initial_page = Horde_Util::getFormData('initial_page');
        $GLOBALS['prefs']->setValue('initial_page', $initial_page);
    }

    /**
     * TODO
     */
    protected function _prefsEncryptSelect()
    {
        $default_encrypt = Horde_Util::getFormData('default_encrypt');
        $GLOBALS['prefs']->setValue('default_encrypt', $default_encrypt);
    }

    /**
     * TODO
     */
    protected function _prefsDefaultSearchSelect()
    {
        $default_search = Horde_Util::getFormData('default_search');
        $GLOBALS['prefs']->setValue('default_search', $default_search);
    }

    /**
     * TODO
     */
    protected function _prefsFlagManagement()
    {
        $imp_flags = IMP_Imap_Flags::singleton();
        $action = Horde_Util::getFormData('flag_action');
        $data = Horde_Util::getFormData('flag_data');

        if ($action == 'add') {
            $imp_flags->addFlag($data);
            return;
        }

        $def_color = $GLOBALS['prefs']->getValue('msgflags_color');

        // Don't set updated on these actions. User may want to do more actions.
        foreach ($imp_flags->getList() as $key => $val) {
            $md5 = hash('md5', $key);

            switch ($action) {
            case 'delete':
                if ($data == ('bg_' . $md5)) {
                    $imp_flags->deleteFlag($key);
                }
                break;

            default:
                /* Change labels for user-defined flags. */
                if ($val['t'] == 'imapp') {
                    $label = Horde_Util::getFormData('label_' . $md5);
                    if (strlen($label) && ($label != $val['l'])) {
                        $imp_flags->updateFlag($key, array('l' => $label));
                    }
                }

                /* Change background for all flags. */
                $bg = strtolower(Horde_Util::getFormData('bg_' . $md5));
                if ((isset($val['b']) && ($bg != $val['b'])) ||
                    (!isset($val['b']) && ($bg != $def_color))) {
                        $imp_flags->updateFlag($key, array('b' => $bg));
                }
                break;
            }
        }
    }

    /* Horde_Auth_Application defined functions. */

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
        if (($params = $this->server()) === null) {
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
        if (($params = $this->server()) === null) {
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
        if (($params = $this->server()) === null) {
            return;
        }

        $params = array_merge($params, $_SESSION['imp']['admin']['params']);
        if (isset($params['admin_password'])) {
            $params['admin_password'] = Horde_Secret::read(Horde_Secret::getKey('imp'), $params['admin_password']);
        }
        $auth = Horde_Auth::singleton('imap', $params);
        return $auth->listUsers();
    }

    /* IMP-specific functions. */

    /**
     * Returns a compose window link.
     *
     * @param string|array $args  List of arguments to pass to compose.php.
     *                            If this is passed in as a string, it will be
     *                            parsed as a
     *                            toaddress?subject=foo&cc=ccaddress
     *                            (mailto-style) string.
     * @param array $extra        Hash of extra, non-standard arguments to
     *                            pass to compose.php.
     *
     * @return string  The link to the message composition screen.
     */
    public function compose($args = array(), $extra = array())
    {
        $link = $this->batchCompose(array($args), array($extra));
        return $link[0];
    }

    /**
     * Return a list of compose window links.
     *
     * @param string|array $args  List of arguments to pass to compose.php.
     *                            If this is passed in as a string, it will be
     *                            parsed as a
     *                            toaddress?subject=foo&cc=ccaddress
     *                            (mailto-style) string.
     * @param array $extra        List of hashes of extra, non-standard
     *                            arguments to pass to compose.php.
     *
     * @return string  The list of links to the message composition screen.
     */
    public function batchCompose($args = array(), $extra = array())
    {
        $GLOBALS['imp_authentication'] = 'none';
        require_once dirname(__FILE__) . '/base.php';

        $links = array();
        foreach ($args as $i => $arg) {
            $links[$i] = IMP::composeLink($arg, !empty($extra[$i]) ? $extra[$i] : array());
        }

        return $links;
    }

    /**
     * Returns the list of folders.
     *
     * @return array  The list of IMAP folders or false if not available.
     */
    public function folderlist()
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            return false;
        }

        $imp_folder = IMP_Folder::singleton();
        return $imp_folder->flist();
    }

    /**
     * Creates a new folder.
     *
     * @param string $folder  The name of the folder to create (UTF7-IMAP).
     *
     * @return string  The full folder name created or false on failure.
     */
    public function createFolder($folder)
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            return false;
        }

        $imp_folder = IMP_Folder::singleton();
        return $imp_folder->create(IMP::appendNamespace($folder), $GLOBALS['prefs']->getValue('subscribe'));
    }

    /**
     * Deletes messages from a mailbox.
     *
     * @param string $mailbox  The name of the mailbox (UTF7-IMAP).
     * @param array $indices   The list of UIDs to delete.
     *
     * @return integer|boolean  The number of messages deleted if successful,
     *                          false if not.
     */
    public function deleteMessages($mailbox, $indices)
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            return false;
        }

        $imp_message = IMP_Message::singleton();
        return $imp_message->delete(array($mailbox => $indices), array('nuke' => true));
    }

    /**
     * Copies messages to a mailbox.
     *
     * @param string $mailbox  The name of the source mailbox (UTF7-IMAP).
     * @param array $indices   The list of UIDs to copy.
     * @param string $target   The name of the target mailbox (UTF7-IMAP).
     *
     * @return boolean  True if successful, false if not.
     */
    public function copyMessages($mailbox, $indices, $target)
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            return false;
        }

        $imp_message = IMP_Message::singleton();
        return $imp_message->copy($target, 'copy', array($mailbox => $indices), true);
    }

    /**
     * Moves messages to a mailbox.
     *
     * @param string $mailbox  The name of the source mailbox (UTF7-IMAP).
     * @param array $indices   The list of UIDs to move.
     * @param string $target   The name of the target mailbox (UTF7-IMAP).
     *
     * @return boolean  True if successful, false if not.
     */
    public function moveMessages($mailbox, $indices, $target)
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            return false;
        }

        $imp_message = IMP_Message::singleton();
        return $imp_message->copy($target, 'move', array($mailbox => $indices), true);
    }

    /**
     * Flag messages.
     *
     * @param string $mailbox  The name of the source mailbox (UTF7-IMAP).
     * @param array $indices   The list of UIDs to flag.
     * @param array $flags     The flags to set.
     * @param boolean $set     True to set flags, false to clear flags.
     *
     * @return boolean  True if successful, false if not.
     */
    public function flagMessages($mailbox, $indices, $flags, $set)
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            return false;
        }

        $imp_message = IMP_Message::singleton();
        return $imp_message->flag($flags, 'move', array($mailbox => $indices), $set);
    }

    /**
     * Return envelope information for the given list of indices.
     *
     * @param string $mailbox  The name of the mailbox (UTF7-IMAP).
     * @param array $indices   The list of UIDs.
     *
     * @return array|boolean  TODO if successful, false if not.
     */
    public function msgEnvelope($mailbox, $indices)
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            return false;
        }

        return $GLOBALS['imp_imap']->ob->fetch($mailbox, array(Horde_Imap_Client::FETCH_ENVELOPE => true), array('ids' => $indices));
    }

    /**
     * Perform a search query on the remote IMAP server.
     *
     * @param string $mailbox                        The name of the source
     *                                               mailbox (UTF7-IMAP).
     * @param Horde_Imap_Client_Search_Query $query  The query object.
     *
     * @return array|boolean  The search results (UID list) or false.
     */
    public function searchMailbox($mailbox, $query)
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            return false;
        }

        return $GLOBALS['imp_search']->runSearchQuery($query, $mailbox);
    }

    /**
     * Returns the cache ID value for a mailbox
     *
     * @param string $mailbox  The name of the source mailbox (UTF7-IMAP).
     *
     * @return string|boolean  The cache ID value, or false if not
     *                         authenticated.
     */
    public function mailboxCacheId($mailbox)
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            return false;
        }

        return $GLOBALS['imp_imap']->ob->getCacheId($mailbox);
    }

    /**
     * Returns information on the currently logged on IMAP server.
     *
     * @return mixed  Returns null if the user has not authenticated into IMP
     *                yet Otherwise, an array with the following entries:
     * <pre>
     * 'hostspec' - (string) The server hostname.
     * 'port' - (integer) The server port.
     * 'protocol' - (string) Either 'imap' or 'pop'.
     * 'secure' - (string) Either 'none', 'ssl', or 'tls'.
     * </pre>
     */
    public function server()
    {
        try {
            $GLOBALS['imp_authentication'] = 'throw';
            require_once dirname(__FILE__) . '/base.php';
        } catch (Horde_Exception $e) {
            return null;
        }

        $imap_obj = unserialize($_SESSION['imp']['imap_ob']);
        return array(
            'hostspec' => $imap_obj->getParam('hostspec'),
            'port' => $imap_obj->getParam('port'),
            'protocol' => $_SESSION['imp']['protocol'],
            'secure' => $imap_obj->getParam('secure')
        );
    }

    /**
     * Returns the list of favorite recipients.
     *
     * @param integer $limit  Return this number of recipients.
     * @param array $filter   A list of messages types that should be returned.
     *                        A value of null returns all message types.
     *
     * @return array  A list with the $limit most favourite recipients.
     */
    public function favouriteRecipients($limit,
                                        $filter = array('new', 'forward', 'reply', 'redirect'))
    {
        $GLOBALS['imp_authentication'] = 'none';
        require_once dirname(__FILE__) . '/base.php';

        if ($GLOBALS['conf']['sentmail']['driver'] != 'none') {
            $sentmail = IMP_Sentmail::factory();
            return $sentmail->favouriteRecipients($limit, $filter);
        }

        return array();
    }

}
