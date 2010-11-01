<?php
/**
 * $Horde: passwd/main.php,v 1.67.2.10 2009/07/05 17:13:32 chuck Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/gpl.php.
 *
 * @author  Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @package Passwd
 */

require_once dirname(__FILE__) . '/lib/Application.php';
require PASSWD_BASE . '/config/backends.php';

// Get the backend details.
$backend_key = Horde_Util::getFormData('backend', false);
if (!isset($backends[$backend_key])) {
    $backend_key = null;
}

// Use a do-while to allow easy breaking if an error is found.
do {
    if (!$backend_key) {
        break;
    }

    // Has the user submitted the form yet?
    $submit = Horde_Util::getFormData('submit', false);
    if (!$submit) {
        // No so we don't need to do anything in this loop.
        break;
    }

    $driver = $backends[$backend_key]['driver'];
    $params = $backends[$backend_key]['params'];
    $password_policy = isset($backends[$backend_key]['password policy'])
        ? $backends[$backend_key]['password policy']
        : array();

    // Get the username.
    if ($conf['user']['change'] === true) {
        $userid = Horde_Util::getFormData('userid');
    } else {
        if ($conf['hooks']['default_username']) {
            $userid = Horde::callHook('_passwd_hook_default_username',
                                      array(Auth::getAuth()),
                                      'passwd');
        } else {
            $userid = $registry->getAuth($conf['hooks']['full_name'] ? null : 'bare');
        }
    }

    // Check for users that cannot change their passwords.
    if (in_array($userid, $conf['user']['refused'])) {
        $notification->push(sprintf(_("You can't change password for user %s"),
                                    $userid), 'horde.error');
        break;
    }

    // We must be passed the old (current) password, or its an error.
    $old_password = Horde_Util::getFormData('oldpassword', false);
    if (!$old_password) {
        $notification->push(_("You must give your current password"),
                            'horde.warning');
        break;
    }

    // See if they entered the new password and verified it.
    $new_password0 = Horde_Util::getFormData('newpassword0', false);
    $new_password1 = Horde_Util::getFormData('newpassword1', false);
    if (!$new_password0) {
        $notification->push(_("You must give your new password"), 'horde.warning');
        break;
    }
    if (!$new_password1) {
        $notification->push(_("You must verify your new password"), 'horde.warning');
        break;
    }
    if ($new_password0 != $new_password1) {
        $notification->push(_("Your new passwords didn't match"), 'horde.warning');
        break;
    }
    if ($new_password0 == $old_password) {
        $notification->push(_("Your new password must be different from your current password"), 'horde.warning');
        break;
    }

    // Check max/min lengths if specified in the backend config.
    if (isset($password_policy['minLength']) &&
        strlen($new_password0) < $password_policy['minLength']) {
        $notification->push(sprintf(_("Your new password must be at least %d characters long!"), $password_policy['minLength']), 'horde.warning');
        break;
    }
    if (isset($password_policy['maxLength']) &&
        strlen($new_password0) > $password_policy['maxLength']) {
        $notification->push(sprintf(_("Your new password is too long; passwords may not be more than %d characters long!"), $password_policy['maxLength']), 'horde.warning');
        break;
    }

    // Disect the password in a localised way.
    $classes = array();
    $alpha = $alnum = $num = $upper = $lower = $space = $symbol = 0;
    for ($i = 0; $i < strlen($new_password0); $i++) {
        $char = substr($new_password0, $i, 1);
        if (ctype_lower($char)) {
            $lower++; $alpha++; $alnum++; $classes['lower'] = 1;
        } elseif (ctype_upper($char)) {
            $upper++; $alpha++; $alnum++; $classes['upper'] = 1;
        } elseif (ctype_digit($char)) {
            $num++; $alnum++; $classes['number'] = 1;
        } elseif (ctype_punct($char)) {
            $symbol++; $classes['symbol'] = 1;
        } elseif (ctype_space($char)) {
            $space++; $classes['symbol'] = 1;
        }
    }

    // Check reamaining password policy options.
    if (isset($password_policy['minUpper']) &&
        $password_policy['minUpper'] > $upper) {
        $notification->push(sprintf(ngettext("Your new password must contain at least %d uppercase character.", "Your new password must contain at least %d uppercase characters.", $password_policy['minUpper']), $password_policy['minUpper']), 'horde.warning');
        break;
    }
    if (isset($password_policy['minLower']) &&
        $password_policy['minLower'] > $lower) {
        $notification->push(sprintf(ngettext("Your new password must contain at least %d lowercase character.", "Your new password must contain at least %d lowercase characters.", $password_policy['minLower']), $password_policy['minLower']), 'horde.warning');
        break;
    }
    if (isset($password_policy['minNumeric']) &&
        $password_policy['minNumeric'] > $num) {
        $notification->push(sprintf(ngettext("Your new password must contain at least %d numeric character.", "Your new password must contain at least %d numeric characters.", $password_policy['minNumeric']), $password_policy['minNumeric']), 'horde.warning');
        break;
    }
    if (isset($password_policy['minAlpha']) &&
        $password_policy['minAlpha'] > $alpha) {
        $notification->push(sprintf(ngettext("Your new password must contain at least %d alphabetic character.", "Your new password must contain at least %d alphabetic characters.", $password_policy['minAlpha']), $password_policy['minAlpha']), 'horde.warning');
        break;
    }
    if (isset($password_policy['minAlphaNum']) &&
        $password_policy['minAlphaNum'] > $alnum) {
        $notification->push(sprintf(ngettext("Your new password must contain at least %d alphanumeric character.", "Your new password must contain at least %d alphanumeric characters.", $password_policy['minAlphaNum']), $password_policy['minAlphaNum']), 'horde.warning');
        break;
    }
    if (isset($password_policy['minClasses']) &&
        $password_policy['minClasses'] > array_sum($classes)) {
        $notification->push(sprintf(_("Your new password must contain at least %d different types of characters. The types are: lower, upper, numeric, and symbols."), $password_policy['minClasses']), 'horde.warning');
        break;
    }
    if (isset($password_policy['maxSpace']) &&
        $password_policy['maxSpace'] < $space) {
        if ($password_policy['maxSpace'] > 0) {
            $notification->push(sprintf(_("Your new password must contain less than %d whitespace characters."), $password_policy['maxSpace'] + 1), 'horde.warning');
        } else {
            $notification->push(_("Your new password must not contain whitespace characters."), 'horde.warning');
        }
        break;
    }
    if (isset($password_policy['minSymbol']) &&
        $password_policy['minSymbol'] > $symbol) {
        $notification->push(sprintf(ngettext("Your new password must contain at least %d symbol character.", "Your new password must contain at least %d symbol characters.", $password_policy['minSymbol']), $password_policy['minSymbol']), 'horde.warning');
        break;
    }

    // Do some simple strength tests, if enabled in the config file.
    if ($conf['password']['strengthtests']) {
        // Check for new==old, pass==user, simple reverse strings, etc.
        if ((strcasecmp($new_password0, $userid) == 0) ||
            (strcasecmp($new_password0, strrev($userid)) == 0) ||
            (strcasecmp($new_password0, $old_password) == 0) ||
            (strcasecmp($new_password0, strrev($old_password)) == 0) ) {
            $notification->push(_("Your new password is too simple to guess. Not changed!"),
                                'horde.warning');
            break;
        }
        // Check for percentages similarity also.  This will catch very simple
        // Things like "password" -> "password2" or "xpasssword"...
        @similar_text($new_password0, $old_password, $percent1);
        @similar_text($new_password0, $userid, $percent2);
        if (($percent1 > 80) || ($percent2 > 80)) {
            $notification->push(_("Your new password is too simple to guess!  Not changed!"),
                                'horde.warning');
            break;
        }
    }

    // Create a Password_Driver instance.
    require_once PASSWD_BASE . '/lib/Driver.php';
    $daemon = Passwd_Driver::factory($driver, $params);

    if (is_a($daemon, 'PEAR_Error')) {
        $notification->push(_("Password module is not properly configured"),
                            'horde.error');
        break;
    }

    $backend_userid = $userid;

    if ($conf['hooks']['username']) {
        $backend_userid = Horde::callHook('_passwd_hook_username',
                                          array($userid, &$daemon),
                                          'passwd');
        if (is_a($backend_userid, 'PEAR_Error')) {
            $notification->push($backend_userid, 'horde.error');
            break;
        }
    }

    $res = $daemon->changePassword($backend_userid, $old_password,
                                   $new_password0);

    if (!is_a($res, 'PEAR_Error')) {
        if (!isset($backends[$backend_key]['no_reset']) ||
            !$backends[$backend_key]['no_reset']) {
            Passwd::resetCredentials($old_password, $new_password0);
        }

        $notification->push(sprintf(_("Password changed on %s."),
                                    $backends[$backend_key]['name']), 'horde.success');

        Horde::callHook('_passwd_password_changed',
                        array($backend_userid, $old_password, $new_password0),
                        'passwd');

        $return_to = Horde_Util::getFormData('return_to');
        if (!empty($return_to)) {
            header('Location: ' . $return_to);
            exit;
        }
    } else {
        $notification->push(sprintf(_("Failure in changing password for %s: %s"),
                                    $backends[$backend_key]['name'],
                                    $res->getMessage()), 'horde.error');
    }
} while (false);

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

// Build the <select> widget for the backends list.
if ($conf['backend']['backend_list'] == 'shown') {
    $backends_list = '';

    foreach ($backends as $key => $current_backend) {
        $sel = ($key == $backend_key) ? ' selected="selected"' : '';
        $backends_list .= '<option value="' . htmlspecialchars($key) . '"' . $sel . '>' .
            htmlspecialchars($current_backend['name']) . '</option>';
    }
}

// Extract userid to be shown in the username field.
if (empty($userid)) {
    if ($conf['hooks']['default_username']) {
        $userid = Horde::callHook('_passwd_hook_default_username',
                                  array(Auth::getAuth()),
                                  'passwd');
    } else {
        $userid = $registry->getAuth($conf['hooks']['full_name'] ? null : 'bare');
    }
}

Horde::addInlineScript(array(
    'setFocus()'
), 'dom');

$title = _("Change Password");
require PASSWD_TEMPLATES . '/common-header.inc';
require PASSWD_TEMPLATES . '/main/main.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
