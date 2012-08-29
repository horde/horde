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

if (!$registry->isAuthenticated()) {
    throw new Horde_Exception_AuthenticationFailure();
}

$vars = Horde_Variables::getDefaultVariables();
$tabs = new Horde_Core_Ui_Tabs('what', $vars);
$tabs->addTab(_("Edit my profile"), Horde::url('edit/edit.php'), 'edit');
$tabs->addTab(_("Privacy"), Horde::url('edit/privacy.php'), 'privacy');
$tabs->addTab(_("Blacklist"), Horde::url('edit/friends/blacklist.php'), 'blacklist');
$tabs->addTab(_("Friends"),  Horde::url('edit/friends/index.php'), 'friends');
$tabs->addTab(_("Groups"),  Horde::url('edit/friends/groups.php'), 'groups');
$tabs->addTab(_("Activity"),  Horde::url('edit/activity.php'), 'activity');
$tabs->addTab(_("Password"), Horde::url('edit/password.php'), 'password');

if ($conf['comments']['allow'] != 'never'
        && $registry->hasMethod('forums/doComments')) {
    $tabs->addTab(_("Comments"), Horde::url('edit/comments.php'), 'comments');
}

if ($conf['facebook']['enabled']) {
    $tabs->addTab(_("Facebook"), Horde::url('edit/facebook.php'), 'facebook');
}


$page_output->addScriptFile('tables.js', 'horde');
