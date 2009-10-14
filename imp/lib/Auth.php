<?php
/**
 * The IMP_Auth:: class provides authentication for IMP.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Auth
{
    /**
     * The preferred server based on the value from the login form.
     *
     * @var string
     */
    static public $prefServer = null;

    /**
     * Authenticate to the mail server.
     *
     * @param array $credentials  An array of login credentials. If empty,
     *                            attempts to login to the cached session.
     * <pre>
     * 'password' - (string) The user password.
     * 'server' - (string) The server key to use (from servers.php).
     * 'userId' - (string) The username.
     * </pre>
     *
     * @return boolean  True if session was created, false if pre-existing
     *                  session used.
     * @throws Horde_Auth_Exception
     */
    static public function authenticate($credentials = array())
    {
        // Do 'horde' authentication.
        if (IMP_Application::$authType == 'horde') {
            if (Horde_Auth::getAuth()) {
                return false;
            }
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        // Check for valid IMAP Client object.
        if (!$GLOBALS['imp_imap']->ob()) {
            if (!isset($credentials['userId']) ||
                !isset($credentials['password'])) {
                throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
            }

            if (!isset($credentials['server'])) {
                $credentials['server'] = self::getAutoLoginServer();
            }

            /* _createSession() will create the imp session variable, so there
             * is no concern for an infinite loop here. */
            if (!isset($_SESSION['imp'])) {
                self::_createSession($credentials);
                return true;
            }

            if (!$GLOBALS['imp_imap']->createImapObject($credentials['userId'], $credentials['password'], $credentials['server'])) {
                self::logMessage('failed', __FILE__, __LINE__);
                throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
            }
        }

        try {
            $GLOBALS['imp_imap']->ob()->login();
        } catch (Horde_Imap_Client_Exception $e) {
            self::logMessage($e->getMessage(), __FILE__, __LINE__);
            if ($e->getCode() == Horde_Imap_Client_Exception::SERVER_CONNECT) {
                throw new Horde_Auth_Exception(_("Could not connect to the remote server."));
            }

            throw new Horde_Auth_Exception($e->getMessage());
        }

        return false;
    }

    /**
     * Perform transparent authentication.
     *
     * @return boolean  Whether transparent login is supported.
     * @throws Horde_Auth_Exception
     */
    static public function transparent()
    {
        $credentials = self::_canAutoLogin();
        if ($credentials === false) {
            return false;
        }

        self::_createSession($credentials);
        return true;
    }

    /**
     * Log login related message.
     *
     * @param string $status  Either 'login', 'logout', 'failed', or an
     *                        error message.
     * @param string $file    The file where the error occurred.
     * @param integer $line   The line where the error occurred.
     * @param integer $level  The logging level.
     */
    static public function logMessage($status, $file, $line,
                                      $level = PEAR_LOG_ERR)
    {
        switch ($status) {
        case 'login':
            $status_msg = 'Login success';
            break;

        case 'logout':
            $status_msg = 'Logout';
            break;

        case 'failed':
            $status_msg = 'FAILED LOGIN';
            break;

        default:
            $status_msg = $status;
            break;
        }

        $auth_id = Horde_Auth::getAuth();
        $imap_ob = $GLOBALS['imp_imap']->ob();

        $msg = sprintf(
            $status_msg . '%s [%s]%s to {%s:%s [%s]}',
            $auth_id ? '' : ' for ' . $auth_id,
            $_SERVER['REMOTE_ADDR'],
            empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? '' : ' (forwarded for [' . $_SERVER['HTTP_X_FORWARDED_FOR'] . '])',
            $imap_ob ? $imap_ob->getParam('hostspec') : '',
            $imap_ob ? $imap_ob->getParam('port') : '',
            empty($_SESSION['imp']['protocol']) ? '' : $_SESSION['imp']['protocol']
        );

        Horde::logMessage($msg, $file, $line, $level);
    }

    /**
     * Set up the IMP session. Handle authentication, if required, and only do
     * enough work to see if the user can log in.
     *
     * Creates the 'imp' session variable with the following entries:
     * 'cache'         -- Various IMP libraries can use this variable to cache
     *                    data.
     * 'file_upload'   -- If file uploads are allowed, the max size.
     * 'filteravail'   -- Can we apply filters manually?
     * 'imap'          -- Config for various IMAP resources (acl, admin,
     *                    namespace, quota)
     * 'imap_ob'       -- The serialized Horde_Imap_Client object.
     * 'maildomain'    -- See config/servers.php.
     * 'notepadavail'  -- Is listing of notepads available?
     * 'protocol'      -- Either 'imap' or 'pop'.
     * 'rteavail'      -- Is the HTML editor available?
     * 'search'        -- Settings used by the IMP_Search library.
     * 'server_key'    -- Server used to login.
     * 'smime'         -- Settings related to the S/MIME viewer.
     * 'smtp'          -- SMTP options ('host' and 'port')
     * 'showunsub'     -- Show unsusubscribed mailboxes on the folders screen.
     * 'tasklistavail' -- Is listing of tasklists available?
     * 'view'          -- The imp view mode (currently dimp, imp, or mimp)
     *
     * @param array $credentials  An array of login credentials.
     * <pre>
     * 'password' - (string) The user password.
     * 'server' - (string) The server key to use (from servers.php).
     * 'userId' - (string) The username.
     * </pre>
     *
     * @throws Horde_Auth_Exception
     */
    static protected function _createSession($credentials)
    {
        global $conf;

        /* Create the imp session variable. */
        $_SESSION['imp'] = array(
            'cache' => array(),
            'imap' => array(),
            'server_key' => $credentials['server'],
            'showunsub' => false
        );

        /* Load the server configuration. */
        $ptr = $GLOBALS['imp_imap']->loadServerConfig($credentials['server']);
        if ($ptr === false) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        /* Try authentication. */
        try {
            self::authenticate(array(
                'password' => $credentials['password'],
                'server' => $credentials['server'],
                'userId' => $credentials['userId']
            ));
        } catch (Horde_Auth_Exception $e) {
            unset($_SESSION['imp']);
            throw $e;
        }
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
        if (!is_null(self::$prefServer)) {
            return ($key == self::$prefServer);
        }

        if (!empty($server['preferred'])) {
            if (is_array($server['preferred'])) {
                if (in_array($_SERVER['SERVER_NAME'], $server['preferred']) ||
                    in_array($_SERVER['HTTP_HOST'], $server['preferred'])) {
                    return true;
                }
            } elseif (($server['preferred'] == $_SERVER['SERVER_NAME']) ||
                      ($server['preferred'] == $_SERVER['HTTP_HOST'])) {
                return true;
            }
        }

        return false;
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
        if (($servers = $GLOBALS['imp_imap']->loadServerConfig()) === false) {
            return false;
        }

        if (is_null($server_key) || !$force) {
            $auto_server = self::getAutoLoginServer();
            if (is_null($server_key)) {
                $server_key = $auto_server;
            }
        }

        if ((!empty($auto_server) || $force) &&
            Horde_Auth::getAuth() &&
            !empty($servers[$server_key]['hordeauth'])) {
            return array(
                'userId' => ((strcasecmp($servers[$server_key]['hordeauth'], 'full') == 0)
                    ? Horde_Auth::getAuth()
                    : Horde_Auth::getBareAuth()),
                'password' => Horde_Auth::getCredential('password'),
                'server' => $server_key
            );
        }

        return false;
    }

    /**
     * Returns the initial page.
     *
     * @param boolean $url  Return a URL instead of a file path.
     *
     * @return string  Either the file path or a URL to the initial page.
     */
    static public function getInitialPage($url = false)
    {
        switch ($_SESSION['imp']['view']) {
        case 'dimp':
            $page = 'index-dimp.php';
            break;

        case 'mimp':
            $page = 'mailbox-mimp.php';
            break;

        default:
            $init_url = ($_SESSION['imp']['protocol'] == 'pop')
                ? 'INBOX'
                : $GLOBALS['prefs']->getValue('initial_page');

            $imp_search = new IMP_Search();

            if (!$GLOBALS['prefs']->getValue('use_vinbox') &&
                $imp_search->isVINBOXFolder($init_url)) {
                $init_url = 'folders.php';
            } elseif (($imp_search->createSearchID($init_url) == $init_url) &&
                      !$imp_search->isVFolder($init_url)) {
                $init_url = 'INBOX';
                if (!$GLOBALS['prefs']->isLocked('initial_page')) {
                    $GLOBALS['prefs']->setValue('initial_page', $init_url);
                }
            }

            switch ($init_url) {
            case 'folders.php':
                $page = $init_url;
                break;

            default:
                $page = 'mailbox.php';
                if ($url) {
                    return Horde_Util::addParameter(Horde::applicationUrl($page, true), array('mailbox' => $init_url));
                }
                IMP::setCurrentMailboxInfo($init_url);
                break;
            }
        }

        return $url
            ? Horde::applicationUrl($page, true)
            : IMP_BASE . '/' . $page;
    }

    /**
     * Perform login tasks. Must wait until now because we need the full
     * IMP environment to properly setup the session.
     *
     * @throws Horde_Auth_Exception
     */
    static public function authenticateCallback()
    {
        global $conf;

        $sess = &$_SESSION['imp'];

        $ptr = $GLOBALS['imp_imap']->loadServerConfig($sess['server_key']);
        if ($ptr === false) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        /* Set the protocol. */
        $sess['protocol'] = isset($ptr['protocol'])
            ? $ptr['protocol']
            : 'imap';

        /* Set the maildomain. */
        $maildomain = $GLOBALS['prefs']->getValue('mail_domain');
        $sess['maildomain'] = $maildomain
            ? $maildomain
            : $ptr['maildomain'];

        /* Store some basic IMAP server information. */
        if ($sess['protocol'] == 'imap') {
            foreach (array('acl', 'admin', 'namespace', 'quota') as $val) {
                if (isset($ptr[$val])) {
                    $sess['imap'][$val] = $ptr[$val];

                    /* 'admin' and 'quota' have password entries - encrypt
                     * these entries in the session if they exist. */
                    if (isset($ptr[$val]['params']['password'])) {
                        $sess['imap'][$val]['params']['password'] = Horde_Secret::write(Horde_Secret::getKey('imp'), $ptr[$val]['params']['password']);
                    }
                }
            }
        }

        /* Set the SMTP options, if needed. */
        if ($conf['mailer']['type'] == 'smtp') {
            $sess['smtp'] = array();
            foreach (array('smtphost' => 'host', 'smtpport' => 'port') as $key => $val) {
                if (!empty($ptr[$key])) {
                    $sess['smtp'][$val] = $ptr[$key];
                }
            }
        }

        /* Does the server allow file uploads? If yes, store the
         * value, in bytes, of the maximum file size. */
        $sess['file_upload'] = $GLOBALS['browser']->allowFileUploads();

        /* Is the 'mail/canApplyFilters' API call available? */
        $registry = Horde_Registry::singleton();
        try {
            if ($registry->call('mail/canApplyFilters')) {
                $sess['filteravail'] = true;
            }
        } catch (Horde_Exception $e) {}

        /* Is the 'tasks/listTasklists' call available? */
        if ($conf['tasklist']['use_tasklist'] &&
            $registry->hasMethod('tasks/listTasklists')) {
            $sess['tasklistavail'] = true;
        }

        /* Is the 'notes/listNotepads' call available? */
        if ($conf['notepad']['use_notepad'] &&
            $registry->hasMethod('notes/listNotepads')) {
            $sess['notepadavail'] = true;
        }

        /* Is the HTML editor available? */
        $imp_ui = new IMP_UI_Compose();
        $editor = $imp_ui->initRTE(null, true);
        $sess['rteavail'] = $editor->supportedByBrowser();

        /* Set view in session/cookie. */
        $sess['view'] = empty($conf['user']['select_view'])
            ? (empty($conf['user']['force_view']) ? 'imp' : $conf['user']['force_view'])
            : (empty($sess['cache']['select_view']) ? 'imp' : $sess['cache']['select_view']);

        setcookie('default_imp_view', $sess['view'], time() + 30 * 86400,
                  $conf['cookie']['path'],
                  $conf['cookie']['domain']);

        /* Suppress menus in options screen, and indicate that it should
         * use the IMP notification listener. */
        if ($sess['view'] == 'dimp') {
            $_SESSION['horde_prefs']['nomenu'] = true;
            $_SESSION['horde_prefs']['status'] = 'imp';
        }

        /* Set up search information for the session. */
        $GLOBALS['imp_search']->initialize();

        /* If the user wants to run filters on login, make sure they get
           run. */
        if ($GLOBALS['prefs']->getValue('filter_on_login')) {
            /* Run filters. */
            $imp_filter = new IMP_Filter();
            $imp_filter->filter('INBOX');
        }

        /* Check for drafts due to session timeouts. */
        $imp_compose = IMP_Compose::singleton();
        $imp_compose->recoverSessionExpireDraft();

        IMP_Auth::logMessage('login', __FILE__, __LINE__, PEAR_LOG_NOTICE);
    }

}
