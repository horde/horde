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
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('impmode' => 'mimp'));

Horde_Nls::setTimeZone();
$vars = Horde_Variables::getDefaultVariables();

/* Make sure we have a valid index. */
$imp_mailbox = IMP_Mailbox::singleton($imp_mbox['mailbox'], $imp_mbox['uid'] . IMP::IDX_SEP . $imp_mbox['thismailbox']);
if (!$imp_mailbox->isValidIndex(false)) {
    header('Location: ' . IMP::generateIMPUrl('mailbox-mimp.php', $imp_mbox['mailbox'])->setRaw(true)->add('a', 'm'));
    exit;
}

$imp_message = $injector->getInstance('IMP_Message');
$imp_hdr_ui = new IMP_Ui_Headers();
$imp_ui = new IMP_Ui_Message();

/* Determine if mailbox is readonly. */
$readonly = $imp_imap->isReadOnly($imp_mbox['mailbox']);

/* Run through action handlers */
$msg_delete = false;
switch ($vars->a) {
// 'd' = delete message
// 'u' = undelete message
case 'd':
case 'u':
    if ($readonly) {
        break;
    }

    /* Get mailbox/UID of message. */
    $index_array = $imp_mailbox->getIMAPIndex();
    $index_array = $imp_mailbox->getIMAPIndex();
    $indices_array = array($index_array['mailbox'] => array($index_array['uid']));

    if ($vars->a == 'u') {
        $imp_message->undelete($indices_array);
    } else {
        try {
            Horde::checkRequestToken('imp.message-mimp', $vars->mt);
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
    $index_array = $imp_mailbox->getIMAPIndex();
    $msg_delete = (IMP_Spam::reportSpam(array($index_array['mailbox'] => array($index_array['uid'])), $vars->a == 'rs' ? 'spam' : 'innocent') === 1);
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
    ($msg_delete && $prefs->getValue('mailbox_return'))) {
    header('Location: ' . IMP::generateIMPUrl('mailbox-mimp.php', $imp_mbox['mailbox'])->setRaw(true)->add('s', $imp_mailbox->getMessageIndex()));
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
    header('Location: ' . IMP::generateIMPUrl('mailbox-mimp.php', $mailbox_name)->setRaw(true)->add('a', 'm'));
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
    header('Location: ' . IMP::generateIMPUrl('mailbox-mimp.php', $mailbox_name)->setRaw(true)->add('a', 'm'));
    exit;
}

/* Get the starting index for the current message and the message count. */
$msgindex = $imp_mailbox->getMessageIndex();
$msgcount = $imp_mailbox->getMessageCount();

/* Generate the mailbox link. */
$mailbox_link = IMP::generateIMPUrl('mailbox-mimp.php', $imp_mbox['mailbox'])->add('s', $msgindex);
$self_link = IMP::generateIMPUrl('message-mimp.php', $imp_mbox['mailbox'], $uid, $mailbox_name);

/* Initialize Horde_Template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);

/* Output download confirmation screen. */
if (($vars->a == 'c') && isset($vars->atc)) {
    $summary = $imp_contents->getSummary($vars->atc, IMP_Contents::SUMMARY_SIZE | IMP_Contents::SUMMARY_DESCRIP_NOLINK_NOHTMLSPECCHARS | IMP_Contents::SUMMARY_DOWNLOAD_NOJS);

    $title = _("Verify Download");

    $t->set('descrip', $summary['description']);
    $t->set('download', $summary['download']);
    $t->set('self_link', $self_link);
    $t->set('size', $summary['size']);
    $t->set('type', $summary['type']);

    require IMP_TEMPLATES . '/common-header.inc';
    echo $t->fetch(IMP_TEMPLATES . '/message/download-mimp.html');

    exit;
}

/* Create the Identity object. */
$user_identity = Horde_Prefs_Identity::singleton(array('imp', 'imp'));

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
    $subject = Horde_String::truncate(IMP::filterText($subject), 30);
} else {
    $subject = _("[No Subject]");
}
$display_headers['subject'] = $subject;

/* Check for the presence of mailing list information. */
$list_info = $imp_ui->getListInformation($mime_headers);

/* See if the priority has been set. */
switch($priority = $imp_hdr_ui->getPriority($mime_headers)) {
case 'high':
case 'low':
    $basic_headers['priority'] = _("Priority");
    $display_headers['priority'] = Horde_String::ucfirst($priority);
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

$flag_parse = $injector->getInstance('IMP_Imap_Flags')->parse(array(
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
    !isset($vars->fullmsg) &&
    (strlen($msg_text) > 250)) {
    $msg_text = Horde_String::substr(trim($msg_text), 0, 250) . " [...]\n";
    $t->set('fullmsg_link', $self_link->copy()->add('fullmsg', 1));
}

$t->set('msg', nl2br(Horde_Text_Filter::filter($msg_text, 'space2html', array('charset' => Horde_Nls::getCharset(), 'encode' => true))));

$compose_params = array(
    'identity' => $identity,
    'thismailbox' => $mailbox_name,
    'uid' => $uid,
);

$menu = array();
if (!$readonly) {
    $menu[] = in_array('\\deleted', $flags)
        ? array(_("Undelete"), $self_link->copy()->add('a', 'u'))
        : array(_("Delete"), $self_link->copy()->add(array('a' => 'd', 'mt' => Horde::getRequestToken('imp.message-mimp'))));
}

/* Add compose actions (Reply, Reply List, Reply All, Forward, Redirect). */
if (IMP::canCompose()) {
    $menu[] = array(_("Reply"), IMP::composeLink(array(), array('a' => 'r') + $compose_params));

    if ($list_info['reply_list']) {
        $menu[] = array(_("Reply to List"), IMP::composeLink(array(), array('a' => 'rl') + $compose_params));
    }

    if (Horde_Mime_Address::addrArray2String(array_merge($envelope['to'], $envelope['cc']), array('filter' => array_keys($user_identity->getAllFromAddresses(true))))) {
        $menu[] = array(_("Reply All"), IMP::composeLink(array(), array('a' => 'ra') + $compose_params));
    }

    $menu[] = array(_("Forward"), IMP::composeLink(array(), array('a' => 'f') + $compose_params));
    $menu[] = array(_("Redirect"), IMP::composeLink(array(), array('a' => 'rc') + $compose_params));
}

/* Generate previous/next links. */
if ($prev_msg = $imp_mailbox->getIMAPIndex(-1)) {
    $menu[] = array(_("Previous Message"), IMP::generateIMPUrl('message-mimp.php', $imp_mbox['mailbox'], $prev_msg['uid'], $prev_msg['mailbox']));
}
if ($next_msg = $imp_mailbox->getIMAPIndex(1)) {
    $menu[] = array(_("Next Message"), IMP::generateIMPUrl('message-mimp.php', $imp_mbox['mailbox'], $next_msg['uid'], $next_msg['mailbox']));
}

$menu[] = array(sprintf(_("To %s"), IMP::getLabel($imp_mbox['mailbox'])), $mailbox_link);

if ($conf['spam']['reporting'] &&
    ($conf['spam']['spamfolder'] ||
     ($mailbox_name != IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
    $menu[] = array(_("Report as Spam"), $self_link->copy()->add(array('a' => 'rs', 'mt' => Horde::getRequestToken('imp.message-mimp'))));
}

if ($conf['notspam']['reporting'] &&
    (!$conf['notspam']['spamfolder'] ||
     ($mailbox_name == IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
    $menu[] = array(_("Report as Innocent"), $self_link->copy()->add(array('a' => 'ri', 'mt' => Horde::getRequestToken('imp.message-mimp'))));
}

$t->set('menu', $injector->getInstance('IMP_Ui_Mimp')->getMenu('message', $menu));

$hdrs = array();
foreach ($display_headers as $head => $val) {
    $tmp = array(
        'label' => htmlspecialchars($basic_headers[$head])
    );
    if ((Horde_String::lower($head) == 'to') &&
        !isset($vars->allto) &&
        (($pos = strpos($val, ',')) !== false)) {
        $val = Horde_String::substr($val, 0, strpos($val, ','));
        $tmp['all_to'] = $self_link->copy()->add('allto', 1);
    }
    $tmp['val'] = $val;
    $hdrs[] = $tmp;
}
$t->set('hdrs', $hdrs);

$atc = array();
foreach ($atc_parts as $key) {
    $summary = $imp_contents->getSummary($key, IMP_Contents::SUMMARY_BYTES | IMP_Contents::SUMMARY_SIZE | IMP_Contents::SUMMARY_DESCRIP_NOLINK_NOHTMLSPECCHARS | IMP_Contents::SUMMARY_DOWNLOAD_NOJS);

    $tmp = array(
        'descrip' => $summary['description'],
        'size' => $summary['size'],
        'type' => $summary['type']
    );

    if (!empty($summary['download'])) {
        /* Preference: if set, only show download confirmation screen if
         * attachment over a certain size. */
        $tmp['download'] = ($summary['bytes'] > $prefs->getValue('mimp_download_confirm'))
            ? $self_link->copy()->add(array('a' => 'c', 'atc' => $key))
            : $summary['download'];
    }

    $atc[] = $tmp;
}
$t->set('atc', $atc);

$title = $display_headers['subject'];
$t->set('title', ($status ? $status . ' | ' : '') . $display_headers['subject'] . ' ' . sprintf(_("(%d of %d)"), $msgindex, $msgcount));

require IMP_TEMPLATES . '/common-header.inc';
IMP::status();
echo $t->fetch(IMP_TEMPLATES . '/message/message-mimp.html');

