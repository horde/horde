<?php
/**
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once __DIR__ . '/../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma');

/* Only admin should be using this. */
if (!Vilma::hasPermission($domain)) {
    throw new Horde_Exception_AuthenticationFailure();
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $form = new Vilma_Form_DeleteDomain($vars);
} catch (Exception $e) {
    $notification->push($e);
    Horde::url('domains/index.php', true)->redirect();
}

if ($vars->get('submitbutton') == _("Do not delete")) {
    $notification->push(_("Domain not deleted."), 'horde.message');
    Horde::url('domains/index.php', true)->redirect();
}

if ($vars->get('submitbutton') == _("Delete")) {
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        try {
            $delete = $vilma->driver->deleteDomain($info['domain_id']);
            $notification->push(_("Domain deleted."), 'horde.success');
            Horde::url('domains/index.php', true)->redirect();
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error deleting domain. %s."), $e->getMessage()), 'horde.error');
        }
    }
}

/* Render the form. */
$renderer = new Horde_Form_Renderer();

$template = $injector->createInstance('Horde_Template');
Horde::startBuffer();
$form->renderActive($renderer, $vars, Horde::url('domains/delete.php'), 'post');
$template->set('main', Horde::endBuffer());
$template->set('menu', Horde::menu());
Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

$page_output->header();
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
$page_output->footer();
