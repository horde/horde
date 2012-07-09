<?php
/**
 * THis class provides authentication for IMP.
 *
 * The following is the list of IMP session variables:
 *   - compose_cache: (array) List of compose objects that have not yet been
 *                    garbage collected.
 *   - csearchavail: (boolean) True if contacts search is available.
 *   - file_upload: (integer) If file uploads are allowed, the max size.
 *   - filteravail: (boolean) Can we apply filters manually?
 *   - imap_acl: (boolean) See 'acl' entry in config/backends.php.
 *   - imap_admin: (array) See 'admin' entry in config/backends.php.
 *   - imap_namespace: (array) See 'namespace' entry in config/backends.php
 *   - imap_ob/*: (Horde_Imap_Client_Base) The IMAP client objects. Stored by
 *                server key.
 *   - imap_quota: (array) See 'quota' entry in config/backends.php.
 *   - imap_thread: (string) The trheading algorithm supported by the server.
 *   - maildomain: (string) See 'maildomain' entry in config/backends.php.
 *   - notepadavail: (boolean) Is listing of notepads available?
 *   - pgp: (array) Cached PGP passhprase values.
 *   - rteavail: (boolean) Is the HTML editor available?
 *   - search: (IMP_Search) The IMP_Search object.
 *   - server_key: (string) Server used to login.
 *   - smime: (array) Settings related to the S/MIME viewer.
 *   - smtp: (array) SMTP configuration.
 *   - showunsub: (boolean) Show unsusubscribed mailboxes on the folders
 *                screen.
 *   - tasklistavail: (boolean) Is listing of tasklists available?
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Auth
{
    /**
     * Authenticate to the mail server.
     *
     * @param array $credentials  An array of login credentials. If empty,
     *                            attempts to login to the cached session.
     *   - password: (string) The user password.
     *   - server: (string) The server key to use (from backends.php).
     *   - userId: (string) The username.
     *
     * @return mixed  If authentication was successful, and no session
     *                exists, an array of data to add to the session.
     *                Otherwise returns false.
     * @throws Horde_Auth_Exception
     */
    static public function authenticate($credentials = array())
    {
        global $injector, $registry;

        $result = false;

        // Do 'horde' authentication.
        $imp_app = $registry->getApiInstance('imp', 'application');
        if (!empty($imp_app->initParams['authentication']) &&
            ($imp_app->initParams['authentication'] == 'horde')) {
            if ($registry->getAuth()) {
                return $result;
            }
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        if (!isset($credentials['server'])) {
            $credentials['server'] = self::getAutoLoginServer();
        }

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create($credentials['server']);

        // Check for valid IMAP Client object.
        if (!$imp_imap->ob) {
            if (!isset($credentials['userId']) ||
                !isset($credentials['password'])) {
                throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
            }

            try {
                $imp_imap->createImapObject($credentials['userId'], $credentials['password'], $credentials['server']);
            } catch (IMP_Imap_Exception $e) {
                self::_logMessage(false, $imp_imap);
                throw $e->authException();
            }

            $result = array(
                'server_key' => $credentials['server']
            );
        }

        try {
            $imp_imap->login();
        } catch (IMP_Imap_Exception $e) {
            self::_logMessage(false, $imp_imap);
            throw $e->authException();
        }

        return $result;
    }

    /**
     * Perform transparent authentication.
     *
     * @param Horde_Auth_Application $auth_ob  The authentication object.
     *
     * @return mixed  If authentication was successful, and no session
     *                exists, an array of data to add to the session.
     *                Otherwise returns false.
     */
    static public function transparent($auth_ob)
    {
        $credentials = $auth_ob->getCredential('credentials');

        if (empty($credentials['transparent'])) {
            /* Attempt hordeauth authentication. */
            $credentials = self::_canAutoLogin();
            if ($credentials === false) {
                return false;
            }
        } else {
            /* It is possible that preauthenticate() set the credentials.
             * If so, use that information instead of hordeauth. */
            $credentials['userId'] = $auth_ob->getCredential('userId');
        }

        try {
            return self::authenticate($credentials);
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

    /**
     * Log login related message.
     *
     * @param boolean $success   True on success, false on failure.
     * @param IMP_Imap $imap_ob  The IMP_Imap object to use.
     */
    static protected function _logMessage($status, $imap_ob)
    {
        if ($status) {
            $msg = 'Login success';
            $level = 'NOTICE';
        } else {
            $msg = 'FAILED LOGIN';
            $level = 'INFO';
        }

        $user = $imap_ob->getParam('username');
        if (($auth_id = $GLOBALS['registry']->getAuth()) !== false) {
            $user .= ' (Horde user ' . $auth_id . ')';
        }

        $protocol = $imap_ob->imap
            ? 'imap'
            : ($imap_ob->pop3 ? 'pop' : '');

        $msg = sprintf(
            $msg . ' for %s [%s]%s to {%s:%s%s}',
            $user,
            $_SERVER['REMOTE_ADDR'],
            empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? '' : ' (forwarded for [' . $_SERVER['HTTP_X_FORWARDED_FOR'] . '])',
            $imap_ob->ob ? $imap_ob->getParam('hostspec') : '',
            $imap_ob->ob ? $imap_ob->getParam('port') : '',
            $protocol ? ' [' . $protocol . ']' : ''
        );

        Horde::logMessage($msg, $level);
    }

    /**
     * Returns the autologin server key.
     *
     * @return string  The server key, or null if none available.
     */
    static public function getAutoLoginServer()
    {
        if (($servers = IMP_Imap::loadServerConfig()) === false) {
            return null;
        }

        $server_key = null;
        foreach ($servers as $key => $val) {
            if (is_null($server_key) && substr($key, 0, 1) != '_') {
                $server_key = $key;
            }
            if (self::isPreferredServer($val, $key)) {
                $server_key = $key;
                break;
            }
        }

        return $server_key;
    }

    /**
     * Determines if the given mail server is the "preferred" mail server for
     * this web server.  This decision is based on the global 'SERVER_NAME'
     * and 'HTTP_HOST' server variables and the contents of the 'preferred'
     * field in the server's definition.  The 'preferred' field may take a
     * single value or an array of multiple values.
     *
     * @param string $server  A complete server entry from the $servers hash.
     * @param string $key     The server key entry.
     *
     * @return boolean  True if this entry is "preferred".
     */
    static public function isPreferredServer($server, $key = null)
    {
        if (empty($server['preferred'])) {
            return false;
        }

        $preferred = is_array($server['preferred'])
            ? $server['preferred']
            : array($server['preferred']);

        return in_array($_SERVER['SERVER_NAME'], $preferred) ||
               in_array($_SERVER['HTTP_HOST'], $preferred);
    }

    /**
     * Returns whether we can log in without a login screen for $server_key.
     *
     * @param string $server_key  The server to check. Defaults to the
     *                            autologin server.
     * @param boolean $force      If true, check $server_key even if there is
     *                            more than one server available.
     *
     * @return array  The credentials needed to login ('userId', 'password',
     *                 'server') or false if autologin not available.
     */
    static protected function _canAutoLogin($server_key = null, $force = false)
    {
        if (($servers = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->loadServerConfig()) === false) {
            return false;
        }

        if (is_null($server_key) || !$force) {
            $auto_server = self::getAutoLoginServer();
            if (is_null($server_key)) {
                $server_key = $auto_server;
            }
        }

        if ((!empty($auto_server) || $force) &&
            $GLOBALS['registry']->getAuth() &&
            !empty($servers[$server_key]['hordeauth'])) {
            return array(
                'userId' => $GLOBALS['registry']->getAuth((strcasecmp($servers[$server_key]['hordeauth'], 'full') == 0) ? null : 'bare'),
                'password' => $GLOBALS['registry']->getAuthCredential('password'),
                'server' => $server_key
            );
        }

        return false;
    }

    /**
     * Returns the initial page.
     *
     * @return object  Object with the following properties:
     *   - fullpath (string)
     *   - mbox (IMP_Mailbox)
     *   - page (string)
     *   - url (Horde_Url)
     */
    static public function getInitialPage()
    {
        $init_url = $GLOBALS['prefs']->getValue('initial_page');
        if (!$init_url ||
            !$GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS)) {
            $init_url = 'INBOX';
        }

        if ($init_url == IMP::INITIAL_FOLDERS) {
            $mbox = null;
        } else {
            $mbox = IMP_Mailbox::get($init_url);
            if (!$mbox->exists) {
                $mbox = IMP_Mailbox::get('INBOX');
            }

            IMP::setMailboxInfo($mbox);
        }

        $result = new stdClass;
        $result->mbox = $mbox;

        switch ($GLOBALS['registry']->getView()) {
        case Horde_Registry::VIEW_BASIC:
            if (is_null($mbox)) {
                $page = 'folders.php';
            } else {
                $page = 'mailbox.php';
                $result->url = $mbox->url($page);
            }
            break;

        case Horde_Registry::VIEW_DYNAMIC:
            $result->url = IMP_Dynamic_Mailbox::url(array(
                'mailbox' => is_null($mbox) ? 'INBOX' : $mbox
            ));
            $page = 'dynamic.php';
            break;

        case Horde_Registry::VIEW_MINIMAL:
            $page = 'minimal.php';
            $result->url = is_null($mbox)
                ? IMP_Minimal_Folders::url()
                : IMP_Minimal_Mailbox::url(array('mailbox' => $mbox));
            break;

        case Horde_Registry::VIEW_SMARTMOBILE:
            $page = 'smartmobile.php';
            if (!is_null($mbox)) {
                $result->url = $mbox->url('mailbox');
            }
            break;
        }

        $result->fullpath = IMP_BASE . '/' . $page;
        $result->page = $page;

        if (!isset($result->url)) {
            $result->url = Horde::url($page, true);
        }

        return $result;
    }

    /**
     * Perform post-login tasks. Session creation requires the full IMP
     * environment, which is not available until this callback.
     *
     * @throws Horde_Exception
     */
    static public function authenticateCallback()
    {
        global $browser, $conf, $injector, $prefs, $registry, $session;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        $ptr = $imp_imap->loadServerConfig($session->get('imp', 'server_key'));
        if ($ptr === false) {
            throw new Horde_Exception(_("Could not initialize mail server configuration."));
        }

        /* Set the maildomain. */
        $maildomain = $prefs->getValue('mail_domain');
        $session->set('imp', 'maildomain', $maildomain ? $maildomain : (isset($ptr['maildomain']) ? $ptr['maildomain'] : ''));

        /* Store some basic IMAP server information. */
        if ($imp_imap->imap) {
            /* Can't call this until now, since we need prefs to be properly
             * loaded to grab the special mailboxes information. */
            $imp_imap->updateFetchIgnore();

            if (!empty($ptr['acl'])) {
                $session->set('imp', 'imap_acl', $ptr['acl']);
            }

            $secret = $injector->getInstance('Horde_Secret');
            if (!empty($ptr['admin'])) {
                $tmp = $ptr['admin'];
                if (isset($tmp['password'])) {
                    $tmp['password'] = $secret->write($secret->getKey(), $tmp['password']);
                }
                $session->set('imp', 'imap_admin', $tmp);
            }

            if (!empty($ptr['namespace'])) {
                $session->set('imp', 'imap_namespace', $ptr['namespace']);
            }

            if (!empty($ptr['quota'])) {
                $tmp = $ptr['quota'];
                if (isset($tmp['params']['password'])) {
                    $tmp['params']['password'] = $secret->write($secret->getKey(), $tmp['params']['password']);
                }
                $session->set('imp', 'imap_quota', $tmp);
            }

            /* Set the IMAP threading algorithm. */
            $thread_cap = $imp_imap->queryCapability('THREAD');
            $session->set(
                'imp',
                'imap_thread',
                in_array(isset($ptr['thread']) ? strtoupper($ptr['thread']) : 'REFERENCES', is_array($thread_cap) ? $thread_cap : array())
                    ? 'REFERENCES'
                    : 'ORDEREDSUBJECT'
            );
        }

        /* Set the SMTP configuration. */
        if ($conf['mailer']['type'] == 'smtp') {
            $session->set('imp', 'smtp', array_merge(
                $conf['mailer']['params'],
                empty($ptr['smtp']) ? array() : $ptr['smtp']
            ));
        }

        /* Does the server allow file uploads? If yes, store the
         * value, in bytes, of the maximum file size. */
        $session->set('imp', 'file_upload', $browser->allowFileUploads());

        /* Is the 'contacts/search' API call available? */
        if ($registry->hasMethod('contacts/search')) {
            $session->set('imp', 'csearchavail', true);
        }

        /* Is the 'mail/canApplyFilters' API call available? */
        try {
            if ($registry->call('mail/canApplyFilters')) {
                $session->set('imp', 'filteravail', true);
            }
        } catch (Horde_Exception $e) {}

        /* Is the 'tasks/listTasklists' call available? */
        if ($conf['tasklist']['use_tasklist'] &&
            $registry->hasMethod('tasks/listTasklists')) {
            $session->set('imp', 'tasklistavail', true);
        }

        /* Is the 'notes/listNotepads' call available? */
        if ($conf['notepad']['use_notepad'] &&
            $registry->hasMethod('notes/listNotepads')) {
            $session->set('imp', 'notepadavail', true);
        }

        /* Is the HTML editor available? */
        $session->set('imp', 'rteavail', $injector->getInstance('Horde_Editor')->supportedByBrowser());

        self::_logMessage(true, $imp_imap);
    }

}
