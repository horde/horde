<?php
/**
 * Copyright 2002-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('trean');

/* Get bookmarks to display. */
$bookmarks = $trean_gateway->listBookmarks($prefs->getValue('sortby'), $prefs->getValue('sortdir'), 0, 100);

Horde::addScriptFile('tables.js', 'horde', true);
Horde::addScriptFile('effects.js', 'horde', true);
$title = _("Browse");
require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
require TREAN_TEMPLATES . '/browse.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
