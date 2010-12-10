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

$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();

// Make sure signups are enabled before proceeding
if ($conf['signup']['allow'] !== true ||
    !$auth->hasCapability('add')) {
    $notification->push(_("User Registration has been disabled for this site."), 'horde.error');
    $registry->authenticateFailure('folks');
}

$signup = $injector->getInstance('Horde_Core_Auth_Signup');
if ($signup instanceof PEAR_Error) {
    $notification->push($signup, 'horde.error');
    $registry->authenticateFailure('folks');
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Core_Auth_Signup_Form($vars);
if ($form->validate()) {
    $form->getInfo(null, $info);
    try {
        if ($conf['signup']['approve']) {
            /* Insert this user into a queue for admin approval. */
            $success = $signup->queueSignup($info);
            $success_message = sprintf(_("Submitted request to add \"%s\" to the system. You cannot log in until your request has been approved."), $info['user_name']);
            $notification->push($success_message, 'horde.success');
        } else {
            /* User can sign up directly, no intervention necessary. */
            $success = $signup->addSignup($info);
            $success_message = sprintf(_("Added \"%s\" to the system. You can log in now."), $info['user_name']);
        }
        $notification->push($success_message, 'horde.success');
        $registry->authenticateFailure('folks');
    } catch (Horde_Exception $e) {
        $notification->push(sprintf(_("There was a problem adding \"%s\" to the system: %s"), $info['user_name'], $e->getMessage()), 'horde.error');
    }
}

$title = _("Sign up");
require $registry->get('templates', 'horde') . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

require FOLKS_TEMPLATES . '/login/signup.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';
