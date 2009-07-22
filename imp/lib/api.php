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

$_services = array(
    'perms' => array(
        'args' => array(),
        'type' => '{urn:horde}stringArray'
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
        'args' => array(),
        'type' => 'boolean'
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
        'args' => array()
    ),

    'authTransparent' => array(
        'args' => array(),
        'checkperms' => false,
        'type' => 'boolean'
    )
);

/* Only available if admin config is set for this server/login. */
if (!empty($_SESSION['imp']['admin'])) {
    $_services = array_merge($_services, array(
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
        )
    ));
}

/**
 * Returns a list of available permissions.
 */
function _imp_perms()
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
 * Returns a list of authentication credentials, i.e. server settings that can
 * be specified by the user on the login screen.
 *
 * @return array  A hash with credentials, suited for the preferences
 *                interface.
 */
function _imp_authCredentials()
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
 * Returns a compose window link.
 *
 * @param string|array $args  List of arguments to pass to compose.php.
 *                            If this is passed in as a string, it will be
 *                            parsed as a toaddress?subject=foo&cc=ccaddress
 *                            (mailto-style) string.
 * @param array $extra        Hash of extra, non-standard arguments to pass to
 *                            compose.php.
 *
 * @return string  The link to the message composition screen.
 */
function _imp_compose($args = array(), $extra = array())
{
    $link = _imp_batchCompose(array($args), array($extra));
    return $link[0];
}

/**
 * Return a list of compose window links.
 *
 * @param mixed $args   List of lists of arguments to pass to compose.php. If
 *                      the lists are passed in as strings, they will be parsed
 *                      as toaddress?subject=foo&cc=ccaddress (mailto-style)
 *                      strings.
 * @param array $extra  List of hashes of extra, non-standard arguments to pass
 *                      to compose.php.
 *
 * @return string  The list of links to the message composition screen.
 */
function _imp_batchCompose($args = array(), $extra = array())
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
function _imp_folderlist()
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
function _imp_createFolder($folder)
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
function _imp_deleteMessages($mailbox, $indices)
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
function _imp_copyMessages($mailbox, $indices, $target)
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
function _imp_moveMessages($mailbox, $indices, $target)
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
function _imp_flagMessages($mailbox, $indices, $flags, $set)
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
function _imp_msgEnvelope($mailbox, $indices)
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
 * @param string $mailbox                        The name of the source mailbox
 *                                               (UTF7-IMAP).
 * @param Horde_Imap_Client_Search_Query $query  The query object.
 *
 * @return array|boolean  The search results (UID list) or false.
 */
function _imp_searchMailbox($mailbox, $query)
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
 * @return string|boolean  The cache ID value, or false if not authenticated.
 */
function _imp_mailboxCacheId($mailbox)
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
 * @return mixed  Returns null if the user has not authenticated into IMP yet.
 *                Otherwise, an array with the following entries:
 * <pre>
 * 'hostspec' - (string) The server hostname.
 * 'port' - (integer) The server port.
 * 'protocol' - (string) Either 'imap' or 'pop'.
 * 'secure' - (string) Either 'none', 'ssl', or 'tls'.
 * </pre>
 */
function _imp_server()
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
function _imp_favouriteRecipients($limit,
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

/**
 * Performs tasks necessary when the language is changed during the session.
 */
function _imp_changeLanguage()
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
    $GLOBALS['imp_search']->sessionSetup(true);
}

/**
 * Application-specific cache output driver.
 *
 * @param array $params  A list of params needed (USED: 'id').
 *
 * @return array  See Horde::getCacheUrl().
 * @throws Horde_Exception
 */
function _imp_cacheOutput($params)
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
                "FCKConfig.EnterMode = \'br\';\n" .
                'FCKConfig.ShiftEnterMode = \'p\';',
            'type' => 'text/javascript'
        );
    }
}

/*
 * TODO
 */
function _imp_authLoginParams()
{
    $params = array();

    if ($GLOBALS['conf']['server']['server_list'] == 'shown') {
        $servers = IMP_Imap::loadServerConfig();
        $server_list = array();
        foreach ($servers as $key => $val) {
            $server_list[$key] = array('name' => $val['name']);
        }
        $params['imp_server_key'] = array(
            'label' => _("Server"),
            'selected' => Horde_Util::getFormData('imp_server_key', IMP_Auth::getAutoLoginServer()),
            'type' => 'select',
            'value' => $server_list
        );
    }

    /* If dimp/mimp are available, show selection of alternate views. */
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
function _imp_authAuthenticate($userId, $credentials)
{
    $GLOBALS['imp_authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    $new_session = IMP_Auth::authenticate(array(
        'password' => $credentials['password'],
        'server' => empty($credentials['imp_server_key']) ? IMP_Auth::getAutoLoginServer() : $credentials['imp_server_key'],
        'userid' => $userId
    ));

    if ($new_session) {
        $_SESSION['imp']['cache']['select_view'] = empty($credentials['imp_select_view']) ? '' : $credentials['imp_select_view'];

        /* Set the Horde ID, since it may have been altered by the 'realm'
         * setting. */
        $credentials['auth_ob']->setCredential('userId', $_SESSION['imp']['uniquser']);
    }
}

/**
 * Tries to transparently authenticate with the mail server and create a mail
 * session.
 *
 * @return boolean  Whether transparent login is supported.
 * @throws Horde_Auth_Exception
 */
function _imp_authTransparent()
{
    /* Transparent auth is a bit goofy - we most likely have reached this
     * code from the pushApp() call in base.php already. As such, some of the
     * IMP init has not yet been done, so we need to do the necessary init
     * here or else things will fail in IMP_Auth. */
    $GLOBALS['imp_authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';
    if (!isset($GLOBALS['imp_imap'])) {
        $GLOBALS['imp_imap'] = new IMP_Imap();
    }
    if (!isset($GLOBALS['imp_search'])) {
        $GLOBALS['imp_search'] = new IMP_Search();
    }

    return IMP_Auth::transparent();
}

/**
 * Does necessary authentication tasks reliant on a full IMP environment.
 *
 * @throws Horde_Auth_Exception
 */
function _imp_authAuthenticateCallback()
{
    require_once dirname(__FILE__) . '/base.php';
    IMP_Auth::authenticateCallback();
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
function _imp_authAddUser($userId, $credentials)
{
    if (($params = _imp_server()) === null) {
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
function _imp_authRemoveUser($userId)
{
    if (($params = _imp_server()) === null) {
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
function _imp_authUserList()
{
    if (($params = _imp_server()) === null) {
        return;
    }

    $params = array_merge($params, $_SESSION['imp']['admin']['params']);
    if (isset($params['admin_password'])) {
        $params['admin_password'] = Horde_Secret::read(Horde_Secret::getKey('imp'), $params['admin_password']);
    }
    $auth = Horde_Auth::singleton('imap', $params);
    return $auth->listUsers();
}
