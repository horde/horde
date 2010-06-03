<?php
/**
 * Horde login page.
 *
 * Valid parameters in:
 * 'app' - The app to login to.
 * 'url' - The url to redirect to after auth.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael Slusarz <slusarz@horde.org>
 */

/* Add anchor to outgoing URL. */
function _addAnchor($url, $type, $url_anchor = null)
{
    switch ($type) {
    case 'param':
        if (!is_null($url_anchor)) {
            $url .= '#' . $url_anchor;
        }
        break;

    case 'url':
        $anchor = Horde_Util::getFormData('anchor_string');
        if (!empty($anchor)) {
            $url .= '#' . $anchor;
        } else {
            return _addAnchor($url, 'param', $url_anchor);
        }
        break;
    }

    return $url;
}

function _getLogoutReasonString($code)
{
    switch ($code) {
    case Horde_Auth::REASON_SESSION:
        return _("Your session has expired. Please login again.");

    case Horde_Auth::REASON_SESSIONIP:
        return _("Your Internet Address has changed since the beginning of your session. To protect your security, you must login again.");

    case Horde_Auth::REASON_BROWSER:
        return _("Your browser appears to have changed since the beginning of your session. To protect your security, you must login again.");

    case Horde_Auth::REASON_LOGOUT:
        return _("You have been logged out.");

    case Horde_Auth::REASON_FAILED:
        return _("Login failed.");

    case Horde_Auth::REASON_BADLOGIN:
        return _("Login failed because your username or password was entered incorrectly.");

    case Horde_Auth::REASON_EXPIRED:
        return _("Your login has expired.");

    case Horde_Auth::REASON_MESSAGE:
        $msg = Horde_Auth::getAuthError(true);
        if (!$msg) {
               Horde_Util::getFormData(Horde_Auth::REASON_MSG_PARAM);
        }
        return $msg;

    default:
        return '';
    }
}


/* Try to login - if we are doing auth to an app, we need to auth to
 * Horde first or else we will lose the session. Ignore any auth errors.
 * Transparent authentication is handled by the Horde_Application::
 * constructor. */
require_once dirname(__FILE__) . '/lib/Application.php';
try {
    Horde_Registry::appInit('horde', array('authentication' => 'none', 'nologintasks' => true));
} catch (Horde_Exception $e) {}

$app = Horde_Util::getFormData('app');
$is_auth = $registry->isAuthenticated();

/* This ensures index.php doesn't pick up the 'url' parameter. */
$horde_login_url = '';
$horde_login_nosidebar = false;

/* Initialize the Auth credentials key. */
if (!$is_auth) {
    $injector->getInstance('Horde_Secret')->setKey('auth');
}

/* Get an Auth object. */
$auth = ($app && $is_auth)
    ? $injector->getInstance('Horde_Auth')->getAuth('application', array('app' => $app))
    : $injector->getInstance('Horde_Auth')->getAuth();

/* Build the list of necessary login parameters. */
$loginparams = array(
    'horde_user' => array(
        'label' => _("Username"),
        'type' => 'text',
        'value' => Horde_Util::getFormData('horde_user')
    ),
    'horde_pass' => array(
        'label' => _("Password"),
        'type' => 'password'
    )
);
$js_code = array(
    'HordeLogin.user_error = ' . Horde_Serialize::serialize(_("Please enter a username."), Horde_Serialize::JSON),
    'HordeLogin.pass_error = ' . Horde_Serialize::serialize(_("Please enter a username."), Horde_Serialize::JSON)
);
$js_files = array(
    array('login.js', 'horde')
);

try {
    $result = $auth->getLoginParams();
    $loginparams = array_filter(array_merge($loginparams, $result['params']));
    $js_code = array_merge($js_code, $result['js_code']);
    $js_files = array_merge($js_files, $result['js_files']);

    if (!empty($result['nosidebar'])) {
        $horde_login_nosidebar = true;
    }
} catch (Horde_Exception $e) {}

/* Get parameters. */
$error_reason = Horde_Util::getFormData(Horde_Auth::REASON_PARAM);
$ie_version = Horde_Util::getFormData('ie_version');

/* Get URL/Anchor strings now. */
$url_anchor = null;
$url_in = Horde_Util::getFormData('url');
if (($pos = strrpos($url_in, '#')) !== false) {
    $url_anchor = substr($url_in, $pos + 1);
    $url_in = substr($url_in, 0, $pos);
}

if ($error_reason) {
    if ($is_auth) {
        Horde::checkRequestToken('horde.logout', Horde_Util::getFormData('horde_logout_token'));
        $is_auth = null;
    }

    $language = $prefs->getValue('language');

    $entry = sprintf('User %s [%s] logged out of Horde', $registry->getAuth(), $_SERVER['REMOTE_ADDR']);
    Horde::logMessage($entry, 'NOTICE');
    $registry->clearAuth();

    /* Redirect the user on logout if redirection is enabled. */
    if (!empty($conf['auth']['redirect_on_logout'])) {
        $logout_url = $conf['auth']['redirect_on_logout'];
        if (!isset($_COOKIE[session_name()])) {
            $logout_url = Horde_Util::addParameter($logout_url, array(session_name() => session_id()), null, false);
        }
        header('Location: ' . _addAnchor($logout_url, 'url', $url_anchor));
        exit;
    }

    $registry->setupSessionHandler();
    @session_start();

    Horde_Nls::setLanguageEnvironment($language, $app);

    /* Hook to preselect the correct language in the widget. */
    $_GET['new_lang'] = $language;
} elseif (Horde_Util::getPost('login_post') ||
          Horde_Util::getPost('login_button')) {
    if ($is_auth) {
        $horde_login_nosidebar = true;
    } else {
        /* Destroy any existing session on login and make sure to use a
         * new session ID, to avoid session fixation issues. */
        $registry->getCleanSession();
    }

    /* Get the login params from the login screen. */
    $auth_params = array(
        'password' => Horde_Util::getPost('horde_pass')
    );
    foreach (array_diff(array_keys($loginparams), array('horde_user', 'horde_pass')) as $val) {
        $auth_params[$val] = Horde_Util::getPost($val);
    }

    if ($ie_version) {
        $browser->setIEVersion($ie_version);
    }

    if ($auth->authenticate(Horde_Util::getPost('horde_user'), $auth_params)) {
        $entry = sprintf('Login success for %s [%s] to %s.', $registry->getAuth(), $_SERVER['REMOTE_ADDR'], ($app && $is_auth) ? $app : 'horde');
        Horde::logMessage($entry, 'NOTICE');

        if (!empty($url_in)) {
            /* $horde_login_url is used by horde/index.php to redirect to URL
             * without the need to redirect to horde/index.php also. */
            $horde_login_url = _addAnchor(Horde::url(Horde_Util::removeParameter($url_in, session_name()), true), 'url');
        }

        /* Do password change request on initial login only. */
        if (!$is_auth && Horde_Auth::passwordChangeRequested()) {
            $notification->push(_("Your password has expired."), 'horde.message');

            if ($auth->hasCapability('update')) {
                $change_url = Horde::applicationUrl('services/changepassword.php');
                if (isset($horde_login_url)) {
                    $change_url = Horde::addParameter($change_url, array('return_to' => $horde_login_url), null, false);
                }

                header('Location: ' . $change_url);
                exit;
            }
        }

        require HORDE_BASE . '/index.php';
        exit;
    }

    $error_reason = Horde_Auth::getAuthError();

    $entry = sprintf('FAILED LOGIN for %s [%s] to Horde',
                     Horde_Util::getFormData('horde_user'), $_SERVER['REMOTE_ADDR']);
    Horde::logMessage($entry, 'ERR');
} else {
    $new_lang = Horde_Util::getGet('new_lang');
    if ($new_lang) {
        Horde_Nls::setLanguageEnvironment($new_lang);
    }
}

/* If we currently are authenticated, and are not trying to authenticate to
 * an application, redirect to initial page. This is done in index.php. */
if ($is_auth && !$app) {
    $horde_login_nosidebar = true;
    require HORDE_BASE . '/index.php';
    exit;
}

/* Redirect the user if an alternate login page has been specified. */
if (!empty($conf['auth']['alternate_login'])) {
    $url = $conf['auth']['alternate_login'];
    if ($app) {
        $url = Horde_Util::addParameter($url, array('app' => $app), null, false);
    }
    if (!isset($_COOKIE[session_name()])) {
        $url = Horde_Util::addParameter($url, array(session_name() => session_id), null, false);
    }
    if (empty($url_in)) {
        $url_in = Horde::selfUrl(true, true, true);
    }
    $anchor = _addAnchor($url_in, 'param', $url_anchor);
    if (strpos($url, '%25u') || strpos($url, '%u')) {
        $url = str_replace(array('%25u', '%u'), rawurlencode($anchor), $url);
    } else {
        $url = Horde_Util::addParameter($url, array('url' => $anchor), null, false);
    }
    header('Location: ' . _addAnchor($url, 'url', $url_anchor));
    exit;
}

/* Build the <select> widget containing the available languages. */
if (!$is_auth && !$prefs->isLocked('language')) {
    $_SESSION['horde_language'] = Horde_Nls::select();
    $langs = array();

    foreach (Horde_Nls::$config['languages'] as $key => $val) {
        $langs[] = array(
            'sel' => ($key == $_SESSION['horde_language']),
            'val' => $key,
            // Language names are already encoded.
            'name' => $val
        );
    }
}

$title = _("Log in");
if ($reason = _getLogoutReasonString($error_reason)) {
    $notification->push(str_replace('<br />', ' ', $reason), 'horde.message');
}

if ($browser->isMobile()) {
    /* Build the <select> widget containing the available languages. */
    if (!$is_auth && !$prefs->isLocked('language')) {
        $tmp = array();
        foreach ($langs as $val) {
            $tmp[$val['val']] = array(
                'name' => $val['name'],
                'selected' => $val['sel']
            );
        }
        $loginparams['new_lang'] = array(
            'label' => _("Language"),
            'type' => 'select',
            'value' => $tmp
        );
    }

    require $registry->get('templates', 'horde') . '/common-header.inc';
    require $registry->get('templates', 'horde') . '/login/mobile.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

$menu = new Horde_Menu(Horde_Menu::MASK_NONE);
$hmenu = $menu->render();

if (!empty($js_files)) {
    Horde::addScriptFile('prototype.js', 'horde');
    foreach ($js_files as $val) {
        Horde::addScriptFile($val[0], $val[1]);
    }
}

require $registry->get('templates', 'horde') . '/common-header.inc';
require $registry->get('templates', 'horde') . '/login/login.inc';

if (!empty($js_code)) {
    echo Horde::wrapInlineScript(array(implode(';', $js_code)));
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
