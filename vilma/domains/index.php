<?php
/**
 * Copyright 2003-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author Marko Djukic <marko@oblo.com>
 */

require_once __DIR__ . '/../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma');

/* Only admin should be using this. */
if (!Vilma::hasPermission()) {
    throw new Horde_Exception_AuthenticationFailure();
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
    $domain['edit_url'] = $editurl->copy()
        ->add('domain_id', $domain['domain_id']);
    $domain['del_url']  = $deleteurl->copy()
        ->add('domain_id', $domain['domain_id']);
    $domain['view_url'] = $userurl->copy()
        ->add('domain_id', $domain['domain_id']);
}

/* Set up the template fields. */
$view = $injector->createInstance('Horde_View');
$view->domains = $domains;

/* Set up the field list. */
$view->images = array(
    'delete' => Horde::img('delete.png', _("Delete Domain")),
    'edit' => Horde::img('edit.png', _("Edit Domain"))
);

/* Render the page. */
$page_output->header();
$notification->notify(array('listeners' => 'status'));
echo $view->render('domains/index');
$page_output->footer();
