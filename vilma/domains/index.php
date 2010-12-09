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
if (!Vilma::hasPermission()) {
    $registry->authenticateFailure('vilma');
}

// Having a current domain doesn't make sense on this page
Vilma::setCurDomain(false);

$domains = $vilma->driver->getDomains();
if (is_a($domains, 'PEAR_Error')) {
    $notification->push($domains, 'horde.error');
    $domains = array();
}
foreach ($domains as $id => $domain) {
    $url = Horde::url('domains/edit.php');
    $domains[$id]['edit_url'] = Horde_Util::addParameter($url, 'domain_id', $domain['domain_id']);
    $url = Horde::url('domains/delete.php');
    $domains[$id]['del_url'] = Horde_Util::addParameter($url, 'domain_id', $domain['domain_id']);
    $url = Horde::url('users/index.php');
    $domains[$id]['view_url'] = Horde_Util::addParameter($url, 'domain_id', $domain['domain_id']);
}

/* Set up the template fields. */
$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set('domains', $domains, true);
$template->set('menu', Horde::menu());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

/* Set up the field list. */
$images = array('delete' => Horde::img('delete.png', _("Delete Domain")),
                'edit' => Horde::img('edit.png', _("Edit Domain")));
$template->set('images', $images);

/* Render the page. */
require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/domains/index.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
