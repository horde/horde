<?php
/**
 * Message thread display.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'impmode' => Horde_Registry::VIEW_BASIC,
    'timezone' => true
));

/* What mode are we in?
 * DEFAULT/'thread' - Thread mode
 * 'msgview' - Multiple message view
 */
$vars = $injector->getInstance('Horde_Variables');
$mode = $vars->mode
    ? $vars->mode
    : 'thread';

$imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
$imp_mailbox = IMP::mailbox()->getListOb(IMP::mailbox(true)->getIndicesOb(IMP::uid()));

$error = false;
if ($mode == 'thread') {
    /* THREAD MODE: Make sure we have a valid index. */
    if (!$imp_mailbox->isValidIndex()) {
        $error = true;
    }
} else {
    /* MSGVIEW MODE: Make sure we have a valid list of messages. */
    $imp_indices = new IMP_Indices($vars->msglist);
    if (!count($imp_indices)) {
        $error = true;
    }
}

if ($error) {
    $actionID = 'message_missing';
    $from_message_page = true;
    $start = null;
    require IMP_BASE . '/mailbox.php';
    exit;
}

/* Run through action handlers. */
$actionID = $vars->actionID;
switch ($actionID) {
case 'add_address':
    try {
        $contact_link = IMP::addAddress($vars->address, $vars->name);
        $notification->push(sprintf(_("Entry \"%s\" was successfully added to the address book"), $contact_link), 'horde.success', array('content.raw'));
    } catch (Horde_Exception $e) {
        $notification->push($e);
    }
    break;
}

$msgs = $tree = array();
$rowct = 0;

$subject = '';
$page_label = IMP::mailbox()->label;

if ($mode == 'thread') {
    $index = $imp_mailbox->getIMAPIndex();
    $imp_indices = $imp_mailbox->getFullThread($index['uid'], $index['mailbox']);
}

$charset = 'UTF-8';
$imp_ui = new IMP_Ui_Message();

$query = new Horde_Imap_Client_Fetch_Query();
$query->envelope();

foreach ($imp_indices as $ob) {
    $fetch_res = $imp_imap->fetch($ob->mbox, $query, array(
        'ids' => $imp_imap->getIdsOb($ob->uids)
    ));

    foreach ($ob->uids as $idx) {
        $envelope = $fetch_res[$idx]->getEnvelope();

        /* Get the body of the message. */
        $curr_msg = $curr_tree = array();
        $contents = $injector->getInstance('IMP_Factory_Contents')->create($ob->mbox->getIndicesOb($idx));
        $mime_id = $contents->findBody();
        if ($contents->canDisplay($mime_id, IMP_Contents::RENDER_INLINE)) {
            $ret = $contents->renderMIMEPart($mime_id, IMP_Contents::RENDER_INLINE);
            $ret = reset($ret);
            $curr_msg['body'] = $ret['data'];
        } else {
            $curr_msg['body'] = '<em>' . _("There is no text that can be displayed inline.") . '</em>';
        }
        $curr_msg['idx'] = $idx;

        /* Get headers for the message. */
        $curr_msg['date'] = $imp_ui->getLocalTime($envelope->date);

        if (IMP::mailbox()->special_outgoing) {
            $curr_msg['addr_to'] = true;
            $curr_msg['addr'] = _("To:") . ' ' . $imp_ui->buildAddressLinks($envelope->to, Horde::selfUrl(true));
            $addr = _("To:") . ' ' . htmlspecialchars(strval($envelope->to[0]), ENT_COMPAT, $charset);
        } else {
            $from = $envelope->from;
            $curr_msg['addr_to'] = false;
            $curr_msg['addr'] = $imp_ui->buildAddressLinks($from, Horde::selfUrl(true));
            $addr = htmlspecialchars(strval($from), ENT_COMPAT, $charset);
        }

        $subject_header = htmlspecialchars($envelope->subject, ENT_COMPAT, $charset);
        if (($mode == 'thread') && empty($subject)) {
            $subject = preg_replace('/^re:\s*/i', '', $subject_header);
        }

        /* Create links to current message and mailbox. */
        if ($mode == 'thread') {
            $curr_msg['link'] = Horde::widget('#display', _("Thread List"), 'widget', '', '', _("Thread List"), true);
        } else {
            $curr_msg['link'] = Horde::widget('#display', _("Back to Multiple Message View Index"), 'widget', '', '', _("Back to Multiple Message View Index"), true);
        }
        $curr_msg['link'] .= ' | ' . Horde::widget(IMP::mailbox()->url('message.php', $idx, $ob->mbox), _("Go to Message"), 'widget', '', '', _("Go to Message"), true);
        $curr_msg['link'] .= ' | ' . Horde::widget(IMP::mailbox()->url('mailbox.php')->add(array('start' => $imp_mailbox->getArrayIndex($idx))), sprintf(_("Back to %s"), $page_label), 'widget', '', '', sprintf(_("Bac_k to %s"), $page_label));

        $curr_tree['subject'] = ($mode == 'thread')
            ? $imp_mailbox[$imp_mailbox->getArrayIndex($fetch_res[$idx]->getUid(), $ob->mbox) + 1]['t']->img
            : ' ';
        $curr_tree['subject'] .= Horde::link('#i' . $idx) . Horde_String::truncate($subject_header, 60) . '</a> (' . $addr . ')';

        $msgs[] = $curr_msg;
        $tree[] = $curr_tree;
    }
}

/* Flag messages as seen. */
$injector->getInstance('IMP_Message')->flag(array(Horde_Imap_Client::FLAG_SEEN), $imp_indices);

$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set(
    'subject',
    $mode == 'thread' ? $subject : sprintf(_("%d Messages"), count($msgs)));
if ($mode == 'thread') {
    $delete_link = IMP::mailbox()->url('mailbox.php')->add(array(
        'actionID' => 'delete_messages',
        'mailbox_token' => $injector->getInstance('Horde_Token')->get('imp.mailbox')
    ));
    foreach ($thread as $val) {
        $delete_link->add(array('indices[]' => strval(IMP::mailbox()->getIndicesOb($val)), 'start' => $imp_mailbox->getArrayIndex($val)));
    }
    $template->set('delete', Horde::link($delete_link, _("Delete Thread"), null, null, null, null, null, array('id' => 'threaddelete')));
    $page_output->addInlineScript(array(
        '$("threaddelete").observe("click", function(e) { if (!window.confirm(' . Horde_Serialize::serialize(_("Are you sure you want to delete all messages in this thread?"), Horde_Serialize::JSON, $charset) . ')) { e.stop(); } })'
    ), true);
}
$template->set('thread', $mode == 'thread');
$template->set('messages', $msgs);
$template->set('tree', $tree);

/* Output page. */
$page_output->addScriptFile('stripe.js', 'horde');
$page_output->noDnsPrefetch();
$menu = IMP::menu();
IMP::header($mode == 'thread' ? _("Thread View") : _("Multiple Message View"));
echo $menu;
IMP::status();
echo $template->fetch(IMP_TEMPLATES . '/imp/thread/thread.html');
$page_output->footer();
