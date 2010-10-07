<?php
/**
 * $Id: password.php 880 2008-09-22 12:33:57Z duck $
 *
 * Copyright 2007 Obala d.o.o. (http://www.obala.si/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Duck <duck@obala.net>
 */

define('FOLKS_BASE', dirname(__FILE__) . '/..');
require_once FOLKS_BASE . '/lib/base.php';
require_once 'tabs.php';

/*
// Make sure auth backend allows passwords to be updated.
$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
if (!$auth->hasCapability('resetpassword')) {
    $notification->push(_("Cannot update password, contact your administrator."), 'horde.error');
    $registry->authenticateFailure('folks');
}
*/

$title = _("Change Your Password");
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, $title, 'password');
$form->setButtons(_("Continue"));
$form->addVariable(_("Current password"), 'old', 'password', true);
$form->addVariable(_("Choose a password"), 'new', 'passwordconfirm', true, false, _("type the password twice to confirm"));

// Use a do-while to allow easy breaking if an error is found.
do {

    // Validate the form
    if (!$form->validate()) {
        break;
    }

    $form->getInfo(null, $info);

    // Check old and new passwords
    if ($info['old'] == $info['new']) {
        $notification->push(_("Your new password must be different from your current password"), 'horde.warning');
        break;
    }

    // Check old password
    if ($info['old'] == $info['new']) {
        $notification->push(_("Your old password didn't match"), 'horde.warning');
        break;
    }

    // Check some password policy
    $password_policy = Horde::loadConfiguration('password_policy.php', 'password_policy', 'folks');
    if (is_array($password_policy)) {
        // Check max/min lengths if specified in the backend config.
        if (isset($password_policy['minLength']) &&
            strlen($info['new']) < $password_policy['minLength']) {
            $notification->push(sprintf(_("Your new password must be at least %d characters long!"), $password_policy['minLength']), 'horde.warning');
            break;
        }
        if (isset($password_policy['maxLength']) &&
            strlen($info['new']) > $password_policy['maxLength']) {
            $notification->push(sprintf(_("Your new password is too long; passwords may not be more than %d characters long!"), $password_policy['maxLength']), 'horde.warning');
            break;
        }

        // Disect the password in a localised way.
        $classes = array();
        $alpha = $alnum = $num = $upper = $lower = $space = $symbol = 0;
        for ($i = 0; $i < strlen($info['new']); $i++) {
            $char = substr($info['new'], $i, 1);
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
            $notification->push(sprintf(_("Your new password must contain at least %d uppercase characters."), $password_policy['minUpper']), 'horde.warning');
            break;
        }
        if (isset($password_policy['minLower']) &&
            $password_policy['minLower'] > $lower) {
            $notification->push(sprintf(_("Your new password must contain at least %d lowercase characters."), $password_policy['minLower']), 'horde.warning');
            break;
        }
        if (isset($password_policy['minNumeric']) &&
            $password_policy['minNumeric'] > $num) {
            $notification->push(sprintf(_("Your new password must contain at least %d numeric characters."), $password_policy['minNumeric']), 'horde.warning');
            break;
        }
        if (isset($password_policy['minAlpha']) &&
            $password_policy['minAlpha'] > $alpha) {
            $notification->push(sprintf(_("Your new password must contain at least %d alphabetic characters."), $password_policy['minAlpha']), 'horde.warning');
            break;
        }
        if (isset($password_policy['minAlphaNum']) &&
            $password_policy['minAlphaNum'] > $alnum) {
            $notification->push(sprintf(_("Your new password must contain at least %d alphanumeric characters."), $password_policy['minAlphaNum']), 'horde.warning');
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
                $notification->push(sprintf(_("Your new password must contain less than %d whitespace characters."), $password_policy['maxSpace']), 'horde.warning');
            } else {
                $notification->push(_("Your new password must not contain whitespace characters."), 'horde.warning');
            }
            break;
        }

        // Do some simple strength tests, if enabled in the config file.
        if ($conf['password']['strengthtests']) {
            // Check for new==old, pass==user, simple reverse strings, etc.
            if ((strcasecmp($info['new'], $userid) == 0) ||
                (strcasecmp($info['new'], strrev($userid)) == 0) ||
                (strcasecmp($info['new'], $old_password) == 0) ||
                (strcasecmp($info['new'], strrev($old_password)) == 0) ) {
                $notification->push(_("Your new password is too simple to guess. Not changed!"),
                                    'horde.warning');
                break;
            }
            // Check for percentages similarity also.  This will catch very simple
            // Things like "password" -> "password2" or "xpasssword"...
            @similar_text($info['new'], $old_password, $percent1);
            @similar_text($info['new'], $userid, $percent2);
            if (($percent1 > 80) || ($percent2 > 80)) {
                $notification->push(_("Your new password is too simple to guess!  Not changed!"),
                                    'horde.warning');
                break;
            }
        }
    }

    // try to chage it
    $result = $folks_driver->changePassword($info['new'], $GLOBALS['registry']->getAuth());
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
        break;
    }

    $notification->push(_("Password changed."), 'horde.success');

    // reset credentials so user is not forced to relogin
    if ($registry->getAuthCredential('password') == $info['old']) {
        $registry->setAuthCredential('password', $info['new']);
    }
} while (false);

// update password reminder prefs
if (Horde_Util::getPost('formname') == 'security') {
    if ($prefs->getValue('security_question') != Horde_Util::getPost('security_question')) {
        $prefs->setValue('security_question', Horde_Util::getPost('security_question'));
    }

    if ($prefs->getValue('security_answer') != Horde_Util::getPost('security_answer')) {
        $prefs->setValue('security_answer', Horde_Util::getPost('security_answer'));
    }

    $notification->push(_("Your securiy questions was updated."), 'horde.success');
}


$form_security = new Horde_Form($vars, _("Security question used when reseting password"), 'security');
$form_security->setButtons(_("Continue"), _("Reset"));
if (!$prefs->isLocked('security_question')) {
    $v = &$form_security->addVariable(_("Security question"), 'security_question', 'text', true);
    $v->setDefault($prefs->getValue('security_question'));
}
$v = &$form_security->addVariable(_("Security answer"), 'security_answer', 'text', true);
$v->setDefault($prefs->getValue('security_answer'));

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

echo $tabs->render('password');
$form->renderActive(null, null, null, 'post');
echo '<br />';
$form_security->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
