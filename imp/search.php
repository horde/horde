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
 * 'edit_query_filter' - (string) The name of the filter being edited.
 * 'edit_query_vfolder' - (string) The name of the virtual folder being
 *                        edited.
 * 'folder_list' - (array) The list of folders to add to the query.
 * 'search_label' - (string) The label to use when saving the search.
 * 'search_mailbox' - (string) Use this mailbox as the default value.
 *                    DEFAULT: INBOX
 * 'search_type' - (string) The type of saved search ('filter', 'vfolder').
 *                 If empty, the search should not be saved.
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

/* Define the criteria list. */
$criteria = array(
    'from' => array(
        'label' => _("From"),
        'type' => 'header'
    ),
    'recip' => array(
        'label' => _("Recipients (To/Cc/Bcc)"),
        'type' => 'header'
    ),
    'to' => array(
        'label' => _("To"),
        'type' => 'header'
    ),
    'cc' => array(
        'label' => _("Cc"),
        'type' => 'header'
    ),
    'bcc' => array(
        'label' => _("Bcc"),
        'type' => 'header'
    ),
    'subject' => array(
        'label' => _("Subject"),
        'type' => 'header'
    ),
    'customhdr' => array(
        'label' => _("Custom Header"),
        'type' => 'customhdr'
    ),
    'body' => array(
        'label' => _("Body"),
        'type' => 'text'
    ),
    'text' => array(
        'label' => _("Entire Message"),
        'type' => 'text'
    ),
    'date_on' => array(
        'label' => _("Date Equals (=)"),
        'type' => 'date'
    ),
    'date_until' => array(
        'label' => _("Date Until (<)"),
        'type' => 'date'
    ),
    'date_since' => array(
        'label' => _("Date Since (>=)"),
        'type' => 'date'
    ),
    'older' => array(
        'label' => _("Older Than"),
        'type' => 'within'
    ),
    'younger' => array(
        'label' => _("Younger Than"),
        'type' => 'within'
    ),
    // Displayed in KB, but stored internally in bytes
    'size_smaller' => array(
        'label' => _("Size (KB) <"),
        'type' => 'size'
    ),
    // Displayed in KB, but stored internally in bytes
    'size_larger' => array(
        'label' => _("Size (KB) >"),
        'type' => 'size'
    ),
);

$filters = array(
    'bulk' => array(
        'label' => _("Bulk Messages"),
        'type' => 'filter'
    ),
    'mailinglist' => array(
        'label' => _("Mailing List Messages"),
        'type' => 'filter'
    ),
    'personal' => array(
        'label' => _("Personal Messages"),
        'type' => 'filter'
    ),
);

/* Define some constants. */
$constants = array(
    'date' => array(
        'date_on' => IMP_Search_Element_Date::DATE_ON,
        'date_until' => IMP_Search_Element_Date::DATE_BEFORE,
        'date_since' => IMP_Search_Element_Date::DATE_SINCE
    ),
    'within' => array(
        'd' => IMP_Search_Element_Within::INTERVAL_DAYS,
        'm' => IMP_Search_Element_Within::INTERVAL_MONTHS,
        'y' => IMP_Search_Element_Within::INTERVAL_YEARS
    )
);

/* Load basic search if javascript is not enabled or searching is not allowed
 * (basic page will do the required redirection in the latter case). */
if (!$browser->hasFeature('javascript') ||
    ($session['imp:protocol'] == 'pop')) {
    require IMP_BASE . '/search-basic.php';
    exit;
}

$imp_flags = $injector->getInstance('IMP_Imap_Flags');
$imp_search = $injector->getInstance('IMP_Search');
$vars = Horde_Variables::getDefaultVariables();

$dimp_view = ($session['imp:view'] == 'dimp');
$js_vars = array();
$search_mailbox = isset($vars->search_mailbox)
    ? $vars->search_mailbox
    : 'INBOX';

$flist = $imp_flags->getFlagList($search_mailbox);

/* Generate the search query if 'criteria_form' is present in the form
 * data. */
if ($vars->criteria_form) {
    $c_data = Horde_Serialize::unserialize($vars->criteria_form, Horde_Serialize::JSON);
    $c_list = array();

    foreach ($c_data as $val) {
        switch ($val->t) {
        case 'from':
        case 'to':
        case 'cc':
        case 'bcc':
        case 'subject':
            $c_list[] = new IMP_Search_Element_Header(
                $val->v,
                $val->t,
                $val->n
            );
            break;

        case 'recip':
            $c_list[] = new IMP_Search_Element_Recipient(
                $val->v,
                $val->n
            );
            break;

        case 'customhdr':
            $c_list[] = new IMP_Search_Element_Header(
                $val->v->s,
                $val->v->h,
                $val->n
            );
            break;

        case 'body':
        case 'text':
            $c_list[] = new IMP_Search_Element_Text(
                $val->v,
                ($val->t == 'body'),
                $val->n
            );
            break;

        case 'date_on':
        case 'date_until':
        case 'date_since':
            $c_list[] = new IMP_Search_Element_Date(
                new DateTime($val->v),
                $constants['date'][$val->t]
            );
            break;

        case 'older':
        case 'younger':
            $c_list[] = new IMP_Search_Element_Within(
                $val->v->v,
                $constants['within'][$val->v->l],
                ($val->t == 'older')
            );
            break;

        case 'size_smaller':
        case 'size_larger':
            $c_list[] = new IMP_Search_Element_Size(
                $val->v,
                ($val->t == 'size_larger')
            );
            break;

        case 'or':
            $c_list[] = new IMP_Search_Element_Or();
            break;

        case 'bulk':
            $c_list[] = new IMP_Search_Element_Bulk(
                $val->n
            );
            break;

        case 'mailinglist':
            $c_list[] = new IMP_Search_Element_Mailinglist(
                $val->n
            );
            break;

        case 'personal':
            $c_list[] = new IMP_Search_Element_Personal(
                $val->n
            );
            break;

        case 'flag':
            /* Flag search. */
            $formdata = $imp_flags->parseFormId($val->v);
            $c_list[] = new IMP_Search_Element_Flag(
                $formdata['flag'],
                ($formdata['set'] && !$val->n)
            );
            break;
        }
    }

    $redirect_dimp = true;
    $redirect_target = false;

    switch ($vars->search_type) {
    case 'filter':
        $q_ob = $imp_search->createQuery(
            $c_list,
            array(),
            $vars->search_label,
            IMP_Search::CREATE_FILTER,
            IMP::formMbox($vars->edit_query_filter, false)
        );

        if ($vars->edit_query_filter) {
            $notification->push(sprintf(_("Filter \"%s\" edited successfully."), $vars->search_label), 'horde.success');
            $redirect_dimp = false;
            $redirect_target = 'prefs';
        } else {
            $notification->push(sprintf(_("Filter \"%s\" created succesfully."), $vars->search_label), 'horde.success');
        }
        break;

    case 'vfolder':
        $q_ob = $imp_search->createQuery(
            $c_list,
            $vars->folder_list,
            $vars->search_label,
            IMP_Search::CREATE_VFOLDER,
            IMP::formMbox($vars->edit_query_vfolder, false)
        );

        if ($vars->edit_query_vfolder) {
            $notification->push(sprintf(_("Virtual Folder \"%s\" edited successfully."), $vars->search_label), 'horde.success');
            $redirect_target = 'prefs';
        } else {
            $notification->push(sprintf(_("Virtual Folder \"%s\" created succesfully."), $vars->search_label), 'horde.success');
            $redirect_target = 'mailbox';
        }
        break;

    default:
        $q_ob = $imp_search->createQuery(
            $c_list,
            $vars->folder_list
        );
        $redirect_target = 'mailbox';
        break;
    }

    /* Redirect to the mailbox page. */
    if ($redirect_target) {
        if ($dimp_view && $redirect_dimp) {
            IMP_Dimp::returnToDimp(strval($q_ob));
            exit;
        }

        switch ($redirect_target) {
        case 'mailbox':
            Horde::url('mailbox.php', true)->add('mailbox', strval($q_ob))->redirect();
            break;

        case 'prefs':
            Horde::getServiceLink('prefs', 'imp')->add('group', 'searches')->redirect();
            break;
        }

        exit;
    }
}

/* Preselect mailboxes. */
$js_vars['ImpSearch.selected'] = array($search_mailbox);

/* Prepare the search template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);
$t->set('action', Horde::url('search.php'));
$t->set('virtualfolder', $session['imp:protocol'] != 'pop');

/* Determine if we are editing a search query. */
if ($vars->edit_query && $imp_search->isSearchMbox($vars->edit_query)) {
    $q_ob = $imp_search[$vars->edit_query];
    if ($imp_search->isVFolder($q_ob)) {
        if (!$imp_search->isVFolder($q_ob, true)) {
            $notification->push(_("Built-in Virtual Folders cannot be edited."), 'horde.error');
            Horde::getServiceLink('prefs', 'imp')->add('group', 'searches')->redirect();
        }
        $t->set('edit_query', true);
        $t->set('edit_query_vfolder', IMP::formMbox($q_ob, true));
    } elseif ($imp_search->isFilter($q_ob)) {
        if (!$imp_search->isFilter($q_ob, true)) {
            $notification->push(_("Built-in Filters cannot be edited."), 'horde.error');
            Horde::getServiceLink('prefs', 'imp')->add('group', 'searches')->redirect();
        }
        $t->set('edit_query', true);
        $t->set('edit_query_filter', IMP::formMbox($q_ob, true));
    }

    if ($t->get('edit_query')) {
        $t->set('search_label', htmlspecialchars($q_ob->label));
        $js_vars['ImpSearch.prefsurl'] = strval(Horde::getServiceLink('prefs', 'imp')->add('group', 'searches')->setRaw(true));
    }

    $js_vars['ImpSearch.i_criteria'] = $q_ob->criteria;
} else {
    /* Process list of recent searches. */
    $rs = array();
    $imp_search->setIteratorFilter(IMP_Search::LIST_QUERY);
    foreach ($imp_search as $val) {
        $rs[$val->id] = array(
            'c' => $val->criteria,
            'l' => Horde_String::truncate($val->querytext),
            'v' => $val->id
        );
    }

    if (!empty($rs)) {
        $js_vars['ImpSearch.recent'] = $rs;
    }
}

/* Create the criteria list. */
$c_list = $types = array();
foreach ($criteria as $key => $val) {
    $c_list[] = array(
        'val' => $key,
        'label' => htmlspecialchars($val['label'])
    );
    $types[$key] = $val['type'];
}
$t->set('clist', $c_list);

/* Create the filter list. These are all-or-nothing searches. */
$f_list = array();
foreach ($filters as $key => $val) {
    $f_list[] = array(
        'val' => $key,
        'label' => htmlspecialchars($val['label'])
    );
    $types[$key] = 'filter';
}
$t->set('filterlist', $f_list);

/* Create the flag list. */
$flag_set = array();
foreach ($flist['set'] as $val) {
    $flag_set[] = array(
        'val' => rawurlencode($val['f']),
        'label' => htmlspecialchars($val['l'])
    );
    $types[rawurlencode($val['f'])] = 'flag';
}
$t->set('flist', $flag_set);

/* Generate master folder list. */
if (!$t->get('edit_query_filter')) {
    $tree = $injector->getInstance('IMP_Imap_Tree')->createTree('imp_search', array(
        'checkbox' => true,
    ));
    $t->set('tree', $tree->getTree());
}

Horde_Core_Ui_JsCalendar::init();
Horde::addScriptFile('horde.js', 'horde');
Horde::addScriptFile('stripe.js', 'horde');
Horde::addScriptFile('search.js', 'imp');

Horde::addInlineJsVars(array_merge($js_vars, array(
    /* Javascript data for this page. */
    'ImpSearch.data' => array(
        'constants' => $constants,
        'dimp' => $dimp_view,
        'months' => Horde_Core_Ui_JsCalendar::months(),
        'searchmbox' => $search_mailbox,
        'types' => $types
    ),
    /* Gettext strings for this page. */
    'ImpSearch.text' => array(
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
    )
)), false, 'dom');

if ($dimp_view) {
    if (!$vars->edit_query) {
        $t->set('return_mailbox_val', sprintf(_("Return to %s"), htmlspecialchars(IMP::displayFolder($search_mailbox))));
    }
} else {
    $menu = IMP::menu();
}

$title = _("Search");
require IMP_TEMPLATES . '/common-header.inc';
if (!$dimp_view) {
    echo $menu;
}
IMP::status();

echo $t->fetch(IMP_TEMPLATES . '/imp/search/search.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
