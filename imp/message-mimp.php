<?php
/**
 * Mobile (MIMP) message display page.
 *
 * URL Parameters:
 *   'a' - (string) actionID
 *   'allto' - (boolean) View all To addresses?
 *   'mt' - (string) Message token
 *   'fullmsg' - (boolean) View full message?
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
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

$vars = Horde_Variables::getDefaultVariables();

/* Make sure we have a valid index. */
$imp_mailbox = $injector->getInstance('IMP_Factory_MailboxList')->create(IMP::$mailbox, new IMP_Indices(IMP::$thismailbox, IMP::$uid));
if (!$imp_mailbox->isValidIndex()) {
    IMP::generateIMPUrl('mailbox-mimp.php', IMP::$mailbox)->add('a', 'm')->redirect();
}

$readonly = $injector->getInstance('IMP_Factory_Imap')->create()->isReadOnly(IMP::$mailbox);

$imp_ui_mimp = $injector->getInstance('IMP_Ui_Mimp');
$imp_hdr_ui = new IMP_Ui_Headers();
$imp_ui = new IMP_Ui_Message();

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
    $msg_index = $imp_mailbox->getMessageIndex();
    $imp_indices = new IMP_Indices($imp_mailbox);
    $imp_message = $injector->getInstance('IMP_Message');

    if ($vars->a == 'd') {
        try {
            $injector->getInstance('Horde_Token')->validate($vars->mt, 'imp.message-mimp');
            $msg_delete = (bool)$imp_message->delete($imp_indices);
        } catch (Horde_Token_Exception $e) {
            $notification->push($e);
        }
    } else {
        $imp_message->undelete($imp_indices);
    }
    break;

// 'rs' = report spam
// 'ri' = report innocent
case 'rs':
case 'ri':
    $msg_index = $imp_mailbox->getMessageIndex();
    $msg_delete = (IMP_Spam::reportSpam(new IMP_Indices($imp_mailbox), $vars->a == 'rs' ? 'spam' : 'innocent', array('mailboxob' => $imp_mailbox)) === 1);
    break;

// 'pa' = part action
// Need to build message information, so don't do action until below.
}

if ($msg_delete && $imp_ui->moveAfterAction()) {
    $imp_mailbox->setIndex(1);
}

/* We may have done processing that has taken us past the end of the
 * message array, so we will return to mailbox.php if that is the
 * case. */
if (!$imp_mailbox->isValidIndex() ||
    ($msg_delete && $prefs->getValue('mailbox_return'))) {
    IMP::generateIMPUrl('mailbox-mimp.php', IMP::$mailbox)->add('s', $msg_index)->redirect();
}

/* Now that we are done processing the messages, get the index and
 * array index of the current message. */
$index_ob = $imp_mailbox->getIMAPIndex();
$mailbox_name = $index_ob['mailbox'];
$uid = $index_ob['uid'];

/* Get envelope/flag/header information. */
try {
    /* Need to fetch flags before HEADERTEXT, because SEEN flag might be set
     * before we can grab it. */
    $query = new Horde_Imap_Client_Fetch_Query();
    $query->flags();
    $flags_ret = $injector->getInstance('IMP_Factory_Imap')->create()->fetch($mailbox_name, $query, array('ids' => array($uid)));

    $query = new Horde_Imap_Client_Fetch_Query();
    $query->envelope();
    $query->headerText(array(
        'parse' => true,
        'peek' => $readonly
    ));
    $fetch_ret = $injector->getInstance('IMP_Factory_Imap')->create()->fetch($mailbox_name, $query, array('ids' => array($uid)));
} catch (Horde_Imap_Client_Exception $e) {
    IMP::generateIMPUrl('mailbox-mimp.php', $mailbox_name)->add('a', 'm')->redirect();
}

$envelope = $fetch_ret[$uid]['envelope'];
$flags = $flags_ret[$uid]['flags'];
$mime_headers = reset($fetch_ret[$uid]['headertext']);
$use_pop = ($session->get('imp', 'protocol') == 'pop');

/* Parse the message. */
try {
    $imp_contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices($imp_mailbox));
} catch (IMP_Exception $e) {
    IMP::generateIMPUrl('mailbox-mimp.php', $mailbox_name)->add('a', 'm')->redirect();
}

/* Get the starting index for the current message and the message count. */
$msgindex = $imp_mailbox->getMessageIndex();
$msgcount = count($imp_mailbox);

/* Generate the mailbox link. */
$mailbox_link = IMP::generateIMPUrl('mailbox-mimp.php', IMP::$mailbox)->add('s', $msgindex);
$self_link = IMP::generateIMPUrl('message-mimp.php', IMP::$mailbox, $uid, $mailbox_name);

/* Initialize Horde_Template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);

/* Output part action screen. */
if (($vars->a == 'pa') &&
    (isset($vars->atc) || isset($vars->id))) {
    if (isset($vars->atc)) {
        $summary = $imp_contents->getSummary($vars->atc, IMP_Contents::SUMMARY_SIZE | IMP_Contents::SUMMARY_DESCRIP_NOLINK_NOHTMLSPECCHARS | IMP_Contents::SUMMARY_DOWNLOAD_NOJS);

        $title = _("Download Attachment");

        $t->set('descrip', $summary['description']);
        $t->set('download', $summary['download']);
        $t->set('size', $summary['size']);
        $t->set('type', $summary['type']);
    } else {
        $title = _("View Attachment");

        $data = $imp_contents->renderMIMEPart($vars->id, $imp_contents->canDisplay($vars->id, IMP_Contents::RENDER_INLINE | IMP_Contents::RENDER_INFO));
        $t->set('view_data', $data ? $data : _("This part is empty."));
    }

    $t->set('self_link', $self_link);

    require IMP_TEMPLATES . '/common-header.inc';
    echo $t->fetch(IMP_TEMPLATES . '/mimp/message/part.html');

    exit;
}

/* Create the Identity object. */
$user_identity = $injector->getInstance('IMP_Identity');

/* Develop the list of headers to display. */
$basic_headers = $imp_ui->basicHeaders();
$display_headers = $msgAddresses = array();

if (($subject = $mime_headers->getValue('subject'))) {
    /* Filter the subject text, if requested. */
    $subject = Horde_String::truncate(IMP::filterText($subject), 50);
} else {
    $subject = _("[No Subject]");
}
$display_headers['subject'] = $subject;

$format_date = $imp_ui->getLocalTime($envelope->date);
if (!empty($format_date)) {
    $display_headers['date'] = $format_date;
}

/* Build From address links. */
$display_headers['from'] = $imp_ui->buildAddressLinks($envelope->from, null, false);

/* Build To/Cc/Bcc links. */
foreach (array('to', 'cc', 'bcc') as $val) {
    $msgAddresses[] = $mime_headers->getValue($val);
    $addr_val = $imp_ui->buildAddressLinks($envelope->$val, null, false);
    if (!empty($addr_val)) {
        $display_headers[$val] = $addr_val;
    }
}

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

$flag_parse = $injector->getInstance('IMP_Flags')->parse(array(
    'flags' => $flags,
    'personal' => $match_identity
));

foreach ($flag_parse as $val) {
    if ($abbrev = $val->abbreviation) {
        $status .= $abbrev;
    } elseif ($val instanceof IMP_Flag_User) {
        $status .= ' *' . Horde_String::truncate($val->label, 8) . '*';
    }
}

/* Create the body of the message. */
$inlineout = $imp_ui->getInlineOutput($imp_contents, array(
    'display_mask' => IMP_Contents::RENDER_INLINE,
    'no_inline_all' => !$prefs->getValue('mimp_inline_all'),
    'sep' => '<br /><hr />'
));

$msg_text = $inlineout['msgtext'];

/* Display the first 250 characters, or display the entire message? */
if ($prefs->getValue('mimp_preview_msg') &&
    !isset($vars->fullmsg) &&
    (strlen($msg_text) > 250)) {
    $msg_text = Horde_String::substr(trim($msg_text), 0, 250) . " [...]\n";
    $t->set('fullmsg_link', $self_link->copy()->add('fullmsg', 1));
}

$t->set('msg', nl2br($injector->getInstance('Horde_Core_Factory_TextFilter')->filter($msg_text, 'space2html', array('encode' => true))));

$compose_params = array(
    'identity' => $identity,
    'thismailbox' => $mailbox_name,
    'uid' => $uid,
);

$menu = array();
if (!$readonly) {
    $menu[] = in_array('\\deleted', $flags)
        ? array(_("Undelete"), $self_link->copy()->add('a', 'u'))
        : array(_("Delete"), $self_link->copy()->add(array('a' => 'd', 'mt' => $injector->getInstance('Horde_Token')->get('imp.message-mimp'))));
}

/* Add compose actions (Reply, Reply List, Reply All, Forward, Redirect). */
if (IMP::canCompose()) {
    $menu[] = array(_("Reply"), IMP::composeLink(array(), array('a' => 'r') + $compose_params));

    if ($list_info['reply_list']) {
        $menu[] = array(_("Reply to List"), IMP::composeLink(array(), array('a' => 'rl') + $compose_params));
    }

    if (Horde_Mime_Address::addrArray2String(array_merge($envelope->to, $envelope->cc), array('charset' => 'UTF-8', 'filter' => array_keys($user_identity->getAllFromAddresses(true))))) {
        $menu[] = array(_("Reply All"), IMP::composeLink(array(), array('a' => 'ra') + $compose_params));
    }

    $menu[] = array(_("Forward"), IMP::composeLink(array(), array('a' => 'f') + $compose_params));
    $menu[] = array(_("Redirect"), IMP::composeLink(array(), array('a' => 'rc') + $compose_params));
}

/* Generate previous/next links. */
if ($prev_msg = $imp_mailbox->getIMAPIndex(-1)) {
    $menu[] = array(_("Previous Message"), IMP::generateIMPUrl('message-mimp.php', IMP::$mailbox, $prev_msg['uid'], $prev_msg['mailbox']));
}
if ($next_msg = $imp_mailbox->getIMAPIndex(1)) {
    $menu[] = array(_("Next Message"), IMP::generateIMPUrl('message-mimp.php', IMP::$mailbox, $next_msg['uid'], $next_msg['mailbox']));
}

$menu[] = array(sprintf(_("To %s"), IMP::getLabel(IMP::$mailbox)), $mailbox_link);

if ($conf['spam']['reporting'] &&
    ($conf['spam']['spamfolder'] ||
     ($mailbox_name != IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
    $menu[] = array(_("Report as Spam"), $self_link->copy()->add(array('a' => 'rs', 'mt' => $injector->getInstance('Horde_Token')->get('imp.message-mimp'))));
}

if ($conf['notspam']['reporting'] &&
    (!$conf['notspam']['spamfolder'] ||
     ($mailbox_name == IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
    $menu[] = array(_("Report as Innocent"), $self_link->copy()->add(array('a' => 'ri', 'mt' => $injector->getInstance('Horde_Token')->get('imp.message-mimp'))));
}

$t->set('menu', $imp_ui_mimp->getMenu('message', $menu));

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
foreach ($inlineout['atc_parts'] as $key) {
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
            ? $self_link->copy()->add(array('a' => 'pa', 'atc' => $key))
            : $summary['download'];
    }

    if ($imp_contents->canDisplay($key, IMP_Contents::RENDER_INLINE_AUTO)) {
        $tmp['view'] = $self_link->copy()->add(array('a' => 'pa', 'id' => $key));
    }

    $atc[] = $tmp;
}
$t->set('atc', $atc);

$title = $display_headers['subject'];
$t->set('title', ($status ? $status . ' ' : '') . sprintf(_("(Message %d of %d)"), $msgindex, $msgcount));

Horde::noDnsPrefetch();

require IMP_TEMPLATES . '/common-header.inc';
IMP::status();
echo $t->fetch(IMP_TEMPLATES . '/mimp/message/message.html');
