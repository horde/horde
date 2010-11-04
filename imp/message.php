<?php
/**
 * View a message (standard mode).
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

function _returnToMailbox($startIndex = null, $actID = null)
{
    $GLOBALS['actionID'] = $actID;
    $GLOBALS['from_message_page'] = true;
    $GLOBALS['start'] = $startIndex;
}

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'impmode' => 'imp'
));

$registry->setTimeZone();

/* We know we are going to be exclusively dealing with this mailbox, so
 * select it on the IMAP server (saves some STATUS calls). Open R/W to clear
 * the RECENT flag. */
if (!($search_mbox = $injector->getInstance('IMP_Search')->isSearchMbox(IMP::$mailbox))) {
    $injector->getInstance('IMP_Injector_Factory_Imap')->create()->openMailbox(IMP::$mailbox, Horde_Imap_Client::OPEN_READWRITE);
}

/* Make sure we have a valid index. */
$imp_mailbox = $injector->getInstance('IMP_Injector_Factory_MailboxList')->create(IMP::$mailbox, new IMP_Indices(IMP::$thismailbox, IMP::$uid));
if (!$imp_mailbox->isValidIndex()) {
    _returnToMailbox(null, 'message_missing');
    require IMP_BASE . '/mailbox.php';
    exit;
}

/* Initialize IMP_Message object. */
$imp_message = $injector->getInstance('IMP_Message');

/* Initialize the user's identities. */
$user_identity = $injector->getInstance('IMP_Identity');

/* Run through action handlers. */
$vars = Horde_Variables::getDefaultVariables();
if ($vars->actionID) {
    try {
        Horde::checkRequestToken('imp.message', $vars->message_token);
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $vars->actionID = null;
    }
}

/* Determine if mailbox is readonly. */
$peek = $readonly = $injector->getInstance('IMP_Injector_Factory_Imap')->create()->isReadOnly(IMP::$mailbox);
if ($readonly &&
    in_array($vars->actionID, array('delete_message', 'undelete_message', 'move_message', 'flag_message', 'strip_attachment', 'strip_all'))) {
    $vars->actionID = null;
}

/* Get mailbox/UID of message. */
$index_array = $imp_mailbox->getIMAPIndex();
$mailbox_name = $index_array['mailbox'];
$uid = $index_array['uid'];
$indices = new IMP_Indices($mailbox_name, $uid);

$imp_flags = $injector->getInstance('IMP_Imap_Flags');
$imp_hdr_ui = new IMP_Ui_Headers();
$imp_ui = new IMP_Ui_Message();

switch ($vars->actionID) {
case 'blacklist':
case 'whitelist':
    if ($vars->actionID == 'blacklist') {
        $injector->getInstance('IMP_Filter')->blacklistMessage($indices);
    } else {
        $injector->getInstance('IMP_Filter')->whitelistMessage($indices);
    }
    break;

case 'delete_message':
case 'undelete_message':
    if ($vars->actionID == 'undelete_message') {
        $imp_message->undelete($indices);
    } else {
        $imp_message->delete(
            $indices,
            array(
                'mailboxob' => $imp_mailbox
            )
        );
        if ($prefs->getValue('mailbox_return')) {
            _returnToMailbox($imp_mailbox->getMessageIndex());
            require IMP_BASE . '/mailbox.php';
            exit;
        }
        if ($imp_ui->moveAfterAction()) {
            $imp_mailbox->setIndex(1);
        }
    }
    break;

case 'move_message':
case 'copy_message':
    if (isset($vars->targetMbox)) {
        $targetMbox = IMP::formMbox($vars->targetMbox, false);
        if ($vars->newMbox) {
            $vars->targetMbox = IMP::folderPref($targetMbox, true);
            $newMbox = true;
        } else {
            $newMbox = false;
        }
        $imp_message->copy(
            $targetMbox,
            ($vars->actionID == 'move_message') ? 'move' : 'copy',
            $indices,
            array(
                'create' => $newMbox,
                'mailboxob' => $imp_mailbox
            )
        );
        if ($prefs->getValue('mailbox_return')) {
            _returnToMailbox($imp_mailbox->getMessageIndex());
            require IMP_BASE . '/mailbox.php';
            exit;
        }
    }
    break;

case 'spam_report':
case 'notspam_report':
    $action = str_replace('_report', '', $vars->actionID);
    switch (IMP_Spam::reportSpam($indices, $action, array('mailboxob' => $imp_mailbox))) {
    case 1:
        if ($imp_ui->moveAfterAction()) {
            $imp_mailbox->setIndex(1);
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
    if (isset($vars->flag) && count($indices)) {
        $peek = true;
        $flag = $imp_flags->parseFormId($vars->flag);
        $imp_message->flag(array($flag['flag']), $indices, $flag['set']);
        if ($prefs->getValue('mailbox_return')) {
            _returnToMailbox($imp_mailbox->getMessageIndex());
            require IMP_BASE . '/mailbox.php';
            exit;
        }
    }
    break;

case 'add_address':
    try {
        $contact_link = IMP::addAddress($vars->address, $vars->name);
        $notification->push(sprintf(_("Entry \"%s\" was successfully added to the address book"), $contact_link), 'horde.success', array('content.raw'));
    } catch (Horde_Exception $e) {
        $notification->push($e);
    }
    break;

case 'strip_all':
case 'strip_attachment':
    try {
        $imp_message->stripPart(
            $indices,
            ($vars->actionID == 'strip_all') ? null : $vars->imapid,
            array(
                'mailboxob' => $imp_mailbox
            )
        );
        $notification->push(_("Attachment successfully stripped."), 'horde.success');
    } catch (Horde_Exception $e) {
        $notification->push($e);
    }
    break;
}

/* We may have done processing that has taken us past the end of the
 * message array, so we will return to mailbox.php if that is the
 * case. */
if (!$imp_mailbox->isValidIndex()) {
    _returnToMailbox(count($imp_mailbox));
    require IMP_BASE . '/mailbox.php';
    exit;
}

/* Now that we are done processing, get the index and array index of
 * the current message. */
$index_array = $imp_mailbox->getIMAPIndex();
$mailbox_name = $index_array['mailbox'];
$uid = $index_array['uid'];

/* Parse the message. */
try {
    $imp_contents = $injector->getInstance('IMP_Injector_Factory_Contents')->create(new IMP_Indices($imp_mailbox));
} catch (IMP_Exception $e) {
    $imp_mailbox->removeMsgs(true);
    _returnToMailbox(null, 'message_missing');
    require IMP_BASE . '/mailbox.php';
    exit;
}

/* Get envelope/flag/header information. */
try {
    /* Need to fetch flags before HEADERTEXT, because SEEN flag might be set
     * before we can grab it. */
    $flags_ret = $injector->getInstance('IMP_Injector_Factory_Imap')->create()->fetch($mailbox_name, array(
        Horde_Imap_Client::FETCH_FLAGS => true,
    ), array('ids' => array($uid)));
    $fetch_ret = $injector->getInstance('IMP_Injector_Factory_Imap')->create()->fetch($mailbox_name, array(
        Horde_Imap_Client::FETCH_ENVELOPE => true,
        Horde_Imap_Client::FETCH_HEADERTEXT => array(array('parse' => true, 'peek' => $peek))
    ), array('ids' => array($uid)));
} catch (Horde_Imap_Client_Exception $e) {
    $fetch_ret = null;
}

if (is_null($fetch_ret) || !isset($fetch_ret[$uid]['headertext'])) {
    _returnToMailbox(null, 'message_missing');
    require IMP_BASE . '/mailbox.php';
    exit;
}

$envelope = $fetch_ret[$uid]['envelope'];
$flags = $flags_ret[$uid]['flags'];
$mime_headers = reset($fetch_ret[$uid]['headertext']);
$use_pop = ($session->get('imp', 'protocol') == 'pop');

/* Get the title/mailbox label of the mailbox page. */
$page_label = IMP::getLabel(IMP::$mailbox);

/* Generate the link to ourselves. */
$msgindex = $imp_mailbox->getMessageIndex();
$message_url = Horde::url('message.php');
$message_token = Horde::getRequestToken('imp.message');
$self_link = IMP::generateIMPUrl('message.php', IMP::$mailbox, $uid, $mailbox_name)->add(array('start' => $msgindex, 'message_token' => $message_token));

/* Develop the list of headers to display. */
$basic_headers = $imp_ui->basicHeaders();
$display_headers = $msgAddresses = array();

$format_date = $imp_ui->getLocalTime($envelope['date']);
if (!empty($format_date)) {
    $display_headers['date'] = $format_date;
}

/* Build From address links. */
$display_headers['from'] = $imp_ui->buildAddressLinks($envelope['from'], $self_link);

/* Add country/flag image. Try X-Originating-IP first, then fall back to the
 * sender's domain name. */
$from_img = '';
$origin_host = str_replace(array('[', ']'), '', $mime_headers->getValue('X-Originating-IP'));
if ($origin_host) {
    if (!is_array($origin_host)) {
        $origin_host = array($origin_host);
    }
    foreach ($origin_host as $host) {
        $from_img .= Horde_Core_Ui_FlagImage::generateFlagImageByHost($host) . ' ';
    }
    trim($from_img);
}

if (empty($from_img) && !empty($envelope['from'])) {
    $from_ob = reset($envelope['from']);
    $from_img .= Horde_Core_Ui_FlagImage::generateFlagImageByHost($from_ob['host']) . ' ';
}

if (!empty($from_img)) {
    $display_headers['from'] .= '&nbsp;' . $from_img;
}

/* Look for Face: information. */
if ($mime_headers->getValue('face')) {
    $view_url = IMP::generateIMPUrl('view.php', IMP::$mailbox, $uid, $mailbox_name);
    // TODO: Use Data URL
    $view_url->add('actionID', 'view_face');
    $display_headers['from'] .= '&nbsp;<img src="' . $view_url . '">';
}

/* Build To/Cc/Bcc links. */
foreach (array('to', 'cc', 'bcc') as $val) {
    $msgAddresses[] = $mime_headers->getValue($val);
    $addr_val = $imp_ui->buildAddressLinks($envelope[$val], $self_link);
    if (!empty($addr_val)) {
        $display_headers[$val] = $addr_val;
    }
}

/* Process the subject now. */
if ($subject = $mime_headers->getValue('subject')) {
    $display_headers['subject'] = $imp_ui->getDisplaySubject($subject);
    $title = sprintf(_("%s: %s"), $page_label, $subject);
    $shortsub = htmlspecialchars(Horde_String::truncate($subject, 100));
} else {
    $display_headers['subject'] = $shortsub = _("[No Subject]");
    $title = sprintf(_("%s: %s"), $page_label, $shortsub);
}

/* See if the priority has been set. */
switch ($priority = $imp_hdr_ui->getPriority($mime_headers)) {
case 'high':
    $basic_headers['priority'] = _("Priority");
    $display_headers['priority'] = '<div class="msgflags flagHighpriority" title="' . htmlspecialchars(_("High Priority")) . '"></div>&nbsp;' . _("High");
    break;

case 'low':
    $basic_headers['priority'] = _("Priority");
    $display_headers['priority'] = '<div class="msgflags flagLowpriority" title="' . htmlspecialchars(_("Low Priority")) . '"></div>&nbsp;' . _("Low");
    break;
}

/* Build Reply-To address link. */
if (!empty($envelope['reply-to']) &&
    (Horde_Mime_Address::bareAddress(Horde_Mime_Address::addrObject2String(reset($envelope['from']), array('charset' => 'UTF-8'))) !=
     Horde_Mime_Address::bareAddress(Horde_Mime_Address::addrObject2String(reset($envelope['reply-to']), array('charset' => 'UTF-8')))) &&
    ($reply_to = $imp_ui->buildAddressLinks($envelope['reply-to'], $self_link))) {
    $display_headers['reply-to'] = $reply_to;
}

/* Determine if all/list/user-requested headers needed. */
$all_headers = $vars->show_all_headers;
$list_headers = $vars->show_list_headers;
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
            (!in_array($head, array('importance', 'x-priority')) ||
             !isset($display_headers['priority']))) {
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
 * stuff in the query string, so we need to do an add/remove of 'uid'. */
$selfURL = Horde::selfUrl(true);
IMP::$newUrl = $selfURL = IMP::generateIMPUrl($selfURL->remove(array('actionID', 'mailbox', 'thismailbox', 'uid')), IMP::$mailbox, $uid, $mailbox_name)->add('message_token', $message_token);
$headersURL = $selfURL->copy()->remove(array('show_all_headers', 'show_list_headers'));

/* Generate previous/next links. */
$prev_msg = $imp_mailbox->getIMAPIndex(-1);
if ($prev_msg) {
    $prev_url = IMP::generateIMPUrl('message.php', IMP::$mailbox, $prev_msg['uid'], $prev_msg['mailbox']);
}
$next_msg = $imp_mailbox->getIMAPIndex(1);
if ($next_msg) {
    $next_url = IMP::generateIMPUrl('message.php', IMP::$mailbox, $next_msg['uid'], $next_msg['mailbox']);
}

/* Generate the mailbox link. */
$mailbox_url = IMP::generateIMPUrl('mailbox.php', IMP::$mailbox)->add('start', $msgindex);

/* Everything below here is related to preparing the output. */

/* Set the status information of the message. */
$msgAddresses[] = $mime_headers->getValue('from');
$identity = $match_identity = $user_identity->getMatchingIdentity($msgAddresses);
if (is_null($identity)) {
    $identity = $user_identity->getDefault();
}

$flag_parse = $imp_flags->parse(array(
    'div' => true,
    'flags' => $flags,
    'personal' => $match_identity
));

$status = '';
foreach ($flag_parse as $val) {
    if ($val['type'] == 'imapp') {
        $status .= '<span class="' . $val['classname'] . '" style="background:' . htmlspecialchars($val['bg']) . ';color:' . htmlspecialchars($val['fg']) . '">' . htmlspecialchars($val['label']) . '</span>';
    } else {
        $status .= $val['div'];
    }
}

/* If this is a search mailbox, display a link to the parent mailbox of the
 * message in the header. */
$h_page_label = htmlspecialchars($page_label);
$header_label = $h_page_label;
if ($search_mbox) {
    $header_label .= ' [' . Horde::link(Horde::url('mailbox.php')->add('mailbox', $mailbox_name)) . IMP::displayFolder($mailbox_name) . '</a>]';
}

/* Prepare the navbar top template. */
$t_template = $injector->createInstance('Horde_Template');
$t_template->set('message_url', $message_url);
$t_template->set('form_input', Horde_Util::formInput());
$t_template->set('mailbox', IMP::formMbox(IMP::$mailbox, true));
$t_template->set('thismailbox', htmlspecialchars($mailbox_name));
$t_template->set('start', htmlspecialchars($msgindex));
$t_template->set('uid', htmlspecialchars($uid));
$t_template->set('label', sprintf(_("%s: %s"), $header_label, $shortsub));
$t_template->set('msg_count', sprintf(_("(%d&nbsp;of&nbsp;%d)"), $msgindex, count($imp_mailbox)));
$t_template->set('status', $status);
$t_template->set('message_token', $message_token);

/* Prepare the navbar navigate template. */
$n_template = $injector->createInstance('Horde_Template');
$n_template->setOption('gettext', true);
$n_template->set('readonly', $readonly);
$n_template->set('usepop', $use_pop);
$n_template->set('id', 1);

if (!$use_pop) {
    $n_template->set('mailbox', IMP::formMbox(IMP::$mailbox, true));

    $tmp = $imp_flags->getFlagList(IMP::$mailbox);
    $n_template->set('flaglist_set', $tmp['set']);
    $n_template->set('flaglist_unset', $tmp['unset']);

    if ($conf['user']['allow_folders']) {
        $n_template->set('move', Horde::widget('#', _("Move to folder"), 'widget moveAction', '', '', _("Move"), true));
        $n_template->set('copy', Horde::widget('#', _("Copy to folder"), 'widget copyAction', '', '', _("Copy"), true));
        $n_template->set('options', IMP::flistSelect(array('heading' => _("This message to"), 'new_folder' => true, 'inc_tasklists' => true, 'inc_notepads' => true)));
    }
}

$n_template->set('back_to', Horde::widget($mailbox_url, sprintf(_("Back to %s"), $h_page_label), 'widget', '', '', sprintf(_("Bac_k to %s"), $h_page_label), true));

$rtl = !empty($registry->nlsconfig['rtl'][$language]);
if (Horde_Util::nonInputVar('prev_url')) {
    $n_template->set('prev', Horde::link($prev_url, _("Previous Message")));
    $n_template->set('prev_img', Horde::img($rtl ? 'nav/right.png' : 'nav/left.png', $rtl ? '>' : '<'));
} else {
    $n_template->set('prev_img', Horde::img($rtl ? 'nav/right-grey.png' : 'nav/left-grey.png'));
}

if (Horde_Util::nonInputVar('next_url')) {
    $n_template->set('next', Horde::link($next_url, _("Next Message")));
    $n_template->set('next_img', Horde::img($rtl ? 'nav/left.png' : 'nav/right.png', $rtl ? '<' : '>'));
} else {
    $n_template->set('next_img', Horde::img($rtl ? 'nav/left-grey.png' : 'nav/right-grey.png'));
}

/* Prepare the navbar actions template. */
$a_template = $injector->createInstance('Horde_Template');
$a_template->setOption('gettext', true);
$compose_params = array('identity' => $identity, 'thismailbox' => $mailbox_name, 'uid' => $uid);
if (!$prefs->getValue('compose_popup')) {
    $compose_params += array('start' => $msgindex, 'mailbox' => IMP::$mailbox);
}

if (!$readonly) {
    if (in_array('\\deleted', $flags)) {
        $a_template->set('delete', Horde::widget($self_link->copy()->add('actionID', 'undelete_message'), _("Undelete"), 'widget', '', '', _("Undelete"), true));
    } else {
        $a_template->set('delete', Horde::widget($self_link->copy()->add('actionID', 'delete_message'), _("Delete"), 'widget', '', '', _("_Delete"), true));
        if ($use_pop) {
            Horde::addInlineJsVars(array(
                'ImpMessage.pop3delete' => _("Are you sure you want to PERMANENTLY delete these messages?")
            ));
        }
    }
}

$disable_compose = !IMP::canCompose();

if (!$disable_compose) {
    $a_template->set('reply', Horde::widget(IMP::composeLink(array(), array('actionID' => 'reply_auto') + $compose_params), _("Reply"), 'widget hasmenu', '', '', _("_Reply"), true));
    $a_template->set('reply_sender', Horde::widget(IMP::composeLink(array(), array('actionID' => 'reply') + $compose_params), _("To Sender"), 'widget', '', '', _("To Sender"), true));

    if ($list_info['reply_list']) {
        $a_template->set('reply_list', Horde::widget(IMP::composeLink(array(), array('actionID' => 'reply_list') + $compose_params), _("To List"), 'widget', '', '', _("To _List"), true));
    }

    if (Horde_Mime_Address::addrArray2String(array_merge($envelope['to'], $envelope['cc']), array('charset' => 'UTF-8', 'filter' => array_keys($user_identity->getAllFromAddresses(true))))) {
        $a_template->set('show_reply_all', Horde::widget(IMP::composeLink(array(), array('actionID' => 'reply_all') + $compose_params), _("To All"), 'widget', '', '', _("To _All"), true));
    }

    $fwd_locked = $prefs->isLocked('forward_default');
    $a_template->set('forward', Horde::widget(IMP::composeLink(array(), array('actionID' => 'forward_auto') + $compose_params), _("Forward"), 'widget' . ($fwd_locked ? '' : ' hasmenu'), '', '', _("Fo_rward"), true));
    if (!$fwd_locked) {
        $a_template->set('forward_attach', Horde::widget(IMP::composeLink(array(), array('actionID' => 'forward_attach') + $compose_params), _("As Attachment"), 'widget', '', '', _("As Attachment"), true));
        $a_template->set('forward_body', Horde::widget(IMP::composeLink(array(), array('actionID' => 'forward_body') + $compose_params), _("In Body Text"), 'widget', '', '', _("In Body Text"), true));
        $a_template->set('forward_both', Horde::widget(IMP::composeLink(array(), array('actionID' => 'forward_both') + $compose_params), _("Attachment and Body Text"), 'widget', '', '', _("Attachment and Body Text"), true));
    }

    $a_template->set('redirect', Horde::widget(IMP::composeLink(array(), array('actionID' => 'redirect_compose') + $compose_params), _("Redirect"), 'widget', '', '', _("Redirec_t"), true));
}

if (IMP::threadSortAvailable(IMP::$mailbox)) {
    $a_template->set('show_thread', Horde::widget(IMP::generateIMPUrl('thread.php', IMP::$mailbox, $uid, $mailbox_name)->add(array('start' => $msgindex)), _("View Thread"), 'widget', '', '', _("_View Thread"), true));
}

if (!$readonly && $registry->hasMethod('mail/blacklistFrom')) {
    $a_template->set('blacklist', Horde::widget($self_link->copy()->add('actionID', 'blacklist'), _("Blacklist"), 'widget', '', '', _("_Blacklist"), true));
}

if (!$readonly && $registry->hasMethod('mail/whitelistFrom')) {
    $a_template->set('whitelist', Horde::widget($self_link->copy()->add('actionID', 'whitelist'), _("Whitelist"), 'widget', '', '', _("_Whitelist"), true));
}

if (!empty($conf['user']['allow_view_source'])) {
    $a_template->set('view_source', $imp_contents->linkViewJS($imp_contents->getMIMEMessage(), 'view_source', _("_Message Source"), array('jstext' => _("Message Source"), 'css' => 'widget', 'widget' => true)));
}

if (!$disable_compose) {
    $imp_ui_mailbox = new IMP_Ui_Mailbox($mailbox_name);
    if ($imp_ui_mailbox->isDraft($flags)) {
        $a_template->set('resume', Horde::widget(IMP::composeLink(array(), array('actionID' => 'draft') + $compose_params), _("Resume"), 'widget', '', '', _("Resume"), true));
    }
}

$imp_params = IMP::getIMPMboxParameters(IMP::$mailbox, $uid, $mailbox_name);
$a_template->set('save_as', Horde::widget(Horde::downloadUrl($subject, array_merge(array('actionID' => 'save_message'), $imp_params)), _("Save as"), 'widget', '', '', _("Sa_ve as"), 2));

if ($conf['spam']['reporting'] &&
    ($conf['spam']['spamfolder'] ||
     ($mailbox_name != IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
    $a_template->set('spam', Horde::widget('#', _("Report as Spam"), 'widget spamAction', '', '', _("Report as Spam"), true));
}

if ($conf['notspam']['reporting'] &&
    (!$conf['notspam']['spamfolder'] ||
     ($mailbox_name == IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
    $a_template->set('notspam', Horde::widget('#', _("Report as Innocent"), 'widget notspamAction', '', '', _("Report as Innocent"), true));
}

$a_template->set('redirect', Horde::widget(IMP::composeLink(array(), array('actionID' => 'redirect_compose') + $compose_params), _("Redirect"), 'widget', '', '', _("Redirec_t"), true));

$a_template->set('headers', Horde::widget('#', _("Headers"), 'widget hasmenu', '', '', _("Headers"), true));
if ($all_headers || $list_headers) {
    $a_template->set('common_headers', Horde::widget($headersURL, _("Show Common Headers"), 'widget', '', '', _("Show Common Headers"), true));
}
if (!$all_headers) {
    $a_template->set('all_headers', Horde::widget($headersURL->copy()->add('show_all_headers', 1), _("Show All Headers"), 'widget', '', '', _("Show All Headers"), true));
}
if ($list_info['exists'] && !$list_headers) {
    $a_template->set('list_headers', Horde::widget($headersURL->copy()->add('show_list_headers', 1), _("Show Mailing List Information"), 'widget', '', '', _("Show Mailing List Information"), true));
}

$hdrs = array();
$i = 1;

/* Prepare the main message template. */
$m_template = $injector->createInstance('Horde_Template');
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

/* Determine the fields that will appear in the MIME info entries. */
$part_info = $part_info_display = array('icon', 'description', 'size');
$part_info_action = array('download', 'download_zip', 'img_save', 'strip');
$part_info_bodyonly = array('print');

$show_parts = isset($vars->show_parts)
    ? $vars->show_parts
    : $prefs->getValue('parts_display');
$strip_atc = $prefs->getValue('strip_attachments');

$part_info_display = array_merge($part_info_display, $part_info_action, $part_info_bodyonly);
$contents_mask = IMP_Contents::SUMMARY_BYTES |
    IMP_Contents::SUMMARY_SIZE |
    IMP_Contents::SUMMARY_ICON |
    IMP_Contents::SUMMARY_DESCRIP_LINK |
    IMP_Contents::SUMMARY_DOWNLOAD |
    IMP_Contents::SUMMARY_DOWNLOAD_ZIP |
    IMP_Contents::SUMMARY_IMAGE_SAVE |
    IMP_Contents::SUMMARY_PRINT;
if (!$readonly && $strip_atc) {
    $contents_mask |= IMP_Contents::SUMMARY_STRIP_LINK;
}

/* Do MDN processing now. */
$mdntext = '';
if ($imp_ui->MDNCheck(IMP::$mailbox, $uid, $mime_headers, $vars->mdn_confirm)) {
    $mdntext .= $imp_ui->formatStatusMsg(array(array('text' => array(_("The sender of this message is requesting a Message Disposition Notification from you when you have read this message."), sprintf(_("Click %s to send the notification message."), Horde::link(htmlspecialchars($selfURL->copy()->add('mdn_confirm', 1))) . _("HERE") . '</a>')))));
}

/* Build body text. This needs to be done before we build the attachment list
 * that lives in the header. */
$inlineout = $imp_ui->getInlineOutput($imp_contents, array(
    'mask' => $contents_mask,
    'part_info_display' => $part_info_display,
    'show_parts' => $show_parts
));

/* Build the Attachments menu. */
$show_atc = false;
if ($show_parts == 'atc') {
    $a_template->set('show_parts_all', Horde::widget($headersURL->copy()->add(array('show_parts' => 'all')), _("Show All Message Parts"), 'widget', '', '', _("Show All Message Parts"), true));
    $show_atc = true;
}

if (count($inlineout['display_ids']) > 2) {
    $a_template->set('download_all', Horde::widget($imp_contents->urlView($imp_contents->getMIMEMessage(), 'download_all'), _("Download All Attachments (in .zip file)"), 'widget', '', '', _("Download All Attachments (in .zip file)"), true));
    if ($strip_atc) {
        $a_template->set('strip_all', Horde::widget(Horde::selfUrl(true)->remove(array('actionID'))->add(array('actionID' => 'strip_all', 'message_token' => $message_token)), _("Strip All Attachments"), 'widget stripAllAtc', '', '', _("Strip All Attachments"), true));
        Horde::addInlineJsVars(array(
            'ImpMessage.stripatc' => _("Are you sure you want to PERMANENTLY delete all attachments?")
        ));
    }

    $show_atc = true;
}

if ($show_atc) {
    $a_template->set('atc', Horde::widget('#', _("Attachments"), 'widget hasmenu', '', '', _("Attachments"), true));
}

/* Show attachment information in headers? 'atc_parts' will be empty if
 * 'parts_display' pref is 'none'. */
if (!empty($inlineout['atc_parts'])) {
    $tmp = array();

    if ($show_parts == 'all') {
        array_unshift($part_info, 'id');
    }

    foreach ($inlineout['atc_parts'] as $id) {
        $summary = $imp_contents->getSummary($id, $contents_mask);
        $tmp[] = '<tr>';
        foreach ($part_info as $val) {
            $tmp[] = '<td>' . $summary[$val] . '</td>';
        }
        $tmp[] = '<td>';
        foreach ($part_info_action as $val) {
            $tmp[] = $summary[$val];
        }
        $tmp[] = '</td></tr>';
    }

    $hdrs[] = array(
        'class' => 'msgheaderParts',
        'name' => ($show_parts == 'all') ? _("Parts") : _("Attachments"),
        'val' => '<table>' . implode('', $tmp) . '</table>',
        'i' => (++$i % 2)
    );
}

$m_template->set('headers', $hdrs);
$m_template->set('msgtext', $mdntext . $inlineout['msgtext']);

/* Output message page now. */
Horde::addInlineScript($inlineout['js_onload'], 'dom');
Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('imp.js', 'imp');
Horde::addScriptFile('message.js', 'imp');

if (!empty($conf['tasklist']['use_notepad']) || !empty($conf['tasklist']['use_tasklist'])) {
    Horde::addScriptFile('dialog.js', 'imp');
    Horde::addScriptFile('redbox.js', 'horde');
}

$menu = IMP::menu();
Horde::noDnsPrefetch();

require IMP_TEMPLATES . '/common-header.inc';

if (!empty($conf['maillog']['use_maillog'])) {
    IMP_Maillog::displayLog($envelope['message-id']);
}
echo $menu;
IMP::status();
IMP::quota();

echo $t_template->fetch(IMP_TEMPLATES . '/imp/message/navbar_top.html');
echo $n_template->fetch(IMP_TEMPLATES . '/imp/message/navbar_navigate.html');
echo $a_template->fetch(IMP_TEMPLATES . '/imp/message/navbar_actions.html');
echo $m_template->fetch(IMP_TEMPLATES . '/imp/message/message.html');

$a_template->set('isbottom', true);
echo $a_template->fetch(IMP_TEMPLATES . '/imp/message/navbar_actions.html');

$n_template->set('id', 2);
$n_template->set('isbottom', true);
echo $n_template->fetch(IMP_TEMPLATES . '/imp/message/navbar_navigate.html');

require $registry->get('templates', 'horde') . '/common-footer.inc';
