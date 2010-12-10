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
if (!$registry->isAdmin() && !Vilma::isDomainAdmin()) {
    $registry->authenticateFailure('vilma');
}

$user = Horde_Util::getFormData('user');
try {
    if (!empty($user)) {
        $virtuals = $vilma->driver->getVirtuals($user);
        $domain = Vilma::stripDomain($user);
    } else {
        $domain = Vilma::getDomain();
        $virtuals = $vilma->driver->getVirtuals($domain);
    }
} catch (Exception $e) {
    $notification->push($e);
    Horde::url('index.php', true)->redirect();
}

foreach ($virtuals as $id => $virtual) {
    $virtuals[$id]['edit_url'] = Horde::url('virtuals/edit.php')
        ->add('virtual_id', $virtual['virtual_id']);
    $virtuals[$id]['del_url'] = Horde::url('virtuals/delete.php')
        ->add('virtual_id', $virtual['virtual_id']);
}

$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set('virtuals', $virtuals, true);

/* Set up the template action links. */
$actions = array();
$url = Horde::url('virtuals/edit.php');
if (!Vilma::isDomainAdmin()) {
    $url->add('domain', $domain);
}
$actions['new_url'] = $url;
$actions['new_text'] = _("New Virtual Email");
$url = Horde::url('users/index.php');
if (!Vilma::isDomainAdmin()) {
    $url->add('domain', $domain);
}
$actions['users_url'] = $url;
$actions['users_text'] = _("Users");
$template->set('actions', $actions);

/* Set up the field list. */
$images = array('delete' => Horde::img('delete.png', _("Delete User")),
                'edit' => Horde::img('edit.png', _("Edit User")));
$template->set('images', $images);

$template->set('menu', Horde::menu());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

/* Render the page. */
require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/virtuals/index.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
