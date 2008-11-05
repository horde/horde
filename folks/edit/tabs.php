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

if (!Auth::isAuthenticated()) {
    Horde::authenticationFailureRedirect();
}

require_once 'Horde/Variables.php';

$vars = Variables::getDefaultVariables();
$tabs = new Horde_UI_Tabs('what', $vars);
$tabs->addTab(_("Edit my profile"), 'edit.php', 'edit');
$tabs->addTab(_("Privacy"), 'privacy.php', 'privacy');
$tabs->addTab(_("Blacklist"), 'blacklist.php', 'blacklist');
$tabs->addTab(_("Friends"), 'friends.php', 'friends');
$tabs->addTab(_("Activity"), 'activity.php', 'activity');
$tabs->addTab(_("Password"), 'password.php', 'password');

if ($conf['comments']['allow'] != 'never'
        && $registry->hasMethod('forums/doComments')) {
    $tabs->addTab(_("Comments"), 'comments.php', 'comments');
}