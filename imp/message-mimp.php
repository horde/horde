<?php
/**
 * Minimalist (MIMP) message display page.
 *
 * URL Parameters:
 *   'a' - (string) actionID
 *   'allto' - (boolean) View all To addresses?
 *   'mt' - (string) Message token
 *   'fullmsg' - (boolean) View full message?
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

/* Make sure we have a valid index. */
$imp_mailbox = IMP_Mailbox::singleton($imp_mbox['mailbox'], $imp_mbox['uid']);
if (!$imp_mailbox->isValidIndex(false)) {
    header('Location: ' . Horde_Util::addParameter(IMP::generateIMPUrl('mailbox-mimp.php', $imp_mbox['mailbox']), array('a' => 'm'), null, false));
    exit;
}

$imp_message = IMP_Message::singleton();
$imp_ui = new IMP_UI_Message();

/* Determine if mailbox is readonly. */
$readonly = $imp_imap->isReadOnly($imp_mbox['mailbox']);

/* Set the current time zone. */
Horde_Nls::setTimeZone();

/* Run through action handlers */
$actionID = Horde_Util::getFormData('a');
$msg_delete = false;
switch ($actionID) {
// 'd' = delete message
// 'u' = undelete message
case 'd':
case 'u':
    if ($readonly) {
        break;
    }

    /* Get mailbox/UID of message. */
    $index_array = $imp_mailbox->getIMAPIndex();
    $indices_array = array($index_array['mailbox'] => array($index_array['uid']));

    if ($actionID == 'u') {
        $imp_message->undelete($indices_array);
    } else {
        try {
            Horde::checkRequestToken('imp.message-mimp', Horde_Util::getFormData('mt'));
            $imp_message->delete($indices_array);
            $msg_delete = false;
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }
    }
    break;

// 'rs' = report spam
// 'ri' = report innocent
case 'rs':
case 'ri':
    if (IMP_Spam::reportSpam(array($index_array['mailbox'] => array($index_array['uid'])), $actionID == 'rs' ? 'spam' : 'innocent') === 1) {
        $delete_msg = true;
        break;
    }
    break;

// 'c' = confirm download
// Need to build message information, so don't do action until below.
}

if ($imp_ui->moveAfterAction()) {
    $imp_mailbox->setIndex(1, 'offset');
}

/* We may have done processing that has taken us past the end of the
 * message array, so we will return to mailbox.php if that is the
 * case. */
if (!$imp_mailbox->isValidIndex() ||
    ($delete_msg && $prefs->getValue('mailbox_return'))) {
    header('Location: ' . Horde_Util::addParameter(IMP::generateIMPUrl('mailbox-mimp.php', $imp_mbox['mailbox']), array('s' => $imp_mailbox->getMessageIndex()), null, false));
    exit;
}

/* Now that we are done processing the messages, get the index and
 * array index of the current message. */
$index_array = $imp_mailbox->getIMAPIndex();
$mailbox_name = $index_array['mailbox'];
$uid = $index_array['uid'];

/* Get envelope/flag/header information. */
try {
    /* Need to fetch flags before HEADERTEXT, because SEEN flag might be set
     * before we can grab it. */
    $flags_ret = $imp_imap->ob()->fetch($mailbox_name, array(
        Horde_Imap_Client::FETCH_FLAGS => true,
    ), array('ids' => array($uid)));
    $fetch_ret = $imp_imap->ob()->fetch($mailbox_name, array(
        Horde_Imap_Client::FETCH_ENVELOPE => true,
        Horde_Imap_Client::FETCH_HEADERTEXT => array(array('parse' => true, 'peek' => $readonly))
    ), array('ids' => array($uid)));
} catch (Horde_Imap_Client_Exception $e) {
    header('Location: ' . Horde_Util::addParameter(IMP::generateIMPUrl('mailbox-mimp.php', $mailbox_name), array('a' => 'm'), null, false));
    exit;
}

$envelope = $fetch_ret[$uid]['envelope'];
$flags = $flags_ret[$uid]['flags'];
$mime_headers = reset($fetch_ret[$uid]['headertext']);
$use_pop = ($_SESSION['imp']['protocol'] == 'pop');

/* Parse the message. */
try {
    $imp_contents = IMP_Contents::singleton($uid . IMP::IDX_SEP . $mailbox_name);
} catch (Horde_Exception $e) {
    header('Location: ' . Horde_Util::addParameter(IMP::generateIMPUrl('mailbox-mimp.php', $mailbox_name), array('a' => 'm'), null, false));
    exit;
}

/* Get the starting index for the current message and the message count. */
$msgindex = $imp_mailbox->getMessageIndex();
$msgcount = $imp_mailbox->getMessageCount();

/* Generate the mailbox link. */
$mailbox_link = Horde_Util::addParameter(IMP::generateIMPUrl('mailbox-mimp.php', $imp_mbox['mailbox']), array('s' => $msgindex));
$self_link = IMP::generateIMPUrl('message-mimp.php', $imp_mbox['mailbox'], $uid, $mailbox_name);

/* Init render object. */
$mimp_render = new Horde_Mobile();

/* Output download confirmation screen. */
$atc_id = Horde_Util::getFormData('atc');
if (($actionID == 'c') && !is_null($atc_id)) {
    $summary = $imp_contents->getSummary($atc_id, IMP_Contents::SUMMARY_SIZE | IMP_Contents::SUMMARY_DESCRIP_NOLINK_NOHTMLSPECCHARS | IMP_Contents::SUMMARY_DOWNLOAD_NOJS);

    $mimp_render->set('title', _("Verify Download"));

    $null = null;
    $hb = &$mimp_render->add(new Horde_Mobile_block($null));

    $hb->add(new Horde_Mobile_text(_("Click to verify download of attachment") . ': '));
    $hb->add(new Horde_Mobile_link($summary['description'], $summary['download']));
    $t = &$hb->add(new Horde_Mobile_text(sprintf(' [%s] %s', $summary['type'], $summary['size']) . "\n"));
    $t->set('linebreaks', true);

    $hb = &$mimp_render->add(new Horde_Mobile_block($null));
    $hb->add(new Horde_Mobile_link(_("Return to message view"), $self_link));

    $mimp_render->display();
    exit;
}

/* Create the Identity object. */
$user_identity = Identity::singleton(array('imp', 'imp'));

/* Develop the list of headers to display. */
$basic_headers = $imp_ui->basicHeaders();
$display_headers = $msgAddresses = array();

$format_date = $imp_ui->getLocalTime($envelope['date']);
if (!empty($format_date)) {
    $display_headers['date'] = $format_date;
}

/* Build From address links. */
$display_headers['from'] = $imp_ui->buildAddressLinks($envelope['from'], null, false);

/* Build To/Cc/Bcc links. */
foreach (array('to', 'cc', 'bcc') as $val) {
    $msgAddresses[] = $mime_headers->getValue($val);
    $addr_val = $imp_ui->buildAddressLinks($envelope[$val], null, false);
    if (!empty($addr_val)) {
        $display_headers[$val] = $addr_val;
    }
}

/* Process the subject now. */
if (($subject = $mime_headers->getValue('subject'))) {
    /* Filter the subject text, if requested. */
    $subject = IMP::filterText($subject);

    /* Generate the shortened subject text. */
    if (Horde_String::length($subject) > $conf['mimp']['mailbox']['max_subj_chars']) {
        $subject = Horde_String::substr($subject, 0, $conf['mimp']['mailbox']['max_subj_chars']) . '...';
    }
} else {
    $subject = _("[No Subject]");
}
$display_headers['subject'] = $subject;

/* Check for the presence of mailing list information. */
$list_info = $imp_ui->getListInformation($mime_headers);

/* See if the 'X-Priority' header has been set. */
$xpriority = $mime_headers->getValue('x-priority');
switch ($imp_ui->getXpriority($xpriority)) {
case 'high':
case 'low':
    $basic_headers['priority'] = _("Priority");
    $display_headers['priority'] = $xpriority;
    break;
}

/* Set the status information of the message. */
$status = '';
$match_identity = $identity = null;

if (!empty($msgAddresses)) {
    $match_identity = $identity = $user_identity->getMatchingIdentity($msgAddresses);
    if (is_null($identity)) {
        $identity = $user_identity->getDefault();
    }
}

$imp_flags = IMP_Imap_Flags::singleton();
$flag_parse = $imp_flags->parse(array(
    'flags' => $flags,
    'personal' => $match_identity
));

foreach ($flag_parse as $val) {
    if (isset($val['abbrev'])) {
        $status .= $val['abbrev'];
    } elseif ($val['type'] == 'imapp') {
        if (Horde_String::length($val['label']) > 8) {
            $status .= ' *' . Horde_String::substr($val['label'], 0, 5) . '...*';
        } else {
            $status .= ' *' . $val['label'] . '*';
        }
    }
}

/* Generate previous/next links. */
$prev_msg = $imp_mailbox->getIMAPIndex(-1);
if ($prev_msg) {
    $prev_link = IMP::generateIMPUrl('message-mimp.php', $imp_mbox['mailbox'], $prev_msg['uid'], $prev_msg['mailbox']);
}
$next_msg = $imp_mailbox->getIMAPIndex(1);
if ($next_msg) {
    $next_link = IMP::generateIMPUrl('message-mimp.php', $imp_mbox['mailbox'], $next_msg['uid'], $next_msg['mailbox']);
}

/* Create the body of the message. */
$parts_list = $imp_contents->getContentTypeMap();
$atc_parts = $display_ids = array();
$body_shown = false;
$msg_text = '';

foreach ($parts_list as $mime_id => $mime_type) {
    if (in_array($mime_id, $display_ids, true)) {
        continue;
    }

    if ($body_shown ||
        !($render_mode = $imp_contents->canDisplay($mime_id, IMP_Contents::RENDER_INLINE | IMP_Contents::RENDER_INFO))) {
        if ($imp_contents->isAttachment($mime_type)) {
            $atc_parts[] = $mime_id;
        }
        continue;
    }

    $render_part = $imp_contents->renderMIMEPart($mime_id, $render_mode);
    if (($render_mode & IMP_Contents::RENDER_INLINE) && empty($render_part)) {
        /* This meant that nothing was rendered - allow this part to appear
         * in the attachment list instead. */
        $atc_parts[] = $mime_id;
        continue;
    }

    while (list($id, $info) = each($render_part)) {
        if ($body_shown) {
            $atc_parts[] = $id;
            continue;
        }

        if (empty($info)) {
            continue;
        }

        $body_shown = true;
        $msg_text = $info['data'];
    }
}

/* Display the first 250 characters, or display the entire message? */
if ($prefs->getValue('mimp_preview_msg') &&
    !Horde_Util::getFormData('fullmsg') &&
    (strlen($msg_text) > 250)) {
    $msg_text = Horde_String::substr(trim($msg_text), 0, 250) . " [...]\n";
    $fullmsg_link = new Horde_Mobile_link(_("View Full Message"), Horde_Util::addParameter($self_link, array('fullmsg' => 1)));
} else {
    $fullmsg_link = null;
}

/* Create message menu. */
$menu = new Horde_Mobile_card('o', _("Menu"));
$mset = &$menu->add(new Horde_Mobile_linkset());

if (!$readonly) {
    if (in_array('\\deleted', $flags)) {
        $mset->add(new Horde_Mobile_link(_("Undelete"), Horde_Util::addParameter($self_link, array('a' => 'u'))));
    } else {
        $mset->add(new Horde_Mobile_link(_("Delete"), Horde_Util::addParameter($self_link, array('a' => 'd', 'mt' => Horde::getRequestToken('imp.message-mimp')))));
    }
}

$compose_params = array(
    'identity' => $identity,
    'thismailbox' => $mailbox_name,
    'uid' => $uid,
);

/* Add compose actions (Reply, Reply List, Reply All, Forward, Redirect). */
if (IMP::canCompose()) {
    $items = array(IMP::composeLink(array(), array('a' => 'r') + $compose_params) => _("Reply"));

    if ($list_info['reply_list']) {
        $items[IMP::composeLink(array(), array('a' => 'rl') + $compose_params)] = _("Reply to List");
    }

    if (Horde_Mime_Address::addrArray2String(array_merge($envelope['to'], $envelope['cc']), array('filter' => array_keys($user_identity->getAllFromAddresses(true))))) {
        $items[IMP::composeLink(array(), array('a' => 'ra') + $compose_params)] = _("Reply All");
    }

    $items[IMP::composeLink(array(), array('a' => 'f') + $compose_params)] = _("Forward");
    $items[IMP::composeLink(array(), array('a' => 'rc') + $compose_params)] = _("Redirect");
}

foreach ($items as $link => $label) {
    $mset->add(new Horde_Mobile_link($label, $link));
}

if (isset($next_link)) {
    $mset->add(new Horde_Mobile_link(_("Next Message"), $next_link));
}
if (isset($prev_link)) {
    $mset->add(new Horde_Mobile_link(_("Previous Message"), $prev_link));
}

$mset->add(new Horde_Mobile_link(sprintf(_("To %s"), IMP::getLabel($imp_mbox['mailbox'])), $mailbox_link));

if ($conf['spam']['reporting'] &&
    ($conf['spam']['spamfolder'] ||
     ($mailbox_name != IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
    $mset->add(new Horde_Mobile_link(_("Report as Spam"), Horde_Util::addParameter($self_link, array('a' => 'rs', 'mt' => Horde::getRequestToken('imp.message-mimp')))));
}

if ($conf['notspam']['reporting'] &&
    (!$conf['notspam']['spamfolder'] ||
     ($mailbox_name == IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
    $mset->add(new Horde_Mobile_link(_("Report as Innocent"), Horde_Util::addParameter($self_link, array('a' => 'ri', 'mt' => Horde::getRequestToken('imp.message-mimp')))));
}

IMP_Mimp::addMIMPMenu($mset, 'message');

$mimp_render->set('title', $display_headers['subject']);

$c = &$mimp_render->add(new Horde_Mobile_card('m', ($status ? $status . ' | ' : '') . $display_headers['subject'] . ' ' . sprintf(_("(%d of %d)"), $msgindex, $msgcount)));
$c->softkey('#o', _("Menu"));

$imp_notify->setMobileObject($c);
$notification->notify(array('listeners' => 'status'));

$null = null;
$hb = &$c->add(new Horde_Mobile_block($null));

$allto_param = Horde_Util::getFormData('allto');

foreach ($display_headers as $head => $val) {
    $all_to = false;
    $hb->add(new Horde_Mobile_text($basic_headers[$head] . ': ', array('b')));
    if ((Horde_String::lower($head) == 'to') &&
        !$allto_param &&
        (($pos = strpos($val, ',')) !== false)) {
        $val = Horde_String::substr($val, 0, strpos($val, ','));
        $all_to = true;
    }
    $t = &$hb->add(new Horde_Mobile_text($val . (($all_to) ? ' ' : "\n")));
    if ($all_to) {
        $hb->add(new Horde_Mobile_link('[' . _("Show All") . ']', Horde_Util::addParameter($self_link, array('allto' => 1))));
        $t = &$hb->add(new Horde_Mobile_text("\n"));
    }
    $t->set('linebreaks', true);
}

foreach ($atc_parts as $key) {
    $summary = $imp_contents->getSummary($key, IMP_Contents::SUMMARY_SIZE | IMP_Contents::SUMMARY_DESCRIP_NOLINK_NOHTMLSPECCHARS | IMP_Contents::SUMMARY_DOWNLOAD_NOJS);
    $hb->add(new Horde_Mobile_text(_("Attachment") . ': ', array('b')));
    if (empty($summary['download'])) {
        $hb->add(new Horde_Mobile_text($summary['description']));
    } else {
        $hb->add(new Horde_Mobile_link($summary['description'], Horde_Util::addParameter($self_link, array('a' => 'c', 'atc' => $key))));
    }
    $t = &$hb->add(new Horde_Mobile_text(sprintf(' [%s] %s', $summary['type'], $summary['size']) . "\n"));
    $t->set('linebreaks', true);
}

$t = &$c->add(new Horde_Mobile_text($msg_text));
$t->set('linebreaks', true);

if (!is_null($fullmsg_link)) {
    $c->add($fullmsg_link);
}

$mimp_render->add($menu);
$mimp_render->display();
