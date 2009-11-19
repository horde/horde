<?php
/**
 * Standard (imp) mailbox display page.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package IMP
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 */

function _outputSummaries($msgs)
{
    static $template;

    /* Allow user to alter template array. */
    try {
        $msgs = Horde::callHook('mailboxarray', array($msgs, 'imp'), 'imp');
    } catch (Horde_Exception_HookNotSet $e) {}

    if (!isset($template)) {
        $template = new Horde_Template();
        $template->setOption('gettext', true);

        // Some browsers have trouble with hidden overflow in table cells
        // but not in divs.
        if ($GLOBALS['browser']->hasQuirk('no_hidden_overflow_tables')) {
            $template->set('overflow_begin', '<div class="ohide">');
            $template->set('overflow_end', '</div>');
        }
    }

    $template->set('messages', $msgs, true);
    echo $template->fetch(IMP_TEMPLATES . '/mailbox/mailbox.html');
}


require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => true));

/* Call the mailbox redirection hook, if requested. */
try {
    $redirect = Horde::callHook('mbox_redirect', array($imp_mbox['mailbox']), 'imp');
    if (!empty($redirect)) {
        $redirect = Horde::applicationUrl($redirect, true);
        header('Location: ' . $redirect);
        exit;
    }
} catch (Horde_Exception_HookNotSet $e) {}

/* Is this a search mailbox? */
$search_mbox = $imp_search->isSearchMbox($imp_mbox['mailbox']);
$vfolder = $imp_search->isVFolder();

/* There is a chance that this page is loaded directly via message.php. If so,
 * don't re-include config files, and the following variables will already be
 * set: $actionID, $start. */
$mailbox_url = Horde::applicationUrl('mailbox.php');
$mailbox_imp_url = IMP::generateIMPUrl('mailbox.php', $imp_mbox['mailbox']);
if (!Horde_Util::nonInputVar('from_message_page')) {
    $actionID = Horde_Util::getFormData('actionID');
    $start = Horde_Util::getFormData('start');
}

/* Get form data and make sure it's the type that we're expecting. */
$targetMbox = Horde_Util::getFormData('targetMbox');
$newMbox = Horde_Util::getFormData('newMbox');
if (!is_array(($indices = Horde_Util::getFormData('indices')))) {
    $indices = array($indices);
}

/* Set the current time zone. */
Horde_Nls::setTimeZone();

$do_filter = false;
$imp_flags = IMP_Imap_Flags::singleton();
$open_compose_window = null;

/* Run through the action handlers */
if ($actionID && ($actionID != 'message_missing')) {
    try {
        Horde::checkRequestToken('imp.mailbox', Horde_Util::getFormData('mailbox_token'));
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $actionID = null;
    }
}

/* We know we are going to be exclusively dealing with this mailbox, so
 * select it on the IMAP server (saves some STATUS calls). Open R/W to clear
 * the RECENT flag. */
if (!$search_mbox) {
    $imp_imap->ob()->openMailbox($imp_mbox['mailbox'], Horde_Imap_Client::OPEN_READWRITE);
}

/* Determine if mailbox is readonly. */
$readonly = $imp_imap->isReadOnly($imp_mbox['mailbox']);
if ($readonly &&
    in_array($actionID, array('delete_messages', 'undelete_messages', 'move_messages', 'flag_messages', 'empty_mailbox', 'filter'))) {
    $actionID = null;
}

switch ($actionID) {
case 'change_sort':
    IMP::setSort(Horde_Util::getFormData('sortby'), Horde_Util::getFormData('sortdir'));
    break;

case 'blacklist':
    $imp_filter = new IMP_Filter();
    $imp_filter->blacklistMessage($indices);
    break;

case 'whitelist':
    $imp_filter = new IMP_Filter();
    $imp_filter->whitelistMessage($indices);
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
    if (!empty($indices)) {
        $options = array('fwddigest' => serialize($indices), 'actionID' => 'fwd_digest');
        $open_compose_window = IMP::openComposeWin($options);
    }
    break;

case 'delete_messages':
    if (!empty($indices)) {
        $imp_message = IMP_Message::singleton();
        $imp_message->delete($indices);
    }
    break;

case 'undelete_messages':
    if (!empty($indices)) {
        $imp_message = IMP_Message::singleton();
        $imp_message->undelete($indices);
    }
    break;

case 'move_messages':
case 'copy_messages':
    if (!empty($indices) && !empty($targetMbox)) {
        $imp_message = IMP_Message::singleton();
        if (!empty($newMbox) && ($newMbox == 1)) {
            $targetMbox = IMP::folderPref($targetMbox, true);
            $newMbox = true;
        } else {
            $newMbox = false;
        }
        $imp_message->copy($targetMbox, ($actionID == 'move_messages') ? 'move' : 'copy', $indices, $newMbox);
    }
    break;

case 'flag_messages':
    $flag = Horde_Util::getPost('flag');
    if ($flag && !empty($indices)) {
        $flag = $imp_flags->parseFormId($flag);
        $imp_message = IMP_Message::singleton();
        $imp_message->flag(array($flag['flag']), $indices, $flag['set']);
    }
    break;

case 'hide_deleted':
    $prefs->setValue('delhide', !$prefs->getValue('delhide'));
    IMP::hideDeletedMsgs($imp_mbox['mailbox'], true);
    break;

case 'expunge_mailbox':
    $imp_message = IMP_Message::singleton();
    $imp_message->expungeMailbox(array($imp_mbox['mailbox'] => 1));
    break;

case 'filter':
    $do_filter = true;
    break;

case 'empty_mailbox':
    $imp_message = IMP_Message::singleton();
    $imp_message->emptyMailbox(array($imp_mbox['mailbox']));
    break;

case 'view_messages':
    $redirect = Horde_Util::addParameter(IMP::generateIMPUrl('thread.php', $imp_mbox['mailbox'], null, null, false), array('mode' => 'msgview', 'msglist' => $GLOBALS['imp_imap']->ob()->utils->toSequenceString(IMP::parseIndicesList($indices), array('mailbox' => true))), null, false);
    header('Location: ' . $redirect);
    exit;
}

/* Token to use in requests */
$mailbox_token = Horde::getRequestToken('imp.mailbox');

/* Deal with filter options. */
if (!$readonly && !empty($_SESSION['imp']['filteravail'])) {
    /* Only allow filter on display for INBOX. */
    if (($imp_mbox['mailbox'] == 'INBOX') &&
        $prefs->getValue('filter_on_display')) {
        $do_filter = true;
    } elseif (($imp_mbox['mailbox'] == 'INBOX') ||
              ($prefs->getValue('filter_any_mailbox') && !$search_mbox)) {
        $filter_url = Horde_Util::addParameter($mailbox_imp_url, array('actionID' => 'filter', 'mailbox_token' => $mailbox_token));
    }
}

/* Run filters now. */
if ($do_filter) {
    $imp_filter = new IMP_Filter();
    $imp_filter->filter($imp_mbox['mailbox']);
}

/* Generate folder options list. */
if ($conf['user']['allow_folders']) {
    $folder_options = IMP::flistSelect(array('heading' => _("Messages to"), 'new_folder' => true, 'inc_tasklists' => true, 'inc_notepads' => true));
}

/* Build the list of messages in the mailbox. */
$imp_mailbox = IMP_Mailbox::singleton($imp_mbox['mailbox']);
$pageOb = $imp_mailbox->buildMailboxPage(Horde_Util::getFormData('page'), $start);
$show_preview = $prefs->getValue('preview_enabled');

$overview_headers = array('x-priority');
$mbox_info = $imp_mailbox->getMailboxArray(range($pageOb['begin'], $pageOb['end']), array('preview' => $show_preview, 'headers' => $overview_headers, 'structure' => $prefs->getValue('atc_flag')));

/* Determine sorting preferences. */
$sortpref = IMP::getSort($imp_mbox['mailbox']);

/* Cache this value since we use it alot on this page. */
$graphicsdir = $registry->getImageDir('horde');

/* Determine if we are going to show the Hide/Purge Deleted Message links. */
if (!$prefs->getValue('use_trash') &&
    !$GLOBALS['imp_search']->isVINBOXFolder()) {
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

/* Generate mailbox summary string. */
if (!empty($pageOb['end'])) {
    $msg_count = sprintf(_("%d to %d of %d Messages"), $pageOb['begin'], $pageOb['end'], $pageOb['msgcount']);
} else {
    $msg_count = sprintf(_("No Messages"));
}

/* Generate paging links. */
if ($pageOb['pagecount']) {
    $rtl = !empty(Horde_Nls::$config['rtl'][$language]);
    if ($pageOb['page'] == 1) {
        $pages_first = Horde::img($rtl ? 'nav/last-grey.png' : 'nav/first-grey.png', null, null, $graphicsdir);
        $pages_prev = Horde::img($rtl ? 'nav/right-grey.png' : 'nav/left-grey.png', null, null, $graphicsdir);
    } else {
        $first_url = Horde_Util::addParameter($mailbox_imp_url, 'page', 1);
        $pages_first = Horde::link($first_url, _("First Page")) . Horde::img($rtl ? 'nav/last.png' : 'nav/first.png', $rtl ? '>>' : '<<', null, $graphicsdir) . '</a>';
        $prev_url = Horde_Util::addParameter($mailbox_imp_url, 'page', $pageOb['page'] - 1);
        $pages_prev = Horde::link($prev_url, _("Previous Page")) . Horde::img($rtl ? 'nav/right.png' : 'nav/left.png', $rtl ? '>' : '<', null, $graphicsdir) . '</a>';
    }

    if ($pageOb['page'] == $pageOb['pagecount']) {
        $pages_last = Horde::img($rtl ? 'nav/first-grey.png' : 'nav/last-grey.png', null, null, $graphicsdir);
        $pages_next = Horde::img($rtl ? 'nav/left-grey.png' : 'nav/right-grey.png', null, null, $graphicsdir);
    } else {
        $next_url = Horde_Util::addParameter($mailbox_imp_url, 'page', $pageOb['page'] + 1);
        $pages_next = Horde::link($next_url, _("Next Page")) . Horde::img($rtl ? 'nav/left.png' : 'nav/right.png', $rtl ? '<' : '>', null, $graphicsdir) . '</a>';
        $last_url = Horde_Util::addParameter($mailbox_imp_url, 'page', $pageOb['pagecount']);
        $pages_last = Horde::link($last_url, _("Last Page")) . Horde::img($rtl ? 'nav/first.png' : 'nav/last.png', $rtl ? '<<' : '>>', null, $graphicsdir) . '</a>';
    }
}

/* Generate RSS link. */
if ($imp_mbox['mailbox'] == 'INBOX') {
    $rss_box = '';
} else {
    $rss_box = $imp_mbox['mailbox'];
    $ns_info = $imp_imap->getNamespace($imp_mbox['mailbox']);
    if ($ns_info !== null) {
        if (!empty($ns_info['name']) &&
            $ns_info['type'] == 'personal' &&
            substr($imp_mbox['mailbox'], 0, strlen($ns_info['name'])) == $ns_info['name']) {
            $rss_box = substr($imp_mbox['mailbox'], strlen($ns_info['name']));
        }
        $rss_box = str_replace(rawurlencode($ns_info['delimiter']), '/', rawurlencode($ns_info['delimiter'] . $rss_box));
    } else {
        $rss_box = null;
    }
}

if (!is_null($rss_box)) {
    $rss_url = Horde::applicationUrl('rss.php') . $rss_box;
}

/* If user wants the mailbox to be refreshed, set time here. */
$refresh_time = $prefs->getValue('refresh_time');
$refresh_url = Horde_Util::addParameter($mailbox_imp_url, 'page', $pageOb['page']);
if (isset($filter_url)) {
    $filter_url = Horde_Util::addParameter($filter_url, 'page', $pageOb['page']);
}

/* Set the folder for the sort links. */
$sort_url = Horde_Util::addParameter($mailbox_imp_url, 'sortdir', ($sortpref['dir']) ? 0 : 1);

/* Determine if we are showing previews. */
$preview_tooltip = $show_preview
    ? $prefs->getValue('preview_show_tooltip')
    : false;
if ($preview_tooltip) {
    Horde::addScriptFile('tooltips.js', 'horde');
} else {
    $strip_preview = $prefs->getValue('preview_strip_nl');
}

$vtrash = null;
if ($search_mbox) {
    $unread = 0;
    if ($imp_search->isVINBOXFolder()) {
        $unread = $imp_mailbox->getMessageCount();
    } elseif ($imp_search->isVTrashFolder()) {
        $vtrash = $imp_search->createSearchID($search_mbox);
    }
} else {
    $unread = $imp_mailbox->unseenMessages(Horde_Imap_Client::SORT_RESULTS_COUNT);
}

/* Get the recent message count. */
$newmsgs = 0;
if ($prefs->getValue('nav_popup') || $prefs->getValue('nav_audio')) {
    $newmsgs = $imp_mailbox->newMessages(Horde_Imap_Client::SORT_RESULTS_COUNT);
}

$pagetitle = $rawtitle = $title = IMP::getLabel($imp_mbox['mailbox']);
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
    ($search_mbox && ($imp_search->searchMboxID() != IMP_Search::BASIC_SEARCH))) {
    $query_text = wordwrap($imp_search->searchQueryText($imp_search->searchMboxID()));
    if ($vfolder) {
        $pagetitle .= ' [' . Horde::linkTooltip('#', $query_text, '', '', '', $query_text) . _("Virtual Folder") . '</a>]';
        $title .= ' [' . _("Virtual Folder") . ']';
    } else {
        $pagetitle = Horde::linkTooltip('#', $query_text, '', '', '', $query_text) . $pagetitle . '</a>';
    }
} else {
    $pagetitle = $title = htmlspecialchars($title);
}

Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('redbox.js', 'horde');
Horde::addScriptFile('mailbox.js', 'imp');

/* Handle compose_popup. */
if ($open_compose_window === false) {
    if (!isset($options)) {
        $options = array();
    }
    Horde::addInlineScript(Horde::popupJs(Horde::applicationUrl('compose.php'), array('params' => array_merge(array('popup' => 1), $options, IMP::getComposeArgs()))), 'dom');
}

if (!empty($newmsgs)) {
    /* Open the mailbox R/W so we ensure the 'recent' flags are cleared from
     * the current mailbox. */
    $imp_imap->ob()->openMailbox($imp_mbox['mailbox'], Horde_Imap_Client::OPEN_READWRITE);

    if (!Horde_Util::getFormData('no_newmail_popup')) {
        /* Newmail audio. */
        if (($sound = $prefs->getValue('nav_audio'))) {
            $notification->push($registry->getImageDir() . '/audio/' . $sound, 'audio');
        }
        /* Newmail alert. */
        if ($prefs->getValue('nav_popup')) {
            Horde::addInlineScript(IMP::getNewMessagePopup($newmsgs), 'dom');
        }
    }
}

IMP::prepareMenu();
require IMP_TEMPLATES . '/common-header.inc';
IMP::menu();
IMP::status();
IMP::quota();

/* Prepare the header template. */
$hdr_template = new Horde_Template();
$hdr_template->set('title', $title);
$hdr_template->set('pagetitle', $pagetitle);
if ($readonly) {
    $hdr_template->set('readonly', Horde::img('locked.png', _("Read-Only")));
}
$hdr_template->set('refresh', Horde::link($refresh_url, $refresh_title, '', '', '', '', $refresh_ak));
if (isset($filter_url)) {
    $hdr_template->set('filter', Horde::link($filter_url, sprintf(_("Apply Filters to %s"), $rawtitle)) . Horde::img('filters.png', _("Apply Filters")) . '</a>');
}
$hdr_template->set('search', false);
if ($_SESSION['imp']['protocol'] != 'pop') {
    if (!$search_mbox) {
        $hdr_template->set('search', Horde::link(Horde_Util::addParameter(Horde::applicationUrl('search-basic.php'), 'search_mailbox', $imp_mbox['mailbox']), sprintf(_("Search %s"), $rawtitle)) . Horde::img('search.png', _("Search")) . '</a>');
        if (!$readonly) {
            $hdr_template->set('empty', Horde::link(Horde_Util::addParameter($mailbox_imp_url, array('actionID' => 'empty_mailbox', 'mailbox' => $imp_mbox['mailbox'], 'mailbox_token' => $mailbox_token)), _("Empty folder"), '', '', "ImpMailbox.confirmDialog(this.href, '" . addslashes(_("Are you sure you wish to delete all mail in this folder?")) . "'); return false;") . Horde::img('empty_spam.png', _("Empty folder")) . '</a>');
        }
    } else {
        if ($imp_search->isEditableVFolder()) {
            $edit_search = sprintf(_("Edit Virtual Folder Definition for %s"), htmlspecialchars($rawtitle));
            $hdr_template->set('delete_vfolder', Horde::link($imp_search->deleteUrl(), sprintf(_("Delete Virtual Folder Definition for %s"), htmlspecialchars($rawtitle)), null, null, "if (confirm('" . addslashes(_("Are you sure you want to delete this Virtual Folder Definition?")) . "')) { return true; } else { return false; }") . Horde::img('delete.png', sprintf(_("Delete Virtual Folder Definition for %s"), $rawtitle), '', $graphicsdir) . '</a>');
        } elseif ($search_mbox && !isset($query_text)) {
            /* Mini search results. */
            $search_mailbox = reset($imp_search->getSearchFolders());
            $hdr_template->set('search', Horde::link(Horde_Util::addParameter(Horde::applicationUrl('search-basic.php'), array('search_mailbox' => $search_mailbox)), sprintf(_("Search %s"), IMP::getLabel($search_mailbox))) . Horde::img('search.png', _("Search")) . '</a>');
            $hdr_template->set('searchclose', Horde::link(IMP::generateIMPUrl('mailbox.php', $search_mailbox), _("Exit Search")));
        } elseif (!$vfolder) {
            $edit_search = _("Edit Search Query");
        }

        if (isset($edit_search)) {
            $hdr_template->set('search', Horde::link($imp_search->editUrl(), $edit_search) . Horde::img('edit.png', $edit_search, '', $graphicsdir) . '</a>');
        }
    }
}
$hdr_template->set('msgcount', $msg_count);
if ($pageOb['pagecount'] > 1) {
    $hdr_template->set('page', sprintf(_("Page %d of %d"), $pageOb['page'], $pageOb['pagecount']));
}

echo $hdr_template->fetch(IMP_TEMPLATES . '/mailbox/header.html');

/* If no messages, exit immediately. */
if (empty($pageOb['end'])) {
    if ($pageOb['anymsg'] && isset($deleted_prompt)) {
        /* Show 'Show Deleted' prompt if mailbox has no viewable message but
           has hidden, deleted messages. */
        $del_template = new Horde_Template();
        $del_template->set('hide', Horde::widget(Horde_Util::addParameter($refresh_url, array('actionID' => 'hide_deleted', 'mailbox_token' => $mailbox_token)), $deleted_prompt, 'widget hideAction', '', '', $deleted_prompt));
        if (!$readonly) {
            $del_template->set('purge', Horde::widget(Horde_Util::addParameter($refresh_url, array('actionID' => 'expunge_mailbox', 'mailbox_token' => $mailbox_token)), _("Purge Deleted"), 'widget purgeAction', '', '', _("Pur_ge Deleted")));
        }
        echo $del_template->fetch(IMP_TEMPLATES . '/mailbox/actions_deleted.html');
    }

    $empty_template = new Horde_Template();
    $empty_template->setOption('gettext', true);
    $empty_template->set('search_mbox', $search_mbox);
    echo $empty_template->fetch(IMP_TEMPLATES . '/mailbox/empty_mailbox.html');
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

/* Display the navbar and actions if there is at least 1 message in mailbox. */
if ($pageOb['msgcount']) {
    $use_trash = $prefs->getValue('use_trash');

    /* Prepare the navbar template. */
    $n_template = new Horde_Template();
    $n_template->setOption('gettext', true);
    $n_template->set('id', 1);
    $n_template->set('sessiontag', Horde_Util::formInput());
    $n_template->set('use_folders', $conf['user']['allow_folders']);
    $n_template->set('readonly', $readonly);
    $n_template->set('use_pop', $_SESSION['imp']['protocol'] == 'pop');

    if (!$n_template->get('use_pop')) {
        $tmp = $imp_flags->getFlagList($search_mbox ? null : $imp_mbox['mailbox']);
        $n_template->set('flaglist_set', $tmp['set']);
        $n_template->set('flaglist_unset', $tmp['unset']);

        if ($n_template->get('use_folders')) {
            $n_template->set('move', Horde::widget('#', _("Move to folder"), 'widget moveAction', '', '', _("Move"), true));
            $n_template->set('copy', Horde::widget('#', _("Copy to folder"), 'widget copyAction', '', '', _("Copy"), true));
            $n_template->set('folder_options', $folder_options);
        }
    }

    $n_template->set('mailbox_url', $mailbox_url);
    $n_template->set('mailbox', htmlspecialchars($imp_mbox['mailbox']));
    if ($pageOb['pagecount'] > 1) {
        $n_template->set('multiple_page', true);
        $n_template->set('pages_first', $pages_first);
        $n_template->set('pages_prev', $pages_prev);
        $n_template->set('pages_next', $pages_next);
        $n_template->set('pages_last', $pages_last);
        $n_template->set('page_val', htmlspecialchars($pageOb['page']));
        $n_template->set('page_size', Horde_String::length($pageOb['pagecount']));
    }

    echo $n_template->fetch(IMP_TEMPLATES . '/mailbox/navbar.html');

    /* Prepare the actions template. */
    $a_template = new Horde_Template();
    if (!$readonly) {
        $del_class = ($use_trash && (($imp_mbox['mailbox'] == (IMP::folderPref($prefs->getValue('trash_folder'), true))) || !is_null($vtrash)))
            ? 'permdeleteAction'
            : 'deleteAction';
        $a_template->set('delete', Horde::widget('#', _("Delete"), 'widget ' . $del_class, '', '', _("_Delete")));
    }

    if ($showdelete['purge'] || !is_null($vtrash)) {
        $a_template->set('undelete', Horde::widget('#', _("Undelete"), 'widget undeleteAction', '', '', _("_Undelete")));
    }

    if ($showdelete['purge']) {
        $mailbox_link = Horde_Util::addParameter($mailbox_imp_url, 'page', $pageOb['page']);
        if (isset($deleted_prompt)) {
            $a_template->set('hide_deleted', Horde::widget(Horde_Util::addParameter($mailbox_link, array('actionID' => 'hide_deleted', 'mailbox_token' => $mailbox_token)), $deleted_prompt, 'widget hideAction', '', '', $deleted_prompt));
        }
        $a_template->set('purge_deleted', Horde::widget(Horde_Util::addParameter($mailbox_link, array('actionID' => 'expunge_mailbox', 'mailbox_token' => $mailbox_token)), _("Purge Deleted"), 'widget purgeAction', '', '', _("Pur_ge Deleted")));
    }

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
         ($imp_mbox['mailbox'] != IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
        $a_template->set('spam', Horde::widget('#', _("Report as Spam"), 'widget spamAction', '', '', _("Report as Spam")));
    }

    if ($conf['notspam']['reporting'] &&
        (!$conf['notspam']['spamfolder'] ||
         ($imp_mbox['mailbox'] == IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
        $a_template->set('notspam', Horde::widget('#', _("Report as Innocent"), 'widget notspamAction', '', '', _("Report as Innocent")));
    }

    $a_template->set('view_messages', Horde::widget('#', _("View Messages"), 'widget viewAction', '', '', _("View Messages")));

    echo $a_template->fetch(IMP_TEMPLATES . '/mailbox/actions.html');
}

/* Define some variables now so we don't have to keep redefining in the
   foreach () loop or the templates. */
$lastMbox = '';
$messages = $threadlevel = array();

/* Get thread object, if necessary. */
if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
    $imp_thread = new IMP_Imap_Thread($imp_mailbox->getThreadOb());
    $threadtree = $imp_thread->getThreadImageTree(reset($mbox_info['uids']), $sortpref['dir']);
}

/* Don't show header row if this is a search mailbox or if no messages in the
   current mailbox. */
$mh_count = 0;
if ($pageOb['msgcount']) {
    $sortImg = ($sortpref['dir']) ? 'za.png' : 'az.png';
    $sortText = ($sortpref['dir']) ? '\/' : '/\\';
    $headers = array(
        Horde_Imap_Client::SORT_ARRIVAL => array(
            'stext' => _("Sort by Arrival"),
            'text' => _("#"),
            'width' => '4%'
        ),
        Horde_Imap_Client::SORT_DATE => array(
            'stext' => _("Sort by Date"),
            'text' => _("Dat_e"),
            'width' => '10%'
        ),
        Horde_Imap_Client::SORT_TO => array(
            'stext' => _("Sort by To Address"),
            'text' => _("To"),
            'width' => '20%'
        ),
        Horde_Imap_Client::SORT_FROM => array(
            'stext' => _("Sort by From Address"),
            'text' => _("Fro_m"),
            'width' => '20%'
        ),
        Horde_Imap_Client::SORT_THREAD => array(
            'stext' => _("Sort by Thread"),
            'text' => _("_Thread"),
            'width' => '52%'
        ),
        Horde_Imap_Client::SORT_SUBJECT => array(
            'stext' => _("Sort by Subject"),
            'text' => _("Sub_ject"),
            'width' => '52%'
        ),
        Horde_Imap_Client::SORT_SIZE => array(
            'stext' => _("Sort by Message Size"),
            'text' => _("Si_ze"),
            'width' => '6%'
        )
    );

    /* If this is the Drafts or Sent-Mail Folder, sort by To instead of
     * From. */
    if (IMP::isSpecialFolder($imp_mbox['mailbox'])) {
        unset($headers[Horde_Imap_Client::SORT_FROM]);
    } else {
        unset($headers[Horde_Imap_Client::SORT_TO]);
    }

    /* Determine which of Subject/Thread to emphasize. */
    if (!IMP::threadSortAvailable($imp_mbox['mailbox'])) {
        unset($headers[Horde_Imap_Client::SORT_THREAD]);
    } else {
        $extra = Horde_Imap_Client::SORT_THREAD;
        $standard = Horde_Imap_Client::SORT_SUBJECT;
        if ($sortpref['by'] == Horde_Imap_Client::SORT_THREAD) {
            $extra = Horde_Imap_Client::SORT_SUBJECT;
            $standard = Horde_Imap_Client::SORT_THREAD;
        }
        $headers[$standard]['extra'] = '&nbsp;<span style="font-size:95%">[' . Horde::widget(Horde_Util::addParameter($mailbox_imp_url, array('sortby' => $extra, 'actionID' => 'change_sort', 'mailbox_token' => $mailbox_token)), $headers[$extra]['stext'], 'widget" style="font-size:95%; font-weight:normal;', null, 'if (window.event) window.event.cancelBubble = true; else if (event) event.stopPropagation();', $headers[$extra]['text']) . ']</span>';
        unset($headers[$extra]);
    }

    foreach ($headers as $key => $val) {
        $ptr = &$headers[$key];
        $ptr['class'] = ($sortpref['by'] == $key) ? 'selected' : 'item';
        if ($sortpref['by'] == $key) {
            $ptr['change_sort_link'] = Horde::link(Horde_Util::addParameter($sort_url, array('sortby' => $key, 'actionID' => 'change_sort', 'mailbox_token' => $mailbox_token)), $val['stext'], null, null, null, $val['stext']) . Horde::img($sortImg, $sortText, '', $graphicsdir) . '</a>';
        } else {
            $ptr['change_sort_link'] = null;
        }
        if ($sortpref['limit']) {
            $ptr['sortlimit_text'] = Horde::stripAccessKey($val['text']);
        } else {
            $ptr['change_sort'] = addslashes(Horde_Util::addParameter(($sortpref['by'] == $key) ? $sort_url : $mailbox_imp_url, array('sortby' => $key, 'actionID' => 'change_sort', 'mailbox_token' => $mailbox_token)));
            $ptr['change_sort_widget'] = Horde::widget(Horde_Util::addParameter(($sortpref['by'] == $key) ? $sort_url : $mailbox_imp_url, array('sortby' => $key, 'actionID' => 'change_sort', 'mailbox_token' => $mailbox_token)), $val['stext'], 'widget', null, null, $val['text']);
            if (!isset($val['extra'])) {
                $ptr['extra'] = null;
            }
        }
    }

    /* Prepare the message headers template. */
    $mh_template = new Horde_Template();
    $mh_template->setOption('gettext', true);
    $mh_template->set('check_all', Horde::getAccessKeyAndTitle(_("Check _All/None")));
    $mh_template->set('form_tag', true);
    $mh_template->set('mailbox_url', $mailbox_url);
    $mh_template->set('mailbox', htmlspecialchars($imp_mbox['mailbox']));
    $mh_template->set('mailbox_token', $mailbox_token);
    $mh_template->set('sessiontag', Horde_Util::formInput());
    $mh_template->set('sortlimit', $sortpref['limit']);
    $mh_template->set('headers', $headers);

    if (!$search_mbox) {
        $mh_template->set('mh_count', $mh_count++);
        echo $mh_template->fetch(IMP_TEMPLATES . '/mailbox/message_headers.html');
    }
}

/* Initialize repetitively used variables. */
$fromlinkstyle = $prefs->getValue('from_link');
$imp_ui = new IMP_UI_Mailbox($imp_mbox['mailbox']);

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
            $folder_link = Horde::url(Horde_Util::addParameter('mailbox.php', 'mailbox', $ob['mailbox']));
            $folder_link = Horde::link($folder_link, sprintf(_("View messages in %s"), IMP::displayFolder($ob['mailbox'])), 'smallheader') . IMP::displayFolder($ob['mailbox']) . '</a>';
            if (is_null($search_template)) {
                $search_template = new Horde_Template();
            }
            $search_template->set('lastMbox', $lastMbox);
            $search_template->set('folder_link', $folder_link);
            echo $search_template->fetch(IMP_TEMPLATES . '/mailbox/searchfolder.html');

            if ($mh_count) {
                $mh_template->set('form_tag', false);
            }
            $mh_template->set('mh_count', $mh_count++);
            echo $mh_template->fetch(IMP_TEMPLATES . '/mailbox/message_headers.html');
        }
    }

    $lastMbox = $ob['mailbox'];

    /* Initialize the data fields. */
    $msg = array(
        'bg' => '',
        'class' => '',
        'date' => htmlspecialchars($imp_ui->getDate($ob['envelope']['date'])),
        'number' => $ob['seq'],
        'preview' => '',
        'status' => '',
        'size' => htmlspecialchars($imp_ui->getSize($ob['size'])),
        'uid' => $ob['uid'] . IMP::IDX_SEP . htmlspecialchars($ob['mailbox']),
    );

    /* Since this value will be used for an ID element, it cannot contain
     * certain characters.  Replace those unavailable chars with '_', and
     * double existing underscores to ensure we don't have a duplicate ID. */
    $msg['id'] = preg_replace('/[^0-9a-z\-_:\.]/i', '_', str_replace('_', '__', rawurlencode($ob['uid'] . $ob['mailbox'])));

    /* Generate the target link. */
    $target = IMP::generateIMPUrl('message.php', $imp_mbox['mailbox'], $ob['uid'], $ob['mailbox']);

    /* Get all the flag information. */
    try {
        $ob['flags'] = array_merge($ob['flags'], Horde::callHook('msglist_flags', array($ob, 'imp'), 'imp'));
    } catch (Horde_Exception_HookNotSet $e) {}

    $flag_parse = $imp_flags->parse(array(
        'atc' => isset($ob['structure']) ? $ob['structure'] : null,
        'div' => true,
        'flags' => $ob['flags'],
        'personal' => Horde_Mime_Address::getAddressesFromObject($ob['envelope']['to']),
        'priority' => $ob['headers']->getValue('x-priority')
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
                $ptext = Horde_Text_Filter::filter($ptext, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::NOHTML, 'charset' => '', 'class' => ''));
            }

            $maxlen = $prefs->getValue('preview_maxlen');
            if (Horde_String::length($ptext) > $maxlen) {
                $ptext = Horde_String::substr($ptext, 0, $maxlen) . ' ...';
            } elseif (empty($ob['previewcut'])) {
                $ptext .= '[[' . _("END") . ']]';
            }
        }
        $msg['preview'] = $ptext;
    }

    /* Format the From: Header. */
    $getfrom = $imp_ui->getFrom($ob['envelope'], array('fullfrom' => true, 'specialchars' => Horde_Nls::getCharset()));
    $msg['from'] = $getfrom['from'];
    $msg['fullfrom'] = $getfrom['fullfrom'];
    switch ($fromlinkstyle) {
    case 0:
        if (!$getfrom['error']) {
            $msg['from'] = Horde::link(IMP::composeLink(array(), array('actionID' => 'mailto', 'thismailbox' => $ob['mailbox'], 'uid' => $ob['uid'], 'mailto' => $getfrom['to'])), sprintf(_("New Message to %s"), $msg['fullfrom'])) . $msg['from'] . '</a>';
        }
        break;

    case 1:
        $from_uri = IMP::generateIMPUrl('message.php', $imp_mbox['mailbox'], $ob['uid'], $ob['mailbox']);
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
        $flag_label = $val['label'];
        if (Horde_String::length($val['label']) > 12) {
            $flag_label = Horde_String::substr($val['label'], 0, 10) . '...';
        }

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

/* Prepare the message footers template. */
$mf_template = new Horde_Template();
$mf_template->set('page', $pageOb['page']);
echo $mf_template->fetch(IMP_TEMPLATES . '/mailbox/message_footers.html');

/* If there are 20 messages or less, don't show the actions/navbar again. */
if (($pageOb['end'] - $pageOb['begin']) >= 20) {
    $a_template->set('isbottom', true);
    echo $a_template->fetch(IMP_TEMPLATES . '/mailbox/actions.html');
    $n_template->set('id', 2);
    $n_template->set('isbottom', true);
    echo $n_template->fetch(IMP_TEMPLATES . '/mailbox/navbar.html');
}

Horde::addInlineScript(array(
    'ImpMailbox.sortlimit = ' . intval($sortpref['limit']),
    'ImpMailbox.unread = ' . strval($unread)
));

require $registry->get('templates', 'horde') . '/common-footer.inc';
