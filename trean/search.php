<?php
/**
 * $Horde: trean/search.php,v 1.32 2009/06/10 17:33:43 slusarz Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('trean');

require_once TREAN_BASE . '/lib/Forms/Search.php';
require_once TREAN_BASE . '/lib/Views/BookmarkList.php';

$title = _("Search");
require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));

// Set up the search form.
$vars = Horde_Variables::getDefaultVariables();
$form = new SearchForm($vars);

// Render the search form.
$form->renderActive(new Horde_Form_Renderer(), $vars, Horde::selfUrl(), 'post');
echo '<br />';

if ($form->validate($vars)) {
    // Create the filter.
    $combine = Horde_Util::getFormData('combine', 'OR');
    $op = Horde_Util::getFormData('op', 'LIKE');
    $criteria = array();

    // Searching for URL?
    if (strlen($u = Horde_Util::getFormData('url'))) {
        $criteria[] = array('url', $op, $u);
    }

    // Searching title?
    if (strlen($t = Horde_Util::getFormData('title'))) {
        $criteria[] = array('title', $op, $t);
    }

    // Searching description?
    if (strlen($d = Horde_Util::getFormData('description'))) {
        $criteria[] = array('description', $op, $d);
    }

    if ($criteria) {
        // Get the bookmarks.
        $bookmarks = $trean_shares->searchBookmarks($criteria, $combine);
        $search_title = sprintf(_("Search Results (%s)"), count($bookmarks));

        // Display the results.
        require TREAN_TEMPLATES . '/search.php';
    }
}

require_once $registry->get('templates', 'horde') . '/common-footer.inc';
