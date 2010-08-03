<?php
/**
 * $Id: activity.php 975 2008-10-07 20:33:50Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

define('FOLKS_BASE', dirname(__FILE__) . '/..');
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
        Horde::applicationUrl('edit/activity.php')->redirect();
    }
}

$form = new Folks_Activity_Form($vars, _("What are you doing right now?"), 'long');
if ($form->validate()) {
    $result = $form->execute();
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    } else {
        $notification->push(_("Activity successfully posted"), 'horde.success');
        Horde::applicationUrl('edit/activity.php')->redirect();
    }
}

$activities = $folks_driver->getActivity($GLOBALS['registry']->getAuth());
if ($activities instanceof PEAR_Error) {
    $notification->push($activities);
    Folks::getUrlFor('list', 'list')->redirect();
}

$delete_url = Horde::applicationUrl('edit/activity.php');
$delete_img = Horde::img('delete.png');

Horde::addScriptFile('tables.js', 'horde');
require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

echo $tabs->render('activity');
require FOLKS_TEMPLATES . '/edit/activity.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';
