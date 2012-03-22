<?php
/**
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once __DIR__ . '/lib/base.php';

$title = _("Popularity");

$count = $folks_driver->countUsers();
if ($count instanceof PEAR_Error) {
    $notification->push($count);
    $count = 0;
}

$page = Horde_Util::getGet('page', 0);
$perpage = $prefs->getValue('per_page');
$criteria = array('sort_by' => 'popularity', 'sort_dir'  => 0);
$users = $folks_driver->getUsers($criteria, $page * $perpage, $perpage);
if ($users instanceof PEAR_Error) {
    $notification->push($users);
    $users = array();
}

$vars = Horde_Variables::getDefaultVariables();
$pager = new Horde_Core_Ui_Pager('page',
                            $vars, array('num' => $count,
                                         'url' => 'popularity.php',
                                         'perpage' => $perpage));

$pager->preserve($criteria);
$list_url = Folks::getUrlFor('list', 'popularity');

$injector->getInstance('Horde_PageOutput')->addScriptFile('stripe.js', 'horde');
require $registry->get('templates', 'horde') . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';
require FOLKS_TEMPLATES . '/list/list.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
