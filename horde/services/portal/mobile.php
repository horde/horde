<?php
/**
 * Mobile portal page.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

$identity = $injector->getInstance('Horde_Prefs_Identity')->getOb();
$fullname = $identity->getValue('fullname');
if (empty($fullname)) {
    $fullname = Horde_Auth::getAuth();
}

$links = array();
foreach ($registry->listApps() as $app) {
    if ($registry->hasMobileView($app)) {
        $links[htmlspecialchars($registry->get('name', $app))] = Horde::url($registry->get('webroot', $app) . '/');
    }
}

$title = _("Welcome");

require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/portal/mobile.inc';
require HORDE_TEMPLATES . '/common-footer.inc';
