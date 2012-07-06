<?php
/**
 * Smartmobile portal page.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */

require_once __DIR__ . '/../../lib/Application.php';
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

$notification->notify(array('listeners' => 'status'));

$page_output->header(array(
    'title' => _("Welcome"),
    'view' => $registry::VIEW_SMARTMOBILE
));
require HORDE_TEMPLATES . '/portal/smartmobile.inc';
$page_output->footer();
