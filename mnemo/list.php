<?php
/**
 * Copyright 2001-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 */
require_once __DIR__ . '/lib/Application.php';
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

$view = $injector->createInstance('Horde_View');
$view->count = count($memos);
$view->searchImg = Horde::img('search.png', _("Search"), '');
$view->searchUrl = Horde::url('search.php');
$view->title = $title;

if (count($memos)) {
    $sortby = $prefs->getValue('sortby');
    $sortdir = $prefs->getValue('sortdir');

    $baseurl = Horde::url('list.php');
    if ($actionID == 'search_memos') {
        $baseurl->add(
            array('actionID' => 'search_memos',
                  'search_pattern' => $search_pattern,
                  'search_type' => $search_type));
    }

    $page_output->addInlineJsVars(array(
        'Mnemo_List.ajaxUrl' => $registry->getServiceLink('ajax', 'mnemo')->url . 'setPrefValue'
    ));
    $view->editImg = Horde::img('edit.png', _("Edit Note"), '');
    $view->showNotepad = $prefs->getValue('show_notepad');
    $view->sortdirclass = $sortdir ? 'sortup' : 'sortdown';
    $view->headers = array();
    if ($view->showNotepad) {
        $view->headers[] = array(
            'id' => 's' . Mnemo::SORT_NOTEPAD,
            'sorted' => $sortby == Mnemo::SORT_NOTEPAD,
            'width' => '2%',
            'label' => Horde::widget(array('url' => $baseurl->add('sortby', Mnemo::SORT_NOTEPAD), 'class' => 'sortlink', 'title' => _("Notepad"))),
        );
    }
    $view->headers[] = array(
        'id' => 's' . MNEMO::SORT_DESC,
        'sorted' => $sortby == MNEMO::SORT_DESC,
        'width' => '80%',
        'label' => Horde::widget(array(
            'url' => $baseurl->add('sortby', Mnemo::SORT_DESC),
            'class' => 'sortlink',
            'title' => _("No_te")
         )),
    );
    $view->headers[] = array(
        'id' => 's' . MNEMO::SORT_MOD_DATE,
        'sorted' => $sortby == Mnemo::SORT_MOD_DATE,
        'width' => '2%',
        'label' => Horde::widget(array(
            'url' => $baseurl->add('sortby', MNEMO::SORT_MOD_DATE),
            'class' => 'sortlink',
            'title' => _("Date")
         )),
    );

    foreach ($memos as $memo_id => &$memo) {
        try {
            $share = $mnemo_shares->getShare($memo['memolist_id']);
        } catch (Horde_Share_Exception $e) {
            $notification->push($e);
            continue;
        }
        if ($view->showNotepad) {
            $memo['notepad'] = Mnemo::getLabel($share);
        }
        if ($share->hasPermission($registry->getAuth(), Horde_Perms::EDIT)) {
            $label = sprintf(_("Edit \"%s\""), $memo['desc']);
            $memo['edit'] = Horde::url('memo.php')
                ->add(array(
                    'memo' => $memo['memo_id'],
                    'memolist' => $memo['memolist_id'],
                    'actionID' => 'modify_memo'
                ))
                ->link(array('title' => $label))
                . Horde::img('edit.png', $label, '') . '</a>';
        }

        $memo['link'] = Horde::linkTooltip(
            Horde::url('view.php')->add(array(
                'memo' => $memo['memo_id'],
                'memolist' => $memo['memolist_id']
            )),
            '', '', '', '',
            ($memo['body'] != $memo['desc']) ? Mnemo::getNotePreview($memo) : ''
        )
            . (strlen($memo['desc']) ? htmlspecialchars($memo['desc']) : '<em>' . _("Empty Note") . '</em>')
            . '</a>';

        // Get memo's most recent modification date or, if nonexistent,
        // the creation (add) date
        if (isset($memo['modified'])) {
            $modified = $memo['modified'];
        } elseif (isset($memo['created'])) {
            $modified = $memo['created'];
        } else {
            $modified = null;
        }
        if ($modified) {
            $memo['modifiedStamp'] = $modified->timestamp();
            $memo['modifiedString'] = $modified->strftime($prefs->getValue('date_format'));
        } else {
            $memo['modifiedStamp'] = $memo['modifiedString'] = '';
        }
    }
}

$page_output->addScriptFile('tables.js', 'horde');
$page_output->addScriptFile('quickfinder.js', 'horde');
$page_output->addScriptFile('list.js');
$page_output->header(array(
    'title' => $title
));
$notification->notify();
echo $view->render('list/header');
if (count($memos)) {
    echo $view->render('list/memo_headers');
    echo $view->renderPartial('list/summary', array('collection' => array_values($memos)));
    echo $view->render('list/memo_footers');
} else {
    echo $view->render('list/empty');
}
$page_output->footer();
