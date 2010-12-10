<?php
/**
 * $Horde: mnemo/list.php,v 1.58 2009/11/29 18:37:52 chuck Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
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

    require MNEMO_TEMPLATES . '/stickies.html.php';
} else {
    require MNEMO_TEMPLATES . '/list/empty.inc';
}

require MNEMO_TEMPLATES . '/panel.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
