<?php
/**
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once dirname(__FILE__) . '/account/tabs.php';
require_once FOLKS_BASE . '/lib/Forms/Login.php';

/*
 * Send notification message to friends online
 */
function _loginNotice($user)
{
    if ($GLOBALS['prefs']->getValue('login_notify') != 1 ||
        !$GLOBALS['registry']->hasInterface('letter')) {
        return;
    }

    require_once FOLKS_BASE . '/lib/Friends.php';
    $friends_driver = Folks_Friends::singleton();
    $friends = $friends_driver->getFriends();
    if ($friends instanceof PEAR_Error) {
        return $friends;
    } elseif (empty($friends)) {
        return true;
    }

    $users = $GLOBALS['folks_driver']->getOnlineUsers();
    if ($users instanceof PEAR_Error) {
        return $users;
    } elseif (empty($users)) {
        return true;
    }

    $notify = array();
    foreach ($friends as $friend) {
        if (array_key_exists($friend, $users)) {
            $notify[] = $friend;
        }
    }

    if (empty($notify)) {
        return true;
    }

    $body = _("User %s just logged in.\n%s");
    $params = array($notify,
                    array('title' => _("Login reminder"),
                          'content' => sprintf($body, $user, Folks::getUrlFor('user', $user, true))));
    $GLOBALS['registry']->callByPackage('letter', 'sendMessage', $params);
}

/*
 * Logout?
 */
if (isset($_GET['logout_reason'])) {
    setcookie('folks_login_user', '', $_SERVER['REQUEST_TIME'] - 1000, $conf['cookie']['path'], $conf['cookie']['domain']);
    setcookie('folks_login_code', '', $_SERVER['REQUEST_TIME'] - 1000, $conf['cookie']['path'], $conf['cookie']['domain']);
    $folks_driver->deleteOnlineUser($GLOBALS['registry']->getAuth());

    @session_destroy();
    if (!empty($_GET['redirect'])) {
        header('Location: ' . $_GET['redirect']);
    } else {
        $page = $registry->getInitialPage('folks');
        header('Location: ' . (empty($page) ? '/' : $page));
    }
    exit;
}

/*
 * Special login for apps (gollem, imp)?
 */
if ($conf['login']['prelogin'] &&
    $GLOBALS['registry']->getAuth() &&
   ($app = Horde_Util::getGet('app'))) {
    Horde::callHook('prelogin', array($app), 'folks');
}

/*
 * Login parameters
 */
$url_param = Horde_Util::getFormData('url');
$login_url = Horde_Util::addParameter(Horde::getServiceLink('login', 'folks'), 'url', $url_param);

/*
 * We are already logged in?
 */
if ($registry->isAuthenticated()) {
    if (empty($url_param)) {
        $url_param = Folks::getUrlFor('user', $GLOBALS['registry']->getAuth());
    }
    header('Location: ' . $url_param);
    exit;
}

/*
 * We have a login cookie?
 */
if (isset($_COOKIE['folks_login_code']) &&
    isset($_COOKIE['folks_login_user']) &&
    $_COOKIE['folks_login_code'] == $folks_driver->getCookie($_COOKIE['folks_login_user'])) {

    // Horde Auto login
    Horde_Auth::setAuth($_COOKIE['folks_login_user'], array('transparent' => 1));

    if (empty($url_param)) {
        $url_param = Folks::getUrlFor('user', $_COOKIE['folks_login_user']);
    }

    header('Location: ' . $url_param);
    exit;
}

/*
 * Form
 */
$title = sprintf(_("Login to %s"), $registry->get('name', 'horde'));
$vars = Horde_Variables::getDefaultVariables();
$form = new Folks_Login_Form($vars, $title, 'folks_login');

/*
 * Check time between one login and anther
 */
$username = Horde_String::lower(trim(Horde_Util::getPost('username')));
if ($username && $conf['login']['diff']) {
    $last_try = $cache->get('login_last_try_' . $username, $conf['cache']['default_lifetime']);
    if ($last_try && $_SERVER['REQUEST_TIME'] - $last_try <= $conf['login']['diff']) {
        $notification->push(_("You are entering your data too fast!"));
        header('Location: ' . $login_url);
        exit;
    } else {
        $cache->set('login_last_try_' . $username, $_SERVER['REQUEST_TIME']);
    }
}

/*
 * Process form
 */
if ($form->isSubmitted()) {

    // check password
    $form->getInfo(null, $info);

    $result = $folks_driver->comparePassword($username, $info['password']);
    if ($result !== true) {
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            $notification->push(_("Your username or password is incorrect."));
        }
        header('Location: ' . $login_url);
        exit;
    }

    // Check user profile status
    $profile = $folks_driver->getRawProfile($username);
    if ($profile instanceof PEAR_Error) {
        $notification->push($profile);
        header('Location: ' . $login_url);
        exit;
    }

    switch ($profile['user_status']) {
    case 'deactivated':
        $notification->push(sprintf(_("Your username was temporary deacirvated. For any additional information please write to %s, and don't forgot to incluide your username."), $conf['folks']['support']), 'horde.warning');
        header('Location: ' . Horde::selfUrl(true));
        exit;
        break;

    case 'unconfirmed':
    case 'inactive':
        $notification->push(_("This account was still not activated. Check your inbox, we send you the activation code there."), 'horde.warning');
        header('Location: ' . Horde::selfUrl(true));
        exit;
        break;

    case 'deleted':
        $notification->push(_("This account was deleted or is expired."), 'horde.warning');
        header('Location: ' . Horde::selfUrl(true));
        exit;
        break;
    }

    // Horde Auto login
    Horde_Auth::setAuth($username, array('transparent' => 1, 'password' => $info['password']));

    // Save user last login info.
    // We ignore last_login pref as it can be turned off by user
    $params = array('last_login_on' => date('Y-m-d H:i:s'),
                    'last_login_by' => $_SERVER['REMOTE_ADDR']);
    if ($profile['user_status'] == 'deleted') {
        $params['user_status'] = 'active';
    }
    $result = $folks_driver->saveProfile($params, $username);
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    }

    // set cookie
    $cookie = $folks_driver->getCookie($username);
    if ($info['loginfor']) {
        $info['loginfor'] = $_SERVER['REQUEST_TIME'] + 2592000;
    } else {
        $info['loginfor'] = 0;
    }
    setcookie('folks_login_user', $username, $info['loginfor'], $conf['cookie']['path'], $conf['cookie']['domain']);
    setcookie('folks_login_code', $cookie, $info['loginfor'], $conf['cookie']['path'], $conf['cookie']['domain']);

    // Notify user's freinds that user come online
    _loginNotice($username);

    // Reset online users
    $folks_driver->resetOnlineUsers();

    if (empty($url_param)) {
        $url_param = Folks::getUrlFor('user', $username);
    }
    header('Location: ' . $url_param);
    exit;
}

require $registry->get('templates', 'horde') . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

require FOLKS_TEMPLATES . '/login/login.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';
