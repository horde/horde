<?php
/**
 * Mobile (MIMP) mailbox display page.
 *
 * URL Parameters:
 *   'a' = (string) actionID
 *   'p' = (integer) page
 *   's' = (integer) start
 *   'sb' = (integer) change sort: by
 *   'sd' = (integer) change sort: dir
 *   'search' = (sring) The search string
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
Horde_Registry::appInit('imp', array(
    'impmode' => 'mimp',
    'timezone' => true
));

$imp_search = $injector->getInstance('IMP_Search');
$imp_ui_mimp = $injector->getInstance('IMP_Ui_Mimp');
$vars = Horde_Variables::getDefaultVariables();

/* Initialize Horde_Template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);

/* Determine if mailbox is readonly. */
$imp_imap = $injector->getInstance('IMP_Imap')->getOb();
$readonly = $imp_imap->isReadOnly(IMP::$mailbox);

/* Get the base URL for this page. */
$mailbox_url = IMP::generateIMPUrl('mailbox-mimp.php', IMP::$mailbox);

/* Perform message actions (via advanced UI). */
switch ($vars->checkbox) {
// 'd' = delete message
// 'u' = undelete message
case 'd':
case 'u':
    if ($readonly) {
        break;
    }

    $imp_message = $injector->getInstance('IMP_Message');

    if ($vars->checkbox == 'd') {
        try {
            Horde::checkRequestToken('imp.message-mimp', $vars->mt);
            $imp_message->delete(new IMP_Indices($vars->indices));
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
    } else {
        $imp_message->undelete(new IMP_Indices($vars->indices));
    }
    break;

// 'rs' = report spam
// 'ri' = report innocent
case 'rs':
case 'ri':
    IMP_Spam::reportSpam(new IMP_Indices($vars->indices), $vars->a == 'rs' ? 'spam' : 'innocent');
    break;
}

/* Run through the action handlers */
switch ($vars->a) {
// 'm' = message missing
case 'm':
    $notification->push(_("There was an error viewing the requested message."), 'horde.error');
    break;

// 'e' = expunge mailbox
case 'e':
    if (!$readonly) {
        $injector->getInstance('IMP_Message')->expungeMailbox(array(IMP::$mailbox => 1));
    }
    break;

// 'c' = change sort
case 'c':
    IMP::setSort($vars->sb, $vars->sd);
    break;

// 's' = search
case 's':
    $title = sprintf(_("Search %s"), IMP::getLabel(IMP::$mailbox));

    $t->set('mailbox', IMP::$mailbox);
    $t->set('menu', $imp_ui_mimp->getMenu('search'));
    $t->set('title', $title);
    $t->set('url', $mailbox_url);

    require_once IMP_TEMPLATES . '/common-header.inc';
    IMP::status();
    echo $t->fetch(IMP_TEMPLATES . '/mimp/mailbox/search.html');
    exit;

// 'rs' = run search
case 'rs':
    if (!empty($vars->search) &&
        ($_SESSION['imp']['protocol'] == 'imap')) {
        /* Create the search query and reset the global mailbox variable. */
        $q_ob = $imp_search->createQuery(
            array(new IMP_Search_Element_Text($vars->search, false)),
            array(IMP::$mailbox)
        );
        IMP::setCurrentMailboxInfo(strval($q_ob));

        /* Need to re-calculate these values. */
        $readonly = $imp_imap->isReadOnly(IMP::$mailbox);
        $mailbox_url = IMP::generateIMPUrl('mailbox-mimp.php', IMP::$mailbox);
    }
    break;
}

/* Build the list of messages in the mailbox. */
$imp_mailbox = $injector->getInstance('IMP_Mailbox_List')->getList(IMP::$mailbox);
$pageOb = $imp_mailbox->buildMailboxPage($vars->p, $vars->s);

/* Generate page title. */
$title = IMP::getLabel(IMP::$mailbox);

/* Modify title for display on page. */
if ($pageOb['msgcount']) {
    $unseen = $imp_mailbox->unseenMessages(Horde_Imap_Client::SORT_RESULTS_COUNT);
    $title .= ' (' . $unseen . '/' .  $pageOb['msgcount'] . ')';
}
if ($pageOb['pagecount'] > 1) {
    $title .= ' - ' . $pageOb['page'] . ' ' . _("of") . ' ' . $pageOb['pagecount'];
}
if ($readonly) {
    $title .= ' [' . _("Read-Only") . ']';
}
$t->set('title', $title);

$curr_time = time();
$curr_time -= $curr_time % 60;
$msgs = array();
$sortpref = IMP::getSort(IMP::$mailbox);

$imp_ui = new IMP_Ui_Mailbox(IMP::$mailbox);

/* Build the array of message information. */
$mbox_info = $imp_mailbox->getMailboxArray(range($pageOb['begin'], $pageOb['end']), array('headers' => true));

/* Get thread information. */
if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
    $imp_thread = new IMP_Imap_Thread($imp_mailbox->getThreadOb());
    $threadtree = $imp_thread->getThreadTextTree(reset($mbox_info['uids']->indices()), $sortpref['dir']);
} else {
    $imp_thread = null;
    $threadtree = array();
}

while (list(,$ob) = each($mbox_info['overview'])) {
    /* Initialize the header fields. */
    $msg = array(
        'status' => '',
        'subject' => trim($imp_ui->getSubject($ob['envelope']['subject'])),
        'uid' => strval(new IMP_Indices($ob['mailbox'], $ob['uid']))
    );

    /* Format the from header. */
    $getfrom = $imp_ui->getFrom($ob['envelope']);
    $msg['from'] = Horde_String::truncate($getfrom['from'], 50);

    /* Get flag information. */
    $flag_parse = $injector->getInstance('IMP_Imap_Flags')->parse(array(
        'flags' => $ob['flags'],
        'personal' => Horde_Mime_Address::getAddressesFromObject($ob['envelope']['to'], array('charset' => $registry->getCharset())),
        'priority' => $ob['headers']
    ));

    foreach ($flag_parse as $val) {
        if (isset($val['abbrev'])) {
            $msg['status'] .= $val['abbrev'];
        } elseif ($val['type'] == 'imapp') {
            $msg['subject'] = '*' . Horde_String::truncate($val['label'], 8) . '* ' . $msg['subject'];
        }
    }

    $msg['subject'] = Horde_String::truncate($msg['subject'], 50);

    /* Thread display. */
    $msg['thread'] = empty($threadtree[$ob['uid']])
        ? ''
        : str_replace(' ', '&nbsp;', $threadtree[$ob['uid']]);

    /* Generate the target link. */
    $msg['target'] = in_array('\\draft', $ob['flags'])
        ? IMP::composeLink(array(), array('a' => 'd', 'thismailbox' => IMP::$mailbox, 'uid' => $ob['uid'], 'bodypart' => 1))
        : IMP::generateIMPUrl('message-mimp.php', IMP::$mailbox, $ob['uid'], $ob['mailbox']);

    $msgs[] = $msg;
}
$t->set('msgs', $msgs);

$mailbox = $mailbox_url->copy()->add('p', $pageOb['page']);
$menu = array(array(_("Refresh"), $mailbox));
$search_mbox = $imp_search->isSearchMbox(IMP::$mailbox);

/* Determine if we are going to show the Purge Deleted link. */
if (!$readonly &&
    !$prefs->getValue('use_trash') &&
    !$imp_search->isVinbox(IMP::$mailbox)) {
    $menu[] = array(_("Purge Deleted"), $mailbox->copy()->add('a', 'e'));
}

/* Create header links. */
$hdr_list = array(
    'hdr_from' => array(_("From"), Horde_Imap_Client::SORT_FROM),
    'hdr_subject' => array(_("Subject"), Horde_Imap_Client::SORT_SUBJECT),
    'hdr_thread' => array(_("Thread"), Horde_Imap_Client::SORT_THREAD)
);
foreach ($hdr_list as $key => $val) {
    $sort_link = $mailbox->copy()->add(array('a' => 'c', 'sb' => $val[1]));
    if ($sortpref['by'] == $val[1]) {
        $t->set($key, $val[0] . ' <a href="' . strval($sort_link->add('sd', intval(!$sortpref['dir']))) . '">' . ($sortpref['dir'] ? '^' : 'v') . '</a>');
    } else {
        $t->set($key, '<a href="' . $sort_link . '">' . $val[0] . '</a>');
    }
}

/* Add thread header entry. */
if (!$search_mbox && IMP::threadSortAvailable(IMP::$mailbox)) {
    if (is_null($imp_thread)) {
        $t->set('hdr_subject_minor', $t->get('hdr_thread'));
    } else {
        $t->set('hdr_subject_minor', $t->get('hdr_subject'));
        $t->set('hdr_subject', $t->get('hdr_thread'));
    }
}

/* Add search link. */
if ($_SESSION['imp']['protocol'] == 'imap') {
    if ($search_mbox) {
        $orig_mbox = reset($imp_search[IMP::$mailbox]->mboxes);
        $menu[] = array(sprintf(_("New Search in %s"), IMP::getLabel($orig_mbox)), IMP::generateIMPUrl('mailbox-mimp.php', $orig_mbox)->add('a', 's'));
    } else {
        $menu[] = array(_("Search"), $mailbox_url->copy()->add('a', 's'));
    }
}

/* Generate page links. */
if ($pageOb['page'] != 1) {
    $menu[] = array(_("First Page"), $mailbox_url->copy()->add('p', 1));
    $menu[] = array(_("Previous Page"), $mailbox_url->copy()->add('p', $pageOb['page'] - 1));
}
if ($pageOb['page'] != $pageOb['pagecount']) {
    $menu[] = array(_("Next Page"), $mailbox_url->copy()->add('p', $pageOb['page'] + 1));
    $menu[] = array(_("Last Page"), $mailbox_url->copy()->add('p', $pageOb['pagecount']));
}

$t->set('menu', $imp_ui_mimp->getMenu('mailbox', $menu));

/* Activate advanced checkbox UI? */
try {
    if (Horde::callHook('mimp_advanced', array('checkbox'), 'imp')) {
        $t->set('checkbox', $mailbox_url->copy()->add('p', $pageOb['page']));
        $t->set('forminput', Horde_Util::formInput());
        $t->set('mt', Horde::getRequestToken('imp.message-mimp'));
    }
} catch (Horde_Exception_HookNotSet $e) {}

require_once IMP_TEMPLATES . '/common-header.inc';
IMP::status();
echo $t->fetch(IMP_TEMPLATES . '/mimp/mailbox/mailbox.html');
