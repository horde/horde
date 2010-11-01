<?php
/**
 * $Id: online.php 1019 2008-10-31 08:18:10Z duck $
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

$title = _("Online");

$count = $folks_driver->countUsers(array('online' => true));
if ($count instanceof PEAR_Error) {
    $notification->push($count);
    $count = 0;
}

if (($sort_by = Horde_Util::getFormData('sort_by')) !== null) {
    $prefs->setValue('sort_by', $sort_by);
} else {
    $sort_by = $prefs->getValue('sort_by');
}

if (($sort_dir = Horde_Util::getFormData('sort_dir')) !== null) {
    $prefs->setValue('sort_dir', $sort_dir);
} else {
    $sort_dir = $prefs->getValue('sort_dir');
}

$page = Horde_Util::getGet('page', 0);
$perpage = $prefs->getValue('per_page');
$criteria = array('online' => true, 'sort_by' => $sort_by, 'sort_dir'  => $sort_dir);
$users = $folks_driver->getUsers($criteria, $page * $perpage, $perpage);
if ($users instanceof PEAR_Error) {
    $notification->push($users);
    $users = array();
}

$vars = Horde_Variables::getDefaultVariables();
$pager = new Horde_Core_Ui_Pager('page',
                            $vars, array('num' => $count,
                                         'url' => 'online.php',
                                         'perpage' => $perpage));

$pager->preserve($criteria);
$list_url = Folks::getUrlFor('list', 'online');

Horde::addScriptFile('stripe.js', 'horde');

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

require FOLKS_TEMPLATES . '/list/list.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';
