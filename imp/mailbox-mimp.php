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

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'impmode' => 'mimp',
    'timezone' => true
));

$imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
$imp_search = $injector->getInstance('IMP_Search');
$imp_ui_mimp = $injector->getInstance('IMP_Ui_Mimp');
$vars = Horde_Variables::getDefaultVariables();

/* Initialize Horde_Template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);

/* Determine if mailbox is readonly. */
$readonly = IMP::mailbox()->readonly;

/* Get the base URL for this page. */
$mailbox_url = IMP::mailbox()->url('mailbox-mimp.php');

/* Perform message actions (via advanced UI). */
switch ($vars->checkbox) {
// 'd' = delete message
// 'u' = undelete message
case 'd':
case 'u':
    $imp_message = $injector->getInstance('IMP_Message');

    if ($vars->checkbox == 'd') {
        try {
            $injector->getInstance('Horde_Token')->validate($vars->mt, 'imp.message-mimp');
            $imp_message->delete(new IMP_Indices($vars->indices));
        } catch (Horde_Token_Exception $e) {
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
    IMP_Spam::reportSpam(new IMP_Indices($vars->indices), $vars->a == 'rs' ? 'spam' : 'notspam');
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
    $injector->getInstance('IMP_Message')->expungeMailbox(array(strval(IMP::mailbox()) => 1));
    break;

// 'c' = change sort
case 'c':
    IMP::mailbox()->setSort($vars->sb, $vars->sd);
    break;

// 's' = search
case 's':
    $title = sprintf(_("Search %s"), IMP::mailbox()->label);

    $t->set('mailbox', IMP::mailbox()->form_to);
    $t->set('menu', $imp_ui_mimp->getMenu('search'));
    $t->set('title', $title);
    $t->set('url', $mailbox_url);

    IMP::header($title);
    IMP::status();
    echo $t->fetch(IMP_TEMPLATES . '/mimp/mailbox/search.html');
    exit;

// 'ds' = do search
case 'ds':
    if (!empty($vars->search) && $imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
        /* Create the search query and reset the global mailbox variable. */
        $q_ob = $imp_search->createQuery(array(new IMP_Search_Element_Text($vars->search, false)), array(
            'mboxes' => array(IMP::mailbox())
        ));
        IMP::setMailboxInfo($q_ob);

        /* Need to re-calculate these values. */
        $readonly = IMP::mailbox()->readonly;
        $mailbox_url = IMP::mailbox()->url('mailbox-mimp.php');
    }
    break;
}

/* Build the list of messages in the mailbox. */
$imp_mailbox = IMP::mailbox()->getListOb();
$pageOb = $imp_mailbox->buildMailboxPage($vars->p, $vars->s);

/* Generate page title. */
$title = IMP::mailbox()->label;

/* Modify title for display on page. */
if ($pageOb['msgcount']) {
    $title .= ' (';
    if ($imp_imap->access(IMP_Imap::ACCESS_UNSEEN)) {
        $unseen = $imp_mailbox->unseenMessages(Horde_Imap_Client::SEARCH_RESULTS_COUNT);
        $title .= sprintf(_("%d unseen"), $unseen) . '/';
    }
    $title .= sprintf(_("%d total"), $pageOb['msgcount']) . ')';
}
if ($pageOb['pagecount'] > 1) {
    $title .= ' - ' . sprintf(_("%d of %d"), $pageOb['page'], $pageOb['pagecount']);
}
if ($readonly) {
    $title .= ' [' . _("Read-Only") . ']';
}
$t->set('title', $title);

$curr_time = time();
$curr_time -= $curr_time % 60;
$msgs = array();
$sortpref = IMP::mailbox()->getSort();
$thread_sort = ($sortpref->sortby == Horde_Imap_Client::SORT_THREAD);

$imp_ui = new IMP_Ui_Mailbox(IMP::mailbox());

/* Build the array of message information. */
$mbox_info = $imp_mailbox->getMailboxArray(range($pageOb['begin'], $pageOb['end']), array('headers' => true));

while (list(,$ob) = each($mbox_info['overview'])) {
    /* Initialize the header fields. */
    $msg = array(
        'status' => '',
        'subject' => trim($imp_ui->getSubject($ob['envelope']->subject)),
        'uid' => strval(new IMP_Indices($ob['mailbox'], $ob['uid']))
    );

    /* Format the from header. */
    $getfrom = $imp_ui->getFrom($ob['envelope']);
    $msg['from'] = htmlspecialchars(Horde_String::truncate($getfrom['from'], 50), ENT_QUOTES, 'UTF-8');

    /* Get flag information. */
    $flag_parse = $injector->getInstance('IMP_Flags')->parse(array(
        'flags' => $ob['flags'],
        'headers' => $ob['headers'],
        'personal' => $ob['envelope']->to
    ));

    foreach ($flag_parse as $val) {
        if ($abbrev = $val->abbreviation) {
            $msg['status'] .= $abbrev;
        } elseif ($val instanceof IMP_Flag_User) {
            $msg['subject'] = '*' . Horde_String::truncate($val->label, 8) . '* ' . $msg['subject'];
        }
    }

    $msg['subject'] = Horde_String::truncate($msg['subject'], 50);

    /* Thread display. */
    if ($sort_thread) {
        $t_ob =  $imp_mailbox[$ob['idx']]['t'];
        $msg['thread'] = str_replace(' ', '&nsbp;', ($sortpref->sortdir ? $t_ob->reverse_txt : $t_ob->txt));
    } else {
        $msg['thread'] = '';
    }

    /* Generate the target link. */
    if (IMP::mailbox()->templates) {
        $compose = 't';
    } elseif (IMP::mailbox()->draft ||
              in_array(Horde_Imap_Client::FLAG_DRAFT, $ob['flags'])) {
        $compose = 'd';
    } else {
        $msg['target'] = IMP::mailbox()->url('message-mimp.php', $ob['uid'], $ob['mailbox']);
    }

    if (!isset($msg['target'])) {
        $msg['target'] = IMP::composeLink(array(), array(
            'a' => $compose,
            'thismailbox' => IMP::mailbox(),
            'uid' => $ob['uid'],
            'bodypart' => 1
        ));
    }

    $msgs[] = $msg;
}
$t->set('msgs', $msgs);

$mailbox = $mailbox_url->copy()->add('p', $pageOb['page']);
$menu = array(array(_("Refresh"), $mailbox));
$search_mbox = $imp_search->isSearchMbox(IMP::mailbox());

/* Determine if we are going to show the Purge Deleted link. */
if (!$prefs->getValue('use_trash') &&
    !$imp_search->isVinbox(IMP::mailbox()) &&
    IMP::mailbox()->access_expunge) {
    $menu[] = array(_("Purge Deleted"), $mailbox->copy()->add('a', 'e'));
}

/* Create header links. */
$hdr_list = array(
    'hdr_from' => array(_("From"), Horde_Imap_Client::SORT_FROM),
    'hdr_subject' => array(_("Subject"), Horde_Imap_Client::SORT_SUBJECT),
    'hdr_thread' => array(_("Thread"), Horde_Imap_Client::SORT_THREAD)
);
foreach ($hdr_list as $key => $val) {
    if ($sortpref->sortby == $val[1]) {
        $sort_link = $val[0];
        if ($sortpref->sortdir_locked) {
            $sort_link .= ' <a href="' . $mailbox->copy()->add(array('a' => 'c', 'sb' => $val[1], 'sd' => intval(!$sortpref->sortdir))) . '">' . ($sortpref->sortdir ? '^' : 'v') . '</a>';
        }
    } else {
        $sort_link = $sortpref->sortby_locked
            ? $val[0]
            : '<a href="' . $mailbox->copy()->add(array('a' => 'c', 'sb' => $val[1])) . '">' . $val[0] . '</a>';
    }
    $t->set($key, strval($val[0]));
}

/* Add thread header entry. */
if (!$sortpref->sortby_locked && IMP::mailbox()->access_sortthread) {
    if ($sortpref->sortby == Horde_Imap_Client::SORT_THREAD) {
        $t->set('hdr_subject_minor', $t->get('hdr_thread'));
    } else {
        $t->set('hdr_subject_minor', $t->get('hdr_subject'));
        $t->set('hdr_subject', $t->get('hdr_thread'));
    }
}

/* Add search link. */
if ($imp_imap->access(IMP_Imap::ACCESS_SEARCH)) {
    if ($search_mbox) {
        $mboxes = $imp_search[strval(IMP::mailbox())]->mboxes;
        $orig_mbox = IMP_Mailbox::get(reset($mboxes));
        $menu[] = array(sprintf(_("New Search in %s"), $orig_mbox->label), $orig_mbox->url('mailbox-mimp.php')->add('a', 's'));
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
        $t->set('delete', IMP::mailbox()->access_deletemsgs);
        $t->set('forminput', Horde_Util::formInput());
        $t->set('mt', $injector->getInstance('Horde_Token')->get('imp.message-mimp'));
    }
} catch (Horde_Exception_HookNotSet $e) {}

IMP::header($title);
IMP::status();
echo $t->fetch(IMP_TEMPLATES . '/mimp/mailbox/mailbox.html');
