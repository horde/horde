<?php
/**
 * $Id: tabs.php 22 2007-12-13 11:10:52Z duck $
 *
 * Copyright 2007 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */

/* Only admin should be using this. */
if (!Auth::isAdmin('news:admin')) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

require_once 'Horde/Variables.php';
require_once 'Horde/UI/Tabs.php';
require_once 'Horde/Form.php';

$vars = Variables::getDefaultVariables();
$tabs = new Horde_UI_Tabs('admin', $vars);

$tabs->addTab(_("Sources"), Horde::applicationUrl('admin/sources/index.php'), 'sources');
$tabs->addTab(_("Categories"), Horde::applicationUrl('admin/categories/index.php'), 'categories');
