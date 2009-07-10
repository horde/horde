<?php
/**
 * Login screen for IMP.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

@define('AUTH_HANDLER', true);
$authentication = 'none';
$login_page = true;
require_once dirname(__FILE__) . '/lib/base.php';

/* Set the 'preferred' server. */
$pref_server = Horde_Util::getFormData('server');
if (!empty($pref_server)) {
    IMP_Session::$prefServer = $pref_server;
}

/* Get an Auth object. */
$imp_auth = (Horde_Auth::getProvider() == 'imp');
$auth = Horde_Auth::singleton($conf['auth']['driver']);
$logout_reason = Horde_Auth::getLogoutReason();

$actionID = (Horde_Util::getFormData('action') == 'compose') ? 'login_compose' : Horde_Util::getFormData('actionID');
$url_param = Horde_Util::getFormData('url');

$load_frameset = intval($imp_auth && empty($conf['menu']['always']));

/* Handle cases where we already have a session. */
if (!empty($_SESSION['imp']) && is_array($_SESSION['imp'])) {
    if ($logout_reason) {
        /* Log logout requests now. */
        if ($logout_reason == Horde_Auth::REASON_LOGOUT) {
            IMP::loginLogMessage('logout', __FILE__, __LINE__, PEAR_LOG_NOTICE);
        } else {
            Horde::logMessage($_SERVER['REMOTE_ADDR'] . ' ' . Horde_Auth::getLogoutReasonString(), __FILE__, __LINE__, PEAR_LOG_NOTICE);
        }

        $language = (isset($prefs)) ? $prefs->getValue('language') : Horde_Nls::select();

        unset($_SESSION['imp']);

        /* Cleanup preferences. */
        if (isset($prefs)) {
            $prefs->cleanup($imp_auth);
        }

        if ($imp_auth) {
            Horde_Auth::clearAuth();
            @session_destroy();
            $registry->setupSessionHandler();
            @session_start();
        }

        Horde_Nls::setLang($language);

        /* Hook to preselect the correct language in the widget. */
        $_GET['new_lang'] = $language;

        $registry->loadPrefs('horde');
        $registry->loadPrefs();
    } else {
        header('Location: ' . IMP_Session::getInitialUrl($actionID, false));
        exit;
    }
}

/* Log session timeouts. */
if ($logout_reason == Horde_Auth::REASON_SESSION) {
    $entry = sprintf('Session timeout for client [%s]', $_SERVER['REMOTE_ADDR']);
    Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_NOTICE);

    /* Make sure everything is really cleared. */
    Horde_Auth::clearAuth();
    unset($_SESSION['imp']);
}

/* Redirect the user on logout if redirection is enabled. */
if ($logout_reason == Horde_Auth::REASON_LOGOUT &&
    (!empty($conf['user']['redirect_on_logout']) ||
     !empty($conf['auth']['redirect_on_logout']))) {
    if (!empty($conf['auth']['redirect_on_logout'])) {
        $url = Horde_Auth::addLogoutParameters($conf['auth']['redirect_on_logout'], Horde_Auth::REASON_LOGOUT);
    } else {
        $url = Horde_Auth::addLogoutParameters($conf['user']['redirect_on_logout'], Horde_Auth::REASON_LOGOUT);
    }
    if (!isset($_COOKIE[session_name()])) {
        $url = Horde_Util::addParameter($url, session_name(), session_id());
    }
    header('Location: ' . $url);
    exit;
}

/* Redirect the user if an alternate login page has been specified. */
if (!empty($conf['auth']['alternate_login'])) {
    $url = Horde_Auth::addLogoutParameters($conf['auth']['alternate_login']);
    if (!isset($_COOKIE[session_name()])) {
        $url = Horde_Util::addParameter($url, session_name(), session_id(), false);
    }
    if ($url_param) {
        $url = Horde_Util::addParameter($url, 'url', $url_param, false);
    }
    header('Location: ' . $url);
    exit;
} elseif (!empty($conf['user']['alternate_login'])) {
    $url = Horde_Auth::addLogoutParameters($conf['user']['alternate_login']);
    if (!isset($_COOKIE[session_name()])) {
        $url = Horde_Util::addParameter($url, session_name(), session_id(), false);
    }
    header('Location: ' . $url);
    exit;
}

/* Initialize the password key. If we are doing Horde auth as well,
 * make sure that the Horde auth key gets set instead. */
Horde_Secret::setKey($imp_auth ? 'auth' : 'imp');

$autologin = Horde_Util::getFormData('autologin', false);
$server_key = Horde_Util::getFormData('server_key', IMP_Session::getAutoLoginServer());
if (($servers = $GLOBALS['imp_imap']->loadServerConfig()) === false) {
    $servers = array();
}
$used_servers = $servers;
if ($conf['server']['server_list'] != 'shown') {
    $used_servers = array($server_key => $servers[$server_key]);
}

if (!$logout_reason && IMP_Session::canAutoLogin($server_key, $autologin)) {
    $url = Horde::applicationUrl('redirect.php', true);
    $params = array('actionID' => 'login', 'autologin' => true);
    if (count($used_servers) == 1) {
        $params['server_key'] = key($used_servers);
    }
    $url = Horde_Util::addParameter($url, $params, null, false);
    header('Location: ' . $url);
    exit;
}

$title = sprintf(_("Welcome to %s"), $registry->get('name', ($imp_auth) ? 'horde' : null));

if ($logout_reason) {
    $notification->push(str_replace('<br />', ' ', Horde_Auth::getLogoutReasonString()), 'horde.message');
}

/* Build the <select> widget for the servers and hordeauth servers lists. */
$show_list = ($conf['server']['server_list'] == 'shown');
if ($show_list) {
    $hordeauth_servers_list = $servers_list = array();
    $isAuth = Horde_Auth::isAuthenticated();
    foreach ($servers as $key => $val) {
        $entry = array(
            'sel' => ($server_key == $key) || IMP_Session::isPreferredServer($val, $key),
            'val' => $key,
            'name' => $val['name']
        );

        if (empty($val['hordeauth']) || !$isAuth) {
            $servers_list[] = $entry;
        } elseif ($isAuth) {
            $hordeauth_servers_list[] = $entry;
        }
    }
}

$lang_url = null;
$choose_language = ($imp_auth && !$prefs->isLocked('language'));
if ($choose_language) {
    $_SESSION['horde_language'] = Horde_Nls::select();
    $langs = array();
    foreach (Horde_Nls::$config['languages'] as $key => $val) {
        $langs[] = array(
            'sel' => ($key == $_SESSION['horde_language']),
            'val' => $key,
            'name' => $val
        );
    }

    if (!empty($url_param)) {
        $lang_url = urlencode($url_param);
    }
}

/* If DIMP/MIMP are available, show selection of alternate views. */
$views = array();
if (!empty($conf['user']['select_view'])) {
    $view_cookie = isset($_COOKIE['default_imp_view'])
        ? $_COOKIE['default_imp_view']
        : ($browser->isMobile() ? 'mimp' : 'imp');
    $views = array(
        array(
            'sel' => $view_cookie == 'imp',
            'val' => 'imp',
            'name' => _("Traditional")
        ),
        array(
            'val' => 'dimp',
            'name' => _("Dynamic"),
            'hide' => true
        ),
        array(
            'sel' => $view_cookie == 'mimp',
            'val' => 'mimp',
            'name' => _("Minimalist")
        )
    );

    /* Dimp selection is handled by javascript. */
    $dimp_sel = ($view_cookie == 'dimp');
}

/* Mobile login page. */
if ($browser->isMobile()) {
    require_once 'Horde/Mobile.php';

    /* Build the <select> widget for the servers list. */
    if ($show_list) {
        $server_select = new Horde_Mobile_select('server', 'popup', _("Server:"));
        foreach ($servers_list as $val) {
            $server_select->add($val['name'], $val['val'], $val['sel']);
        }
    }

    /* Build the <select> widget containing the available languages. */
    if ($choose_language) {
        // Language names are already encoded.
        $lang_select = new Horde_Mobile_select('new_lang', 'popup', _("Language:"));
        $lang_select->set('htmlchars', true);
        foreach ($langs as $val) {
            $lang_select->add($val['name'], $val['val'], $val['sel']);
        }
    }

    /* Build the <select> widget containing the available views. */
    if (!empty($views)) {
        $view_select = new Horde_Mobile_select('select_view', 'popup', _("Mode:"));
        foreach ($views as $val) {
            $view_select->add($val['name'], $val['val'], $val['sel']);
        }
    }

    require IMP_TEMPLATES . '/login/mobile.inc';
    exit;
}

$display_list = ($show_list && !empty($hordeauth_servers_list));

/* Prepare the login template. */
$t = new Horde_Template();
$t->setOption('gettext', true);
$tabindex = 0;

$t->set('action', Horde::url('redirect.php', false, -1, true));
$t->set('imp_auth', intval($imp_auth));
$t->set('formInput', Horde_Util::formInput());
$t->set('actionID', htmlspecialchars($actionID));
$t->set('url', htmlspecialchars($url_param));
$t->set('autologin', intval($autologin));
$t->set('anchor_string', htmlspecialchars(Horde_Util::getFormData('anchor_string')));
$t->set('server_key', (!$display_list) ? htmlspecialchars($server_key) : null);

/* Do we need to do IE version detection? */
$t->set('ie_clientcaps', (!Horde_Auth::getAuth() && ($browser->getBrowser() == 'msie') && ($browser->getMajor() >= 5)));

$extra_hidden = array();
foreach (IMP::getComposeArgs() as $arg => $value) {
    $extra_hidden[] = array('name' => htmlspecialchars($arg), 'value' => htmlspecialchars($value));
}
$t->set('extra_hidden', $extra_hidden);

$menu = new Horde_Menu(Horde_Menu::MASK_NONE);
$t->set('menu', $menu->render());
$t->set('title', sprintf(_("Welcome to %s"), $registry->get('name', ($imp_auth) ? 'horde' : null)));

ob_start();
$notification->notify(array('listeners' => 'status'));
$t->set('notification_output', ob_get_contents());
ob_end_clean();

$t->set('display_list', $display_list);
if ($display_list) {
    $t->set('hsl_skey_tabindex', ++$tabindex);
    $t->set('hsl', $hordeauth_servers_list);
    $t->set('hsl_tabindex', ++$tabindex);
}

$t->set('server_list', ($show_list && !empty($servers_list)));
if ($t->get('server_list')) {
    $t->set('slist_tabindex', ++$tabindex);
    $t->set('slist', $servers_list);
}

$t->set('username_tabindex', ++$tabindex);
$t->set('username', htmlspecialchars(Horde_Util::getFormData('imapuser')));
$t->set('user_vinfo', null);
if (!empty($conf['hooks']['vinfo'])) {
    $t->set('user_vinfo', Horde::callHook('_imp_hook_vinfo', array('vdomain'), 'imp'));
}
$t->set('password_tabindex', ++$tabindex);

$t->set('choose_language', $choose_language);
if ($choose_language) {
    $t->set('langs_tabindex', ++$tabindex);
    $t->set('langs', $langs);
}

$t->set('select_view', !empty($views));
if ($t->get('select_view')) {
    $t->set('view_tabindex', ++$tabindex);
    $t->set('views', $views);
}

$t->set('login_tabindex', ++$tabindex);
$t->set('login', _("Login"));

$t->set('signup_link', false);
if ($conf['signup']['allow'] && isset($auth) && $auth->hasCapability('add')) {
    $t->set('signup_text', _("Don't have an account? Sign up."));
    $t->set('signup_link', Horde::link(Horde_Util::addParameter(Horde::url($registry->get('webroot', 'horde') . '/signup.php'), 'url', $url_param), $t->get('signup_text'), 'light'));
}

$login_page = true;
Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('login.js', 'imp', true);
require IMP_TEMPLATES . '/common-header.inc';

$charset = Horde_Nls::getCharset();
$login_params = $autologin
    ? array('autologin' => $autologin, 'server_key' => '')
    : array('server_key' => '');

IMP::addInlineScript(array(
    'ImpLogin.autologin_url = ' . Horde_Serialize::serialize(Horde_Util::addParameter(Horde::selfUrl(), $login_params, null, false), Horde_Serialize::JSON, $charset),
    'ImpLogin.ie_clientcaps = ' . intval($t->get('ie_clientcaps')),
    'ImpLogin.imp_auth = ' . intval($imp_auth),
    'ImpLogin.lang_url = ' . Horde_Serialize::serialize($lang_url, Horde_Serialize::JSON, $charset),
    'ImpLogin.nomenu = ' . $load_frameset,
    'ImpLogin.reloadmenu = ' . intval($logout_reason && $load_frameset),
    'ImpLogin.show_list = ' . intval($show_list),
    'ImpLogin.dimp_sel = ' . intval($dimp_sel),
));

echo $t->fetch(IMP_TEMPLATES . '/login/login.html');

try {
    Horde::loadConfiguration('motd.php', null, null, true);
} catch (Horde_Exception $e) {}
require $registry->get('templates', 'horde') . '/common-footer.inc';
