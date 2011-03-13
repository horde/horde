<?php
/**
 * $Horde: trean/browse.php,v 1.73 2009-11-29 15:51:42 chuck Exp $
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

require_once TREAN_BASE . '/lib/Views/BookmarkList.php';

/* Get bookmarks to display. */
//$bookmarks = $folder->listBookmarks($prefs->getValue('sortby'), $prefs->getValue('sortdir'));
$bookmarks = array();

Horde::addScriptFile('tables.js', 'horde', true);
Horde::addScriptFile('effects.js', 'horde', true);
Horde::addScriptFile('redbox.js', 'horde', true);
$title = _("Browse");
require $registry->get('templates', 'horde') . '/common-header.inc';
if (!Horde_Util::getFormData('popup')) {
    echo Horde::menu();
    $notification->notify(array('listeners' => 'status'));
}
require TREAN_TEMPLATES . '/browse.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
