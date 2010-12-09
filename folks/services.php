<?php
/**
 * $Id: services.php 1019 2008-10-31 08:18:10Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once dirname(__FILE__) . '/lib/base.php';

$title = _("Available services");

foreach ($registry->listApps() as $app) {
    if (!in_array($app, $conf['services']['ignore'])) {
        $apps[$app] = $registry->get('name', $app);
    }
}
asort($apps);

require $registry->get('templates', 'horde') . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';
require FOLKS_TEMPLATES . '/services/services.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';