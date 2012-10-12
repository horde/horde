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

$title = _("Birthday");

$count = $folks_driver->countUsers(array('birthday' => date('-m-d')));
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
$criteria = array('birthday' => date('-m-d'), 'sort_by' => $sort_by, 'sort_dir'  => $sort_dir);
$users = $folks_driver->getUsers($criteria, $page * $perpage, $perpage);
if ($users instanceof PEAR_Error) {
    $notification->push($users);
    $users = array();
}

$vars = Horde_Variables::getDefaultVariables();
$pager = new Horde_Core_Ui_Pager('page',
                            $vars, array('num' => $count,
                                         'url' => 'birthday.php',
                                         'perpage' => $perpage));

$pager->preserve($criteria);
$list_url = Folks::getUrlFor('list', 'birthday');

$page_output->addScriptFile('stripe.js', 'horde');
$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
require FOLKS_TEMPLATES . '/list/list.php';
$page_output->footer();
