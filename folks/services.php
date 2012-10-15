<?php
/**
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once __DIR__ . '/lib/base.php';

$title = _("Available services");

foreach ($registry->listApps() as $app) {
    if (!in_array($app, $conf['services']['ignore'])) {
        $apps[$app] = $registry->get('name', $app);
    }
}
asort($apps);

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
require FOLKS_TEMPLATES . '/services/services.php';
$page_output->footer();
