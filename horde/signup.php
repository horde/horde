<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();

// Make sure signups are enabled before proceeding
if ($conf['signup']['allow'] !== true ||
    !$auth->hasCapability('add')) {
    $notification->push(_("User Registration has been disabled for this site."), 'horde.error');
    $registry->getServiceLink('login')->redirect();
}

try {
    $signup = $injector->getInstance('Horde_Core_Auth_Signup');
} catch (Horde_Exception $e) {
    Horde::logMessage($e, 'ERR');
    $notification->push(_("User Registration is not properly configured for this site."), 'horde.error');
    $registry->getServiceLink('login')->redirect();
}

$vars = $injector->getInstance('Horde_Variables');
$username = $vars->get('user_name');
$email = $vars->get('email');
if (!(bool)filter_var($username, FILTER_VALIDATE_EMAIL) && !empty($username) && !empty($conf['signup']['altemail'])) {
    $showEmail = true;
    if ($conf['signup']['altemail'] == 1) {
        $requireEmail = true;
    }
}
$formsignup = new Horde_Core_Auth_Signup_Form($vars,$showEmail,$requireEmail);
if ($formsignup->validate()) {
    $formsignup->getInfo($vars, $info);
    $error = $success_message = null;

    if ($info instanceof PEAR_Error) {
        $notification->push(sprintf(_("There was a problem adding \"%s\" to the system: %s"), $vars->get('user_name'), $info->getMessage()), 'horde.error');
    } else {
        if (!$conf['signup']['approve']) {
            /* User can sign up directly, no intervention necessary. */
            try {
                $signup->addSignup($info);
                $success_message = sprintf(_("Added \"%s\" to the system. You can log in now."), $info['user_name']);
            } catch (Horde_Exception $e) {
                $error = $e;
            }
        } elseif ($conf['signup']['approve']) {
            /* Insert this user into a queue for admin approval. */
            try {
                $signup->queueSignup($info);
                $success_message = sprintf(_("Submitted request to add \"%s\" to the system. You cannot log in until your request has been approved."), $info['user_name']);
            } catch (Horde_Exception $e) {
                $error = $e;
            }
        }

        if ($error) {
            $notification->push(sprintf(_("There was a problem adding \"%s\" to the system: %s"), $info['user_name'], $e->getMessage()), 'horde.error');
        } else {
            $notification->push($success_message, 'horde.success');
            $registry->getServiceLink('login')->add('url', $info['url'])->redirect();
        }
    }
} else {
    if(!empty($username)) {
        try {
            $signup->checkUsername($username);
        } catch (Horde_Exception $e) {
            $notification->push($e->getMessage(), 'horde.error');
        }
    }
    if(!empty($email) && !$signup->checkEmail($email)) {
        $notification->push(_("Email address not valid."), 'horde.error');
    }
}

$page_output->topbar = $page_output->sidebar = false;

$page_output->header(array(
    'body_class' => 'modal-form',
    'title' => _("User Registration")
));
require HORDE_TEMPLATES . '/login/signup.inc';
$page_output->footer();
