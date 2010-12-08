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

/* Only admin should be using this. */
if (!$registry->isAdmin()) {
    $registry->authenticateFailure('vilma', $e);
}

$vars = Horde_Variables::getDefaultVariables();
$virtual_id = $vars->get('virtual_id');
$formname = $vars->get('formname');

$virtual = $vilma->driver->getVirtual($virtual_id);
$domain = Vilma::stripDomain($virtual['virtual_email']);
$domain = $vilma->driver->getDomainByName($domain);

$form = new Horde_Form($vars, _("Delete Virtual Email Address"));

/* Set up the form. */
$form->setButtons(array(_("Delete"), _("Do not delete")));
$form->addHidden('', 'virtual_id', 'text', false);
$form->addVariable(sprintf(_("Delete the virtual email address \"%s\" => \"%s\"?"), $virtual['virtual_email'], $virtual['virtual_destination']), 'description', 'description', false);

if ($vars->get('submitbutton') == _("Delete")) {
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        $delete = $vilma->driver->deleteVirtual($info['virtual_id']);
        if (is_a($delete, 'PEAR_Error')) {
            Horde::logMessage($delete, 'ERR');
            $notification->push(sprintf(_("Error deleting virtual email. %s."), $delete->getMessage()), 'horde.error');
        } else {
            $notification->push(_("Virtual email deleted."), 'horde.success');
            Horde::url('virtuals/index.php')
                ->add('domain_id', $domain['domain_id'])
                ->redirect();
        }
    }
} elseif ($vars->get('submitbutton') == _("Do not delete")) {
    $notification->push(_("Virtual email not deleted."), 'horde.message');
    Horde::url('virtuals/index.php')
        ->add('domain_id', $domain['domain_id'])
        ->redirect();
}

/* Render the form. */
require_once 'Horde/Form/Renderer.php';
$renderer = &new Horde_Form_Renderer();

Horde::startBuffer();
$form->renderActive($renderer, $vars, 'delete.php', 'post');
$main = Horde::endBuffer();

$template = $injector->createInstance('Horde_Template');
$template->set('main', $main);
$template->set('menu', Vilma::getMenu('string'));

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require VILMA_TEMPLATES . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
