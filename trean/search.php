<?php
/**
 *
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('trean');

$vars = Horde_Variables::getDefaultVariables();

$bookmarks = null;
if (strlen($vars->searchfield)) {
    // Get the bookmarks.
    try {
        $bookmarks = $trean_gateway->searchBookmarks($vars->searchfield);
    } catch (Trean_Exception $e) {
        $notification->push($e);
    }
}

if ($GLOBALS['conf']['content_index']['enabled']) {
    $topbar = $GLOBALS['injector']->getInstance('Horde_View_Topbar');
    $topbar->search = true;
    $topbar->searchAction = Horde::url('search.php');
}

Trean::addFeedLink();

$page_output->header(array(
    'title' => _("Search")
));
$notification->notify(array('listeners' => 'status'));

// Display the results.
if (strlen($vars->searchfield)) {
    if (!$bookmarks) {
        echo '<p><em>' . _("No bookmarks found") . '</em></p>';
    } else {
        $view = new Trean_View_BookmarkList($bookmarks);
        $view->showTagBrowser(false);
        echo $view->render(sprintf(_("Search results (%s)"), count($bookmarks)));
    }
} else {
    echo '<p><em>' . _("No search") . '</em></p>';
}

$page_output->footer();
