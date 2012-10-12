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

$links = $mobile_links = array();
foreach (array_diff($registry->listApps(), array('horde')) as $app) {
    $name = $registry->get('name', $app);
    $tmp = array(
        Horde::url('', true, array('app' => $app)),
        $registry->get('icon', $app)
    );
    if ($registry->hasView($registry::VIEW_SMARTMOBILE, $app)) {
        $mobile_links[$name] = $tmp;
    } else {
        $links[$name] = $tmp;
    }
}
ksort($links, SORT_LOCALE_STRING);
ksort($mobile_links, SORT_LOCALE_STRING);

$notification->notify(array('listeners' => 'status'));

$page_output->header(array(
    'title' => _("Welcome"),
    'view' => $registry::VIEW_SMARTMOBILE
));
require HORDE_TEMPLATES . '/portal/smartmobile.inc';
$page_output->footer();
