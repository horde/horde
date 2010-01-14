<?php
/**
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

@define('CRUMB_BASE', dirname(__FILE__));
require_once CRUMB_BASE . '/lib/base.php';

$clients = $crumb_driver->listClients();

$title = _("List");

require CRUMB_TEMPLATES . '/common-header.inc';
require CRUMB_TEMPLATES . '/menu.inc';
print_r($clients);
require $registry->get('templates', 'horde') . '/common-footer.inc';
