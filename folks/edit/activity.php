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

define('FOLKS_BASE', __DIR__ . '/..');
require_once FOLKS_BASE . '/lib/base.php';
require_once FOLKS_BASE . '/lib/Forms/Activity.php';
require_once 'tabs.php';

$title = _("Activity");

$activity_scope = Horde_Util::getGet('activity_scope');
$activity_date = Horde_Util::getGet('activity_date');
if ($activity_scope && $activity_date) {
    $result = $folks_driver->deleteActivity($activity_scope, $activity_date);
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    } else {
        $notification->push(_("Activity successfully deleted"), 'horde.success');
        Horde::url('edit/activity.php')->redirect();
    }
}

$form = new Folks_Activity_Form($vars, _("What are you doing right now?"), 'long');
if ($form->validate()) {
    $result = $form->execute();
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    } else {
        $notification->push(_("Activity successfully posted"), 'horde.success');
        Horde::url('edit/activity.php')->redirect();
    }
}

$activities = $folks_driver->getActivity($GLOBALS['registry']->getAuth());
if ($activities instanceof PEAR_Error) {
    $notification->push($activities);
    Folks::getUrlFor('list', 'list')->redirect();
}

$delete_url = Horde::url('edit/activity.php');
$delete_img = Horde::img('delete.png');

$page_output->addScriptFile('tables.js', 'horde');
$page_output->header(array(
    'title' => $title
));
require FOLKS_TEMPLATES . '/menu.inc';

echo $tabs->render('activity');
require FOLKS_TEMPLATES . '/edit/activity.php';

$page_output->footer();
