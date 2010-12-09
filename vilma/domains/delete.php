<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma');

require_once VILMA_BASE . '/lib/Forms/DeleteDomainForm.php';

/* Only admin should be using this. */
if (!Vilma::hasPermission($domain)) {
    $registry->authenticateFailure('vilma', $e);
}

$vars = Horde_Variables::getDefaultVariables();
$form = new DeleteDomainForm($vars);

if ($vars->get('submitbutton') == _("Delete")) {
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        $delete = $vilma->driver->deleteDomain($info['domain_id']);
        if (is_a($delete, 'PEAR_Error')) {
            Horde::logMessage($delete, 'ERR');
            $notification->push(sprintf(_("Error deleting domain. %s."), $delete->getMessage()), 'horde.error');
        } else {
            $notification->push(_("Domain deleted."), 'horde.success');
            Horde::url('domains/index.php', true)->redirect();
        }
    }
} elseif ($vars->get('submitbutton') == _("Do not delete")) {
    $notification->push(_("Domain not deleted."), 'horde.message');
    Horde::url('domains/index.php', true)->redirect();
}

/* Render the form. */
require_once 'Horde/Form/Renderer.php';
$renderer = new Horde_Form_Renderer();

Horde::startBuffer();
$form->renderActive($renderer, $vars, 'delete.php', 'post');
$main = Horde::endBuffer();

$template = $injector->createInstance('Horde_Template');
$template->set('main', $main);
$template->set('menu', Vilma::getMenu('string'));

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
