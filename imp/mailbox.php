<?php
/**
 * Standard (imp) mailbox display page.
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

function _outputSummaries($msgs)
{
    static $template;

    /* Allow user to alter template array. */
    try {
        $msgs = Horde::callHook('mailboxarray', array($msgs, 'imp'), 'imp');
    } catch (Horde_Exception_HookNotSet $e) {}

    if (!isset($template)) {
        $template = $GLOBALS['injector']->createInstance('Horde_Template');
        $template->setOption('gettext', true);
    }

    $template->set('messages', $msgs, true);
    echo $template->fetch(IMP_TEMPLATES . '/imp/mailbox/mailbox.html');
}


require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp');

$registry->setTimeZone();

/* Call the mailbox redirection hook, if requested. */
try {
    $redirect = Horde::callHook('mbox_redirect', array(IMP::$mailbox), 'imp');
    if (!empty($redirect)) {
        Horde::url($redirect, true)->redirect();
    }
} catch (Horde_Exception_HookNotSet $e) {}

/* Is this a search mailbox? */
$imp_search = $injector->getInstance('IMP_Search');
$search_mbox = $imp_search->isSearchMbox(IMP::$mailbox);
$vars = Horde_Variables::getDefaultVariables();
$vfolder = $imp_search->isVFolder(IMP::$mailbox);

/* There is a chance that this page is loaded directly via message.php. If so,
 * don't re-include config files, and the following variables will already be
 * set: $actionID, $start. */
$mailbox_url = Horde::url('mailbox.php');
$mailbox_imp_url = IMP::generateIMPUrl('mailbox.php', IMP::$mailbox)->add('newmail', 1);
if (!Horde_Util::nonInputVar('from_message_page')) {
    $actionID = $vars->actionID;
    $start = $vars->start;
}

$do_filter = false;
$imp_flags = $injector->getInstance('IMP_Imap_Flags');
$imp_imap = $injector->getInstance('IMP_Imap')->getOb();
$indices = new IMP_Indices($vars->indices);

/* Run through the action handlers */
if ($actionID && ($actionID != 'message_missing')) {
    try {
        Horde::checkRequestToken('imp.mailbox', $vars->mailbox_token);
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $actionID = null;
    }
}

/* We know we are going to be exclusively dealing with this mailbox, so
 * select it on the IMAP server (saves some STATUS calls). Open R/W to clear
 * the RECENT flag. */
if (!$search_mbox) {
    try {
        $imp_imap->openMailbox(IMP::$mailbox, Horde_Imap_Client::OPEN_READWRITE);
    } catch (Horde_Imap_Client_Exception $e) {
        $actionID = null;
    }
}

/* Determine if mailbox is readonly. */
$readonly = $imp_imap->isReadOnly(IMP::$mailbox);
if ($readonly &&
    in_array($actionID, array('delete_messages', 'undelete_messages', 'move_messages', 'flag_messages', 'empty_mailbox', 'filter'))) {
    $actionID = null;
}

switch ($actionID) {
case 'change_sort':
    IMP::setSort($vars->sortby, $vars->sortdir);
    break;

case 'blacklist':
    $injector->getInstance('IMP_Filter')->blacklistMessage($indices);
    break;

case 'whitelist':
    $injector->getInstance('IMP_Filter')->whitelistMessage($indices);
    break;

case 'spam_report':
    IMP_Spam::reportSpam($indices, 'spam');
    break;

case 'notspam_report':
    IMP_Spam::reportSpam($indices, 'notspam');
    break;

case 'message_missing':
    $notification->push(_("Requested message not found."), 'horde.error');
    break;

case 'fwd_digest':
    if (count($indices)) {
        $options = array_merge(array(
            'actionID' => 'fwd_digest',
            'fwddigest' => strval($indices)
        ), IMP::getComposeArgs());

        if ($prefs->getValue('compose_popup')) {
            Horde::addInlineScript(array(
                Horde::popupJs(Horde::url('compose.php'), array('novoid' => true, 'params' => array_merge(array('popup' => 1), $options)))
            ), 'dom');
        } else {
            Horde::url('compose.php', true)->add($options)->redirect();
        }
    }
    break;

case 'delete_messages':
    $injector->getInstance('IMP_Message')->delete($indices);
    break;

case 'undelete_messages':
    $injector->getInstance('IMP_Message')->undelete($indices);
    break;

case 'move_messages':
case 'copy_messages':
    if (isset($vars->targetMbox) && count($indices)) {
        $targetMbox = IMP::formMbox($vars->targetMbox, false);
        if (!empty($vars->newMbox) && ($vars->newMbox == 1)) {
            $targetMbox = IMP::folderPref($targetMbox, true);
            $newMbox = true;
        } else {
            $targetMbox = $targetMbox;
            $newMbox = false;
        }
        $injector->getInstance('IMP_Message')->copy($targetMbox, ($actionID == 'move_messages') ? 'move' : 'copy', $indices, array('create' => $newMbox));
    }
    break;

case 'flag_messages':
    $flag = Horde_Util::getPost('flag');
    if ($flag && count($indices)) {
        $flag = $imp_flags->parseFormId($flag);
        $injector->getInstance('IMP_Message')->flag(array($flag['flag']), $indices, $flag['set']);
    }
    break;

case 'hide_deleted':
    $prefs->setValue('delhide', !$prefs->getValue('delhide'));
    IMP::hideDeletedMsgs(IMP::$mailbox, true);
    break;

case 'expunge_mailbox':
    $injector->getInstance('IMP_Message')->expungeMailbox(array(IMP::$mailbox => 1));
    break;

case 'filter':
    $do_filter = true;
    break;

case 'empty_mailbox':
    $injector->getInstance('IMP_Message')->emptyMailbox(array(IMP::$mailbox));
    break;

case 'view_messages':
    IMP::generateIMPUrl('thread.php', IMP::$mailbox, null, null, false)->add(array('mode' => 'msgview', 'msglist' => strval($indices)))->redirect();
}

/* Token to use in requests */
$mailbox_token = Horde::getRequestToken('imp.mailbox');

/* Deal with filter options. */
if (!$readonly && !empty($_SESSION['imp']['filteravail'])) {
    /* Only allow filter on display for INBOX. */
    if ((IMP::$mailbox == 'INBOX') &&
        $prefs->getValue('filter_on_display')) {
        $do_filter = true;
    } elseif ((IMP::$mailbox == 'INBOX') ||
              ($prefs->getValue('filter_any_mailbox') && !$search_mbox)) {
        $filter_url = $mailbox_imp_url->copy()->add(array(
            'actionID' => 'filter',
            'mailbox_token' => $mailbox_token
        ));
    }
}

/* Run filters now. */
if ($do_filter) {
    $injector->getInstance('IMP_Filter')->filter(IMP::$mailbox);
}

/* Generate folder options list. */
if ($conf['user']['allow_folders']) {
    $folder_options = IMP::flistSelect(array('heading' => _("Messages to"), 'new_folder' => true, 'inc_tasklists' => true, 'inc_notepads' => true));
}

/* Build the list of messages in the mailbox. */
$imp_mailbox = $injector->getInstance('IMP_Mailbox')->getOb(IMP::$mailbox);
$pageOb = $imp_mailbox->buildMailboxPage($vars->page, $start);
$show_preview = $prefs->getValue('preview_enabled');

$mbox_info = $imp_mailbox->getMailboxArray(range($pageOb['begin'], $pageOb['end']), array('preview' => $show_preview, 'headers' => true, 'structure' => $prefs->getValue('atc_flag')));

/* Determine sorting preferences. */
$sortpref = IMP::getSort(IMP::$mailbox);

/* Determine if we are going to show the Hide/Purge Deleted Message links. */
if (!$prefs->getValue('use_trash') &&
    !$imp_search->isVINBOXFolder(IMP::$mailbox)) {
    $showdelete = array('hide' => ($sortpref['by'] != Horde_Imap_Client::SORT_THREAD), 'purge' => true);
} else {
    $showdelete = array('hide' => false, 'purge' => false);
}
if ($showdelete['hide'] && !$prefs->isLocked('delhide')) {
    if ($prefs->getValue('delhide')) {
        $deleted_prompt = _("Show Deleted");
    } else {
        $deleted_prompt = _("Hide Deleted");
    }
}
if ($readonly) {
    $showdelete['purge'] = false;
}

/* Generate paging links. */
if ($pageOb['pagecount']) {
    $rtl = !empty($registry->nlsconfig['rtl'][$language]);
    if ($pageOb['page'] == 1) {
        $pages_first = Horde::img($rtl ? 'nav/last-grey.png' : 'nav/first-grey.png');
        $pages_prev = Horde::img($rtl ? 'nav/right-grey.png' : 'nav/left-grey.png');
    } else {
        $first_url = $mailbox_imp_url->copy()->add('page', 1);
        $pages_first = Horde::link($first_url, _("First Page")) . Horde::img($rtl ? 'nav/last.png' : 'nav/first.png', $rtl ? '>>' : '<<') . '</a>';
        $prev_url = $mailbox_imp_url->copy()->add('page', $pageOb['page'] - 1);
        $pages_prev = Horde::link($prev_url, _("Previous Page")) . Horde::img($rtl ? 'nav/right.png' : 'nav/left.png', $rtl ? '>' : '<') . '</a>';
    }

    if ($pageOb['page'] == $pageOb['pagecount']) {
        $pages_last = Horde::img($rtl ? 'nav/first-grey.png' : 'nav/last-grey.png');
        $pages_next = Horde::img($rtl ? 'nav/left-grey.png' : 'nav/right-grey.png');
    } else {
        $next_url = $mailbox_imp_url->copy()->add('page', $pageOb['page'] + 1);
        $pages_next = Horde::link($next_url, _("Next Page")) . Horde::img($rtl ? 'nav/left.png' : 'nav/right.png', $rtl ? '<' : '>') . '</a>';
        $last_url = $mailbox_imp_url->copy()->add('page', $pageOb['pagecount']);
        $pages_last = Horde::link($last_url, _("Last Page")) . Horde::img($rtl ? 'nav/first.png' : 'nav/last.png', $rtl ? '<<' : '>>') . '</a>';
    }
}

/* Generate RSS link. */
if (IMP::$mailbox == 'INBOX') {
    $rss_box = '';
} else {
    $rss_box = IMP::$mailbox;
    $ns_info = $imp_imap->getNamespace(IMP::$mailbox);
    if ($ns_info !== null) {
        if (!empty($ns_info['name']) &&
            $ns_info['type'] == 'personal' &&
            substr(IMP::$mailbox, 0, strlen($ns_info['name'])) == $ns_info['name']) {
            $rss_box = substr(IMP::$mailbox, strlen($ns_info['name']));
        }
        $rss_box = str_replace(rawurlencode($ns_info['delimiter']), '/', rawurlencode($ns_info['delimiter'] . $rss_box));
    } else {
        $rss_box = null;
    }
}

if (!is_null($rss_box)) {
    $rss_url = Horde::url('rss.php') . $rss_box;
}

/* If user wants the mailbox to be refreshed, set time here. */
$refresh_url = $mailbox_imp_url->copy()->add('page', $pageOb['page']);
if (isset($filter_url)) {
    $filter_url->add('page', $pageOb['page']);
}

/* Set the folder for the sort links. */
$sort_url = $mailbox_imp_url->copy()->add('sortdir', ($sortpref['dir']) ? 0 : 1);

/* Determine if we are showing previews. */
$preview_tooltip = $show_preview
    ? $prefs->getValue('preview_show_tooltip')
    : false;
if (!$preview_tooltip) {
    $strip_preview = $prefs->getValue('preview_strip_nl');
}

$unread = $imp_mailbox->unseenMessages(Horde_Imap_Client::SORT_RESULTS_COUNT);
$vtrash = $imp_search->isVTrashFolder(IMP::$mailbox)
    ? $imp_search->createSearchID($search_mbox)
    : null;

Horde::addInlineScript(array(
    'ImpMailbox.unread = ' . intval($unread)
));

/* Get the recent message count. */
$newmsgs = 0;
if ($prefs->getValue('nav_popup') || $prefs->getValue('nav_audio')) {
    $newmsgs = $imp_mailbox->newMessages(Horde_Imap_Client::SORT_RESULTS_COUNT);
}

$pagetitle = $title = IMP::getLabel(IMP::$mailbox);
$refresh_title = sprintf(_("_Refresh %s"), $title);
$refresh_ak = Horde::getAccessKey($refresh_title);
$refresh_title = Horde::stripAccessKey($refresh_title);
if (!empty($refresh_ak)) {
    $refresh_title .= sprintf(_(" (Accesskey %s)"), $refresh_ak);
}

if ($unread) {
    $pagetitle = $title .= ' (' . $unread . ')';
}

if ($vfolder ||
    ($search_mbox && (IMP::$mailbox != IMP_Search::BASIC_SEARCH))) {
    $query_text = wordwrap($imp_search->searchQueryText(IMP::$mailbox));
    if ($vfolder) {
        $pagetitle .= ' [' . Horde::linkTooltip('#', $query_text, '', '', '', $query_text) . _("Virtual Folder") . '</a>]';
        $title .= ' [' . _("Virtual Folder") . ']';
    } else {
        $pagetitle = Horde::linkTooltip('#', $query_text, '', '', '', $query_text) . $pagetitle . '</a>';
    }
} else {
    $pagetitle = $title = htmlspecialchars($title);
}

Horde::addScriptFile('dialog.js', 'imp');
Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('redbox.js', 'horde');
Horde::addScriptFile('mailbox.js', 'imp');

if (!empty($newmsgs)) {
    /* Open the mailbox R/W so we ensure the 'recent' flags are cleared from
     * the current mailbox. */
    $imp_imap->openMailbox(IMP::$mailbox, Horde_Imap_Client::OPEN_READWRITE);

    if ($vars->newmail) {
        /* Newmail alerts. */
        IMP::newmailAlerts($newmsgs);
    }
}

IMP::prepareMenu();
Horde::metaRefresh($prefs->getValue('refresh_time'), $refresh_url);
require IMP_TEMPLATES . '/common-header.inc';
IMP::menu();
IMP::status();
IMP::quota();

/* Prepare the header template. */
$hdr_template = $injector->createInstance('Horde_Template');
$hdr_template->setOption('gettext', true);

$hdr_template->set('title', $title);
$hdr_template->set('pagetitle', $pagetitle);
if ($readonly) {
    $hdr_template->set('readonly', Horde::img('locked.png', _("Read-Only")));
}
$hdr_template->set('refresh', Horde::link($refresh_url, $refresh_title, '', '', '', '', $refresh_ak));
if (isset($filter_url)) {
    $hdr_template->set('filter_url', $filter_url);
    $hdr_template->set('filter_img', Horde::img('filters.png', _("Apply Filters")));
}
$hdr_template->set('search', false);
if ($_SESSION['imp']['protocol'] != 'pop') {
    $hdr_template->set('search_img', Horde::img('search.png', _("Search")));

    if (!$search_mbox) {
        $hdr_template->set('search_url', Horde::url('search-basic.php')->add('search_mailbox', IMP::$mailbox));
        if (!$readonly) {
            $hdr_template->set('empty', $mailbox_imp_url->copy()->add(array(
                'actionID' => 'empty_mailbox',
                'mailbox' => IMP::$mailbox,
                'mailbox_token' => $mailbox_token
            )));
            $hdr_template->set('empty_img', Horde::img('empty_spam.png', _("Empty folder")));
        }
    } else {
        if ($imp_search->isEditableVFolder(IMP::$mailbox)) {
            $edit_search = _("Edit Virtual Folder");
            $hdr_template->set('delete_vfolder_url', $imp_search->deleteUrl(IMP::$mailbox));
            $hdr_template->set('delete_vfolder_img', Horde::img('delete.png', _("Delete Virtual Folder")));
        } elseif ($search_mbox && !isset($query_text)) {
            /* Mini search results. */
            $search_mailbox = reset($imp_search->getSearchFolders(IMP::$mailbox));
            $hdr_template->set('search_url', Horde::url('search-basic.php')->add('search_mailbox', $search_mailbox));
            $hdr_template->set('searchclose', IMP::generateIMPUrl('mailbox.php', $search_mailbox));
        } elseif (!$vfolder) {
            $edit_search = _("Edit Search Query");
        }

        if (isset($edit_search)) {
            $hdr_template->set('edit_search_url', $imp_search->editUrl(IMP::$mailbox));
            $hdr_template->set('edit_search_title', $edit_search);
            $hdr_template->set('edit_search_img', Horde::img('edit.png', $edit_search));
        }
    }
}
/* Generate mailbox summary string. */
if (empty($pageOb['end'])) {
    $hdr_template->set('msgcount', _("No Messages"));
} elseif ($pageOb['pagecount'] > 1) {
    $hdr_template->set('msgcount', sprintf(_("%d - %d of %d Messages"), $pageOb['begin'], $pageOb['end'], $pageOb['msgcount']));
    $hdr_template->set('page', sprintf(_("Page %d of %d"), $pageOb['page'], $pageOb['pagecount']));
} else {
    $hdr_template->set('msgcount', sprintf(_("%d Messages"), $pageOb['msgcount']));
}

echo $hdr_template->fetch(IMP_TEMPLATES . '/imp/mailbox/header.html');

/* If no messages, exit immediately. */
if (empty($pageOb['end'])) {
    if ($pageOb['anymsg'] && isset($deleted_prompt)) {
        /* Show 'Show Deleted' prompt if mailbox has no viewable message but
           has hidden, deleted messages. */
        $del_template = $injector->createInstance('Horde_Template');
        $del_template->set('hide', Horde::widget($refresh_url->copy()->add(array('actionID' => 'hide_deleted', 'mailbox_token' => $mailbox_token)), $deleted_prompt, 'widget hideAction', '', '', $deleted_prompt));
        if (!$readonly) {
            $del_template->set('purge', Horde::widget($refresh_url->copy()->add(array('actionID' => 'expunge_mailbox', 'mailbox_token' => $mailbox_token)), _("Purge Deleted"), 'widget purgeAction', '', '', _("Pur_ge Deleted")));
        }
        echo $del_template->fetch(IMP_TEMPLATES . '/imp/mailbox/actions_deleted.html');
    }

    $empty_template = $injector->createInstance('Horde_Template');
    $empty_template->setOption('gettext', true);
    $empty_template->set('search_mbox', $search_mbox);
    echo $empty_template->fetch(IMP_TEMPLATES . '/imp/mailbox/empty_mailbox.html');
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

/* Display the navbar and actions if there is at least 1 message in mailbox. */
if ($pageOb['msgcount']) {
    $use_trash = $prefs->getValue('use_trash');

    /* Prepare the navbar template. */
    $n_template = $injector->createInstance('Horde_Template');
    $n_template->setOption('gettext', true);
    $n_template->set('id', 1);
    $n_template->set('sessiontag', Horde_Util::formInput());
    $n_template->set('use_folders', $conf['user']['allow_folders']);
    $n_template->set('readonly', $readonly);
    $n_template->set('use_pop', $_SESSION['imp']['protocol'] == 'pop');

    if (!$n_template->get('use_pop')) {
        $tmp = $imp_flags->getFlagList($search_mbox ? null : IMP::$mailbox);
        $n_template->set('flaglist_set', $tmp['set']);
        $n_template->set('flaglist_unset', $tmp['unset']);

        if ($n_template->get('use_folders')) {
            $n_template->set('move', Horde::widget('#', _("Move to folder"), 'widget moveAction', '', '', _("Move"), true));
            $n_template->set('copy', Horde::widget('#', _("Copy to folder"), 'widget copyAction', '', '', _("Copy"), true));
            $n_template->set('folder_options', $folder_options);
        }
    }

    $n_template->set('mailbox_url', $mailbox_url);
    $n_template->set('mailbox', IMP::formMbox(IMP::$mailbox, true));
    if ($pageOb['pagecount'] > 1) {
        $n_template->set('multiple_page', true);
        $n_template->set('pages_first', $pages_first);
        $n_template->set('pages_prev', $pages_prev);
        $n_template->set('pages_next', $pages_next);
        $n_template->set('pages_last', $pages_last);
        $n_template->set('page_val', htmlspecialchars($pageOb['page']));
        $n_template->set('page_size', Horde_String::length($pageOb['pagecount']));
    }

    echo $n_template->fetch(IMP_TEMPLATES . '/imp/mailbox/navbar.html');

    /* Prepare the actions template. */
    $a_template = $injector->createInstance('Horde_Template');
    if (!$readonly) {
        $del_class = ($use_trash && ((IMP::$mailbox == (IMP::folderPref($prefs->getValue('trash_folder'), true))) || !is_null($vtrash)))
            ? 'permdeleteAction'
            : 'deleteAction';
        $a_template->set('delete', Horde::widget('#', _("Delete"), 'widget ' . $del_class, '', '', _("_Delete")));
    }

    if ($showdelete['purge'] || !is_null($vtrash)) {
        $a_template->set('undelete', Horde::widget('#', _("Undelete"), 'widget undeleteAction', '', '', _("_Undelete")));
    }

    $mboxactions = array();
    if ($showdelete['purge']) {
        $mailbox_link = $mailbox_imp_url->copy()->add('page', $pageOb['page']);
        if (isset($deleted_prompt)) {
            $mboxactions[] = array(
                'v' => Horde::widget($mailbox_link->copy()->add(array('actionID' => 'hide_deleted', 'mailbox_token' => $mailbox_token)), $deleted_prompt, 'widget hideAction', '', '', $deleted_prompt)
            );
        }
        $mboxacrtions[] = array(
            'v' => Horde::widget($mailbox_link->copy()->add(array('actionID' => 'expunge_mailbox', 'mailbox_token' => $mailbox_token)), _("Purge Deleted"), 'widget purgeAction', '', '', _("Pur_ge Deleted"))
        );
    }

    if ($sortpref['by'] != Horde_Imap_Client::SORT_SEQUENCE) {
        $mboxactions[] = array(
            'v' => Horde::widget($sort_url->copy()->remove('sortdir')->add(array('sortby' => Horde_Imap_Client::SORT_SEQUENCE, 'actionID' => 'change_sort', 'mailbox_token' => $mailbox_token)), _("Clear Sort"), 'widget', '', '', _("Clear Sort"))
        );
    }

    /* Hack since IE doesn't support :last-child CSS selector. */
    if (!empty($mboxactions)) {
        $mboxactions[count($mboxactions) - 1]['last'] = true;
    }
    $a_template->set('mboxactions', $mboxactions);

    if ($registry->hasMethod('mail/blacklistFrom')) {
        $a_template->set('blacklist', Horde::widget('#', _("Blacklist"), 'widget blacklistAction', '', '', _("_Blacklist")));
    }

    if ($registry->hasMethod('mail/whitelistFrom')) {
        $a_template->set('whitelist', Horde::widget('#', _("Whitelist"), 'widget whitelistAction', '', '', _("_Whitelist")));
    }

    if (IMP::canCompose()) {
        $a_template->set('forward', Horde::widget('#', _("Forward"), 'widget forwardAction', '', '', _("Fo_rward")));
    }

    if ($conf['spam']['reporting'] &&
        ($conf['spam']['spamfolder'] ||
         (IMP::$mailbox != IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
        $a_template->set('spam', Horde::widget('#', _("Report as Spam"), 'widget spamAction', '', '', _("Report as Spam")));
    }

    if ($conf['notspam']['reporting'] &&
        (!$conf['notspam']['spamfolder'] ||
         (IMP::$mailbox == IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
        $a_template->set('notspam', Horde::widget('#', _("Report as Innocent"), 'widget notspamAction', '', '', _("Report as Innocent")));
    }

    $a_template->set('view_messages', Horde::widget('#', _("View Messages"), 'widget viewAction', '', '', _("View Messages")));

    echo $a_template->fetch(IMP_TEMPLATES . '/imp/mailbox/actions.html');
}

/* Define some variables now so we don't have to keep redefining in the
   foreach () loop or the templates. */
$lastMbox = '';
$messages = $threadlevel = array();

/* Get thread object, if necessary. */
if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
    $imp_thread = new IMP_Imap_Thread($imp_mailbox->getThreadOb());
    $threadtree = $imp_thread->getThreadImageTree(reset($mbox_info['uids']->indices()), $sortpref['dir']);
}

$mh_count = 0;
$sortImg = ($sortpref['dir']) ? 'za.png' : 'az.png';
$sortText = ($sortpref['dir']) ? '\/' : '/\\';
$headers = array(
    IMP::IMAP_SORT_DATE => array(
        'id' => 'mboxdate',
        'stext' => _("Sort by Date"),
        'text' => _("Dat_e")
    ),
    Horde_Imap_Client::SORT_TO => array(
        'id' => 'mboxto',
        'stext' => _("Sort by To Address"),
        'text' => _("To")
    ),
    Horde_Imap_Client::SORT_FROM => array(
        'id' => 'mboxfrom',
        'stext' => _("Sort by From Address"),
        'text' => _("Fro_m")
    ),
    Horde_Imap_Client::SORT_THREAD => array(
        'id' => 'mboxthread',
        'stext' => _("Sort by Thread"),
        'text' => _("_Thread")
    ),
    Horde_Imap_Client::SORT_SUBJECT => array(
        'id' => 'mboxsubject',
        'stext' => _("Sort by Subject"),
        'text' => _("Sub_ject")
    ),
    Horde_Imap_Client::SORT_SIZE => array(
        'id' => 'mboxsize',
        'stext' => _("Sort by Message Size"),
        'text' => _("Si_ze")
    )
);

/* If this is the Drafts or Sent-Mail Folder, sort by To instead of From. */
if (IMP::isSpecialFolder(IMP::$mailbox)) {
    unset($headers[Horde_Imap_Client::SORT_FROM]);
} else {
    unset($headers[Horde_Imap_Client::SORT_TO]);
}

/* Determine which of Subject/Thread to emphasize. */
if (!IMP::threadSortAvailable(IMP::$mailbox)) {
    unset($headers[Horde_Imap_Client::SORT_THREAD]);
} else {
    if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
        $extra = Horde_Imap_Client::SORT_SUBJECT;
        $standard = Horde_Imap_Client::SORT_THREAD;
    } else {
        $extra = Horde_Imap_Client::SORT_THREAD;
        $standard = Horde_Imap_Client::SORT_SUBJECT;
    }
    $headers[$standard]['altsort'] = Horde::widget($mailbox_imp_url->copy()->add(array(
        'actionID' => 'change_sort',
        'mailbox_token' => $mailbox_token,
        'sortby' => $extra
    )), $headers[$extra]['stext'], 'widget', null, null, $headers[$extra]['text']);
    unset($headers[$extra]);
}

foreach ($headers as $key => $val) {
    $ptr = &$headers[$key];
    $ptr['class'] = ($sortpref['by'] == $key) ? 'selected' : 'item';

    $ptr['change_sort_link'] = ($sortpref['by'] == $key)
        ? Horde::link($sort_url->copy()->add(array('sortby' => $key, 'actionID' => 'change_sort', 'mailbox_token' => $mailbox_token)), $val['stext'], null, null, null, $val['stext']) . Horde::img($sortImg, $sortText) . '</a>'
        : null;

    $tmp = ($sortpref['by'] == $key) ? $sort_url : $mailbox_imp_url;
    $ptr['change_sort_widget'] = Horde::widget($tmp->copy()->add(array(
        'actionID' => 'change_sort',
        'mailbox_token' => $mailbox_token,
        'sortby' => $key
    )), $val['stext'], 'widget', null, null, $val['text']);
}

/* Output the form start. */
$f_template = $injector->createInstance('Horde_Template');
$f_template->set('mailbox', IMP::formMbox(IMP::$mailbox, true));
$f_template->set('mailbox_token', $mailbox_token);
$f_template->set('mailbox_url', $mailbox_url);
$f_template->set('sessiontag', Horde_Util::formInput());
$f_template->set('page', $pageOb['page']);
echo $f_template->fetch(IMP_TEMPLATES . '/imp/mailbox/form_start.html');

/* Prepare the message headers template. */
$mh_template = $injector->createInstance('Horde_Template');
$mh_template->setOption('gettext', true);
$mh_template->set('check_all', Horde::getAccessKeyAndTitle(_("Check _All/None")));
$mh_template->set('headers', $headers);

if (!$search_mbox) {
    $mh_template->set('show_checkbox', !$mh_count++);
    echo $mh_template->fetch(IMP_TEMPLATES . '/imp/mailbox/message_headers.html');
}

/* Initialize repetitively used variables. */
$fromlinkstyle = $prefs->getValue('from_link');
$imp_ui = new IMP_Ui_Mailbox(IMP::$mailbox);

/* Display message information. */
$msgs = array();
$search_template = null;
while (list(,$ob) = each($mbox_info['overview'])) {
    if ($search_mbox) {
        if (empty($lastMbox) || ($ob['mailbox'] != $lastMbox)) {
            if (!empty($lastMbox)) {
                _outputSummaries($msgs);
                $msgs = array();
            }
            $folder_link = $mailbox_url->copy()->add('mailbox', $ob['mailbox']);
            $folder_link = Horde::link($folder_link, sprintf(_("View messages in %s"), IMP::displayFolder($ob['mailbox'])), 'smallheader') . IMP::displayFolder($ob['mailbox']) . '</a>';
            if (is_null($search_template)) {
                $search_template = $injector->createInstance('Horde_Template');
            }
            $search_template->set('lastMbox', $lastMbox);
            $search_template->set('folder_link', $folder_link);
            echo $search_template->fetch(IMP_TEMPLATES . '/imp/mailbox/searchfolder.html');

            $mh_template->set('show_checkbox', !$mh_count++);
            echo $mh_template->fetch(IMP_TEMPLATES . '/imp/mailbox/message_headers.html');
        }
    }

    $lastMbox = $ob['mailbox'];

    /* Initialize the data fields. */
    $msg = array(
        'bg' => '',
        'class' => '',
        'date' => htmlspecialchars($imp_ui->getDate($ob['envelope']['date'])),
        'preview' => '',
        'status' => '',
        'size' => htmlspecialchars($imp_ui->getSize($ob['size'])),
        'uid' => htmlspecialchars(new IMP_Indices($ob['mailbox'], $ob['uid']))
    );

    /* Since this value will be used for an ID element, it cannot contain
     * certain characters.  Replace those unavailable chars with '_', and
     * double existing underscores to ensure we don't have a duplicate ID. */
    $msg['id'] = preg_replace('/[^0-9a-z\-_:\.]/i', '_', str_replace('_', '__', rawurlencode($ob['uid'] . $ob['mailbox'])));

    /* Generate the target link. */
    $target = IMP::generateIMPUrl('message.php', IMP::$mailbox, $ob['uid'], $ob['mailbox']);

    /* Get all the flag information. */
    try {
        $ob['flags'] = array_merge($ob['flags'], Horde::callHook('msglist_flags', array($ob, 'imp'), 'imp'));
    } catch (Horde_Exception_HookNotSet $e) {}

    $flag_parse = $imp_flags->parse(array(
        'atc' => isset($ob['structure']) ? $ob['structure'] : null,
        'div' => true,
        'flags' => $ob['flags'],
        'personal' => Horde_Mime_Address::getAddressesFromObject($ob['envelope']['to'], array('charset' => $registry->getCharset())),
        'priority' => $ob['headers']
    ));

    $subject_flags = array();
    foreach ($flag_parse as $val) {
        if ($val['type'] == 'imapp') {
            $subject_flags[] = $val;
        } else {
            if (isset($val['div'])) {
                $msg['status'] .= $val['div'];
            }
            if (isset($val['classname'])) {
                $msg['class'] = $val['classname'];
            }
            $msg['bg'] = $val['bg'];
        }
    }

    /* Show message preview? */
    if ($show_preview && isset($ob['preview'])) {
        if (empty($ob['preview'])) {
            $ptext = '[[' . _("No Preview Text") . ']]';
        } else {
            if (!empty($strip_preview)) {
                $ptext = preg_replace(array('/\n/', '/(\s)+/'), array(' ', '$1'), str_replace("\r", "\n", $ob['preview']));
            } else {
                $ptext = str_replace("\r", '', $ob['preview']);
            }

            if (!$preview_tooltip) {
                $ptext = $injector->getInstance('Horde_Text_Filter')->filter($ptext, 'text2html', array(
                    'parselevel' => Horde_Text_Filter_Text2html::NOHTML
                ));
            }

            $maxlen = $prefs->getValue('preview_maxlen');
            if (Horde_String::length($ptext) > $maxlen) {
                $ptext = Horde_String::truncate($ptext, $maxlen);
            } elseif (empty($ob['previewcut'])) {
                $ptext .= '[[' . _("END") . ']]';
            }
        }
        $msg['preview'] = $ptext;
    }

    /* Format the From: Header. */
    $getfrom = $imp_ui->getFrom($ob['envelope'], array('fullfrom' => true, 'specialchars' => $registry->getCharset()));
    $msg['from'] = $getfrom['from'];
    $msg['fullfrom'] = $getfrom['fullfrom'];
    switch ($fromlinkstyle) {
    case 0:
        if (!$getfrom['error']) {
            $msg['from'] = Horde::link(IMP::composeLink(array(), array('actionID' => 'mailto', 'thismailbox' => $ob['mailbox'], 'uid' => $ob['uid'], 'mailto' => $getfrom['to'])), sprintf(_("New Message to %s"), $msg['fullfrom'])) . $msg['from'] . '</a>';
        }
        break;

    case 1:
        $from_uri = IMP::generateIMPUrl('message.php', IMP::$mailbox, $ob['uid'], $ob['mailbox']);
        $msg['from'] = Horde::link($from_uri, $msg['fullfrom']) . $msg['from'] . '</a>';
        break;
    }

    /* Format the Subject: Header. */
    $msg['subject'] = $imp_ui->getSubject($ob['envelope']['subject'], true);
    if ($preview_tooltip) {
        $msg['subject'] = substr(Horde::linkTooltip($target, $msg['preview'], '', '', '', $msg['preview']), 0, -1) . ' class="mboxSubject">' . $msg['subject'] . '</a>';
    } else {
        $msg['subject'] = substr(Horde::link($target, $msg['preview']), 0, -1) . ' class="mboxSubject">' . $msg['subject'] . '</a>' . (!empty($msg['preview']) ? '<br /><small>' . $msg['preview'] . '</small>' : '');
    }

    /* Add subject flags. */
    foreach ($subject_flags as $val) {
        $flag_label = Horde_String::truncate($val['label'], 12);

        $msg['subject'] = '<span class="' . $val['classname'] . '" style="background:' . htmlspecialchars($val['bg']) . ';color:' . htmlspecialchars($val['fg']) . '" title="' . htmlspecialchars($val['label']) . '">' . htmlspecialchars($flag_label) . '</span>' . $msg['subject'];
    }

    /* Set up threading tree now. */
    if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
        if (!empty($threadtree[$ob['uid']])) {
            $msg['subject'] = $threadtree[$ob['uid']] . ' ' . $msg['subject'];
        }
    }

    $msgs[$ob['uid']] = $msg;
}

_outputSummaries($msgs);

echo '</form>';

/* If there are 20 messages or less, don't show the actions/navbar again. */
if (($pageOb['end'] - $pageOb['begin']) >= 20) {
    $a_template->set('isbottom', true);
    echo $a_template->fetch(IMP_TEMPLATES . '/imp/mailbox/actions.html');
    $n_template->set('id', 2);
    $n_template->set('isbottom', true);
    echo $n_template->fetch(IMP_TEMPLATES . '/imp/mailbox/navbar.html');
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
