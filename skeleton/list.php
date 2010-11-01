<?php
/**
 * Example list script.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Your Name <you@example.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('skeleton');

$title = _("List");

require SKELETON_TEMPLATES . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
require $registry->get('templates', 'horde') . '/common-footer.inc';
