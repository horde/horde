<?php
/**
 * The IMP_Auth:: class provides authentication for IMP.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Auth
{
    /**
     * Authenticate to the mail server.
     *
     * @param array $credentials  An array of login credentials. If empty,
     *                            attempts to login to the cached session.
     * <pre>
     * 'password' - (string) The user password.
     * 'server' - (string) The server key to use (from backends.php).
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
        $imp_app = $GLOBALS['registry']->getApiInstance('imp', 'application');
        if (!empty($imp_app->initParams['authentication']) &&
            ($imp_app->initParams['authentication'] == 'horde')) {
            if ($GLOBALS['registry']->getAuth()) {
                return false;
            }
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create();

        // Check for valid IMAP Client object.
        if (!$imp_imap->ob) {
            if (!isset($credentials['userId']) ||
                !isset($credentials['password'])) {
                throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
            }

            if (!isset($credentials['server'])) {
                $credentials['server'] = self::getAutoLoginServer();
            }

            /* _createSession() will create the imp session variable, so there
             * is no concern for an infinite loop here. */
            if (!isset($GLOBALS['session']['imp:server_key'])) {
                self::_createSession($credentials);
                return true;
            }

            if (!$imp_imap->createImapObject($credentials['userId'], $credentials['password'], $credentials['server'])) {
                self::_logMessage(false);
                Horde::logMessage('Could not create IMAP object', 'ERR');
                throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
            }
        }

        try {
            $imp_imap->login();
        } catch (Horde_Imap_Client_Exception $e) {
            self::_logMessage(false);

            switch ($e->getCode()) {
            case Horde_Imap_Client_Exception::LOGIN_AUTHENTICATIONFAILED:
            case Horde_Imap_Client_Exception::LOGIN_AUTHORIZATIONFAILED:
                $code = Horde_Auth::REASON_BADLOGIN;
                break;

            case Horde_Imap_Client_Exception::LOGIN_EXPIRED:
                $code = Horde_Auth::REASON_EXPIRED;
                break;

            case Horde_Imap_Client_Exception::LOGIN_UNAVAILABLE:
                $code = Horde_Auth::REASON_MESSAGE;
                $e = _("Remove server is down. Please try again later.");
                break;

            case Horde_Imap_Client_Exception::LOGIN_NOAUTHMETHOD:
            case Horde_Imap_Client_Exception::LOGIN_PRIVACYREQUIRED:
            case Horde_Imap_Client_Exception::LOGIN_TLSFAILURE:
            case Horde_Imap_Client_Exception::SERVER_CONNECT:
            default:
                $code = Horde_Auth::REASON_FAILED;
                break;
            }

            throw new Horde_Auth_Exception($e, $code);
        }

        return false;
    }

    /**
     * Perform transparent authentication.
     *
     * @param Horde_Auth_Application $auth_ob  The authentication object.
     *
     * @return boolean  Whether transparent login is supported.
     */
    static public function transparent($auth_ob)
    {
        /* It is possible that preauthenticate() set the credentials.
         * If so, use that information instead of hordeauth. */
        if ($auth_ob->getCredential('transparent')) {
            $credentials = $auth_ob->getCredential();
            if (!isset($credentials['server'])) {
                $credentials['server'] = self::getAutoLoginServer();
            }
        } else {
            /* Attempt hordeauth authentication. */
            $credentials = self::_canAutoLogin();
            if ($credentials === false) {
                return false;
            }
        }

        try {
            self::_createSession($credentials);
            return true;
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

    /**
     * Log login related message.
     *
     * @param boolean $success  True on success, false on failure.
     */
    static protected function _logMessage($status)
    {
        if ($status) {
            $msg = 'Login success';
            $level = 'NOTICE';
        } else {
            $msg = 'FAILED LOGIN';
            $level = 'INFO';
        }

        $auth_id = $GLOBALS['registry']->getAuth();
        $imap_ob = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create();

        $msg = sprintf(
            $msg . ' %s [%s]%s to {%s:%s%s}',
            !strlen($auth_id) ? '' : ' for ' . $auth_id,
            $_SERVER['REMOTE_ADDR'],
            empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? '' : ' (forwarded for [' . $_SERVER['HTTP_X_FORWARDED_FOR'] . '])',
            $imap_ob ? $imap_ob->getParam('hostspec') : '',
            $imap_ob ? $imap_ob->getParam('port') : '',
            isset($GLOBALS['session:imp']['protocol']) ? ' [' . $GLOBALS['session']['imp:protocol'] . ']' : ''
        );

        Horde::logMessage($msg, $level);
    }

    /**
     * Set up the IMP session. Handle authentication, if required, and only do
     * enough work to see if the user can log in.
     *
     * The following is the list of session variables in the imp namespace:
     * <pre>
     * compose_cache - (array) TODO
     * file_upload - (integer) If file uploads are allowed, the max size.
     * filteravail - (boolean) Can we apply filters manually?
     * imap_acl - (boolean) TODO
     * imap_admin - (array) TODO [params]
     * imap_namespace - (array) TODO
     * imap_ob/* - (Horde_Imap_Client_Base) The IMAP client objects. Stored by
     *             server key.
     * imap_quota - (array) TODO [driver, hide_when_unlimited, params]
     * imap_thread - (string) TODO
     * maildomain - (string) See config/backends.php.
     * notepadavail - (boolean) Is listing of notepads available?
     * pgp - (array) TODO
     * protocol - (string) Either 'imap' or 'pop'.
     * rteavail - (boolean) Is the HTML editor available?
     * search - (IMP_Search) The IMP_Search object.
     * select_view - (string) TODO
     * server_key - (string) Server used to login.
     * smime - (array) Settings related to the S/MIME viewer.
     * smtp - (array) SMTP options ('host' and 'port')
     * showunsub - (boolean) Show unsusubscribed mailboxes on the folders
     *             screen.
     * tasklistavail - (boolean) Is listing of tasklists available?
     * view - (string) The imp view mode (dimp, imp, or mimp)
     * </pre>
     *
     * @param array $credentials  An array of login credentials.
     * <pre>
     * 'password' - (string) The user password.
     * 'server' - (string) The server key to use (from backends.php).
     * 'userId' - (string) The username.
     * </pre>
     *
     * @throws Horde_Auth_Exception
     */
    static protected function _createSession($credentials)
    {
        $GLOBALS['session']['imp:server_key'] = $credentials['server'];

        /* Load the server configuration. */
        $ptr = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->loadServerConfig($credentials['server']);
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
            unset($GLOBALS['session']['imp:']);
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
        if (($servers = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->loadServerConfig()) === false) {
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
     * @param boolean $url  Return a URL instead of a file path.
     *
     * @return string  Either the file path or a URL to the initial page.
     */
    static public function getInitialPage($url = false)
    {
        switch ($GLOBALS['session']['imp:view']) {
        case 'dimp':
            $page = 'index-dimp.php';
            break;

        case 'mimp':
            $page = 'mailbox-mimp.php';
            break;

        default:
            $init_url = ($GLOBALS['session']['imp:protocol'] == 'pop')
                ? 'INBOX'
                : $GLOBALS['prefs']->getValue('initial_page');

            $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
            if ($imp_search->isSearchMbox($init_url) &&
                (!$imp_search[$init_url]->enabled)) {
                $init_url = 'INBOX';
            }

            switch ($init_url) {
            case 'folders.php':
                $page = $init_url;
                break;

            default:
                $page = 'mailbox.php';
                if ($url) {
                    return Horde::url($page, true)->add('mailbox', $init_url);
                }
                IMP::setCurrentMailboxInfo($init_url);
                break;
            }
        }

        return $url
            ? Horde::url($page, true)
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
        global $conf, $session;

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create();
        $ptr = $imp_imap->loadServerConfig($session['imp:server_key']);
        if ($ptr === false) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        /* Set the protocol. */
        $session['imp:protocol'] = isset($ptr['protocol'])
            ? $ptr['protocol']
            : 'imap';

        /* Set the maildomain. */
        $maildomain = $GLOBALS['prefs']->getValue('mail_domain');
        $session['imp:maildomain'] = $maildomain
            ? $maildomain
            : $ptr['maildomain'];

        /* Store some basic IMAP server information. */
        if ($session['imp:protocol'] == 'imap') {
            foreach (array('acl', 'admin', 'namespace', 'quota') as $val) {
                if (!empty($ptr[$val])) {
                    $tmp = $ptr[$val];

                    /* 'admin' and 'quota' have password entries - encrypt
                     * these entries in the session if they exist. */
                    foreach (array('password', 'admin_password') as $key) {
                        if (isset($ptr[$val]['params'][$key])) {
                            $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
                            $tmp['params'][$key] = $secret->write($secret->getKey('imp'), $ptr[$val]['params'][$key]);
                        }
                    }

                    $session['imp:imap_' . $val] = $tmp;
                }
            }

            /* Set the IMAP threading algorithm. */
            $session['imp:imap_thread'] = in_array(isset($ptr['thread']) ? strtoupper($ptr['thread']) : 'REFERENCES', $imp_imap->queryCapability('THREAD'))
                ? 'REFERENCES'
                : 'ORDEREDSUBJECT';
        }

        /* Set the SMTP options, if needed. */
        if ($conf['mailer']['type'] == 'smtp') {
            $smtp = array();
            foreach (array('smtphost' => 'host', 'smtpport' => 'port') as $key => $val) {
                if (!empty($ptr[$key])) {
                    $smtp[$val] = $ptr[$key];
                }
            }

            if (!empty($smtp)) {
                $session['imp:smtp'] = $smtp;
            }
        }

        /* Does the server allow file uploads? If yes, store the
         * value, in bytes, of the maximum file size. */
        $session['imp:file_upload'] = $GLOBALS['browser']->allowFileUploads();

        /* Is the 'mail/canApplyFilters' API call available? */
        try {
            if ($GLOBALS['registry']->call('mail/canApplyFilters')) {
                $session['imp:filteravail'] = true;
            }
        } catch (Horde_Exception $e) {}

        /* Is the 'tasks/listTasklists' call available? */
        if ($conf['tasklist']['use_tasklist'] &&
            $GLOBALS['registry']->hasMethod('tasks/listTasklists')) {
            $session['imp:tasklistavail'] = true;
        }

        /* Is the 'notes/listNotepads' call available? */
        if ($conf['notepad']['use_notepad'] &&
            $GLOBALS['registry']->hasMethod('notes/listNotepads')) {
            $session['imp:notepadavail'] = true;
        }

        /* Is the HTML editor available? */
        $imp_ui = new IMP_Ui_Compose();
        $session['imp:rteavail'] = $GLOBALS['injector']->getInstance('Horde_Editor')->supportedByBrowser();

        /* Determine view. */
        $setcookie = false;
        if (empty($conf['user']['force_view'])) {
            if (empty($conf['user']['select_view']) ||
                !$session['imp:select_view']) {
                $view = $GLOBALS['browser']->isMobile()
                    ? 'mimp'
                    : ($GLOBALS['prefs']->getValue('dynamic_view') ? 'dimp' : 'imp');
            } else {
                $setcookie = true;
                $view = $session['imp:select_view'];
            }
        } else {
            $view = $conf['user']['force_view'];
        }

        self::setViewMode($view);

        if ($setcookie) {
            setcookie('default_imp_view', $session['imp:view'], time() + 30 * 86400, $conf['cookie']['path'], $conf['cookie']['domain']);
        }

        /* Indicate that notifications should use AJAX mode. */
        if ($session['imp:view'] == 'dimp') {
            $GLOBALS['session']['horde:notification_override'] = array(
                IMP_BASE . '/lib/Notification/Listener/AjaxStatus.php',
                'IMP_Notification_Listener_AjaxStatus'
            );
        }

        /* Set up search information for the session. Need to manually do
         * first init() here since there is a cyclic IMP_Imap_Tree dependency
         * otherwise. */
        $GLOBALS['injector']->getInstance('IMP_Search')->init();

        /* If the user wants to run filters on login, make sure they get
           run. */
        if ($GLOBALS['prefs']->getValue('filter_on_login')) {
            $GLOBALS['injector']->getInstance('IMP_Filter')->filter('INBOX');
        }

        /* Check for drafts due to session timeouts. */
        $imp_compose = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Compose')->create()->recoverSessionExpireDraft();

        self::_logMessage(true);
    }

    /**
     * Sets the current view mode.
     *
     * @return string  Either 'dimp', 'imp', or 'mimp'.
     */
    static public function setViewMode($view)
    {
        /* Enforce minimum browser standards for DIMP. */
        if (($view == 'dimp') && !Horde::ajaxAvailable()) {
            $view = 'imp';
            $GLOBALS['notification']->push(_("Your browser is too old to display the dynamic mode. Using traditional mode instead."), 'horde.warning');
        }

        $GLOBALS['session']['imp:view'] = $view;
    }

}
