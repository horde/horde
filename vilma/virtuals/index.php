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
    $registry->authenticateFailure('vilma', $e);
}

$user = Horde_Util::getFormData('user');
if (!empty($user)) {
    $virtuals = $vilma->driver->getVirtuals($user);
    $domain = Vilma::stripDomain($user);
} else {
    $domain = Vilma::getDomain();
    $virtuals = $vilma->driver->getVirtuals($domain);
}

if (is_a($virtuals, 'PEAR_Error')) {
    $notification->push($virtuals);
    Horde::url('index.php')->redirect();
}

foreach ($virtuals as $id => $virtual) {
    $url = Horde::url('virtuals/edit.php');
    $virtuals[$id]['edit_url'] = Horde_Util::addParameter($url, 'virtual_id', $virtual['virtual_id']);
    $url = Horde::url('virtuals/delete.php');
    $virtuals[$id]['del_url'] = Horde_Util::addParameter($url, 'virtual_id', $virtual['virtual_id']);
}

$template->set('virtuals', $virtuals, true);

/* Set up the template action links. */
$actions = array();
$url = Horde::url('virtuals/edit.php');
$actions['new_url'] = (Vilma::isDomainAdmin() ? $url : Horde_Util::addParameter($url, 'domain', $domain));
$actions['new_text'] = _("New Virtual Email");
$url = Horde::url('users/index.php');
$actions['users_url'] = (Vilma::isDomainAdmin() ? $url : Horde_Util::addParameter($url, 'domain', $domain));
$actions['users_text'] = _("Users");
$template->set('actions', $actions);

/* Set up the field list. */
$images = array('delete' => Horde::img('delete.png', _("Delete User")),
                'edit' => Horde::img('edit.png', _("Edit User")));
$template->set('images', $images);

$template->set('menu', Vilma::getMenu('string'));

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

/* Render the page. */
require VILMA_TEMPLATES . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/virtuals/index.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
