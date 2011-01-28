<?php
/**
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();

// Make sure signups are enabled before proceeding
if ($conf['signup']['allow'] !== true ||
    !$auth->hasCapability('add')) {
    $notification->push(_("User Registration has been disabled for this site."), 'horde.error');
    Horde::getServiceLink('login')->redirect();
}

try {
    $signup = $injector->getInstance('Horde_Core_Auth_Signup');
} catch (Horde_Exception $e) {
    Horde::logMessage($e, 'ERR');
    $notification->push(_("User Registration is not properly configured for this site."), 'horde.error');
    Horde::getServiceLink('login')->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$formsignup = new Horde_Core_Auth_Signup_Form($vars);
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
            Horde::getServiceLink('login')->add('url', $info['url'])->redirect();
        }
    }
}

$title = _("User Registration");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/login/header.inc';
$notification->notify(array('listeners' => 'status'));
$formsignup->renderActive($formsignup->getRenderer(), $vars, 'signup.php', 'post');
require HORDE_TEMPLATES . '/common-footer.inc';
