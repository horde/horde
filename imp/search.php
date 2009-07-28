<?php
/**
 * IMP search script.
 *
 * URL Parameters:
 * ---------------
 * 'search_mailbox'  --  If exists, don't show the folder selection list; use
 *                       the passed in mailbox value instead.
 * 'edit_query'      --  If exists, the search query to edit.
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

require_once dirname(__FILE__) . '/lib/base.php';

/* Load mailbox page if searching is not allowed. */
if ($_SESSION['imp']['protocol'] == 'pop') {
    $notification->push(_("Searching is not available with a POP3 server."), 'horde.error');
    $from_message_page = true;
    $actionID = $start = null;
    require_once IMP_BASE . '/mailbox.php';
    exit;
}

$actionID = Horde_Util::getFormData('actionID');
$edit_query = Horde_Util::getFormData('edit_query');
$edit_query_vfolder = Horde_Util::getFormData('edit_query_vfolder');
$search_mailbox = Horde_Util::getFormData('search_mailbox');

$imp_search_fields = $imp_search->searchFields();

$charset = Horde_Nls::getCharset();

/* Get URL parameter data. */
$search = array();
if (Horde_Util::getFormData('no_match')) {
    $search = $imp_search->retrieveUIQuery();
    $retrieve_search = true;
} elseif (($edit_query !== null) && $imp_search->isSearchMbox($edit_query)) {
    if ($imp_search->isVFolder($edit_query)) {
        if (!$imp_search->isEditableVFolder($edit_query)) {
            $notification->push(_("Special Virtual Folders cannot be edited."), 'horde.error');
            header('Location: ' . Horde::applicationUrl('mailbox.php', true));
            exit;
        }
        $edit_query_vfolder = $edit_query;
    }
    $search = $imp_search->retrieveUIQuery($edit_query);
    $retrieve_search = true;
} else {
    $retrieve_search = false;
}
if (empty($search)) {
    $search['field'] = Horde_Util::getFormData('field', array('from', 'to', 'subject', 'body'));
    if (!empty($search['field']) && !end($search['field'])) {
        array_pop($search['field']);
    }
    $search['field_end'] = count($search['field']);
    $search['match'] = Horde_Util::getFormData('search_match');
    $search['text'] = Horde_Util::getFormData('search_text');
    $search['text_not'] = Horde_Util::getFormData('search_text_not');
    $search['date'] = Horde_Util::getFormData('search_date');
    $search['folders'] = Horde_Util::getFormData('search_folders', array());
    $search['save_vfolder'] = Horde_Util::getFormData('save_vfolder');
    $search['vfolder_label'] = Horde_Util::getFormData('vfolder_label');
    $search['mbox'] = Horde_Util::getFormData('mbox', $search_mailbox);
}

/* Run through the action handlers. */
switch ($actionID) {
case 'do_search':
    /* Need to convert size from KB to bytes. */
    for ($i = 0; $i <= $search['field_end']; $i++) {
        if (isset($search['field'][$i]) &&
            isset($imp_search_fields[$search['field'][$i]]) &&
            ($imp_search_fields[$search['field'][$i]]['type'] == IMP_Search::SIZE)) {
            $search['text'][$i] *= 1024;
        }
    }

    /* Create the search query. */
    $query = $imp_search->createQuery($search);

    /* Save the search as a virtual folder if requested. */
    if (!empty($search['save_vfolder'])) {
        if (empty($search['vfolder_label'])) {
            $notification->push(_("Virtual Folders require a label."), 'horde.error');
            break;
        }

        $id = $imp_search->addVFolder($query, $search['folders'], $search, $search['vfolder_label'], (empty($edit_query_vfolder) ? null : $edit_query_vfolder));
        $notification->push(sprintf(_("Virtual Folder \"%s\" created succesfully."), $search['vfolder_label']), 'horde.success');
    } else {
        /* Set the search in the IMP session. */
        $id = $imp_search->createSearchQuery($query, $search['folders'], $search, _("Search Results"));
    }

    /* Redirect to the Mailbox Screen. */
    header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('mailbox.php', true), 'mailbox', $GLOBALS['imp_search']->createSearchID($id), false));
    exit;

case 'reset_search':
    if ($def_search = $prefs->getValue('default_search')) {
        $search['field'] = array($def_search);
        $search['field_end'] = 1;
    } else {
        $search['field'] = array();
        $search['field_end'] = 0;
    }
    $search['match'] = null;
    $search['date'] = $search['text'] = $search['text_not'] = $search['flag'] = array();
    $search['folders'] = array();
    break;

case 'delete_field':
    $key = Horde_Util::getFormData('delete_field_id');

    /* Unset all entries in array input and readjust ids. */
    $vars = array('field', 'text', 'text_not', 'date');
    foreach ($vars as $val) {
        unset($search[$val][$key]);
        if (!empty($search[$val])) {
            $search[$val] = array_values($search[$val]);
        }
    }
    $search['field_end'] = count($search['field']);
    break;
}

$shown = null;
if (!$conf['user']['allow_folders']) {
    $search['mbox'] = 'INBOX';
    $search['folders'][] = 'INBOX';
    $subscribe = false;
} elseif ($subscribe = $prefs->getValue('subscribe')) {
    $shown = Horde_Util::getFormData('show_subscribed_only', $subscribe);
}

/* Prepare the search template. */
$t = new Horde_Template();
$t->setOption('gettext', true);

$t->set('action', Horde::applicationUrl('search.php'));
$t->set('subscribe', $subscribe);
$t->set('shown', htmlspecialchars($shown));
$t->set('edit_query_vfolder', htmlspecialchars($edit_query_vfolder));
if (!$edit_query_vfolder) {
    if (empty($search['mbox'])) {
        $t->set('search_title', _("Search"));
    } else {
        $t->set('search_title',
                sprintf(
                    _("Search %s"),
                    Horde::link(
                        Horde::url(Horde_Util::addParameter('mailbox.php',
                                                      'mailbox',
                                                      $search['mbox'])))
                    . htmlspecialchars(IMP::displayFolder($search['mbox']))
                    . '</a>'));
    }
}
$t->set('search_help', Horde_Help::link('imp', 'search'));
$t->set('match_or', $search['match'] == 'or');
$t->set('label_or', Horde::label('search_match_or', _("Match Any Query")));
$t->set('match_and', ($search['match'] == null) || ($search['match'] == 'and'));
$t->set('label_and', Horde::label('search_match_and', _("Match All Queries")));

$saved_searches = $imp_search->getSearchQueries();
if (!empty($saved_searches)) {
    $ss = array();
    foreach ($saved_searches as $key => $val) {
        $ss[] = array('val' => htmlspecialchars($key), 'text' => htmlspecialchars(Horde_String::truncate($val)));
    }
    $t->set('saved_searches', $ss);
}

$fields = $f_fields = $s_fields = array();
$js_first = 0;

/* Process the list of fields. */
foreach ($imp_search_fields as $key => $val) {
    $s_fields[$key] = array(
        'val' => $key,
        'label' => $val['label'],
        'sel' => null
    );
}
foreach ($imp_search->flagFields() as $key => $val) {
    $f_fields[$key] = array(
        'val' => $key,
        'label' => $val['label'],
        'sel' => null
    );
}

for ($i = 0; $i <= $search['field_end']; $i++) {
    $curr = (isset($search['field'][$i])) ? $search['field'][$i] : null;
    $fields[$i] = array(
        'i' => $i,
        'last' => ($i == $search['field_end']),
        'curr' => $curr,
        'f_fields' => $f_fields,
        'first' => (($i == 0) && ($i != $search['field_end'])),
        'notfirst' => ($i > 0),
        's_fields' => $s_fields,
        'search_text' => false,
        'search_date' => false,
        'js_calendar' => null
    );
    if ($curr !== null) {
        if (isset($f_fields[$curr])) {
            $fields[$i]['f_fields'][$curr]['sel'] = true;
        } else {
            $fields[$i]['s_fields'][$curr]['sel'] = true;
            if (in_array($imp_search_fields[$curr]['type'], array(IMP_Search::HEADER, IMP_Search::BODY, IMP_Search::TEXT, IMP_Search::SIZE))) {
                $fields[$i]['search_text'] = true;
                $fields[$i]['search_text_val'] = (!empty($search['text'][$i])) ? @htmlspecialchars($search['text'][$i], ENT_COMPAT, $charset) : null;
                if ($retrieve_search &&
                    ($imp_search_fields[$curr]['type'] == IMP_Search::SIZE)) {
                    $fields[$i]['search_text_val'] /= 1024;
                }
                if ($imp_search_fields[$curr]['not']) {
                    $fields[$i]['show_not'] = true;
                    $fields[$i]['search_text_not'] = (!empty($search['text_not'][$i]));
                }
            } elseif ($imp_search_fields[$curr]['type'] == IMP_Search::DATE) {
                if (!isset($curr_date)) {
                    $curr_date = getdate();
                }
                $fields[$i]['search_date'] = true;

                $fields[$i]['month'] = array();
                $month_default = isset($search['date'][$i]['month']) ? $search['date'][$i]['month'] : $curr_date['mon'];
                for ($month = 1; $month <= 12; $month++) {
                    $fields[$i]['month'][] = array(
                        'val' => $month,
                        'sel' => ($month == $month_default),
                        'label' => strftime('%B', mktime(0, 0, 0, $month, 1))
                    );
                }

                $fields[$i]['day'] = array();
                $day_default = isset($search['date'][$i]['day']) ? $search['date'][$i]['day'] : $curr_date['mday'];
                for ($day = 1; $day <= 31; $day++) {
                    $fields[$i]['day'][] = array(
                        'val' => $day,
                        'sel' => ($day == $day_default)
                    );
                }

                $fields[$i]['year'] = array();
                $year_default = isset($search['date'][$i]['year']) ? $search['date'][$i]['year'] : $curr_date['year'];
                if (!isset($curr_year)) {
                    $curr_year = date('Y');
                    $yearlist = array();
                    $years = -20;
                    $startyear = (($year_default < $curr_year) && ($years > 0)) ? $year_default : $curr_year;
                    $startyear = min($startyear, $startyear + $years);
                    for ($j = 0; $j <= abs($years); $j++) {
                        $yearlist[] = $startyear++;
                    }
                    if ($years < 0) {
                        $yearlist = array_reverse($yearlist);
                    }
                }
                foreach ($yearlist as $year) {
                    $fields[$i]['year'][] = array(
                        'val' => $year,
                        'sel' => ($year == $year_default)
                    );
                }

                if ($browser->hasFeature('javascript')) {
                    Horde::addScriptFile('open_calendar.js', 'horde');
                    $fields[$i]['js_calendar_first'] = !$js_first++;
                    $fields[$i]['js_calendar'] = Horde::link('#', _("Select a date"), '', '', 'openCalendar(\'dateimg' . $i . '\', \'search_date_' . $i . '\'); return false;');
                    $fields[$i]['js_calendar_img'] = Horde::img('calendar.png', _("Calendar"), 'align="top" id="dateimg' . $i . '"', $GLOBALS['registry']->getImageDir('horde'));
                }
            }
        }
    }
}
$t->set('fields', array_values($fields));
$t->set('delete_img', $registry->getImageDir('horde') . '/delete.png');
$t->set('remove', _("Remove Field From Search"));

if ($subscribe) {
    $t->set('inverse_subscribe', ($shown == IMP_Search::SHOW_UNSUBSCRIBED) ? IMP_Search::SHOW_SUBSCRIBED_ONLY : IMP_Search::SHOW_UNSUBSCRIBED);
}

$t->set('mbox', htmlspecialchars($search['mbox']));
$t->set('virtualfolder', $_SESSION['imp']['protocol'] != 'pop');
if ($t->get('virtualfolder')) {
    $t->set('save_vfolder', !empty($search['save_vfolder']));
    $t->set('vfolder_label', !empty($search['vfolder_label']) ? htmlspecialchars($search['vfolder_label'], ENT_COMPAT, $charset) : null);
}

if (empty($search['mbox'])) {
    $count = -1;
    $mboxes = array();
    $newcol = $numcolumns = 1;

    $imp_folder = IMP_Folder::singleton();
    $mailboxes = $imp_folder->flist(array(), !is_null($shown) ? $shown : null);
    $total = ceil(count($mailboxes) / 3);

    if (empty($search['folders']) && ($actionID != 'update_search')) {
        /* Default to Inbox search. */
        $search['folders'][] = 'INBOX';
    }

    foreach ($mailboxes as $key => $mbox) {
        $mboxes[$key] = array(
            'count' => ++$count,
            'val' => (!empty($mbox['val']) ? htmlspecialchars($mbox['val']) : null),
            'sel' => false,
            'label' => str_replace(' ', '&nbsp;', $mbox['label']),
            'newcol' => false
        );

        if (!empty($mbox['val']) &&
            in_array($mbox['val'], $search['folders'])) {
            $mboxes[$key]['sel'] = true;
        }

        if ((++$newcol > $total) && ($numcolumns != 3)) {
            $newcol = 1;
            ++$numcolumns;
            $mboxes[$key]['newcol'] = true;
        }
    }
    $t->set('mboxes', array_values($mboxes));
}

$title = _("Message Search");
Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('stripe.js', 'horde', true);
Horde::addScriptFile('search.js', 'imp', true);
require IMP_TEMPLATES . '/common-header.inc';
IMP::menu();
IMP::status();

Horde::addInlineScript(array(
    'ImpSearch.search_date = ' . Horde_Serialize::serialize(array('m' => date('m'), 'd' => date('d'), 'y' => date('Y')), Horde_Serialize::JSON, $charset),
    'ImpSearch.not_search = ' . intval(empty($search['mbox'])),
    'ImpSearch.inverse_sub = ' . intval($t->get('inverse_subscribe')),
));
echo $t->fetch(IMP_TEMPLATES . '/search/search.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
