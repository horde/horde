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
    $registry->authenticateFailure('vilma', $e);
}

// Input validation: make sure we have a valid section
$vars = Horde_Variables::getDefaultVariables();
$section = $vars->get('section');
$tmp = Vilma::getUserMgrTypes();
if (!array_key_exists($section, Vilma::getUserMgrTypes())) {
    $section = 'all';
    $vars->set('section', $section);
}
$tabs = Vilma::getUserMgrTabs($vars);

$addresses = $vilma->driver->getAddresses($curdomain['domain_name'], $section);
if (is_a($addresses, 'PEAR_Error')) {
    $notification->push($addresses);
    Horde::url('index.php')->redirect();
}

// Page results
$page = Horde_Util::getGet('page', 0);
$perpage = $prefs->getValue('addresses_perpage');
$url = 'users/index.php';
$url = Horde_Util::addParameter($url, 'section', $section);
$pager = new Horde_Core_Ui_Pager('page',
                            Horde_Variables::getDefaultVariables(),
                            array('num' => count($addresses),
                                  'url' => $url,
                                  'page_count' => 10,
                                  'perpage' => $perpage));
$addresses = array_slice($addresses, $page*$perpage, $perpage);

$types = Vilma::getUserMgrTypes();
foreach ($addresses as $i => $address) {
    $type = $address['type'];
    $id = $address['id'];

    if($type === 'alias') {
        $url = Horde::url('users/editAlias.php');
        $url = Util::addParameter($url, 'alias', $id);
        $url = Util::addParameter($url, 'section', $section);
        $addresses[$i]['edit_url'] = $url;
        $addresses[$i]['add_alias_url'] = false;
        $addresses[$i]['add_forward_url'] = false;
    } elseif($type === 'forward') {
        $url = Horde::url('users/editForward.php');
        $url = Util::addParameter($url, 'forward', $id);
        $url = Util::addParameter($url, 'section', $section);
        $addresses[$i]['edit_url'] = $url;
        $addresses[$i]['add_alias_url'] = false;
        $addresses[$i]['add_forward_url'] = false;
    } else {
        $url = Horde::url('users/edit.php');
        $url = Util::addParameter($url, 'address', $id);
        $url = Util::addParameter($url, 'section', $section);
        $addresses[$i]['edit_url'] = $url;
        $url = Horde::url('users/editAlias.php');
        $url = Util::addParameter($url, 'address', $id);
        $url = Util::addParameter($url, 'section', $section);
        $addresses[$i]['add_alias_url'] = $url;
        $url = Horde::url('users/editForward.php');
        $url = Util::addParameter($url, 'address', $id);
        $url = Util::addParameter($url, 'section', $section);
        $addresses[$i]['add_forward_url'] = $url;
    }
    $url = Horde::url('users/delete.php');
    $currentAddress = $address['address'];
    if(!isset($currentAddress) || empty($currentAddress)) {
        $currentAddress = $address['user_name'] . $address['domain'];
    }
    $url = Horde_Util::addParameter($url, 'address', $currentAddress);
    //$addresses[$i]['del_url'] = Horde_Util::addParameter($url, 'address', $id);
    $addresses[$i]['del_url'] = Horde_Util::addParameter($url, 'section', $section);
    //$url = Horde::url('users/edit.php');
    //$addresses[$i]['view_url'] = Horde_Util::addParameter($url, 'address', $address['user_name']);

    if($type === 'alias') {
        $url = Horde::url('users/editAlias.php');
        $url = Util::addParameter($url, 'alias', $id);
        $url = Util::addParameter($url, 'section', $section);
        $addresses[$i]['view_url'] = $url;
    } elseif ($type === 'forward') {
        $url = Horde::url('users/editForward.php');
        $url = Util::addParameter($url, 'forward', $id);
        $url = Util::addParameter($url, 'section', $section);
        $addresses[$i]['view_url'] = $url;
    } else {
        $url = Horde::url('users/edit.php');
        $url = Util::addParameter($url, 'address', $id);
        $url = Util::addParameter($url, 'section', $section);
        $addresses[$i]['view_url'] = $url;
    }
    $addresses[$i]['type'] = $types[$address['type']]['singular'];
    $addresses[$i]['status'] = $vilma->driver->getUserStatus($address);
}

/* Set up the template action links. */
if ($vilma->driver->isBelowMaxUsers($curdomain['domain_name'])) {
    $url = Horde::url('users/edit.php');
    $maxusers = '';
} else {
    $maxusers = _("Maximum Users");
}

$url = Horde::url('virtuals/edit.php');

/* Set up the template fields. */
$template->set('addresses', $addresses, true);
$template->set('maxusers', $maxusers, true);
$template->set('menu', Vilma::getMenu('string'));
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
require VILMA_TEMPLATES . '/common-header.inc';
echo $template->fetch(VILMA_TEMPLATES . '/users/index.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
