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
$folderId = Horde_Util::getFormData('f');

/* Default to the current user's default folder or if we are a guest, try to get
 * a list of folders we have Horde_Perms::READ for. */
if (empty($folderId) && $registry->getAuth()) {
    $folderId = $trean_shares->getId($registry->getAuth());
    $folder = $trean_shares->getFolder($folderId);
    if ($folder instanceof PEAR_Error) {
        /* Can't redirect back to browse since that would set up a loop. */
        throw new Horde_Exception($folder);
    }
} elseif (empty($folderId)) {
    /* We're accessing Trean as a guest, try to get a folder to browse */
    $folders = Trean::listFolders(Horde_Perms::READ);
    if (count($folders)) {
        $folder = array_pop(array_values($folders));
    }
} else {
    $folder = $trean_shares->getFolder($folderId);
    if ($folder instanceof PEAR_Error) {
        /* Can't redirect back to browse since that would set up a loop. */
        throw new Horde_Exception($folder);
    }

    /* Make sure user has permission to view this folder. */
    if (!$folder->hasPermission($registry->getAuth(), Horde_Perms::READ)) {
        $notification->push(_("You do not have permission to view this folder."), 'horde.error');
        Horde::url('browse.php', true)->redirect();
    }
}

if (!empty($folder)) {
    /* Get folder contents. */
    $bookmarks = $folder->listBookmarks($prefs->getValue('sortby'),
                                        $prefs->getValue('sortdir'));
}

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
