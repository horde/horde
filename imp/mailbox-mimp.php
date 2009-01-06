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
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/lib/base.php';

/* Determine if mailbox is readonly. */
$readonly = $imp_imap->isReadOnly($imp_mbox['mailbox']);

/* Set the current time zone. */
NLS::setTimeZone();

/* Run through the action handlers */
$actionID = Util::getFormData('a');
switch ($actionID) {
// 'm' = message missing
case 'm':
    $notification->push(_("There was an error viewing the requested message."), 'horde.error');
    break;

// 'e' = expunge mailbox
case 'e':
    if (!$readonly) {
        $imp_message = &IMP_Message::singleton();
        $imp_message->expungeMailbox(array($imp_mbox['mailbox'] => 1));
    }
    break;

// 'c' = change sort
case 'c':
    IMP::setSort(Util::getFormData('sb'), Util::getFormData('sd'));
    break;
}

/* Initialize the user's identities. */
require_once 'Horde/Identity.php';
$identity = &Identity::singleton(array('imp', 'imp'));

/* Get the base URL for this page. */
$mailbox_url = IMP::generateIMPUrl('mailbox-mimp.php', $imp_mbox['mailbox']);

/* Build the list of messages in the mailbox. */
$imp_mailbox = &IMP_Mailbox::singleton($imp_mbox['mailbox']);
$pageOb = $imp_mailbox->buildMailboxPage(Util::getFormData('p'), Util::getFormData('s'));

/* Generate page links. */
$pages_first = $pages_prev = $pages_last = $pages_next = null;
if ($pageOb['page'] != 1) {
    $pages_first = new Horde_Mobile_link(_("First Page"), Util::addParameter($mailbox_url, 'p', 1));
    $pages_prev = new Horde_Mobile_link(_("Previous Page"), Util::addParameter($mailbox_url, 'p', $pageOb['page'] - 1));
}
if ($pageOb['page'] != $pageOb['pagecount']) {
    $pages_next = new Horde_Mobile_link(_("Next Page"), Util::addParameter($mailbox_url, 'p', $pageOb['page'] + 1));
    $pages_last = new Horde_Mobile_link(_("Last Page"), Util::addParameter($mailbox_url, 'p', $pageOb['pagecount']));
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
$mbox_info = $imp_mailbox->getMailboxArray(range($pageOb['begin'], $pageOb['end']));

/* Get thread information. */
$threadob = ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD)
    ? $imp_mailbox->getThreadOb()
    : null;

reset($mbox_info);
while (list(,$ob) = each($mbox_info['overview'])) {
    /* Initialize the header fields. */
    $msg = array(
        'number' => $ob['seq'],
        'status' => ''
    );

    /* Format the from header. */
    $getfrom = $imp_ui->getFrom($ob['envelope']);
    $msg['from'] = $getfrom['from'];
    if (String::length($msg['from']) > $conf['mimp']['mailbox']['max_from_chars']) {
        $msg['from'] = String::substr($msg['from'], 0, $conf['mimp']['mailbox']['max_from_chars']) . '...';
    }

    $msg['subject'] = $imp_ui->getSubject($ob['envelope']['subject']);

    if (!is_null($threadob) && ($threadob->getThreadIndent($ob['uid']) - 1)) {
        $msg['subject'] = '>> ' . ltrim($msg['subject']);
    }

    if (String::length($msg['subject']) > $conf['mimp']['mailbox']['max_subj_chars']) {
        $msg['subject'] = String::substr($msg['subject'], 0, $conf['mimp']['mailbox']['max_subj_chars']) . '...';
    }

    /* Generate the target link. */
    $target = IMP::generateIMPUrl('message-mimp.php', $imp_mbox['mailbox'], $ob['uid'], $ob['mailbox']);

    /* Get flag information. */
    if ($_SESSION['imp']['protocol'] != 'pop') {
        $to_ob = Horde_Mime_Address::getAddressesFromObject($ob['envelope']['to']);
        if (!empty($to_ob) && $identity->hasAddress($to_ob[0]['inner'])) {
            $msg['status'] .= '+';
        }
        if (!in_array('\\seen', $ob['flags'])) {
            $msg['status'] .= 'N';
        }
        if (in_array('\\answered', $ob['flags'])) {
            $msg['status'] .= 'r';
        }
        if (in_array('\\draft', $ob['flags'])) {
            $target = IMP::composeLink(array(), array('a' => 'd', 'thismailbox' => $imp_mbox['mailbox'], 'index' => $ob['uid'], 'bodypart' => 1));
        }
        if (in_array('\\flagged', $ob['flags'])) {
            $msg['status'] .= 'I';
        }
        if (in_array('\\deleted', $ob['flags'])) {
            $msg['status'] .= 'D';
        }

        /* Support for the pseudo-standard '$Forwarded' flag. */
        if (in_array('$forwarded', $ob['flags'])) {
            $msg['status'] .= 'F';
        }
    }

    $msg['target'] = $target;
    $msgs[] = $msg;
}

$mailbox = Util::addParameter($mailbox_url, 'p', $pageOb['page']);
$items = array($mailbox => _("Refresh"));

/* Determine if we are going to show the Purge Deleted link. */
if (!$readonly &&
    !$prefs->getValue('use_trash') &&
    !$imp_search->isVINBOXFolder()) {
    $items[Util::addParameter($mailbox, array('a' => 'e'))] = _("Purge Deleted");
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
        if (($key == Horde_Imap_Client::SORT_SUBJECT) && IMP::threadSortAvailable($mailbox)) {
            if (is_null($threadob)) {
                $items[Util::addParameter($mailbox, array('a' => 'c', 'sb' => Horde_Imap_Client::SORT_THREAD, 'sd' => $sortdir))] = _("Sort by Thread");
            } else {
                $sortkey = Horde_Imap_Client::SORT_THREAD;
                $items[Util::addParameter($mailbox, array('a' => 'c', 'sb' => Horde_Imap_Client::SORT_SUBJECT, 'sd' => $sortdir))] = _("Do Not Sort by Thread");
            }
        }
        if ($sortpref['by'] == $key) {
            $val = '*' . $val;
            $sortdir = !$sortdir;
        }
        $sort[$key] = new Horde_Mobile_link($val, Util::addParameter($mailbox, array('a' => 'c', 'sb' => $sortkey, 'sd' => $sortdir)));
    }
}

/* Create mailbox menu. */
$menu = new Horde_Mobile_card('o', _("Menu"));
$mset = &$menu->add(new Horde_Mobile_linkset());

foreach ($items as $link => $label) {
    $mset->add(new Horde_Mobile_link($label, $link));
}

$nav = array('pages_first', 'pages_prev', 'pages_next', 'pages_last');
foreach ($nav as $n) {
    if (Util::nonInputVar($n)) {
        $mset->add($$n);
    }
}

MIMP::addMIMPMenu($mset, 'mailbox');
require IMP_TEMPLATES . '/mailbox/mailbox-mimp.inc';
