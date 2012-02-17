<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 */
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

/* Get the current action ID. */
$actionID = Horde_Util::getFormData('actionID');

/* Sort out the sorting values. */
if (Horde_Util::getFormData('sortby') !== null) {
    $prefs->setValue('sortby', Horde_Util::getFormData('sortby'));
}
if (Horde_Util::getFormData('sortdir') !== null) {
   $prefs->setValue('sortdir', Horde_Util::getFormData('sortdir'));
}

/* Get the full, sorted notepad. */
$memos = Mnemo::listMemos($prefs->getValue('sortby'),
                          $prefs->getValue('sortdir'));

/* Page variables. */
$title = _("My Notes");

switch ($actionID) {
case 'search_memos':
    /* If we're searching, only list those notes that match the search
     * result. */
    $search_pattern = Horde_Util::getFormData('search_pattern');
    $search_type = Horde_Util::getFormData('search_type');
    $search_desc = ($search_type == 'desc');
    $search_body = ($search_type == 'body');

    if (!empty($search_pattern) && ($search_body || $search_desc)) {
        $search_pattern = '/' . preg_quote($search_pattern, '/') . '/i';
        $search_result = array();
        foreach ($memos as $memo_id => $memo) {
            if (($search_desc && preg_match($search_pattern, $memo['desc'])) ||
                ($search_body && preg_match($search_pattern, $memo['body']))) {
                $search_result[$memo_id] = $memo;
            }
        }

        /* Reassign $memos to the search result. */
        $memos = $search_result;
        $title = _("Search Results");
    }
    break;
}

if ($prefs->getValue('show_panel')) {
    $bodyClass = 'rightPanel';
}

Horde::addScriptFile('tables.js', 'horde', true);
Horde::addScriptFile('quickfinder.js', 'horde', true);
require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify();
require MNEMO_TEMPLATES . '/list/header.inc';

if (count($memos)) {
    $cManager = new Horde_Prefs_CategoryManager();
    $colors = $cManager->colors();
    $fgcolors = $cManager->fgColors();
    $sortby = $prefs->getValue('sortby');
    $sortdir = $prefs->getValue('sortdir');
    $showNotepad = $prefs->getValue('show_notepad');

    $baseurl = 'list.php';
    if ($actionID == 'search_memos') {
        $baseurl = Horde_Util::addParameter(
            $baseurl,
            array('actionID' => 'search_memos',
                  'search_pattern' => $search_pattern,
                  'search_type' => $search_type));
    }

    require MNEMO_TEMPLATES . '/list/memo_headers.inc';

    $history = $GLOBALS['injector']->getInstance('Horde_History');
    foreach ($memos as $memo_id => $memo) {
        $viewurl = Horde_Util::addParameter(
            'view.php',
            array('memo' => $memo['memo_id'],
                  'memolist' => $memo['memolist_id']));

        $memourl = Horde_Util::addParameter(
            'memo.php', array('memo' => $memo['memo_id'],
                              'memolist' => $memo['memolist_id']));
        try {
            $share = $GLOBALS['mnemo_shares']->getShare($memo['memolist_id']);
            $notepad = $share->get('name');
        } catch (Horde_Share_Exception $e) {
            $notepad = $memo['memolist_id'];
        }

        // Get memo`s most recent modification date or, if nonexistent,
        // the creation (add) date
        $guid = 'mnemo:' . $memo['memolist_id'] . ':' . $memo['uid'];
        $modDate = $history->getActionTimestamp($guid, 'modify');
        if ($modDate == 0) {
            $modDate = $history->getActionTimestamp($guid, 'add');
        }

        require MNEMO_TEMPLATES . '/list/memo_summaries.inc';
    }

    require MNEMO_TEMPLATES . '/list/memo_footers.inc';
} else {
    require MNEMO_TEMPLATES . '/list/empty.inc';
}

require MNEMO_TEMPLATES . '/panel.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
