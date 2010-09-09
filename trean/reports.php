<?php
/**
 * $Horde: trean/reports.php,v 1.15 2009/06/10 05:25:16 slusarz Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Ben Chavet <ben@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('trean');

require_once TREAN_BASE . '/lib/Views/BookmarkList.php';

$drilldown = Horde_Util::getFormData('drilldown');
$title = _("Reports");
Horde::addScriptFile('stripe.js', 'horde', true);
require TREAN_TEMPLATES . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));

if ($drilldown) {
    $bookmarks = $trean_shares->searchBookmarks(array(array('http_status', 'LIKE', substr($drilldown, 0, 1), array('begin' => true))));
    $search_title = _("HTTP Status") . ' :: ' . sprintf(_("%s Response Codes"), $drilldown) . ' (' . count($bookmarks) . ')';

    /* Display the results. */
    require TREAN_TEMPLATES . '/search.php';
} else {
    require TREAN_TEMPLATES . '/reports.php';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
