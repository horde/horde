<?php
/**
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma');

/* Only admin should be using this. */
if (!$registry->isAdmin()) {
    $registry->authenticateFailure('vilma');
}

$vars = Horde_Variables::getDefaultVariables();
$virtual_id = $vars->virtual_id;
$formname = $vars->formname;

$virtual = $vilma->driver->getVirtual($virtual_id);
$domain = Vilma::stripDomain($virtual['virtual_email']);
$domain = $vilma->driver->getDomainByName($domain);

if ($vars->submitbutton == _("Do not delete")) {
    $notification->push(_("Virtual email not deleted."), 'horde.message');
    Horde::url('virtuals/index.php')
        ->add('domain_id', $domain['domain_id'])
        ->redirect();
}

$form = new Horde_Form($vars, _("Delete Virtual Email Address"));

/* Set up the form. */
$form->setButtons(array(_("Delete"), _("Do not delete")));
$form->addHidden('', 'virtual_id', 'text', false);
$form->addVariable(sprintf(_("Delete the virtual email address \"%s\" => \"%s\"?"), $virtual['virtual_email'], $virtual['virtual_destination']), 'description', 'description', false);

if ($vars->submitbutton == _("Delete") &&
    $form->validate($vars)) {
    $form->getInfo($vars, $info);
    try {
        $delete = $vilma->driver->deleteVirtual($info['virtual_id']);
        $notification->push(_("Virtual email deleted."), 'horde.success');
        Horde::url('virtuals/index.php', true)
            ->add('domain_id', $domain['domain_id'])
            ->redirect();
    } catch (Exception $e) {
        Horde::logMessage($e);
        $notification->push(sprintf(_("Error deleting virtual email. %s."), $e->getMessage()), 'horde.error');
    }
}

/* Render the form. */
$renderer = new Horde_Form_Renderer();

$template = $injector->createInstance('Horde_Template');

Horde::startBuffer();
$form->renderActive($renderer, $vars, 'delete.php', 'post');
$template->set('main', Horde::endBuffer());
$template->set('menu', Horde::menu());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
