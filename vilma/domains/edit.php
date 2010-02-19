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
require_once VILMA_BASE . '/lib/Forms/EditDomainForm.php';

/* Only admin should be using this. */
if (!Vilma::hasPermission($domain)) {
    Horde_Auth::authenticateFailure('vilma', $e);
}

//$domain_id = Horde_Util::getFormData('domain_id');
$vars = Horde_Variables::getDefaultVariables();
$form = new EditDomainForm($vars);

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $info['name'] = Horde_String::lower($info['name']);
    $domain_id = $vilma_driver->saveDomain($info);
    if (is_a($domain_id, 'PEAR_Error')) {
        Horde::logMessage($domain_id, 'ERR');
        $notification->push(sprintf(_("Error saving domain: %s."), $domain_id->getMessage()), 'horde.error');
    } else {
        $notification->push(_("Domain saved."), 'horde.success');
        $url = Horde::applicationUrl('domains/index.php', true);
        header('Location: ' . $url);
        exit;
    }
}

/* Render the form. */
require_once 'Horde/Form/Renderer.php';
$renderer = &new Horde_Form_Renderer();
$main = Horde_Util::bufferOutput(array($form, 'renderActive'), $renderer, $vars, 'edit.php', 'post');

$template->set('main', $main);
$template->set('menu', Vilma::getMenu('string'));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require VILMA_TEMPLATES . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
