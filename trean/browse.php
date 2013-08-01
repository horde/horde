<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Trean
 */
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('trean');

/* Get bookmarks to display. */
$view = new Trean_View_BookmarkList();
if (!$view->hasBookmarks()) {
    $notification->push(_("No bookmarks yet."), 'horde.message');
    require __DIR__ . '/add.php';
    exit;
}

if ($GLOBALS['conf']['content_index']['enabled']) {
    $topbar = $GLOBALS['injector']->getInstance('Horde_View_Topbar');
    $topbar->search = true;
    $topbar->searchAction = Horde::url('search.php');
}

Trean::addFeedLink();
$page_output->header(array(
    'title' => _("Browse")
));
$notification->notify(array('listeners' => 'status'));
echo $view->render();
$page_output->footer();
