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

// Set up the search form.
$vars = Horde_Variables::getDefaultVariables();
$form = new Trean_Form_Search($vars);

$bookmarks = null;
if ($form->validate($vars)) {
    $q = Horde_Util::getFormData('q');
    if ($q) {
        // Get the bookmarks.
        try {
            $bookmarks = $trean_gateway->searchBookmarks($q);
        } catch (Trean_Exception $e) {
            $notification->push($e);
        }
    }
}

Trean::addFeedLink();

$page_output->header(array(
    'title' => _("Search")
));
$notification->notify(array('listeners' => 'status'));

// Render the search form.
$form->renderActive(new Horde_Form_Renderer(), $vars, Horde::selfUrl(), 'post');
echo '<br />';

// Display the results.
if (!$bookmarks) {
    echo '<p><em>' . _("No Bookmarks found") . '</em></p>';
} else {
    $view = new Trean_View_BookmarkList($bookmarks);
    $view->showTagBrowser(false);
    echo $view->render(sprintf(_("Search Results (%s)"), count($bookmarks)));
}

$page_output->footer();
