<?php
/**
 * Process an single photo (to be called by ajax)
 *
 * $Horde: ansel/faces/search/tabs.php,v 1.10 2009/07/22 21:08:09 mrubinsk Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once dirname(__FILE__) . '/../../lib/base.php';
require_once ANSEL_BASE . '/lib/Faces.php';
require_once 'Horde/UI/Tabs.php';

$faces = Ansel_Faces::factory();
if (is_a($faces, 'PEAR_Error')) {
    die($faces->getMessage());
}

/* Face search is allowed only to authenticated users */
if (!Horde_Auth::isauthenticated()) {
    Horde_Auth::authenticationFailureRedirect();
}

/* Show tabs */
$vars = Horde_Variables::getDefaultVariables();
$tabs = new Horde_UI_Tabs('search_faces', $vars);
$tabs->addTab(_("All faces"), Horde::applicationUrl('faces/search/all.php'), 'all');
$tabs->addTab(_("From my galleries"), Horde::applicationUrl('faces/search/owner.php'), 'owner');
$tabs->addTab(_("Named faces"), Horde::applicationUrl('faces/search/named.php'), 'named');
$tabs->addTab(_("Search by name"), Horde::applicationUrl('faces/search/name.php'), 'name');
if ($conf['faces']['search']) {
    $tabs->addTab(_("Search by photo"), Horde::applicationUrl('faces/search/image.php'), 'image');
}
