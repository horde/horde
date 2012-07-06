<?php
/**
 * Example list script.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Your Name <you@example.com>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('skeleton');

$page_output->header(array(
    'title' => _("List")
));

echo Horde::menu();
$notification->notify(array('listeners' => 'status'));

$page_output->footer();
