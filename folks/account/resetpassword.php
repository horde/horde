<?php
/**
 * Copyright 2007 Obala d.o.o. (http://www.obala.si/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Duck <duck@obala.net>
 */

require_once __DIR__ . '/tabs.php';

// We are already logged
if ($registry->isAuthenticated()) {
    Folks::getUrlFor('user', $GLOBALS['registry']->getAuth())->redirect();
}

// Make sure auth backend allows passwords to be reset.
$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
if (!$auth->hasCapability('resetpassword')) {
    $notification->push(_("Cannot reset password automatically, contact your administrator."), 'horde.error');
    throw new Horde_Exception_AuthenticationFailure();
}

$vars = Horde_Variables::getDefaultVariables();

$title = _("Reset Your Password");
$form = new Horde_Form($vars, $title);
$form->setButtons(_("Continue"));

// Get user security pass
$user = Horde_Util::getFormData('username');
if ($user) {
    $u_prefs = $injector->getInstance('Horde_Prefs')->getPrefs('horde', array(
        'cache' => false,
        'user' => $registry->convertUsername($user, true)
    ));
    $answer = $u_prefs->getValue('security_answer');
    $question = $u_prefs->getValue('security_question');
} else {
    $answer = $prefs->getValue('security_answer');
    $question = $prefs->getValue('security_question');
}
/* Set up the fields for the username and alternate email. */
$form->addHidden('', 'url', 'text', false);
$form->addVariable(_("Username"), 'username', 'text', true);

if (!empty($answer)) {
    if (!empty($question)) {
        $form->addVariable(_("Please respond to your security question: ") . $question, 'security_question', 'description', true);
    }
    $form->addVariable(_("Security answer"), 'security_answer', 'text', true);
} else {
    $desc = _("The picture above is for antispam checking. Please retype the characters from the picture. They are case sensitive.");
    $form->addVariable(_("Human check"), 'captcha', 'captcha', true, false, $desc,
                        array(Folks::getCAPTCHA(!$form->isSubmitted()), HORDE_BASE . '/config/couri.ttf'));
}

/* Validate the form. */
if ($form->validate()) {
    $form->getInfo(null, $info);

    /* Get user email. */
    $email = Folks::getUserEmail($info['username']);
    if ($email instanceof PEAR_Error) {
        $notification->push($email);
        throw new Horde_Exception_AuthenticationFailure();
    }

    /* Check the given values with the prefs stored ones. */
    if ((!empty($answer) && Horde_String::lower($answer) == Horde_String::lower($info['security_answer'])) ||
            empty($answer)) {

        /* Info matches, so reset the password. */
        $password = $auth->resetPassword($info['username']);
        if ($password instanceof PEAR_Error) {
            $notification->push($password);
        throw new Horde_Exception_AuthenticationFailure();
        }

        $body = sprintf(_("Your new password for %s is: %s. \n\n It was requested by %s on %s"),
                            $registry->get('name', 'horde'),
                            $password,
                            $_SERVER['REMOTE_ADDR'],
                            date('Ymd H:i:s'));

        Folks::sendMail($email, _("Your password has been reset"), $body);

        $notification->push(sprintf(_("Your password has been reset, check your email (%s) and log in with your new password."), $email), 'horde.success');
        throw new Horde_Exception_AuthenticationFailure();
    } else {
        /* Info submitted does not match what is in prefs, redirect user back
         * to login. */
        $notification->push(_("Could not reset the password for the requested user. Some or all of the details are not correct. Try again or contact your administrator if you need further help."), 'horde.error');
    }
}

$page_output->header(array(
    'title' => $title
));
require FOLKS_TEMPLATES . '/menu.inc';
require FOLKS_TEMPLATES . '/login/signup.php';
$page_output->footer();
