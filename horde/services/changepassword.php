<?php
/**
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Jason Felice <jason.m.felice@gmail.com>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintasks' => true));

// Make sure auth backend allows passwords to be reset.
$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
if (!$auth->hasCapability('update')) {
    $notification->push(_("Changing your password is not supported with the current configuration.  Contact your administrator."), 'horde.error');
    $registry->getServiceLink('login')->add('url', Horde_Util::getFormData('url'))->redirect();
}

$vars = Horde_Variables::getDefaultVariables();

$title = _("Change Your Password");
$form = new Horde_Form($vars, $title);
$form->setButtons(_("Continue"));

$form->addHidden('', 'return_to', 'text', false);
$form->addVariable(_("Old password"), 'old_password', 'password', true);
$form->addVariable(_("New password"), 'password_1', 'password', true);
$form->addVariable(_("Retype new password"), 'password_2', 'password', true);

if ($vars->exists('formname')) {
    $form->validate($vars);
    if ($form->isValid()) {
        $form->getInfo($vars, $info);

        if ($GLOBALS['registry']->getAuthCredential('password') != $info['old_password']) {
            $notification->push(_("Old password is not correct."), 'horde.error');
        } elseif ($info['password_1'] != $info['password_2']) {
            $notification->push(_("New passwords don't match."), 'horde.error');
        } elseif ($info['old_password'] == $info['password_1']) {
            $notification->push(_("Old and new passwords must be different."), 'horde.error');
        } else {
            /* TODO: Need to clean up password policy patch and commit before
             * enabling this...
             * Horde_Auth::testPasswordStrength($info['password_1'],
             *                                  $conf['auth']['password_policy']);
             */
            try {
                $auth->updateUser($registry->getAuth(), $registry->getAuth(), array('password' => $info['password_1']));

                $notification->push(_("Password changed successfully."), 'horde.success');

                $index_url = Horde::url('index.php', true);
                if (!empty($info['return_to'])) {
                    $index_url->add('url', $info['return_to']);
                }

                $index_url->redirect();
            } catch (Horde_Auth_Exception $e) {
                $notification->push(sprintf(_("Error updating password: %s"), $e->getMessage()), 'horde.error');
            }
        }
    }
}

$vars->remove('old_password');
$vars->remove('password_1');
$vars->remove('password_2');

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
$form->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('services/changepassword.php'), 'post');
$page_output->footer();
