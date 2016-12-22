<?php
/**
 * Copyright 2003-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @author Ben Klang <ben@alkaloid.net>
 * @author David Cummings <davidcummings@acm.org>
 */

require_once __DIR__ . '/../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma');

/* Only admin should be using this. */
if (!Vilma::hasPermission($curdomain)) {
    throw new Horde_Exception_AuthenticationFailure();
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
    $address['del_url'] = $url = Horde::url('users/delete.php')
        ->add(array('address' => $id, 'section' => $section));

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
$view = $injector->createInstance('Horde_View');
$view->addresses = $addresses;
if (!$vilma->driver->isBelowMaxUsers($curdomain['domain_name'])) {
    $view->maxusers = _("Maximum Users");
}
$view->tabs = $tabs->render();
$view->pager = $pager->render();

/* Set up the field list. */
$view->images = array(
    'delete' => Horde::img('delete.png', _("Delete User")),
    'edit' => Horde::img('edit.png', _("Edit User"))
);

/* Render the page. */
$page_output->header();
$notification->notify(array('listeners' => 'status'));
echo $view->render('users/index');
$page_output->footer();
