<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

@define('VILMA_BASE', dirname(__FILE__) . '/..');
require_once VILMA_BASE . '/lib/base.php';
require_once 'Horde/Form.php';

/* Only admin should be using this. */
if (!Horde_Auth::isAdmin()) {
    Horde_Auth::authenticateFailure('vilma', $e);
}

$vars = Horde_Variables::getDefaultVariables();
$virtual_id = $vars->get('virtual_id');
$formname = $vars->get('formname');

$virtual = $vilma_driver->getVirtual($virtual_id);
$domain = Vilma::stripDomain($virtual['virtual_email']);
$domain = $vilma_driver->getDomainByName($domain);

$form = new Horde_Form($vars, _("Delete Virtual Email Address"));

/* Set up the form. */
$form->setButtons(array(_("Delete"), _("Do not delete")));
$form->addHidden('', 'virtual_id', 'text', false);
$form->addVariable(sprintf(_("Delete the virtual email address \"%s\" => \"%s\"?"), $virtual['virtual_email'], $virtual['virtual_destination']), 'description', 'description', false);

if ($vars->get('submitbutton') == _("Delete")) {
    if ($form->validate($vars)) {
        $form->getInfo($vars, $info);
        $delete = $vilma_driver->deleteVirtual($info['virtual_id']);
        if (is_a($delete, 'PEAR_Error')) {
            Horde::logMessage($delete, __FILE__, __LINE__, PEAR_LOG_ERR);
            $notification->push(sprintf(_("Error deleting virtual email. %s."), $delete->getMessage()), 'horde.error');
        } else {
            $notification->push(_("Virtual email deleted."), 'horde.success');
            $url = Horde_Util::addParameter(Horde::applicationUrl('virtuals/index.php'), 'domain_id', $domain['domain_id'], false);
            header('Location: ' . $url);
            exit;
        }
    }
} elseif ($vars->get('submitbutton') == _("Do not delete")) {
    $notification->push(_("Virtual email not deleted."), 'horde.message');
    $url = Horde_Util::addParameter(Horde::applicationUrl('virtuals/index.php'), 'domain_id', $domain['domain_id'], false);
    header('Location: ' . $url);
    exit;
}

/* Render the form. */
require_once 'Horde/Form/Renderer.php';
$renderer = &new Horde_Form_Renderer();
$main = Horde_Util::bufferOutput(array($form, 'renderActive'), $renderer, $vars, 'delete.php', 'post');

$template->set('main', $main);
$template->set('menu', Vilma::getMenu('string'));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require VILMA_TEMPLATES . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
