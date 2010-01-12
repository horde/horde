<?php
/**
 * $Id: tabs.php 1175 2009-01-19 15:17:06Z duck $
 *
 * Copyright 2007 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */

/* Only admin should be using this. */
if (!Horde_Auth::isAdmin('news:admin')) {
    $notification->push(_("You are not authorised for this action."), 'horde.warning');
    Horde_Auth::authenticateFailure('news');
}

$vars = Horde_Variables::getDefaultVariables();
$tabs = new Horde_Ui_Tabs('admin', $vars);

$tabs->addTab(_("Sources"), Horde::applicationUrl('admin/sources/index.php'), 'sources');
$tabs->addTab(_("Categories"), Horde::applicationUrl('admin/categories/index.php'), 'categories');
