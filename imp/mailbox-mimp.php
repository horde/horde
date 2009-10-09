<?php
/**
 * Minimalist mailbox display page.
 *
 * URL Parameters:
 *   'a' = (string) actionID
 *   'p' = (integer) page
 *   's' = (integer) start
 *   'sb' = (integer) change sort: by
 *   'sd' = (integer) change sort: dir
 *   'search' = (sring) The search string
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

/* Determine if mailbox is readonly. */
$readonly = $imp_imap->isReadOnly($imp_mbox['mailbox']);

/* Set the current time zone. */
Horde_Nls::setTimeZone();

/* Run through the action handlers */
$actionID = Horde_Util::getFormData('a');
switch ($actionID) {
// 'm' = message missing
case 'm':
    $notification->push(_("There was an error viewing the requested message."), 'horde.error');
    break;

// 'e' = expunge mailbox
case 'e':
    if (!$readonly) {
        $imp_message = IMP_Message::singleton();
        $imp_message->expungeMailbox(array($imp_mbox['mailbox'] => 1));
    }
    break;

// 'c' = change sort
case 'c':
    IMP::setSort(Horde_Util::getFormData('sb'), Horde_Util::getFormData('sd'));
    break;

// 's' = search
case 's':
    require IMP_TEMPLATES . '/mailbox/search-mimp.inc';
    exit;

// 'rs' = run search
case 'rs':
    $search_query = Horde_Util::getFormData('search');
    if (!empty($search_query) &&
        ($_SESSION['imp']['protocol'] == 'imap')) {
        $query = new Horde_Imap_Client_Search_Query();
        $query->text($search_query, false);

        /* Create the search query and reset the global $imp_mbox variable. */
        $sq = $imp_search->createSearchQuery($query, array(Horde_Util::getFormData('mailbox')), array(), _("Search Results"));
        IMP::setCurrentMailboxInfo($imp_search->createSearchID($sq));

        /* Need to re-calculate the read-only value. */
        $readonly = $imp_imap->isReadOnly($imp_mbox['mailbox']);
    }
    break;
}

/* Get the base URL for this page. */
$mailbox_url = IMP::generateIMPUrl('mailbox-mimp.php', $imp_mbox['mailbox']);

/* Build the list of messages in the mailbox. */
$imp_mailbox = IMP_Mailbox::singleton($imp_mbox['mailbox']);
$pageOb = $imp_mailbox->buildMailboxPage(Horde_Util::getFormData('p'), Horde_Util::getFormData('s'));

/* Need Horde_Mobile init here for autoloading purposes. */
$mimp_render = new Horde_Mobile();

/* Generate page links. */
$pages_first = $pages_prev = $pages_last = $pages_next = null;
if ($pageOb['page'] != 1) {
    $pages_first = new Horde_Mobile_link(_("First Page"), Horde_Util::addParameter($mailbox_url, 'p', 1));
    $pages_prev = new Horde_Mobile_link(_("Previous Page"), Horde_Util::addParameter($mailbox_url, 'p', $pageOb['page'] - 1));
}
if ($pageOb['page'] != $pageOb['pagecount']) {
    $pages_next = new Horde_Mobile_link(_("Next Page"), Horde_Util::addParameter($mailbox_url, 'p', $pageOb['page'] + 1));
    $pages_last = new Horde_Mobile_link(_("Last Page"), Horde_Util::addParameter($mailbox_url, 'p', $pageOb['pagecount']));
}

/* Generate mailbox summary string. */
$title = IMP::getLabel($imp_mbox['mailbox']);
$mimp_render->set('title', $title);
if ($pageOb['msgcount']) {
    $msgcount = $pageOb['msgcount'];
    $unseen = $imp_mailbox->unseenMessages(true);
}

$curr_time = time();
$curr_time -= $curr_time % 60;
$msgs = array();
$sortpref = IMP::getSort($imp_mbox['mailbox']);

$imp_ui = new IMP_UI_Mailbox($imp_mbox['mailbox']);

/* Build the array of message information. */
$mbox_info = $imp_mailbox->getMailboxArray(range($pageOb['begin'], $pageOb['end']), array('headers' => array('x-priority')));

/* Get thread information. */
if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
    $imp_thread = new IMP_Imap_Thread($imp_mailbox->getThreadOb());
    $threadtree = $imp_thread->getThreadTextTree(reset($mbox_info['uids']), $sortpref['dir']);
} else {
    $imp_thread = null;
    $threadtree = array();
}

while (list(,$ob) = each($mbox_info['overview'])) {
    /* Initialize the header fields. */
    $msg = array(
        'status' => '',
        'subject' => $imp_ui->getSubject($ob['envelope']['subject'])
    );

    /* Format the from header. */
    $getfrom = $imp_ui->getFrom($ob['envelope']);
    $msg['from'] = $getfrom['from'];
    if (Horde_String::length($msg['from']) > $conf['mimp']['mailbox']['max_from_chars']) {
        $msg['from'] = Horde_String::substr($msg['from'], 0, $conf['mimp']['mailbox']['max_from_chars']) . '...';
    }

    /* Get flag information. */
    $imp_flags = IMP_Imap_Flags::singleton();
    $flag_parse = $imp_flags->parse(array(
        'flags' => $ob['flags'],
        'personal' => Horde_Mime_Address::getAddressesFromObject($ob['envelope']['to']),
        'priority' => $ob['headers']->getValue('x-priority')
    ));

    foreach ($flag_parse as $val) {
        if (isset($val['abbrev'])) {
            $msg['status'] .= $val['abbrev'];
        } elseif ($val['type'] == 'imapp') {
            $msg['subject'] = '*' .
                ((Horde_String::length($val['label']) > 8)
                     ? Horde_String::substr($val['label'], 0, 5) . '...'
                     : $val['label']
                ) .
                '* ' . $msg['subject'];
        }
    }

    if (!empty($threadtree[$ob['uid']])) {
        $msg['subject'] = $threadtree[$ob['uid']] . trim($msg['subject']);
    }

    if (Horde_String::length($msg['subject']) > $conf['mimp']['mailbox']['max_subj_chars']) {
        $msg['subject'] = Horde_String::substr($msg['subject'], 0, $conf['mimp']['mailbox']['max_subj_chars']) . '...';
    }

    /* Generate the target link. */
    $msg['target'] = in_array('\\draft', $ob['flags'])
        ? IMP::composeLink(array(), array('a' => 'd', 'thismailbox' => $imp_mbox['mailbox'], 'index' => $ob['uid'], 'bodypart' => 1))
         : IMP::generateIMPUrl('message-mimp.php', $imp_mbox['mailbox'], $ob['uid'], $ob['mailbox']);

    $msgs[] = $msg;
}

$mailbox = Horde_Util::addParameter($mailbox_url, 'p', $pageOb['page']);
$items = array($mailbox => _("Refresh"));
$search_mbox = $imp_search->isSearchMbox($imp_mbox['mailbox']);

/* Determine if we are going to show the Purge Deleted link. */
if (!$readonly &&
    !$prefs->getValue('use_trash') &&
    !$imp_search->isVINBOXFolder()) {
    $items[Horde_Util::addParameter($mailbox, array('a' => 'e'))] = _("Purge Deleted");
}

/* Create sorting links. */
$sort = array();
$sort_list = array(
    Horde_Imap_Client::SORT_ARRIVAL => '#',
    Horde_Imap_Client::SORT_FROM => _("From"),
    Horde_Imap_Client::SORT_SUBJECT => _("Subject")
);
foreach ($sort_list as $key => $val) {
    if ($sortpref['limit']) {
        $sort[$key] = (($key == Horde_Imap_Client::SORT_ARRIVAL) ? '*' : '') . $val;
    } else {
        $sortdir = $sortpref['dir'];
        $sortkey = $key;
        if (($key == Horde_Imap_Client::SORT_SUBJECT) &&
            IMP::threadSortAvailable($mailbox) &&
            !$search_mbox) {
            if (is_null($imp_thread)) {
                $items[Horde_Util::addParameter($mailbox, array('a' => 'c', 'sb' => Horde_Imap_Client::SORT_THREAD, 'sd' => $sortdir))] = _("Sort by Thread");
            } else {
                $sortkey = Horde_Imap_Client::SORT_THREAD;
                $items[Horde_Util::addParameter($mailbox, array('a' => 'c', 'sb' => Horde_Imap_Client::SORT_SUBJECT, 'sd' => $sortdir))] = _("Do Not Sort by Thread");
            }
        }
        if ($sortpref['by'] == $key) {
            $val = '*' . $val;
            $sortdir = !$sortdir;
        }
        $sort[$key] = new Horde_Mobile_link($val, Horde_Util::addParameter($mailbox, array('a' => 'c', 'sb' => $sortkey, 'sd' => $sortdir)));
    }
}

/* Add search link. */
if (!$search_mbox &&
    ($_SESSION['imp']['protocol'] == 'imap')) {
    $items[Horde_Util::addParameter($mailbox_url, array('a' => 's'))] = _("Search");
}

/* Create mailbox menu. */
$menu = new Horde_Mobile_card('o', _("Menu"));
$mset = &$menu->add(new Horde_Mobile_linkset());

foreach ($items as $link => $label) {
    $mset->add(new Horde_Mobile_link($label, $link));
}

$nav = array('pages_first', 'pages_prev', 'pages_next', 'pages_last');
foreach ($nav as $n) {
    if (Horde_Util::nonInputVar($n)) {
        $mset->add($$n);
    }
}

IMP_Mimp::addMIMPMenu($mset, 'mailbox');
require IMP_TEMPLATES . '/mailbox/mailbox-mimp.inc';
