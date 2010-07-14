<?php
/**
 * IMP advanced search script. This search script only works with javascript
 * enabled browsers. All other browsers are limited to the basic search
 * script only.
 *
 * URL Parameters:
 * ---------------
 * 'criteria_form' - (string) JSON representation of the search query.
 * 'edit_query' - (string) The search query to edit.
 * 'edit_query_vfolder' - (string) The name of the vfolder being edited.
 * 'search_folders_form' - (array) The list of folders to add to the query.
 * 'search_label' - (string) The label to use when saving the search.
 * 'search_mailbox' - (string) Use this mailbox as the default value.
 *                    DEFAULT: INBOX
 * 'search_save' - (boolean) If set, save search.
 * 'search_type' - (string) The type of saved search ('vfolder').
 * 'show_unsub' - (integer) If set, return a JSON object with folder
 *                information used to create the folder list.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp');

/* Load basic search if javascript is not enabled or searching is not
 * allowed (basic page will do the required redirection in the latter case). */
if (!$browser->hasFeature('javascript') ||
    ($_SESSION['imp']['protocol'] == 'pop')) {
    require IMP_BASE . '/search-basic.php';
    exit;
}

$imp_search = $injector->getInstance('IMP_Search');

$charset = $registry->getCharset();
$criteria = Horde_Util::getFormData('criteria_form');
$dimp_view = ($_SESSION['imp']['view'] == 'dimp');
$search_fields = $imp_search->searchFields();
$search_mailbox = Horde_Util::getFormData('search_mailbox', 'INBOX');

/* Generate the search query if 'criteria_form' is present in the form
 * data. */
if (!empty($criteria)) {
    $criteria = Horde_Serialize::unserialize($criteria, Horde_Serialize::JSON);
    $folders = Horde_Util::getFormData('search_folders_form');

    /* Create the search query. */
    $imp_ui_search = new IMP_Ui_Search();
    $query = $imp_ui_search->createQuery($criteria);

    /* Save the search if requested. */
    if (Horde_Util::getFormData('search_save')) {
        $search_label = Horde_Util::getFormData('search_label');

        switch (Horde_Util::getFormData('search_type')) {
        case 'vfolder':
            $edit_query_vfolder = Horde_Util::getFormData('edit_query_vfolder');
            $id = $imp_search->addVFolder($query, $folders, $criteria, $search_label, empty($edit_query_vfolder) ? null : $edit_query_vfolder);
            $notification->push(sprintf(_("Virtual Folder \"%s\" created succesfully."), $search_label), 'horde.success');
            break;
        }
    } else {
        /* Set the search in the session. */
        $id = $imp_search->createSearchQuery($query, $folders, $criteria, _("Search Results"));
    }

    /* Redirect to the mailbox page. */
    $id = $imp_search->createSearchID($id);
    if ($dimp_view) {
        /* Output javascript code to close the IFRAME and load the search
         * mailbox in DIMP. */
        print '<html><head>' .
            Horde::wrapInlineScript(array('window.parent.DimpBase.go(' . Horde_Serialize::serialize('folder:' . $id, Horde_Serialize::JSON, $charset) . ')')) .
            '</head></html>';
    } else {
        header('Location: ' . Horde::applicationUrl('mailbox.php', true)->setRaw(true)->add('mailbox', $id));
    }
    exit;
}

/* Generate master folder list. */
$imp_imap_tree = $injector->getInstance('IMP_Imap_Tree');
$mask = IMP_Imap_Tree::NEXT_SHOWCLOSED;
$subscribe = $prefs->getValue('subscribe');
if ($subscribe || !Horde_Util::getFormData('show_unsub')) {
    $mask |= IMP_Imap_Tree::NEXT_SHOWSUB;
}

list($raw_rows,) = $imp_imap_tree->build($mask);

$imp_ui_folder = new IMP_Ui_Folder();
$tree_imgs = $imp_ui_folder->getTreeImages($raw_rows);

$folders = array();
foreach ($raw_rows as $key => $val) {
    $folders[] = array(
        'c' => intval($val['container']),
        'l' => $tree_imgs[$key] . ' ' . $val['name'],
        'v' => $val['value']
    );
}

if (Horde_Util::getFormData('show_unsub') !== null) {
    Horde::sendHTTPResponse($folders, 'json');
}

$js_load = array(
    'ImpSearch.updateFolderList(' . Horde_Serialize::serialize($folders, Horde_Serialize::JSON, $charset) . ')'
);

/* Process list of recent searches. */
$recent_searches = $imp_search->listQueries(IMP_Search::LIST_SEARCH | IMP_Search::NO_BASIC_SEARCH, false);
if (!empty($recent_searches)) {
    $rs = array();
    foreach ($recent_searches as $key => $val) {
        $rs[$key] = array(
            'c' => $imp_search->getCriteria($key),
            'l' => Horde_String::truncate($val),
            'v' => $key
        );
    }
    $js_load[] = 'ImpSearch.updateRecentSearches(' . Horde_Serialize::serialize($rs, Horde_Serialize::JSON, $charset) . ')';
}

/* Preselect mailboxes. */
$js_load[] = 'ImpSearch.updateSelectedFolders(' . Horde_Serialize::serialize(array($search_mailbox), Horde_Serialize::JSON, $charset) . ')';

/* Prepare the search template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);
$t->set('action', Horde::applicationUrl('search.php'));
$t->set('subscribe', $subscribe);
$t->set('virtualfolder', $_SESSION['imp']['protocol'] != 'pop');

/* Determine if we are editing a current search folder. */
$edit_query = Horde_Util::getFormData('edit_query');
if (!is_null($edit_query) && $imp_search->isSearchMbox($edit_query)) {
    if ($imp_search->isVFolder($edit_query)) {
        if (!$imp_search->isEditableVFolder($edit_query)) {
            $notification->push(_("Special Virtual Folders cannot be edited."), 'horde.error');
            header('Location: ' . Horde::applicationUrl('mailbox.php', true));
            exit;
        }
        $t->set('edit_query_vfolder', htmlspecialchars($edit_query));
    }
    $js_load[] = 'ImpSearch.updateSearchCriteria(' . Horde_Serialize::serialize($imp_search->getCriteria($edit_query), Horde_Serialize::JSON, $charset) . ')';
    $js_load[] = 'ImpSearch.updateSavedSearches(' . Horde_Serialize::serialize($imp_search->getLabel($edit_query), Horde_Serialize::JSON, $charset) . ')';
}

$f_fields = $s_fields = $types = array();

/* Process the list of fields. */
foreach ($search_fields as $key => $val) {
    $s_fields[] = array(
        'val' => $key,
        'label' => $val['label']
    );
    $types[$key] = $val['type'];
}
$t->set('s_fields', $s_fields);

foreach ($imp_search->flagFields() as $key => $val) {
    $f_fields[] = array(
        'val' => $key,
        'label' => $val
    );
    $types[$key] = 'flag';
}
$t->set('f_fields', $f_fields);

Horde_Core_Ui_JsCalendar::init();

/* Gettext strings for this page. */
$gettext_strings = array(
    'and' => _("and"),
    'customhdr' => _("Custom Header:"),
    'dateselection' => _("Date Selection"),
    'flag' => _("Flag:"),
    'loading' => _("Loading..."),
    'need_criteria' => _("Please select at least one search criteria."),
    'need_folder' => _("Please select at least one folder to search."),
    'need_label' => _("Saved searches require a label."),
    'not_match' => _("Do NOT Match"),
    'or' => _("OR"),
    'search_term' => _("Search Term:")
);

/* Javascript data for this page. */
$js_data = array(
    'months' => Horde_Core_Ui_JsCalendar::months(),
    'searchmbox' => $search_mailbox,
    'types' => $types
);

Horde::addInlineScript(array(
    'ImpSearch.data = ' . Horde_Serialize::serialize($js_data, Horde_Serialize::JSON, $charset),
    'ImpSearch.text = ' . Horde_Serialize::serialize($gettext_strings, Horde_Serialize::JSON, $charset)
));
Horde::addInlineScript($js_load, 'dom');

$title = _("Search");
Horde::addScriptFile('horde.js', 'horde');
Horde::addScriptFile('stripe.js', 'horde');
Horde::addScriptFile('search.js', 'imp');

if ($dimp_view) {
    $t->set('return_mailbox_text', htmlspecialchars($search_mailbox));
} else {
    IMP::prepareMenu();
}
require IMP_TEMPLATES . '/common-header.inc';
if (!$dimp_view) {
    IMP::menu();
}
IMP::status();

echo $t->fetch(IMP_TEMPLATES . '/imp/search/search.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
