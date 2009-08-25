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
 * 'search_mailbox' - (string) Use this mailbox as the default value.
 *                    DEFAULT: INBOX
 *
 * TODO:
 * 'edit_query_vfolder'
 * 'search_folders_form[]'
 * 'show_unsub'
 * 'vfolder_label'
 * 'vfolder_save'
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => true));

/* Load basic search if javascript is not enabled or searching is not
 * allowed (basic page will do the required redirection in the latter case). */
if (!$browser->hasFeature('javascript') ||
    ($_SESSION['imp']['protocol'] == 'pop')) {
    require_once IMP_BASE . '/search-basic.php';
    exit;
}

$charset = Horde_Nls::getCharset();
$criteria = Horde_Util::getFormData('criteria_form');
$edit_query = Horde_Util::getFormData('edit_query');
$imp_search_fields = $imp_search->searchFields();

/* Generate the search query if 'criteria_form' is present in the form
 * data. */
if (!empty($criteria)) {
    $criteria = Horde_Serialize::unserialize($criteria, Horde_Serialize::JSON);
    $folders = Horde_Util::getFormData('search_folders_form');

    /* Create the search query. */
    $imp_ui_search = new IMP_UI_Search();
    $query = $imp_ui_search->createQuery($criteria);

    /* Save the search as a virtual folder if requested. */
    if (Horde_Util::getFormData('vfolder_save')) {
        $edit_query_vfolder = Horde_Util::getFormData('edit_query_vfolder');
        $vfolder_label = Horde_Util::getFormData('vfolder_label');
        $id = $imp_search->addVFolder($query, $folders, $criteria, $vfolder_label, empty($edit_query_vfolder) ? null : $edit_query_vfolder);
        $notification->push(sprintf(_("Virtual Folder \"%s\" created succesfully."), $vfolder_label), 'horde.success');
    } else {
        /* Set the search in the session. */
        $id = $imp_search->createSearchQuery($query, $folders, $criteria, _("Search Results"));
    }

    /* Redirect to the mailbox page. */
    header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('mailbox.php', true), array('mailbox' => $GLOBALS['imp_search']->createSearchID($id)), null, false));
    exit;
}

/* Generate master folder list. */
$show_unsub = ($subscribe = $prefs->getValue('subscribe'))
    ? Horde_Util::getFormData('show_unsub', false)
    : false;

$imp_folder = IMP_Folder::singleton();
$folders = array();
foreach ($imp_folder->flist(array(), $subscribe && !$show_unsub) as $val) {
    $folders[] = array_filter(array(
        'l' => Horde_Text_Filter::filter($val['label'], 'space2html', array('charset' => $charset, 'encode' => true)),
        'v' => $val['val']
    ));
}

if (Horde_Util::getFormData('show_unsub') !== null) {
    Horde::sendHTTPResponse($folders, 'json');
}

$on_domload = array(
    'ImpSearch.updateFolderList(' . Horde_Serialize::serialize($folders, Horde_Serialize::JSON, $charset) . ')'
);

/* Process list of saved searches. */
$saved_searches = $imp_search->listQueries(IMP_Search::LIST_SEARCH | IMP_Search::NO_BASIC_SEARCH, false);
if (!empty($saved_searches)) {
    $ss = array();
    foreach ($saved_searches as $key => $val) {
        $ss[$key] = array(
            'c' => $imp_search->getCriteria($key),
            'l' => Horde_String::truncate($val),
            'v' => $key
        );
    }
    $on_domload[] = 'ImpSearch.updateSavedSearches(' . Horde_Serialize::serialize($ss, Horde_Serialize::JSON, $charset) . ')';
}

/* Preselect mailboxes. */
$on_domload[] = 'ImpSearch.updateSelectedFolders(' . Horde_Serialize::serialize(array(Horde_Util::getFormData('search_mailbox', 'INBOX')), Horde_Serialize::JSON, $charset) . ')';

/* Prepare the search template. */
$t = new Horde_Template();
$t->setOption('gettext', true);
$t->set('action', Horde::applicationUrl('search.php'));
$t->set('subscribe', $subscribe);

/* Determine if we are editing a current search folder. */
if (!is_null($edit_query) && $imp_search->isSearchMbox($edit_query)) {
    if ($imp_search->isVFolder($edit_query)) {
        if (!$imp_search->isEditableVFolder($edit_query)) {
            $notification->push(_("Special Virtual Folders cannot be edited."), 'horde.error');
            header('Location: ' . Horde::applicationUrl('mailbox.php', true));
            exit;
        }
        $t->set('edit_query_vfolder', htmlspecialchars($edit_query));
    }
    $on_domload[] = 'ImpSearch.updateSearchCriteria(' . Horde_Serialize::serialize($imp_search->getCriteria($edit_query), Horde_Serialize::JSON, $charset) . ')';
}

$f_fields = $s_fields = $types = array();

/* Process the list of fields. */
foreach ($imp_search_fields as $key => $val) {
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

$t->set('virtualfolder', $_SESSION['imp']['protocol'] != 'pop');

Horde_UI_JsCalendar::init();

Horde::addInlineScript(array(
    'ImpSearch.loading = ' . Horde_Serialize::serialize(_("Loading..."), Horde_Serialize::JSON, $charset),
    'ImpSearch.months = ' . Horde_Serialize::serialize(Horde_UI_JsCalendar::months(), Horde_Serialize::JSON, $charset),
    'ImpSearch.need_criteria = ' . Horde_Serialize::serialize(_("Please select at least one search criteria."), Horde_Serialize::JSON, $charset),
    'ImpSearch.need_folder = ' . Horde_Serialize::serialize(_("Please select at least one folder to search."), Horde_Serialize::JSON, $charset),
    'ImpSearch.need_vfolder_label = ' . Horde_Serialize::serialize(_("Virtual Folders require a label."), Horde_Serialize::JSON, $charset),
    'ImpSearch.types = ' . Horde_Serialize::serialize($types, Horde_Serialize::JSON, $charset)
));
Horde::addInlineScript($on_domload, 'dom');

$title = _("Search");
Horde::addScriptFile('horde.js', 'horde', true);
Horde::addScriptFile('search.js', 'imp', true);
IMP::prepareMenu();
require IMP_TEMPLATES . '/common-header.inc';
IMP::menu();
IMP::status();

echo $t->fetch(IMP_TEMPLATES . '/search/search.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
