<?php
/**
 * IMP advanced search script. This search script only works with javascript
 * enabled browsers. All other browsers are limited to the basic search
 * script only.
 *
 * URL Parameters:
 * ---------------
 *   - criteria_form: (string) JSON representation of the search query.
 *   - edit_query: (integer) If true, edit a search query (contained in
 *                 IMP::$mailbox).
 *   - edit_query_filter: (string) The name of the filter being edited.
 *   - edit_query_vfolder: (string) The name of the virtual folder being
 *                         edited.
 *   - folders_form: (string) JSON representation of the list of mailboxes for
 *                   the query. Hash containing 2 keys: mbox & subfolder.
 *                   Both values are base64url encoded.
 *   - mailbox_list: (array) A list of mailboxes to process (base64url
 *                   encoded) If empty, uses IMP::$mailbox.
 *   - search_label: (string) The label to use when saving the search.
 *   - search_type: (string) The type of saved search ('filter', 'vfolder').
 *                  If empty, the search should not be saved.
 *   - subfolder: (boolean) If set, search mailbox will default to subfolder
 *                search.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
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
    'attach' => array(
        'label' => _("Contains Attachment(s)"),
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
$imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
if (!$imp_imap->access(IMP_Imap::ACCESS_SEARCH) ||
    !$browser->hasFeature('javascript')) {
    require IMP_BASE . '/search-basic.php';
    exit;
}

$imp_flags = $injector->getInstance('IMP_Flags');
$imp_search = $injector->getInstance('IMP_Search');
$vars = Horde_Variables::getDefaultVariables();

$dimp_view = (IMP::getViewMode() == 'dimp');
$js_vars = array();

if (isset($vars->mailbox_list)) {
    if (is_array($vars->mailbox_list)) {
        $default_mailbox = IMP_Mailbox::get('INBOX');
        $search_mailbox = IMP_Mailbox::formFrom($vars->mailbox_list);
    } else {
        $default_mailbox = IMP_Mailbox::formFrom($vars->mailbox_list);
        $search_mailbox = array($default_mailbox);
    }
} elseif (IMP::$mailbox) {
    $default_mailbox = IMP::$mailbox;
    $search_mailbox = array($default_mailbox);
} else {
    $default_mailbox = IMP_Mailbox::get('INBOX');
    $search_mailbox = array(IMP_Mailbox::get('INBOX'));
}

$flist = $imp_flags->getList(array(
    'imap' => true,
    'mailbox' => $default_mailbox
));

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

        case 'attach':
            $c_list[] = new IMP_Search_Element_Attachment(
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
            $formdata = $imp_flags->parseFormId(rawurldecode($val->v));
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
        $q_ob = $imp_search->createQuery($c_list, array(
            'id' => IMP_Mailbox::formFrom($vars->edit_query_filter),
            'label' => $vars->search_label,
            'type' => IMP_Search::CREATE_FILTER
        ));

        if ($vars->edit_query_filter) {
            $notification->push(sprintf(_("Filter \"%s\" edited successfully."), $vars->search_label), 'horde.success');
            $redirect_dimp = false;
            $redirect_target = 'prefs';
        } else {
            $notification->push(sprintf(_("Filter \"%s\" created succesfully."), $vars->search_label), 'horde.success');
        }
        break;

    case 'vfolder':
        $folders_form = Horde_Serialize::unserialize($vars->folders_form, Horde_Serialize::JSON);
        $q_ob = $imp_search->createQuery($c_list, array(
            'id' => IMP_Mailbox::formFrom($vars->edit_query_vfolder),
            'label' => $vars->search_label,
            'mboxes' => IMP_Mailbox::formFrom($folders_form->mbox),
            'subfolders' => IMP_Mailbox::formFrom($folders_form->subfolder),
            'type' => IMP_Search::CREATE_VFOLDER
        ));

        if ($vars->edit_query_vfolder) {
            $notification->push(sprintf(_("Virtual Folder \"%s\" edited successfully."), $vars->search_label), 'horde.success');
            $redirect_target = 'prefs';
        } else {
            $notification->push(sprintf(_("Virtual Folder \"%s\" created succesfully."), $vars->search_label), 'horde.success');
            $redirect_target = 'mailbox';
        }
        break;

    default:
        $folders_form = Horde_Serialize::unserialize($vars->folders_form, Horde_Serialize::JSON);
        $q_ob = $imp_search->createQuery($c_list, array(
            'mboxes' => IMP_Mailbox::formFrom($folders_form->mbox),
            'subfolders' => IMP_Mailbox::formFrom($folders_form->subfolder)
        ));
        $redirect_target = 'mailbox';
        break;
    }

    /* Redirect to the mailbox page. */
    if ($redirect_target) {
        if ($dimp_view && $redirect_dimp) {
            print '<html><head>' .
                Horde::wrapInlineScript(array('window.parent.DimpBase.go("mbox", "' . $q_ob->mbox_ob->form_to . '")')) .
                '</head></html>';
            exit;
        }

        switch ($redirect_target) {
        case 'mailbox':
            $q_ob->mbox_ob->url('mailbox.php')->redirect();
            break;

        case 'prefs':
            Horde::getServiceLink('prefs', 'imp')->add('group', 'searches')->redirect();
            break;
        }

        exit;
    }
}

/* Prepare the search template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);
$t->set('action', Horde::url('search.php'));

/* Determine if we are editing a search query. */
$q_ob = IMP::$mailbox->getSearchOb();
if ($vars->edit_query && IMP::$mailbox->search) {
    if (IMP::$mailbox->vfolder) {
        if (!IMP::$mailbox->editvfolder) {
            $notification->push(_("Built-in Virtual Folders cannot be edited."), 'horde.error');
            Horde::getServiceLink('prefs', 'imp')->add('group', 'searches')->redirect();
        }
        $t->set('edit_query', true);
        $t->set('edit_query_vfolder', IMP::$mailbox->formTo);
    } elseif ($imp_search->isFilter($q_ob)) {
        if (!$imp_search->isFilter($q_ob, true)) {
            $notification->push(_("Built-in Filters cannot be edited."), 'horde.error');
            Horde::getServiceLink('prefs', 'imp')->add('group', 'searches')->redirect();
        }
        $t->set('edit_query', true);
        $t->set('edit_query_filter', IMP::$mailbox->formTo);
    }

    if ($t->get('edit_query')) {
        $t->set('search_label', htmlspecialchars($q_ob->label));
        $js_vars['ImpSearch.prefsurl'] = strval(Horde::getServiceLink('prefs', 'imp')->add('group', 'searches')->setRaw(true));
    }
} else {
    /* Process list of recent searches. */
    $rs = array();
    $imp_search->setIteratorFilter(IMP_Search::LIST_QUERY);
    foreach ($imp_search as $val) {
        $rs[$val->formid] = array(
            'c' => $val->criteria,
            'f' => array(
                'm' => IMP_Mailbox::formTo($val->all ? array(IMP_Search_Query::ALLSEARCH) : array_map('strval', $val->mbox_list)),
                's' => IMP_Mailbox::formTo(array_map('strval', $val->subfolder_list))
            ),
            'l' => Horde_String::truncate($val->querytext)
        );
    }

    if (!empty($rs)) {
        $js_vars['ImpSearch.i_recent'] = $rs;
    }

    $s_mboxes = IMP_Mailbox::formTo($search_mailbox);
    $js_vars['ImpSearch.i_folders'] = array(
        'm' => $vars->subfolder ? array() : $s_mboxes,
        's' => $vars->subfolder ? $s_mboxes : array()
    );
}

if (IMP::$mailbox->search) {
    $js_vars['ImpSearch.i_criteria'] = $q_ob->criteria;
    $js_vars['ImpSearch.i_folders'] = array(
        'm' => IMP_Mailbox::formTo($q_ob->all ? array(IMP_Search_Query::ALLSEARCH) : $q_ob->mbox_list),
        's' => IMP_Mailbox::formTo($q_ob->subfolder_list)
    );
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
foreach ($flist as $val) {
    $flag_set[] = array(
        'val' => rawurlencode($val->form_set),
        'label' => htmlspecialchars($val->label)
    );
    $types[rawurlencode($val->form_set)] = 'flag';
}
$t->set('flist', $flag_set);

/* Generate master folder list. */
$folder_list = array();
if (!$t->get('edit_query_filter')) {
    $js_vars['ImpSearch.allsearch'] = IMP_Mailbox::formTo(IMP_Search_Query::ALLSEARCH);
    $ob = $injector->getInstance('IMP_Ui_Search')->getSearchMboxList();
    $folder_list = $ob->folder_list;
    $t->set('tree', $ob->tree->getTree());

    if ($prefs->getValue('subscribe')) {
        $t->set('subscribe', true);
        $js_vars['ImpSearch.ajaxurl'] = Horde::getServiceLink('ajax', 'imp')->url;
    }
}

Horde_Core_Ui_JsCalendar::init();
Horde::addScriptFile('horde.js', 'horde');
Horde::addScriptFile('search.js', 'imp');

Horde::addInlineJsVars(array_merge($js_vars, array(
    /* Javascript data for this page. */
    'ImpSearch.data' => array(
        'constants' => $constants,
        'dimp' => $dimp_view,
        'folder_list' => $folder_list,
        'months' => Horde_Core_Ui_JsCalendar::months(),
        'searchmbox' => $default_mailbox->form_to,
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
        'search_all' => _("Search All Mailboxes"),
        'search_term' => _("Search Term:"),
        'subfolder_search' => _("Search all subfolders?")
    )
)), array('onload' => 'dom'));

if ($dimp_view) {
    if (!$vars->edit_query) {
        $t->set('return_mailbox_val', sprintf(_("Return to %s"), $default_mailbox->display_html));
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
