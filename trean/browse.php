<?php
/**
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('trean');

/* Get bookmarks to display. */
$bookmarks = $trean_gateway->listBookmarks($prefs->getValue('sortby'),
                                           $prefs->getValue('sortdir'),
                                           0, 100);
if (!count($bookmarks)) {
    $notification->push(_("No bookmarks yet."), 'horde.message');
    require __DIR__ . '/add.php';
    exit;
}

$view = new Trean_View_BookmarkList($bookmarks);
$page_output = $injector->getInstance('Horde_PageOutput');
$page_output->addScriptFile('tables.js', 'horde');
$page_output->addScriptFile('effects.js', 'horde');
$title = _("Browse");
require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
echo $view->render();
require $registry->get('templates', 'horde') . '/common-footer.inc';
