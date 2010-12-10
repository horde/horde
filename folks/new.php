<?php
/**
 * $Id: new.php 1019 2008-10-31 08:18:10Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once dirname(__FILE__) . '/lib/base.php';

$title = _("New");

$count = $folks_driver->countUsers();
if ($count instanceof PEAR_Error) {
    $notification->push($count);
    $count = 0;
}

$page = Horde_Util::getGet('page', 0);
$perpage = $prefs->getValue('per_page');
$criteria = array('sort_by' => 'signup_at', 'sort_dir'  => 0);
$users = $folks_driver->getUsers($criteria, $page * $perpage, $perpage);
if ($users instanceof PEAR_Error) {
    $notification->push($users);
    $users = array();
}

$vars = Horde_Variables::getDefaultVariables();
$pager = new Horde_Core_Ui_Pager('page',
                            $vars, array('num' => $count,
                                         'url' => 'new.php',
                                         'perpage' => $perpage));

$pager->preserve($criteria);
$list_url = Folks::getUrlFor('list', 'new');

Horde::addScriptFile('stripe.js', 'horde');

require $registry->get('templates', 'horde') . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';
require FOLKS_TEMPLATES . '/list/list.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';
