<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @author Ben Klang <ben@alkaloid.net>
 * @author David Cummings <davidcummings@acm.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma');

/* Only admin should be using this. */
if (!Vilma::hasPermission($curdomain)) {
    $registry->authenticateFailure('vilma');
}

// Input validation: make sure we have a valid section.
$vars = Horde_Variables::getDefaultVariables();
$section = $vars->section;
$types = Vilma::getUserMgrTypes();
if (!isset($types[$section])) {
    $vars->section = $section = 'all';
}
$tabs = Vilma::getUserMgrTabs($vars);

try {
    $addresses = $vilma->driver->getAddresses($curdomain['domain_name'], $section);
} catch (Exception $e) {
    $notification->push($e);
    Horde::url('index.php', true)->redirect();
}

// Page results
$perpage = $prefs->getValue('addresses_perpage');
$url = Horde::url('users/index.php')->add('section', $section);
$pager = new Horde_Core_Ui_Pager('page',
                                 $vars,
                                 array('num' => count($addresses),
                                       'url' => $url,
                                       'page_count' => 10,
                                       'perpage' => $perpage));
$addresses = array_slice($addresses, $vars->page * $perpage, $perpage);

foreach ($addresses as &$address) {
    $type = $address['type'];
    $id = $address['id'];

    switch ($type) {
    case 'alias':
        $address['edit_url'] = Horde::url('users/editAlias.php')
            ->add(array('alias' => $id, 'section' => $section));
        $address['add_alias_url'] = $address['add_forward_url'] = false;
        break;
    case 'forward':
        $address['edit_url'] = Horde::url('users/editForward.php')
            ->add(array('forward' => $id, 'section' => $section));
        $address['add_alias_url'] = $address['add_forward_url'] = false;
        break;
    default:
        $params = array('address' => $id, 'section' => $section);
        $address['edit_url'] = Horde::url('users/edit.php')
            ->add($params);
        $address['add_alias_url'] = Horde::url('users/editAlias.php')
            ->add($params);
        $address['add_forward_url'] = Horde::url('users/editForward.php')
            ->add($params);
        break;
    }
    $currentAddress = $address['address'];
    if (empty($currentAddress)) {
        $currentAddress = $address['user_name'] . $address['domain'];
    }
    $address['del_url'] = $url = Horde::url('users/delete.php')
        ->add(array('address' => $currentAddress, 'section' => $section));

    switch ($type) {
    case 'alias':
        $address['view_url'] = Horde::url('users/editAlias.php')
            ->add(array('alias' => $id, 'section' => $section));
        break;
    case 'forward':
        $address['view_url'] = Horde::url('users/editForward.php')
            ->add(array('forward' => $id, 'section' => $section));
        break;
    default:
        $address['view_url'] = Horde::url('users/edit.php')
            ->add(array('address' => $id, 'section' => $section));
        break;
    }
    $address['type'] = $types[$address['type']]['singular'];
    $address['status'] = $vilma->driver->getUserStatus($address);
}

/* Set up the template fields. */
$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set('addresses', $addresses);
if (!$vilma->driver->isBelowMaxUsers($curdomain['domain_name'])) {
    $template->set('maxusers', _("Maximum Users"));
}
$template->set('menu', Horde::menu());
$template->set('tabs', $tabs->render());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

$template->set('pager', $pager->render());

/* Set up the field list. */
$images = array('delete' => Horde::img('delete.png', _("Delete User")),
                'edit' => Horde::img('edit.png', _("Edit User")));
$template->set('images', $images);

/* Render the page. */
require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/users/index.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
