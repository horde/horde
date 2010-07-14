<?php
/**
 * $Id: tabs.php 974 2008-10-07 19:46:00Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

if (!$registry->isAuthenticated()) {
    $registry->authenticateFailure('folks');
}

$vars = Horde_Variables::getDefaultVariables();
$tabs = new Horde_Core_Ui_Tabs('what', $vars);
$tabs->addTab(_("Edit my profile"), Horde::applicationUrl('edit/edit.php'), 'edit');
$tabs->addTab(_("Privacy"), Horde::applicationUrl('edit/privacy.php'), 'privacy');
$tabs->addTab(_("Blacklist"), Horde::applicationUrl('edit/friends/blacklist.php'), 'blacklist');
$tabs->addTab(_("Friends"),  Horde::applicationUrl('edit/friends/index.php'), 'friends');
$tabs->addTab(_("Groups"),  Horde::applicationUrl('edit/friends/groups.php'), 'groups');
$tabs->addTab(_("Activity"),  Horde::applicationUrl('edit/activity.php'), 'activity');
$tabs->addTab(_("Password"), Horde::applicationUrl('edit/password.php'), 'password');

if ($conf['comments']['allow'] != 'never'
        && $registry->hasMethod('forums/doComments')) {
    $tabs->addTab(_("Comments"), Horde::applicationUrl('edit/comments.php'), 'comments');
}

if ($conf['facebook']['enabled']) {
    $tabs->addTab(_("Facebook"), Horde::applicationUrl('edit/facebook.php'), 'facebook');
}


Horde::addScriptFile('tables.js', 'horde');
