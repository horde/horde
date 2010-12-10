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
if (!Vilma::hasPermission($domain)) {
    $registry->authenticateFailure('vilma');
}

$vars = Horde_Variables::getDefaultVariables();
try {
    $form = new Vilma_Form_EditDomain($vars);
} catch (Exception $e) {
    $notification->push($e);
    Horde::url('domains/index.php', true)->redirect();
}

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $info['name'] = Horde_String::lower($info['name']);
    try {
        $domain_id = $vilma->driver->saveDomain($info);
        $notification->push(_("Domain saved."), 'horde.success');
        Horde::url('domains/index.php', true)->redirect();
    } catch (Exception $e) {
        $notification->push(sprintf(_("Error saving domain: %s."), $e->getMessage()), 'horde.error');
    }
}

/* Render the form. */
$renderer = new Horde_Form_Renderer();

$template = $injector->createInstance('Horde_Template');
Horde::startBuffer();
$form->renderActive($renderer, $vars, 'edit.php', 'post');
$template->set('main', Horde::endBuffer());
$template->set('menu', Horde::menu());
Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/main/main.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
