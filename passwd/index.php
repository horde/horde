<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/gpl.php.
 *
 * @author  Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Jan Schneider <jan@horde.org>
 * @package Passwd
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('passwd');

$backends = Passwd::getBackends();
$userid = null;
$return_to = Horde_Util::getFormData('return_to');

// Get the backend details.
$backend_key = Horde_Util::getFormData('backend');
if (!isset($backends[$backend_key])) {
    $backend_key = null;
}

if (!$backend_key) {
    goto proceed;
}

// Has the user submitted the form yet?
if (!Horde_Util::getFormData('submit')) {
    // No so we don't need to do anything in this loop.
    goto proceed;
}

$driver = $backends[$backend_key]['driver'];
$params = $backends[$backend_key]['params'];
$password_policy = isset($backends[$backend_key]['policy'])
    ? $backends[$backend_key]['policy']
    : array();

// Get the username.
if ($conf['user']['change'] === true) {
    $userid = Horde_Util::getFormData('userid');
} else {
    try {
        $userid = Horde::callHook('default_username', array($registry->getAuth()), 'passwd');
    } catch (Horde_Exception_HookNotSet $e) {
        $userid = $registry->getAuth();
    }
}

// Check for users that cannot change their passwords.
if (in_array($userid, $conf['user']['refused'])) {
    $notification->push(sprintf(_("You can't change password for user %s"),
                                $userid), 'horde.error');
    goto proceed;
}

// We must be passed the old (current) password, or its an error.
$old_password = Horde_Util::getFormData('oldpassword', false);
if (!$old_password) {
    $notification->push(_("You must give your current password"),
                        'horde.warning');
    goto proceed;
}

// See if they entered the new password and verified it.
$new_password0 = Horde_Util::getFormData('newpassword0', false);
$new_password1 = Horde_Util::getFormData('newpassword1', false);
if (!$new_password0) {
    $notification->push(_("You must give your new password"), 'horde.warning');
    goto proceed;
}
if (!$new_password1) {
    $notification->push(_("You must verify your new password"), 'horde.warning');
    goto proceed;
}
if ($new_password0 != $new_password1) {
    $notification->push(_("Your new passwords didn't match"), 'horde.warning');
    goto proceed;
}
if ($new_password0 == $old_password) {
    $notification->push(_("Your new password must be different from your current password"), 'horde.warning');
    goto proceed;
}

try {
    Horde_Auth::checkPasswordPolicy($new_password0, $password_policy);
} catch (Horde_Auth_Exception $e) {
    $notification->push($e->getMessage(), 'horde.warning');
    goto proceed;
}

// Do some simple strength tests, if enabled in the config file.
if ($conf['password']['strengthtests']) {
    try {
        Horde_Auth::checkPasswordSimilarity($new_password0,
                                            array($userid, $old_password));
    } catch (Horde_Auth_Exception $e) {
        $notification->push($e->getMessage(), 'horde.warning');
        goto proceed;
    }
}

// Create a Password_Driver instance.
try {
    $daemon = $GLOBALS['injector']->getInstance('Passwd_Factory_Driver')->setBackends($backends)->create($backend_key);
}
catch (Passwd_Exception $e) {
    Horde::logMessage($e);
    $notification->push(_("Password module is not properly configured"),
                        'horde.error');
    goto proceed;
}

try {
    $backend_userid = Horde::callHook('username', array($userid, $daemon), 'passwd');
} catch (Horde_Exception_HookNotSet $e) {
    $backend_userid = $userid;
}

try {
    $res = $daemon->changePassword($backend_userid, $old_password,
                                   $new_password0);
    if (!isset($backends[$backend_key]['no_reset']) ||
        !$backends[$backend_key]['no_reset']) {
        Passwd::resetCredentials($old_password, $new_password0);
    }

    $notification->push(sprintf(_("Password changed on %s."),
                                $backends[$backend_key]['name']), 'horde.success');

    try {
        Horde::callHook('password_changed', array($backend_userid, $old_password, $new_password0), 'passwd');
    } catch (Horde_Exception_HookNotSet $e) {
    }

    if (!empty($return_to)) {
        header('Location: ' . $return_to);
        exit;
    }
} catch (Exception $e) {
    $notification->push(sprintf(_("Failure in changing password for %s: %s"),
                                $backends[$backend_key]['name'],
                                $e->getMessage()), 'horde.error');
}

proceed:

// Choose the prefered backend from config/backends.php.
foreach ($backends as $key => $current_backend) {
    if (!isset($backend_key) && substr($key, 0, 1) != '_') {
        $backend_key = $key;
    }
    if (Passwd::isPreferredBackend($current_backend)) {
        $backend_key = $key;
        break;
    }
}

// Extract userid to be shown in the username field.
if (empty($userid)) {
    try {
        $userid = Horde::callHook('default_username', array($registry->getAuth()), 'passwd');
    } catch (Horde_Exception_HookNotSet $e) {
        $userid = $registry->getAuth();
    }
}

$view = new Horde_View(array('templatePath' => PASSWD_TEMPLATES));
new Horde_View_Helper_Text($view);
$view->formInput = Horde_Util::formInput();
$view->url = $return_to;
$view->userid = $userid;
$view->userChange = $conf['user']['change'];
$view->showlist = $conf['backend']['backend_list'] == 'shown';
$view->backend = $backend_key;
$view->label = (object)array(
    'userid'       => Horde::label('userid', _("Username:")),
    'oldpassword'  => Horde::label('oldpassword', _("Old password:")),
    'newpassword0' => Horde::label('newpassword0', _("New password:")),
    'newpassword1' => Horde::label('newpassword1', _("Confirm new password:")),
    'backend'      => Horde::label('backend', _("Change password for:")));
$view->help = (object)array(
    'username'        => Horde_Help::link('passwd', 'passwd-username'),
    'oldpassword'     => Horde_Help::link('passwd', 'passwd-old-password'),
    'newpassword'     => Horde_Help::link('passwd', 'passwd-new-password'),
    'confirmpassword' => Horde_Help::link('passwd', 'passwd-confirm-password'),
    'server'          => Horde_Help::link('passwd', 'passwd-server'));

// Build the <select> widget for the backends list.
if ($view->showlist) {
    foreach ($backends as $key => &$backend) {
        $backend['selected'] = ($key == $backend_key)
            ? ' selected="selected"'
            : '';
    }
    $view->backends = $backends;
    $view->header = _("Change your password");
} else {
    $view->header = sprintf(_("Changing password for %s"),
                            htmlspecialchars($backends[$backend_key]['name']));
}

$menu = new Horde_Menu(Horde_Menu::MASK_ALL & ~Horde_Menu::MASK_PREFS);
$view->menu = $menu->render();

$page_output->addScriptFile('stripe.js', 'horde');
$page_output->addScriptFile('passwd.js');
$page_output->addInlineScript(array(
    '$(\'passwd\').focusFirstElement()'
), true);
$page_output->addInlineJsVars(array(
    'var Passwd' => array(
        'current_pass' => _("Please provide your current password"),
        'new_pass' => _("Please provide a new password"),
        'verify_pass' => _("Please verify your new password"),
        'no_match' => _("Your passwords do not match"),
    )
));

$page_output->header(array(
    'title' => _("Change Password")
));
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
echo $view->render('index');
$page_output->footer();
