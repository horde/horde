<?php
/**
 * Message thread display.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'timezone' => true
));

/* What mode are we in?
 * DEFAULT/'thread' - Thread mode
 * 'msgview' - Multiple message view
 */
$vars = Horde_Variables::getDefaultVariables();
$mode = $vars->mode
    ? $vars->mode
    : 'thread';

$imp_imap = $injector->getInstance('IMP_Imap')->getOb();
$imp_mailbox = $injector->getInstance('IMP_Mailbox')->getOb(IMP::$mailbox, new IMP_Indices(IMP::$thismailbox, IMP::$uid));

$error = false;
if ($mode == 'thread') {
    /* THREAD MODE: Make sure we have a valid index. */
    if (!$imp_mailbox->isValidIndex()) {
        $error = true;
    }
} else {
    /* MSGVIEW MODE: Make sure we have a valid list of messages. */
    $imp_indices = new IMP_Indices($vars->msglist);
    if (!$imp_indices->count()) {
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
$page_label = IMP::getLabel(IMP::$mailbox);

if ($mode == 'thread') {
    $threadob = $imp_mailbox->getThreadOb();
    $index_array = $imp_mailbox->getIMAPIndex();
    $thread = $threadob->getThread($index_array['uid']);

    $imp_thread = new IMP_Imap_Thread($threadob);
    $threadtree = $imp_thread->getThreadImageTree($thread, false);
    $imp_indices = new IMP_Indices(IMP::$mailbox, $thread);
}

$charset = $registry->getCharset();
$imp_ui = new IMP_Ui_Message();

foreach ($imp_indices->indices() as $mbox => $idxlist) {
    $fetch_res = $imp_imap->fetch($mbox, array(
        Horde_Imap_Client::FETCH_ENVELOPE => true
    ), array('ids' => $idxlist));

    foreach ($idxlist as $idx) {
        $envelope = $fetch_res[$idx]['envelope'];

        /* Get the body of the message. */
        $curr_msg = $curr_tree = array();
        $contents = $injector->getInstance('IMP_Contents')->getOb(new IMP_Indices($mbox, $idx));
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
        $curr_msg['date'] = $imp_ui->getLocalTime($envelope['date']);

        if (IMP::isSpecialFolder($mbox)) {
            $curr_msg['addr_to'] = true;
            $curr_msg['addr'] = _("To:") . ' ' . $imp_ui->buildAddressLinks($envelope['to'], Horde::selfUrl(true));
            $addr = _("To:") . ' ' . htmlspecialchars(Horde_Mime_Address::addrObject2String(reset($envelope['to'])), ENT_COMPAT, $charset);
        } else {
            $curr_msg['addr_to'] = false;
            $curr_msg['addr'] = $imp_ui->buildAddressLinks($envelope['from'], Horde::selfUrl(true));
            $addr = htmlspecialchars(Horde_Mime_Address::addrObject2String(reset($envelope['from'])), ENT_COMPAT, $charset);
        }

        $subject_header = htmlspecialchars($envelope['subject'], ENT_COMPAT, $charset);
        if (($mode == 'thread') && empty($subject)) {
            $subject = preg_replace('/^re:\s*/i', '', $subject_header);
        }

        /* Create links to current message and mailbox. */
        if ($mode == 'thread') {
            $curr_msg['link'] = Horde::widget('#display', _("Thread List"), 'widget', '', '', _("Thread List"), true);
        } else {
            $curr_msg['link'] = Horde::widget('#display', _("Back to Multiple Message View Index"), 'widget', '', '', _("Back to Multiple Message View Index"), true);
        }
        $curr_msg['link'] .= ' | ' . Horde::widget(IMP::generateIMPUrl('message.php', IMP::$mailbox, $idx, $mbox), _("Go to Message"), 'widget', '', '', _("Go to Message"), true);
        $curr_msg['link'] .= ' | ' . Horde::widget(IMP::generateIMPUrl('mailbox.php', $mbox)->add(array('start' => $imp_mailbox->getArrayIndex($idx))), sprintf(_("Back to %s"), $page_label), 'widget', '', '', sprintf(_("Bac_k to %s"), $page_label));

        $curr_tree['subject'] = (($mode == 'thread') ? $threadtree[$idx] : null) . ' ' . Horde::link('#i' . $idx) . Horde_String::truncate($subject_header, 60) . '</a> (' . $addr . ')';

        $msgs[] = $curr_msg;
        $tree[] = $curr_tree;
    }
}

/* Flag messages as seen. */
$injector->getInstance('IMP_Message')->flag(array('\\seen'), $imp_indices);

$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set(
    'subject',
    $mode == 'thread' ? $subject : sprintf(_("%d Messages"), count($msgs)));
if ($mode == 'thread') {
    $delete_link = IMP::generateIMPUrl('mailbox.php', $mbox)->add(array(
        'actionID' => 'delete_messages',
        'mailbox_token' => Horde::getRequestToken('imp.mailbox')
    ));
    foreach ($thread as $val) {
        $delete_link->add(array('indices[]' => strval(new IMP_Indices(IMP::$mailbox, $val)), 'start' => $imp_mailbox->getArrayIndex($val)));
    }
    $template->set('delete', Horde::link('#', _("Delete Thread"), null, null, "if (confirm('" . addslashes(_("Are you sure you want to delete all messages in this thread?")) . "')) { window.location = '" . $delete_link . "'; } return false;") . Horde::img('delete.png', _("Delete Thread")) . '</a>');
}
$template->set('thread', $mode == 'thread');
$template->set('messages', $msgs);
$template->set('tree', $tree);

/* Output page. */
$title = ($mode == 'thread') ? _("Thread View") : _("Multiple Message View");
Horde::addScriptFile('stripe.js', 'horde');
Horde::noDnsPrefetch();
IMP::prepareMenu();
require IMP_TEMPLATES . '/common-header.inc';
IMP::menu();
IMP::status();
echo $template->fetch(IMP_TEMPLATES . '/imp/thread/thread.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
