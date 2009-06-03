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

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray');

$_services['authCredentials'] = array(
    'args' => array(),
    'type' => '{urn:horde}hashHash'
);

$_services['authenticate'] = array(
    'args' => array('userID' => 'string', 'credentials' => '{urn:horde}hash', 'params' => '{urn:horde}hash'),
    'checkperms' => false,
    'type' => 'boolean'
);

$_services['compose'] = array(
    'args' => array('args' => '{urn:horde}hash', 'extra' => '{urn:horde}hash'),
    'type' => 'string'
);

$_services['batchCompose'] = array(
    'args' => array('args' => '{urn:horde}hash', 'extra' => '{urn:horde}hash'),
    'type' => 'string'
);

$_services['folderlist'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray'
);

$_services['createFolder'] = array(
    'args' => array('folder' => 'string'),
    'type' => 'string'
);

$_services['deleteMessages'] = array(
    'args' => array('mailbox' => 'string', 'indices' => '{urn:horde}integerArray'),
    'type' => 'integer'
);

$_services['copyMessages'] = array(
    'args' => array('mailbox' => 'string', 'indices' => '{urn:horde}integerArray', 'target' => 'string'),
    'type' => 'boolean'
);

$_services['moveMessages'] = array(
    'args' => array('mailbox' => 'string', 'indices' => '{urn:horde}integerArray', 'target' => 'string'),
    'type' => 'boolean'
);

$_services['flagMessages'] = array(
    'args' => array('mailbox' => 'string', 'indices' => '{urn:horde}integerArray', 'flags' => '{urn:horde}stringArray', 'set' => 'boolean'),
    'type' => 'boolean'
);

$_services['msgEnvelope'] = array(
    'args' => array('mailbox' => 'string', 'indices' => '{urn:horde}integerArray'),
    'type' => '{urn:horde}hashHash'
);

$_services['searchMailbox'] = array(
    'args' => array('mailbox' => 'string', 'query' => 'object'),
    'type' => '{urn:horde}integerArray'
);

$_services['mailboxCacheId'] = array(
    'args' => array('mailbox' => 'string'),
    'type' => 'string'
);

$_services['server'] = array(
    'args' => array(),
    'type' => 'string'
);

$_services['favouriteRecipients'] = array(
    'args' => array('limit' => 'int'),
    'type' => '{urn:horde}stringArray'
);

$_services['changeLanguage'] = array(
    'args' => array(),
    'type' => 'boolean'
);

if (!empty($_SESSION['imp']['admin'])) {
    $_services['userList'] = array(
        'type' => '{urn:horde}stringArray'
    );

    $_services['addUser'] = array(
        'args' => array('userId' => 'string')
    );

    $_services['removeUser'] = array(
        'args' => array('userId' => 'string')
    );
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

    require_once dirname(__FILE__) . '/Imap.php';
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
 * Tries to authenticate with the mail server and create a mail session.
 *
 * @param string $userID The username of the user.
 * @param array $credentials Credentials of the user. Only allowed key:
 * 'password'.
 * @param array $params Additional parameters. Only allowed key:
 * 'server'.
 *
 * @return boolean True on success, false on failure.
 */
function _imp_authenticate($userID, $credentials, $params = array())
{
    $GLOBALS['authentication'] = 'none';
    $GLOBALS['noset_view'] = true;
    require_once dirname(__FILE__) . '/base.php';
    require_once IMP_BASE . '/lib/Session.php';

    $server_key = empty($params['server'])
    ? IMP_Session::getAutoLoginServer()
    : $params['server'];

     return IMP_Session::createSession($userID, $credentials['password'], $server_key);
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
    $GLOBALS['authentication'] = 'none';
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
    $GLOBALS['authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    if (IMP::checkAuthentication(true)) {
        $imp_folder = &IMP_Folder::singleton();
        return $imp_folder->flist();
    }

    return false;
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
    $GLOBALS['authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    if (IMP::checkAuthentication(true)) {
        $imp_folder = &IMP_Folder::singleton();
        return $imp_folder->create(IMP::appendNamespace($folder), $GLOBALS['prefs']->getValue('subscribe'));
    }

    return false;
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
    $GLOBALS['authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    if (IMP::checkAuthentication(true)) {
        $imp_message = &IMP_Message::singleton();
        return $imp_message->delete(array($mailbox => $indices), array('nuke' => true));
    }

    return false;
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
    $GLOBALS['authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    if (IMP::checkAuthentication(true)) {
        $imp_message = &IMP_Message::singleton();
        return $imp_message->copy($target, 'copy', array($mailbox => $indices), true);
    }

    return false;
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
    $GLOBALS['authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    if (IMP::checkAuthentication(true)) {
        $imp_message = &IMP_Message::singleton();
        return $imp_message->copy($target, 'move', array($mailbox => $indices), true);
    }

    return false;
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
    $GLOBALS['authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    if (IMP::checkAuthentication(true)) {
        $imp_message = &IMP_Message::singleton();
        return $imp_message->flag($flags, 'move', array($mailbox => $indices), $set);
    }

    return false;
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
    $GLOBALS['authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    return IMP::checkAuthentication(true)
        ? $GLOBALS['imp_imap']->ob->fetch($mailbox, array(Horde_Imap_Client::FETCH_ENVELOPE => true), array('ids' => $indices))
        : false;
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
    $GLOBALS['authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    return IMP::checkAuthentication(true)
        ? $GLOBALS['imp_search']->runSearchQuery($mailbox, $query)
        : false;
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
    $GLOBALS['authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    return IMP::checkAuthentication(true)
        ? $GLOBALS['imp_imap']->ob->getCacheId($mailbox);
        : null;
}

/**
 * Returns the currently logged on IMAP server.
 *
 * @return string  The server hostname.  Returns null if the user has not
 *                 authenticated into IMP yet.
 */
function _imp_server()
{
    $GLOBALS['authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    return IMP::checkAuthentication(true)
        ? $_SESSION['imp']['server']
        : null;
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
    $GLOBALS['authentication'] = 'none';
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
    $GLOBALS['authentication'] = 'none';
    require_once dirname(__FILE__) . '/base.php';

    if (IMP::checkAuthentication(true)) {
        $imp_folder = &IMP_Folder::singleton();
        $imp_folder->clearFlistCache();
        $imaptree = &IMP_Imap_Tree::singleton();
        $imaptree->init();
        $GLOBALS['imp_search']->sessionSetup(true);
    }
}

/**
 * Adds a set of authentication credentials.
 *
 * @param string $userId  The userId to add.
 *
 * @return boolean  True on success or a PEAR_Error object on failure.
 */
function _imp_addUser($userId)
{
    return _imp_adminDo('add', array($userId));
}

/**
 * Deletes a set of authentication credentials.
 *
 * @param string $userId  The userId to delete.
 *
 * @return boolean  True on success or a PEAR_Error object on failure.
 */
function _imp_removeUser($userId)
{
    return _imp_adminDo('remove', array($userId));
}

/**
 * Lists all users in the system.
 *
 * @return array  The array of userIds, or a PEAR_Error object on failure.
 */
function _imp_userList()
{
    return _imp_adminDo('list', array());
}

/**
 * Private function to perform an admin event.
 */
function _imp_adminDo($task, $params)
{
    require_once 'Horde/IMAP/Admin.php';

    $admin_params = $_SESSION['imp']['admin']['params'];
    $admin_params['admin_user'] = $admin_params['login'];
    $admin_params['admin_password'] = Horde_Secret::read(IMP::getAuthKey(), $admin_params['password']);
    $imap = new IMAP_Admin($admin_params);

    switch ($task) {
    case 'add':
        return $imap->addMailbox(String::convertCharset($params[0], NLS::getCharset(), 'utf7-imap'));

    case 'remove':
        return $imap->removeMailbox(String::convertCharset($params[0], NLS::getCharset(), 'utf7-imap'));

    case 'list':
        return $imap->listMailboxes();
    }
}
