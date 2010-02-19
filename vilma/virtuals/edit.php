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
require_once 'Horde/Form/Action.php';

/* Only admin should be using this. */
if (!Horde_Auth::isAdmin() && !Vilma::isDomainAdmin()) {
    Horde_Auth::authenticateFailure('vilma', $e);
}

$domain = Vilma::getDomain();
$vars = Horde_Variables::getDefaultVariables();
$virtual_id = $vars->get('virtual_id');
$user = $vars->get('user');
$formname = $vars->get('formname');

/* Check if a form is being edited. */
$editing = false;
if ($virtual_id && !$formname) {
    $vars = new Horde_Variables($vilma_driver->getVirtual($virtual_id));
    $editing = true;
}

if (empty($domain)) {
    $domain = Vilma::stripDomain($vars->get('virtual_destination'));
}
$users = $vilma_driver->getUsers($domain);
$user_list = array();
foreach ($users as $user) {
    $virtual_destination = substr($user['user_name'], 0, strpos($user['user_name'], '@'));
    $user_list[$user['user_name']] = $virtual_destination;
}

$form = new Horde_Form($vars, ($editing) ? _("Edit Virtual Email Address") : _("New Virtual Email Address"));

/* Set up the form. */
$form->setButtons(true, true);
$form->addHidden('', 'virtual_id', 'int', false);
$form->addHidden('', 'domain', 'text', false);
$form->addVariable(_("Virtual Email"), 'stripped_email', 'text', true, false, sprintf(_("Enter a virtual email address @%s and then indicate below where mail sent to that address is to be delivered. The address must begin with an alphanumerical character, it must contain only alphanumerical and '._-' characters, and must end with an alphanumerical character."), $domain), array('~^[a-zA-Z0-9]{1,1}[a-zA-Z0-9._-]*[a-zA-Z0-9]$~'));
$var = &$form->addVariable(_("Destination type"), 'destination_type', 'enum',
                           true, false, null,
                           array(array('local' => _("Local user"),
                                       'remote' => _("Remote address"))));
$action = Horde_Form_Action::factory('reload');
$var->setAction($action);
if ($vars->get('destination_type') == 'remote') {
    $form->addVariable(_("Remote e-mail address"), 'virtual_destination',
                       'email', true, false);
} else {
    $form->addVariable(_("Destination"), 'virtual_destination', 'enum',
                       true, false, null, array($user_list, true));
}

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $info['stripped_email'] = Horde_String::lower($info['stripped_email']);
    if ($info['destination_type'] == 'remote') {
        $info['virtual_destination'] = Horde_String::lower($info['virtual_destination']);
    }
    $virtual_id = $vilma_driver->saveVirtual($info, $domain);
    if (is_a($virtual_id, 'PEAR_Error')) {
        Horde::logMessage($virtual_id, 'ERR');
        $notification->push(sprintf(_("Error saving virtual email. %s."), $virtual_id->getMessage()), 'horde.error');
    } else {
        $notification->push(_("Virtual email saved."), 'horde.success');
        $url = Horde::applicationUrl('virtuals/index.php', true);
        header('Location: ' . Horde_Util::addParameter($url, 'user', $info['virtual_destination'], false));
        exit;
    }
}

/* Render the form. */
require_once 'Horde/Form/Renderer.php';
$renderer = new Horde_Form_Renderer();
$main = Horde_Util::bufferOutput(array($form, 'renderActive'), $renderer, $vars, 'edit.php', 'post');

$template->set('main', $main);
$template->set('menu', Vilma::getMenu('string'));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require VILMA_TEMPLATES . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
