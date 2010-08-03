<?php
/**
 * $Id: resetpassword.php 935 2008-09-27 09:37:38Z duck $
 *
 * Copyright 2007 Obala d.o.o. (http://www.obala.si/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/tabs.php';

/**
 * Returns a new or the current CAPTCHA string.
 *
 * @param boolean $new string
 */
function _getCAPTCHA($new = false)
{
    if ($new || empty($_SESSION['folks']['CAPTCHA'])) {
        $_SESSION['folks']['CAPTCHA'] = '';
        for ($i = 0; $i < 5; $i++) {
            $_SESSION['folks']['CAPTCHA'] .= chr(rand(65, 90));
        }
    }
    return $_SESSION['folks']['CAPTCHA'];
}

// We are already logged
if ($registry->isAuthenticated()) {
    Folks::getUrlFor('user', $GLOBALS['registry']->getAuth())->redirect();
}

// Make sure auth backend allows passwords to be reset.
$auth = $injector->getInstance('Horde_Auth')->getAuth();
if (!$auth->hasCapability('resetpassword')) {
    $notification->push(_("Cannot reset password automatically, contact your administrator."), 'horde.error');
    $registry->authenticateFailure('folks');
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
                        array(_getCAPTCHA(!$form->isSubmitted()), HORDE_BASE . '/config/couri.ttf'));
}

/* Validate the form. */
if ($form->validate()) {
    $form->getInfo(null, $info);

    /* Get user email. */
    $email = Folks::getUserEmail($info['username']);
    if ($email instanceof PEAR_Error) {
        $notification->push($email);
        $registry->authenticateFailure('folks');
    }

    /* Check the given values with the prefs stored ones. */
    if ((!empty($answer) && Horde_String::lower($answer) == Horde_String::lower($info['security_answer'])) ||
            empty($answer)) {

        /* Info matches, so reset the password. */
        $password = $auth->resetPassword($info['username']);
        if ($password instanceof PEAR_Error) {
            $notification->push($password);
            $registry->authenticateFailure('folks');
        }

        $body = sprintf(_("Your new password for %s is: %s. \n\n It was requested by %s on %s"),
                            $registry->get('name', 'horde'),
                            $password,
                            $_SERVER['REMOTE_ADDR'],
                            date('Ymd H:i:s'));

        Folks::sendMail($email, _("Your password has been reset"), $body);

        $notification->push(sprintf(_("Your password has been reset, check your email (%s) and log in with your new password."), $email), 'horde.success');
        $registry->authenticateFailure('folks');
    } else {
        /* Info submitted does not match what is in prefs, redirect user back
         * to login. */
        $notification->push(_("Could not reset the password for the requested user. Some or all of the details are not correct. Try again or contact your administrator if you need further help."), 'horde.error');
    }
}

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

require FOLKS_TEMPLATES . '/login/signup.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';
