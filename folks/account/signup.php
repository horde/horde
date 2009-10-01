<?php
/**
 * $Id: signup.php 918 2008-09-25 02:18:59Z duck $
 *
 * Copyright 2007 Obala d.o.o. (http://www.obala.si/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/tabs.php';
require_once 'Horde/Auth/Signup.php';

$auth = Horde_Auth::singleton($conf['auth']['driver']);

// Make sure signups are enabled before proceeding
if ($conf['signup']['allow'] !== true ||
    !$auth->hasCapability('add')) {
    $notification->push(_("User Registration has been disabled for this site."), 'horde.error');
    Horde_Auth::authenticateFailure('folks');
}

$vars = Horde_Variables::getDefaultVariables();
$form = new HordeSignupForm($vars);
if ($form->validate()) {
    $form->getInfo(null, $info);

    $signup = new Horde_Auth_Signup();
    $success_message = null;

    if (!$conf['signup']['approve']) {
        /* User can sign up directly, no intervention necessary. */
        $success = $signup->addSignup($info);
        if (!($success instanceof PEAR_Error)) {
            $success_message = sprintf(_("Added \"%s\" to the system. You can log in now."), $info['user_name']);
        }
    } elseif ($conf['signup']['approve']) {
        /* Insert this user into a queue for admin approval. */
        $success = $signup->queueSignup($info);
        if (!($success instanceof PEAR_Error)) {
            $success_message = sprintf(_("Submitted request to add \"%s\" to the system. You cannot log in until your request has been approved."), $info['user_name']);
        }
    }

    if ($info instanceof PEAR_Error) {
        $username = $vars->get('user_name') ? $vars->get('user_name') : $vars->get('extra[user_name]');
        $notification->push(sprintf(_("There was a problem adding \"%s\" to the system: %s"), $username, $info->getMessage()), 'horde.error');
    } elseif ($success instanceof PEAR_Error) {
        $notification->push(sprintf(_("There was a problem adding \"%s\" to the system: %s"), $info['user_name'], $success->getMessage()), 'horde.error');
    } else {
        $notification->push($success_message, 'horde.success');
        Horde_Auth::authenticateFailure('folks');
    }
}

$title = _("Sign up");
require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

require FOLKS_TEMPLATES . '/login/signup.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';
