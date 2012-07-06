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

$vars = Horde_Variables::getDefaultVariables();

$title = _("Forgot your username?");
$form = new Horde_Form($vars, $title);
$form->setButtons(_("Send me my username"));
$form->addVariable(_("Your email"), 'email', 'email', true);

/* Validate the form. */
if ($form->validate()) {
    $form->getInfo(null, $info);

    $users = $folks_driver->getUsers(array('email' => $info['email']));
    if ($users instanceof PEAR_Error) {
        $notification->push($users);
    } elseif (empty($users) || count($users) != 1) {
        $notification->push(_("Could not find any username with this email."), 'horde.warning');
    } else {
        $users = current($users);

        $body = sprintf(_("Your username on %s %s is: %s. \n\n It was requested by %s on %s"),
                            $registry->get('name', 'horde'),
                            Horde::url($registry->get('webroot', 'horde'), true),
                            $users['user_uid'],
                            $_SERVER['REMOTE_ADDR'],
                            date('Ymd H:i:s'));

        Folks::sendMail($info['email'], _("Your username was requested"), $body);

        $notification->push(sprintf(_("Your username was sent, check your email (%s)."), $users['user_email']), 'horde.success');
        throw new Horde_Exception_AuthenticationFailure();

    }
}

$page_output->header(array(
    'title' => $title
));
require FOLKS_TEMPLATES . '/menu.inc';
require FOLKS_TEMPLATES . '/login/signup.php';
$page_output->footer();
