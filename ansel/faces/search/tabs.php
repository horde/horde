<?php
/**
 * Process an single photo (to be called by ajax)
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('ansel');

$faces = $GLOBALS['injector']->getInstance('Ansel_Faces');

/* Show tabs */
$vars = Horde_Variables::getDefaultVariables();
$tabs = new Horde_Core_Ui_Tabs('search_faces', $vars);
$tabs->addTab(_("All faces"), Horde::url('faces/search/all.php'), 'all');
$tabs->addTab(_("From my galleries"), Horde::url('faces/search/owner.php'), 'owner');
$tabs->addTab(_("Named faces"), Horde::url('faces/search/named.php'), 'named');
$tabs->addTab(_("Search by name"), Horde::url('faces/search/name.php'), 'name');
if ($conf['faces']['search']) {
    $tabs->addTab(_("Search by photo"), Horde::url('faces/search/image.php'), 'image');
}
