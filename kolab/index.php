<?php
/**
 * Kolab index script.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Your Name <you@example.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kolab');

$title = _("Overview");

require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
require $registry->get('templates', 'horde') . '/common-footer.inc';
