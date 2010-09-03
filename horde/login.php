<?php
/**
 * Horde login page.
 *
 * Valid parameters in:
 * 'app' - The app to login to.
 * 'horde_logout_token' - TODO
 * 'horde_user' - TODO
 * 'logout_msg' - Logout message.
 * 'logout_reason' - Logout reason (Horde_Auth or Horde_Core_Auth_Wrapper
 *                   constant).
 * 'url' - The url to redirect to after auth.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
 */

/* Add anchor to outgoing URL. */
function _addAnchor($url, $type, $vars, $url_anchor = null)
{
    switch ($type) {
    case 'param':
        if (!is_null($url_anchor)) {
            $url->anchor = $url_anchor;
        }
        break;

    case 'url':
        $anchor = $vars->anchor_string;
        if (!empty($anchor)) {
            $url->setAnchor($anchor);
        } else {
            return _addAnchor($url, 'param', $vars, $url_anchor);
        }
        break;
    }

    return $url;
}


/* Try to login - if we are doing auth to an app, we need to auth to
 * Horde first or else we will lose the session. Ignore any auth errors.
 * Transparent authentication is handled by the Horde_Application::
 * constructor. */
require_once dirname(__FILE__) . '/lib/Application.php';
try {
    Horde_Registry::appInit('horde', array('authentication' => 'none', 'nologintasks' => true));
} catch (Horde_Exception $e) {}

$vars = Horde_Variables::getDefaultVariables();
$is_auth = $registry->isAuthenticated();

/* This ensures index.php doesn't pick up the 'url' parameter. */
$horde_login_url = '';

/* Initialize the Auth credentials key. */
if (!$is_auth) {
    $injector->getInstance('Horde_Secret')->setKey('auth');
}

/* Get an Auth object. */
$auth = $injector->getInstance('Horde_Auth')->getAuth(($is_auth && $vars->app) ? $vars->app : null);

/* Build the list of necessary login parameters. */
$loginparams = array(
    'horde_user' => array(
        'label' => _("Username"),
        'type' => 'text',
        'value' => $vars->horde_user
    ),
    'horde_pass' => array(
        'label' => _("Password"),
        'type' => 'password'
    )
);
$js_code = array(
    'HordeLogin.user_error' => _("Please enter a username."),
    'HordeLogin.pass_error' => _("Please enter a password.")
);
$js_files = array(
    array('login.js', 'horde')
);

try {
    $result = $auth->getLoginParams();
    $loginparams = array_filter(array_merge($loginparams, $result['params']));
    $js_code = array_merge($js_code, $result['js_code']);
    $js_files = array_merge($js_files, $result['js_files']);
} catch (Horde_Exception $e) {}

/* Get URL/Anchor strings now. */
if ($vars->url) {
    $url_in = new Horde_Url($vars->url);
    $url_anchor = $url_in->anchor;
    $url_in->anchor = null;
} else {
    $url_anchor = $url_in = null;
}

if (!($logout_reason = $auth->getError())) {;
    $logout_reason = $vars->logout_reason;
}
if ($logout_reason) {
    if ($is_auth) {
        try {
            Horde::checkRequestToken('horde.logout', $vars->horde_logout_token);
        } catch (Horde_Exception $e) {
            $notification->push($e, 'horde.error');
            require HORDE_BASE . '/index.php';
            exit;
        }
        $is_auth = null;
    }

    $language = $prefs->getValue('language');

    $entry = sprintf('User %s [%s] logged out of Horde', $registry->getAuth(), $_SERVER['REMOTE_ADDR']);
    Horde::logMessage($entry, 'NOTICE');
    $registry->clearAuth();

    /* Redirect the user on logout if redirection is enabled and this is an
     * an intended logout. */
    if (($logout_reason == Horde_Auth::REASON_LOGOUT) &&
        !empty($conf['auth']['redirect_on_logout'])) {
        $logout_url = new Horde_Url($conf['auth']['redirect_on_logout'], true);
        if (!isset($_COOKIE[session_name()])) {
            $logout_url->add(session_name(), session_id());
        }
        _addAnchor($logout_url, 'url', $vars, $url_anchor)->redirect();
    }

    $registry->setupSessionHandler();
    $registry->setLanguageEnvironment($language, $vars->app);

    /* Hook to preselect the correct language in the widget. */
    $_GET['new_lang'] = $language;
} elseif (Horde_Util::getPost('login_post') ||
          Horde_Util::getPost('login_button')) {
    if (!$is_auth) {
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

    if ($vars->ie_version) {
        $browser->setIEVersion($vars->ie_version);
    }

    if ($auth->authenticate(Horde_Util::getPost('horde_user'), $auth_params)) {
        $entry = sprintf('Login success for %s [%s] to %s.', $registry->getAuth(), $_SERVER['REMOTE_ADDR'], ($vars->app && $is_auth) ? $vars->app : 'horde');
        Horde::logMessage($entry, 'NOTICE');

        if (!empty($url_in)) {
            /* $horde_login_url is used by horde/index.php to redirect to URL
             * without the need to redirect to horde/index.php also. */
            $horde_login_url = Horde::url(_addAnchor($url_in->remove(session_name()), 'url', $vars), true);
        }

        /* Do password change request on initial login only. */
        if (!$is_auth && $registry->passwordChangeRequested()) {
            $notification->push(_("Your password has expired."), 'horde.message');

            if ($auth->hasCapability('update')) {
                $change_url = Horde::url('services/changepassword.php');
                if (isset($horde_login_url)) {
                    $change_url->add('return_to', $horde_login_url);
                }

                $change_url->redirect();
            }
        }

        require HORDE_BASE . '/index.php';
        exit;
    }

    $entry = sprintf('FAILED LOGIN for %s [%s] to Horde',
                     $vars->horde_user, $_SERVER['REMOTE_ADDR']);
    Horde::logMessage($entry, 'ERR');
} else {
    $new_lang = Horde_Util::getGet('new_lang');
    if ($new_lang) {
        $registry->setLanguageEnvironment($new_lang);
    }
}

/* If we currently are authenticated, and are not trying to authenticate to
 * an application, redirect to initial page. This is done in index.php.
 * If we are trying to authenticate to an application, but don't have to,
 * redirect to the requesting URL. */
if ($is_auth) {
    if (!$vars->app) {
        require HORDE_BASE . '/index.php';
        exit;
    } elseif ($url_in &&
              $registry->isAuthenticated(array('app' => $vars->app))) {
        _addAnchor($url_in, 'param', null, $url_anchor)->redirect();
    }
}

/* Redirect the user if an alternate login page has been specified. */
if (!empty($conf['auth']['alternate_login'])) {
    $url = new Horde_Url($conf['auth']['alternate_login'], true);
    if ($vars->app) {
        $url->add('app', $vars->app);
    }
    if (!isset($_COOKIE[session_name()])) {
        $url->add(session_name(), session_id);
    }

    if (empty($url_in)) {
        $url_in = Horde::selfUrl(true, true, true);
    }
    $anchor = _addAnchor($url_in, 'param', $vars, $url_anchor);
    $found = false;
    foreach ($url->parameters as $key => $value) {
        if (strpos($value, '%u') !== false) {
            $url->parameters[$key] = str_replace('%u', $anchor, $value);
            $found = true;
        }
    }
    if (!$found) {
        $url->add('url', $anchor);
    }
    _addAnchor($url, 'url', $vars, $url_anchor)->redirect();
}

/* Build the <select> widget containing the available languages. */
if (!$is_auth && !$prefs->isLocked('language')) {
    $_SESSION['horde_language'] = $registry->preferredLang($vars->new_lang);
    $langs = array();

    foreach ($registry->nlsconfig['languages'] as $key => $val) {
        $langs[] = array(
            'sel' => ($key == $_SESSION['horde_language']),
            'val' => $key,
            // Language names are already encoded.
            'name' => $val
        );
    }
}

$title = _("Log in");

$reason = null;
switch ($logout_reason) {
case Horde_Auth::REASON_SESSION:
    $reason = _("Your session has expired. Please login again.");
    break;

case Horde_Core_Auth_Application::REASON_SESSIONIP:
    $reason = _("Your Internet Address has changed since the beginning of your session. To protect your security, you must login again.");
    break;

case Horde_Core_Auth_Application::REASON_BROWSER:
    $reason = _("Your browser appears to have changed since the beginning of your session. To protect your security, you must login again.");
    break;

case Horde_Auth::REASON_LOGOUT:
    $reason = _("You have been logged out.");
    break;

case Horde_Auth::REASON_FAILED:
    $reason = _("Login failed.");
    break;

case Horde_Auth::REASON_BADLOGIN:
    $reason = _("Login failed because your username or password was entered incorrectly.");
    break;

case Horde_Auth::REASON_EXPIRED:
    $reason = _("Your login has expired.");
    break;

case Horde_Auth::REASON_MESSAGE:
    if (!($reason = $auth->getError(true))) {
        $reason = $vars->logout_msg;
    }
    break;
}
if ($reason) {
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

Horde::addInlineJsVars($js_code);

require $registry->get('templates', 'horde') . '/common-header.inc';
require $registry->get('templates', 'horde') . '/login/login.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
