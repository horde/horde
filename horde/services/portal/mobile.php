<?php
/**
 * Mobile portal page.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

$identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();
$fullname = $identity->getValue('fullname');
if (empty($fullname)) {
    $fullname = $registry->getAuth();
}

$links = array();
foreach ($registry->listApps() as $app) {
    if ($app != 'horde') {
        $links[htmlspecialchars($registry->get('name', $app))] = array(Horde::url('', true, array('app' => $app)), $registry->get('icon', $app));
    }
}

$title = _("Welcome");

require HORDE_TEMPLATES . '/common-header-mobile.inc';
require HORDE_TEMPLATES . '/portal/mobile.inc';
require HORDE_TEMPLATES . '/common-footer-mobile.inc';
