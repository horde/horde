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
Vilma::setCurDomain();

try {
    $domains = $vilma->driver->getDomains();
} catch (Exception $e) {
    $notification->push($e, 'horde.error');
    $domains = array();
}

$editurl   = Horde::url('domains/edit.php');
$deleteurl = Horde::url('domains/delete.php');
$userurl   = Horde::url('users/index.php');
foreach ($domains as &$domain) {
    $domain['edit_url'] = $editurl->copy()->add('domain_id', $domain['domain_id']);
    $domain['del_url']  = $deleteurl->copy()->add('domain_id', $domain['domain_id']);
    $domain['view_url'] = $userurl->copy()->add('domain_id', $domain['domain_id']);
}

/* Set up the template fields. */
$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set('domains', $domains);
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
