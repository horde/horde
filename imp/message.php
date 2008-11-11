<?php
/**
 * View a message.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael Slusarz <slusarz@horde.org>
 */

function _returnToMailbox($startIndex = null, $actID = null)
{
    global $actionID, $from_message_page, $start;

    $actionID = null;
    $from_message_page = true;
    $start = null;

    if ($startIndex !== null) {
        $start = $startIndex;
    }
    if ($actID !== null) {
        $actionID = $actID;
    }
}

function _moveAfterAction()
{
    return (($_SESSION['imp']['protocol'] != 'pop') &&
            !IMP::hideDeletedMsgs() &&
            !$GLOBALS['prefs']->getValue('use_trash'));
}

require_once dirname(__FILE__) . '/lib/base.php';

/* Make sure we have a valid index. */
$imp_mailbox = &IMP_Mailbox::singleton($imp_mbox['mailbox'], $imp_mbox['index']);
if (!$imp_mailbox->isValidIndex()) {
    _returnToMailbox(null, 'message_missing');
    require IMP_BASE . '/mailbox.php';
    exit;
}

$printer_friendly = false;

/* Set the current time zone. */
NLS::setTimeZone();

/* Initialize the user's identities. */
require_once 'Horde/Identity.php';
$user_identity = &Identity::singleton(array('imp', 'imp'));

/* Run through action handlers. */
$actionID = Util::getFormData('actionID');
if ($actionID && ($actionID != 'print_message')) {
    $result = IMP::checkRequestToken('imp.message', Util::getFormData('message_token'));
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result);
        $actionID = null;
    }
}

/* Determine if mailbox is readonly. */
$readonly = $imp_imap->isReadOnly($imp_mbox['mailbox']);
if ($readonly &&
    in_array($actionID, array('blacklist', 'whitelist', 'delete_message', 'undelete_message', 'move_message', 'spam_report', 'notspam_report', 'flag_message', 'strip_attachment', 'strip_all'))) {
    $actionID = null;
}

/* Get mailbox/UID of message. */
$index_array = $imp_mailbox->getIMAPIndex();
$index = $index_array['index'];
$mailbox_name = $index_array['mailbox'];
$indices_array = array($mailbox_name => array($index));

switch ($actionID) {
case 'blacklist':
case 'whitelist':
    $imp_filter = new IMP_Filter();
    if ($actionID == 'blacklist') {
        $imp_filter->blacklistMessage($indices_array);
    } else {
        $imp_filter->whitelistMessage($indices_array);
    }
    break;

case 'print_message':
    $printer_friendly = true;
    IMP::printMode(true);
    break;

case 'delete_message':
case 'undelete_message':
    $imp_message = &IMP_Message::singleton();
    if ($actionID == 'undelete_message') {
        $imp_message->undelete($indices_array);
    } else {
        $imp_message->delete($indices_array);
        if ($prefs->getValue('mailbox_return')) {
            _returnToMailbox($imp_mailbox->getMessageIndex());
            require IMP_BASE . '/mailbox.php';
            exit;
        }
        if (_moveAfterAction()) {
            $imp_mailbox->setIndex(1, 'offset');
        }
    }
    break;

case 'move_message':
case 'copy_message':
    if (($targetMbox = Util::getFormData('targetMbox')) !== null) {
        $imp_message = &IMP_Message::singleton();

        $action = ($actionID == 'move_message') ? IMP_Message::MOVE : IMP_Message::COPY;

        if (Util::getFormData('newMbox', 0) == 1) {
            $targetMbox = IMP::folderPref($targetMbox, true);
            $newMbox = true;
        } else {
            $newMbox = false;
        }
        $imp_message->copy($targetMbox, $action, $indices_array, $newMbox);
        if ($prefs->getValue('mailbox_return')) {
            _returnToMailbox($imp_mailbox->getMessageIndex());
            require IMP_BASE . '/mailbox.php';
            exit;
        }
    }
    break;

case 'spam_report':
case 'notspam_report':
    $action = str_replace('_report', '', $actionID);
    $imp_spam = new IMP_Spam();
    switch ($imp_spam->reportSpam($indices_array, $action)) {
    case 1:
        if (_moveAfterAction()) {
            $imp_mailbox->setIndex(1, 'offset');
        }
        break;
    }
    if ($prefs->getValue('mailbox_return')) {
        _returnToMailbox($imp_mailbox->getMessageIndex());
        require IMP_BASE . '/mailbox.php';
        exit;
    }
    break;

case 'flag_message':
    $flag = Util::getFormData('flag');
    if ($flag) {
        if ($flag[0] == '0') {
            $flag = substr($flag, 1);
            $set = false;
        } else {
            $set = true;
        }
        $imp_message = &IMP_Message::singleton();
        $imp_message->flag(array($flag), $indices_array, $set);
        if ($prefs->getValue('mailbox_return')) {
            _returnToMailbox($imp_mailbox->getMessageIndex());
            require IMP_BASE . '/mailbox.php';
            exit;
        }
    }
    break;

case 'add_address':
    $contact_link = IMP::addAddress(Util::getFormData('address'), Util::getFormData('name'));
    if (is_a($contact_link, 'PEAR_Error')) {
        $notification->push($contact_link);
    } else {
        $notification->push(sprintf(_("Entry \"%s\" was successfully added to the address book"), $contact_link), 'horde.success', array('content.raw'));
    }
    break;

case 'strip_attachment':
    $imp_message = &IMP_Message::singleton();
    $result = $imp_message->stripPart($imp_mailbox, Util::getFormData('imapid'));
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    }

    break;

case 'strip_all':
    $imp_message = &IMP_Message::singleton();
    $result = $imp_message->stripPart($imp_mailbox);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    }

    break;
}

/* Token to use in requests */
$message_token = IMP::getRequestToken('imp.message');

/* We may have done processing that has taken us past the end of the
 * message array, so we will return to mailbox.php if that is the
 * case. */
if (!$imp_mailbox->isValidIndex()) {
    _returnToMailbox($imp_mailbox->getMessageIndex());
    require IMP_BASE . '/mailbox.php';
    exit;
}

/* Now that we are done processing, get the index and array index of
 * the current message. */
$index_array = $imp_mailbox->getIMAPIndex();
$index = $index_array['index'];
$mailbox_name = $index_array['mailbox'];

/* Create the IMP_UI_Message:: object. */
$imp_ui = new IMP_UI_Message();

/* Get envelope/flag/header information. */
try {
    /* Need to fetch flags before HEADERTEXT, because SEEN flag might be set
     * before we can grab it. */
    $flags_ret = $imp_imap->ob->fetch($mailbox_name, array(
        Horde_Imap_Client::FETCH_FLAGS => true,
    ), array('ids' => array($index)));
    $fetch_ret = $imp_imap->ob->fetch($mailbox_name, array(
        Horde_Imap_Client::FETCH_ENVELOPE => true,
        Horde_Imap_Client::FETCH_HEADERTEXT => array(array('parse' => true, 'peek' => $readonly))
    ), array('ids' => array($index)));
} catch (Horde_Imap_Client_Exception $e) {
    $imp_imap->logException($e);
    require IMP_BASE . '/mailbox.php';
    exit;
}

$envelope = $fetch_ret[$index]['envelope'];
$flags = $flags_ret[$index]['flags'];
$mime_headers = $fetch_ret[$index]['headertext'][0];
$use_pop = ($_SESSION['imp']['protocol'] == 'pop');

/* Parse the message. */
$imp_contents = &IMP_Contents::singleton($index . IMP::IDX_SEP . $mailbox_name);
if (is_a($imp_contents, 'PEAR_Error')) {
    _returnToMailbox(null, 'message_missing');
    require IMP_BASE . '/mailbox.php';
    exit;
}

$contents_mask = IMP_Contents::SUMMARY_RENDER |
    IMP_Contents::SUMMARY_BYTES |
    IMP_Contents::SUMMARY_SIZE |
    IMP_Contents::SUMMARY_ICON |
    IMP_Contents::SUMMARY_TEXTBODY;
if ($printer_friendly) {
    $contents_mask |= IMP_Contents::SUMMARY_DESCRIP_NOLINK;
} else {
    $contents_mask |= IMP_Contents::SUMMARY_DESCRIP_LINK |
        IMP_Contents::SUMMARY_DOWNLOAD |
        IMP_Contents::SUMMARY_DOWNLOAD_ZIP |
        IMP_Contents::SUMMARY_IMAGE_SAVE |
        IMP_Contents::SUMMARY_DOWNLOAD_ALL;
    if (!$readonly && $prefs->getValue('strip_attachments')) {
        $contents_mask |= IMP_Contents::SUMMARY_STRIP_LINK;
    }
}

$summary = $imp_contents->getSummary($contents_mask);

/* Get the title/mailbox label of the mailbox page. */
$page_label = IMP::getLabel($imp_mbox['mailbox']);

/* Generate the link to ourselves. */
$msgindex = $imp_mailbox->getMessageIndex();
$message_url = Horde::applicationUrl('message.php');
$self_link = Util::addParameter(IMP::generateIMPUrl('message.php', $imp_mbox['mailbox'], $index, $mailbox_name), array('start' => $msgindex, 'message_token' => $message_token));

/* Develop the list of headers to display. */
$basic_headers = $imp_ui->basicHeaders();
$display_headers = $msgAddresses = array();

$display_headers['date'] = $imp_ui->addLocalTime(nl2br($envelope['date']));

/* Build From address links. */
$display_headers['from'] = $imp_ui->buildAddressLinks($envelope['from'], $self_link, !$printer_friendly);

/* Add country/flag image. Try X-Originating-IP first, then fall back to the
 * sender's domain name. */
if (!$printer_friendly) {
    $from_img = '';
    $origin_host = str_replace(array('[', ']'), '', $mime_headers->getValue('X-Originating-IP'));
    if ($origin_host) {
        if (!is_array($origin_host)) {
            $origin_host = array($origin_host);
        }
        foreach ($origin_host as $host) {
            $from_img .= NLS::generateFlagImageByHost($host) . ' ';
        }
        trim($from_img);
    }

    if (empty($from_img) && !empty($envelope['from'])) {
        $from_ob = reset($envelope['from']);
        $from_img = NLS::generateFlagImageByHost($from_ob['host']);
    }

    if (!empty($from_img)) {
        $display_headers['from'] .= '&nbsp;' . $from_img;
    }
}

/* Build To/Cc/Bcc links. */
foreach (array('to', 'cc', 'bcc') as $val) {
    $msgAddresses[] = $mime_headers->getValue($val);
    $addr_val = $imp_ui->buildAddressLinks($envelope[$val], $self_link, !$printer_friendly);
    if (!empty($addr_val)) {
        $display_headers[$val] = $addr_val;
    }
}

/* Process the subject now. */
if (($subject = $mime_headers->getValue('subject'))) {
    /* Filter the subject text, if requested. */
    require_once 'Horde/Text.php';
    $subject = IMP::filterText($subject);
    $display_headers['subject'] = Text::htmlSpaces($subject);

    $title = sprintf(_("%s: %s"), $page_label, $subject);
    $shortsub = htmlspecialchars($subject);
} else {
    $display_headers['subject'] = $shortsub = _("[No Subject]");
    $title = sprintf(_("%s: %s"), $page_label, $shortsub);
}

/* See if the 'X-Priority' header has been set. */
switch ($imp_ui->getXpriority($mime_headers)) {
case 'high':
    $basic_headers['priority'] = _("Priority");
    $display_headers['priority'] = Horde::img('mail_priority_high.png', _("High Priority")) . '&nbsp;' . $mime_headers->getValue('x-priority');
    break;

case 'low':
    $basic_headers['priority'] = _("Priority");
    $display_headers['priority'] = Horde::img('mail_priority_low.png', _("Low Priority")) . '&nbsp;' . $mime_headers->getValue('x-priority');
    break;
}

/* Build Reply-To address links. */
$reply_to = $imp_ui->buildAddressLinks($envelope['reply-to'], $self_link, !$printer_friendly);
if (!empty($reply_to) &&
    (!($from = $display_headers['from']) || ($from != $reply_to))) {
    $display_headers['reply-to'] = $reply_to;
}

/* Determine if all/list/user-requested headers needed. */
$all_headers = Util::getFormData('show_all_headers');
$list_headers = Util::getFormData('show_list_headers');
$user_hdrs = $imp_ui->getUserHeaders();

/* Check for the presence of mailing list information. */
$list_info = $imp_ui->getListInformation($mime_headers);

/* See if the mailing list information has been requested to be displayed. */
if ($list_info['exists'] && ($list_headers || $all_headers)) {
    $all_list_headers = $imp_ui->parseAllListHeaders($mime_headers);
    $list_headers_lookup = $mime_headers->listHeaders();
} else {
    $all_list_headers = array();
}

/* Display all headers or, optionally, the user-specified headers for the
 * current identity. */
$custom_headers = $full_headers = array();
if ($all_headers) {
    $header_array = $mime_headers->toArray();
    foreach ($header_array as $head => $val) {
        $lc_head = strtolower($head);

        /* Skip the header if we have already dealt with it. */
        if (!isset($display_headers[$head]) &&
            !isset($all_list_headers[$head]) &&
            (($head != 'x-priority') || !isset($display_headers['priority']))) {
            $full_headers[$head] = $val;
        }
    }
} elseif (!empty($user_hdrs)) {
    foreach ($user_hdrs as $user_hdr) {
        $user_val = $mime_headers->getValue($user_hdr);
        if (!empty($user_val)) {
            $full_headers[$user_hdr] = $user_val;
        }
    }
}
ksort($full_headers);

/* For the self URL link, we can't trust the index in the query string as it
 * may have changed if we deleted/copied/moved messages. We may need other
 * stuff in the query string, so we need to do an add/remove of 'index'. */
$selfURL = Util::removeParameter(Horde::selfUrl(true), array('index', 'actionID', 'mailbox', 'thismailbox'));
$selfURL = IMP::generateIMPUrl($selfURL, $imp_mbox['mailbox'], $index, $mailbox_name);
$selfURL = Util::addParameter($selfURL, 'message_token', $message_token);
$headersURL = Util::removeParameter($selfURL, array('show_all_headers', 'show_list_headers'));

/* Generate previous/next links. */
$prev_msg = $imp_mailbox->getIMAPIndex(-1);
if ($prev_msg) {
    $prev_url = IMP::generateIMPUrl('message.php', $imp_mbox['mailbox'], $prev_msg['index'], $prev_msg['mailbox']);
}
$next_msg = $imp_mailbox->getIMAPIndex(1);
if ($next_msg) {
    $next_url = IMP::generateIMPUrl('message.php', $imp_mbox['mailbox'], $next_msg['index'], $next_msg['mailbox']);
}

/* Generate the mailbox link. */
$mailbox_url = Util::addParameter(IMP::generateIMPUrl('mailbox.php', $imp_mbox['mailbox']), 'start', $msgindex);

/* Generate the view link. */
$view_link = IMP::generateIMPUrl('view.php', $imp_mbox['mailbox'], $index, $mailbox_name);

Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('popup.js', 'imp', true);
Horde::addScriptFile('message.js', 'imp', true);
require IMP_TEMPLATES . '/common-header.inc';

/* Determine if we need to show the Reply to All link. */
$addresses = array_keys($user_identity->getAllFromAddresses(true));
$show_reply_all = true;
if (!Horde_Mime_Address::addrArray2String(array_merge($envelope['to'], $envelope['cc']), $addresses)) {
    $show_reply_all = false;
}

/* Retrieve any history information for this message. */
if (!$printer_friendly && !empty($conf['maillog']['use_maillog'])) {
    IMP_Maillog::displayLog($envelope['message-id']);

    /* Do MDN processing now. */
    if ($imp_ui->MDNCheck($mime_headers, Util::getFormData('mdn_confirm'))) {
        $confirm_link = Horde::link(Util::addParameter($selfURL, 'mdn_confirm', 1)) . _("HERE") . '</a>';
        $notification->push(sprintf(_("The sender of this message is requesting a Message Disposition Notification from you when you have read this message. Please click %s to send the notification message."), $confirm_link), 'horde.message', array('content.raw'));
    }
}

/* Everything below here is related to preparing the output. */
if (!$printer_friendly) {
    IMP::menu();
    IMP::status();
    IMP::quota();

    /* Set the status information of the message. */
    $identity = $status = null;
    if (!$use_pop) {
        if ($msgAddresses) {
            $identity = $user_identity->getMatchingIdentity($msgAddresses);
            if (($identity !== null) ||
                $user_identity->getMatchingIdentity($msgAddresses, false) !== null) {
                $status .= Horde::img('mail_personal.png', _("Personal"), array('title' => _("Personal")));
            }
            if ($identity === null) {
                $identity = $user_identity->getDefault();
            }
        }

        /* Set status flags. */
        if (!in_array('\\seen', $flags)) {
            $status .= Horde::img('mail_unseen.png', _("Unseen"), array('title' => _("Unseen")));
        }
        $flag_array = array(
            'answered' => _("Answered"),
            'draft'    => _("Draft"),
            'flagged'  => _("Flagged For Followup"),
            'deleted'  => _("Deleted")
        );
        foreach ($flag_array as $flag => $desc) {
            if (in_array('\\' . $flag, $flags)) {
                $status .= Horde::img('mail_' . $flag . '.png', $desc, array('title' => $desc));
            }
        }
    }

    /* If this is a search mailbox, display a link to the parent mailbox of the
     * message in the header. */
    $h_page_label = htmlspecialchars($page_label);
    $header_label = $h_page_label;
    if (isset($imp_search) && $imp_search->isSearchMbox()) {
        $header_label .= ' [' . Horde::link(Util::addParameter(Horde::applicationUrl('mailbox.php'), 'mailbox', $mailbox_name)) . IMP::displayFolder($mailbox_name) . '</a>]';
    }

    /* Prepare the navbar top template. */
    $t_template = new IMP_Template();
    $t_template->set('message_url', $message_url);
    $t_template->set('form_input', Util::formInput());
    $t_template->set('mailbox', htmlspecialchars($imp_mbox['mailbox']));
    $t_template->set('thismailbox', htmlspecialchars($mailbox_name));
    $t_template->set('start', htmlspecialchars($msgindex));
    $t_template->set('index', htmlspecialchars($index));
    $t_template->set('label', sprintf(_("%s: %s (%d&nbsp;of&nbsp;%d)"), $header_label, $shortsub, $msgindex, $imp_mailbox->getMessageCount()));
    $t_template->set('status', $status);
    $t_template->set('message_token', $message_token);

    echo $t_template->fetch(IMP_TEMPLATES . '/message/navbar_top.html');

    /* Prepare the navbar navigate template. */
    $n_template = new IMP_Template();
    $n_template->setOption('gettext', true);
    $n_template->set('readonly', $readonly);
    $n_template->set('usepop', $use_pop);
    $n_template->set('id', 1);

    if ($conf['user']['allow_folders']) {
        $n_template->set('move', Horde::widget('#', _("Move to folder"), 'widget', '', "transfer('move_message', 1); return false;", _("Move"), true));
        $n_template->set('copy', Horde::widget('#', _("Copy to folder"), 'widget', '', "transfer('copy_message', 1); return false;", _("Copy"), true));
        $n_template->set('options', IMP::flistSelect(array('heading' => _("This message to"), 'new_folder' => true, 'inc_tasklists' => true, 'inc_notepads' => true)));
    }

    $n_template->set('back_to', Horde::widget($mailbox_url, sprintf(_("Back to %s"), $h_page_label), 'widget', '', '', sprintf(_("Bac_k to %s"), $h_page_label), true));

    $rtl = !empty($nls['rtl'][$language]);
    if (Util::nonInputVar('prev_url')) {
        $n_template->set('prev', Horde::link($prev_url, _("Previous Message")));
        $n_template->set('prev_img', Horde::img($rtl ? 'nav/right.png' : 'nav/left.png', $rtl ? '>' : '<', '', $registry->getImageDir('horde')));
    } else {
        $n_template->set('prev_img', Horde::img($rtl ? 'nav/right-grey.png' : 'nav/left-grey.png', '', '', $registry->getImageDir('horde')));
    }

    if (Util::nonInputVar('next_url')) {
        $n_template->set('next', Horde::link($next_url, _("Next Message")));
        $n_template->set('next_img', Horde::img($rtl ? 'nav/left.png' : 'nav/right.png', $rtl ? '<' : '>', '', $registry->getImageDir('horde')));
    } else {
        $n_template->set('next_img', Horde::img($rtl ? 'nav/left-grey.png' : 'nav/right-grey.png', '', '', $registry->getImageDir('horde')));
    }

    echo $n_template->fetch(IMP_TEMPLATES . '/message/navbar_navigate.html');

    /* Prepare the navbar actions template. */
    $a_template = new IMP_Template();
    $a_template->setOption('gettext', true);
    $a_template->set('readonly', $readonly);
    $compose_params = array('index' => $index, 'identity' => $identity, 'thismailbox' => $mailbox_name);
    if (!$prefs->getValue('compose_popup')) {
        $compose_params += array('start' => $msgindex, 'mailbox' => $imp_mbox['mailbox']);
    }

    if (!$readonly) {
        if (in_array('\\deleted', $flags)) {
            $a_template->set('delete', Horde::widget(Util::addParameter($self_link, 'actionID', 'undelete_message'), _("Undelete"), 'widget', '', '', _("Undelete"), true));
        } else {
            $a_template->set('delete', Horde::widget(Util::addParameter($self_link, 'actionID', 'delete_message'), _("Delete"), 'widget', '', ($use_pop) ? "return window.confirm('" . addslashes(_("Are you sure you wish to PERMANENTLY delete these messages?")) . "');" : '', _("_Delete"), true));
        }
    }

    $disable_compose = !empty($conf['hooks']['disable_compose']) &&
                       Horde::callHook('_imp_hook_disable_compose', array(false), 'imp');

    if (!$disable_compose) {
        $a_template->set('reply', Horde::widget(IMP::composeLink(array(), array('actionID' => 'reply') + $compose_params), _("Reply"), 'widget hasmenu', '', '', _("_Reply"), true));
        $a_template->set('reply_sender', Horde::widget(IMP::composeLink(array(), array('actionID' => 'reply') + $compose_params), _("To Sender"), 'widget', '', '', _("To Sender"), true));

        if ($list_info['reply_list']) {
            $a_template->set('reply_list', Horde::widget(IMP::composeLink(array(), array('actionID' => 'reply_list') + $compose_params), _("To List"), 'widget', '', '', _("To _List"), true));
        }

        if ($show_reply_all) {
            $a_template->set('show_reply_all', Horde::widget(IMP::composeLink(array(), array('actionID' => 'reply_all') + $compose_params), _("To All"), 'widget', '', '', _("To _All"), true));
        }

        $a_template->set('forward', Horde::widget(IMP::composeLink(array(), array('actionID' => $prefs->getValue('forward_default')) + $compose_params), _("Forward"), 'widget hasmenu', '', '', _("Fo_rward"), true));
        $a_template->set('forwardall', Horde::widget(IMP::composeLink(array(), array('actionID' => 'forward_all') + $compose_params), _("Entire Message"), 'widget', '', '', _("Entire Message"), true));
        $a_template->set('forwardbody', Horde::widget(IMP::composeLink(array(), array('actionID' => 'forward_body') + $compose_params), _("Body Text Only"), 'widget', '', '', _("Body Text Only"), true));
        $a_template->set('forwardattachments', Horde::widget(IMP::composeLink(array(), array('actionID' => 'forward_attachments') + $compose_params), _("Body Text with Attachments"), 'widget', '', '', _("Body Text with Attachments"), true));

        $a_template->set('redirect', Horde::widget(IMP::composeLink(array(), array('actionID' => 'redirect_compose') + $compose_params), _("Redirect"), 'widget', '', '', _("Redirec_t"), true));
    }

    if (isset($imp_search) && !$imp_search->searchMboxID()) {
        $a_template->set('show_thread', Horde::widget(Util::addParameter(IMP::generateIMPUrl('thread.php', $imp_mbox['mailbox'], $index, $mailbox_name), array('start' => $msgindex)), _("View Thread"), 'widget', '', '', _("_View Thread"), true));
    }

    if (!$readonly && $registry->hasMethod('mail/blacklistFrom')) {
        $a_template->set('blacklist', Horde::widget(Util::addParameter($self_link, 'actionID', 'blacklist'), _("Blacklist"), 'widget', '', '', _("_Blacklist"), true));
    }

    if (!$readonly && $registry->hasMethod('mail/whitelistFrom')) {
        $a_template->set('whitelist', Horde::widget(Util::addParameter($self_link, 'actionID', 'whitelist'), _("Whitelist"), 'widget', '', '', _("_Whitelist"), true));
    }

    if (!empty($conf['user']['allow_view_source'])) {
        $a_template->set('view_source', $imp_contents->linkViewJS($imp_contents->getMIMEMessage(), 'view_source', _("_Message Source"), array('jstext' => _("Message Source"), 'css' => 'widget', 'widget' => true)));
    }

    if (!$disable_compose &&
        (!empty($conf['user']['allow_resume_all']) ||
         (!empty($conf['user']['allow_resume_all_in_drafts']) &&
          $mailbox_name == IMP::folderPref($prefs->getValue('drafts_folder'), true)) ||
         in_array('\\draft', $flags))) {
        $a_template->set('resume', Horde::widget(IMP::composeLink(array(), array('actionID' => 'draft') + $compose_params), _("Resume"), 'widget', '', '', _("Resume"), true));
    }

    $imp_params = IMP::getIMPMboxParameters($imp_mbox['mailbox'], $index, $mailbox_name);
    $a_template->set('save_as', Horde::widget(Horde::downloadUrl($subject, array_merge(array('actionID' => 'save_message'), $imp_params)), _("Save as"), 'widget', '', '', _("Sa_ve as"), 2));

    $print_params = array_merge(array('actionID' => 'print_message'), $imp_params);
    $a_template->set('print', Horde::widget(Util::addParameter(IMP::generateIMPUrl('message.php', $imp_mbox['mailbox']), $print_params), _("Print"), 'widget', '_blank', IMP::popupIMPString('message.php', $print_params) . 'return false;', _("_Print"), true));

    if (!$readonly &&
        $conf['spam']['reporting'] &&
        ($conf['spam']['spamfolder'] ||
         ($mailbox_name != IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
        $a_template->set('spam', Horde::widget('#', _("Report as Spam"), 'widget', '', "message_submit('spam_report'); return false;", _("Report as Spam"), true));
    }

    if (!$readonly &&
        $conf['notspam']['reporting'] &&
        (!$conf['notspam']['spamfolder'] ||
         ($mailbox_name == IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
        $a_template->set('notspam', Horde::widget('#', _("Report as Innocent"), 'widget', '', "message_submit('notspam_report'); return false;", _("Report as Innocent"), true));
    }

    $a_template->set('redirect', Horde::widget(IMP::composeLink(array(), array('actionID' => 'redirect_compose') + $compose_params), _("Redirect"), 'widget', '', '', _("Redirec_t"), true));

    $a_template->set('headers', Horde::widget('#', _("Headers"), 'widget hasmenu', '', '', _("Headers"), true));
    if ($all_headers || $list_headers) {
        $a_template->set('common_headers', Horde::widget($headersURL, _("Show Common Headers"), 'widget', '', '', _("Show Common Headers"), true));
    }
    if (!$all_headers) {
        $a_template->set('all_headers', Horde::widget(Util::addParameter($headersURL, 'show_all_headers', 1), _("Show All Headers"), 'widget', '', '', _("Show All Headers"), true));
    }
    if ($list_info['exists'] && !$list_headers) {
        $a_template->set('list_headers', Horde::widget(Util::addParameter($headersURL, 'show_list_headers', 1), _("Show Mailing List Information"), 'widget', '', '', _("Show Mailing List Information"), true));
    }

    echo $a_template->fetch(IMP_TEMPLATES . '/message/navbar_actions.html');
}

$hdrs = array();
$i = 1;

/* Prepare the main message template. */
$m_template = new IMP_Template();
foreach ($display_headers as $head => $val) {
    $hdrs[] = array('name' => $basic_headers[$head], 'val' => $val, 'i' => (++$i % 2));
}
foreach ($full_headers as $head => $val) {
    if (is_array($val)) {
        $hdrs[] = array('name' => $head, 'val' => '<ul style="margin:0;padding-left:15px"><li>' . implode("</li>\n<li>", array_map('htmlspecialchars', $val)) . '</li></ul>', 'i' => (++$i % 2));
    } else {
        $hdrs[] = array('name' => $head, 'val' => htmlspecialchars($val), 'i' => (++$i % 2));
    }
}
foreach ($all_list_headers as $head => $val) {
    $hdrs[] = array('name' => $list_headers_lookup[$head], 'val' => $val, 'i' => (++$i % 2));
}

/* Show attachment information in headers? */
$download_all = $show_atc = false;
reset($summary['parts']);
if (!empty($summary['parts']) &&
    ((count($summary['parts']) > 1) ||
     (key($summary['parts']) != $summary['info']['textbody']))) {
    $atc_display = $prefs->getValue('attachment_display');
    $show_atc = (($atc_display == 'list') || ($atc_display == 'both'));
    $download_all = (empty($summary['info']['download_all']) || !$printer_friendly)
        ? false
        : $imp_contents->urlView($imp_contents->getMIMEMessage(), 'download_all', array('params' => array('download_ids' => serialize($summary['info']['download_all']))));
}

$part_info = array('icon', 'description', 'type', 'size');
foreach (array('download', 'download_zip', 'img_save', 'strip') as $val) {
    if (isset($summary['info']['has'][$val])) {
        $part_info[] = $val;
    }
}

if ($show_atc || $download_all) {
    $val = '';

    if ($show_atc) {
        $tmp = array();
        foreach ($summary['parts'] as $key => $val) {
            if ($key != $summary['info']['textbody']) {
                $tmp[] = '<tr>';
                foreach ($part_info as $val2) {
                    $tmp[] = '<td>' . $val[$val2] . '</td>';
                }
                $tmp[] = '</tr>';
            }
        }
        $val = '<table>' . implode('', $tmp) . '</table>';
    }

    if ($download_all) {
        $val .= Horde::link($download_all, _("Download All Attachments (in .zip file)")) . _("Download All Attachments (in .zip file)") . ' ' . Horde::img('compressed.png', _("Download All Attachments (in .zip file)"), '', $registry->getImageDir('horde') . '/mime') . '</a>';
        if ($prefs->getValue('strip_attachments')) {
            $url = Util::addParameter(Util::removeParameter(Horde::selfUrl(true), array('actionID')), array('actionID' => 'strip_all', 'message_token' => $message_token));
            $val .= '<br />' . Horde::link($url, _("Strip All Attachments"), null, null, "return window.confirm('" . addslashes(_("Are you sure you wish to PERMANENTLY delete all attachments?")) . "');") . _("Strip All Attachments") . ' ' . Horde::img('delete.png', _("Strip Attachments"), null, $registry->getImageDir('horde')) . '</a>';
        }
    }

    $hdrs[] = array('name' => _("Attachment(s)"), 'val' => $val, 'i' => (++$i % 2));
}

if ($printer_friendly && !empty($conf['print']['add_printedby'])) {
    $hdrs[] = array('name' => _("Printed By"), 'val' => $user_identity->getFullname() ? $user_identity->getFullname() : Auth::getAuth(), 'i' => (++$i % 2));
}

$m_template->set('headers', $hdrs);

/* Build body text. */
$msgtext = '';
foreach ($summary['info']['render'] as $mime_id => $type) {
    $render_part = $imp_contents->renderMIMEPart($mime_id, $type);
    $ptr = $summary['parts'][$mime_id];
    $tmp_part = $tmp_status = array();

    foreach ($part_info as $val) {
        $tmp[] = $ptr[$val];
    }

    foreach ($render_part['status'] as $val) {
        // TODO: status msgs.
        $tmp_status[] = $render_part['status']['text'];
    }

    $msgtext .= '<span class="mimePartInfo">' .
        implode(' ', $tmp) .
        '</span>' .
        implode(' ', $tmp_status) .
        $render_part['data'];
}
$m_template->set('msgtext', $msgtext);

echo $m_template->fetch(IMP_TEMPLATES . '/message/message.html');

if (!$printer_friendly) {
    echo '<input type="hidden" name="flag" id="flag" value="" />';
    $a_template->set('isbottom', true);
    echo $a_template->fetch(IMP_TEMPLATES . '/message/navbar_actions.html');

    $n_template->set('id', 2);
    $n_template->set('isbottom', true);
    if ($n_template->get('move')) {
        $n_template->set('move', Horde::widget('#', _("Move to folder"), 'widget', '', "transfer('move_message', 2); return false;", _("Move"), true), true);
        $n_template->set('copy', Horde::widget('#', _("Copy to folder"), 'widget', '', "transfer('copy_message', 2); return false;", _("Copy"), true));
    }
    echo $n_template->fetch(IMP_TEMPLATES . '/message/navbar_navigate.html');
}

if ($browser->hasFeature('javascript') && $printer_friendly) {
    require $registry->get('templates', 'horde') . '/javascript/print.js';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
