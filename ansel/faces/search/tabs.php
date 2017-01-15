<?php
/**
 * Process an single photo (to be called by ajax)
 *
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('ansel');

$faces = $GLOBALS['injector']->getInstance('Ansel_Faces');

/* Show tabs */
$vars = Horde_Variables::getDefaultVariables();
$tabs = new Horde_Core_Ui_Tabs(null, $vars);
$tabs->addTab(_("All faces"), Horde::url('faces/search/all.php'));
$tabs->addTab(_("From my galleries"), Horde::url('faces/search/owner.php'));
$tabs->addTab(_("Named faces"), Horde::url('faces/search/named.php'));
$tabs->addTab(_("Search by name"), Horde::url('faces/search/name.php'));
if ($conf['faces']['search']) {
    $tabs->addTab(_("Search by photo"), Horde::url('faces/search/image.php'));
}
