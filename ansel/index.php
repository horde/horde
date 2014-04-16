<?php
/**
 * Copyright 2001-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ansel');

/* Determine View */
switch ($registry->getView()) {
case Horde_Registry::VIEW_MINIMAL:
case Horde_Registry::VIEW_SMARTMOBILE:
    include ANSEL_BASE . '/smartmobile.php';
    exit;

case Horde_Registry::VIEW_BASIC:
case Horde_Registry::VIEW_DYNAMIC:
    if ($registry->getView() == Horde_Registry::VIEW_DYNAMIC &&
        $prefs->getValue('dynamic_view')) {
        break;
    }
    Ansel::getUrlFor('default_view', array())->redirect();
    exit;
}

/** Load Ajax interface **/
$topbar = $injector->getInstance('Horde_View_Topbar');
//$topbar->search = true;
$injector->getInstance('Ansel_Ajax')->init();
require ANSEL_TEMPLATES . '/dynamic/index.inc';
echo $injector->getInstance('Ansel_View_Sidebar');
$page_output->footer();