<?php
/**
 * Message thread display.
 * Usable in both basic and dynamic views.
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
    'timezone' => true
));

$vars = $injector->getInstance('Horde_Variables');

$imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
$imp_mailbox = IMP::mailbox()->getListOb(IMP::mailbox(true)->getIndicesOb(IMP::uid()));

$error = false;
switch ($mode = $vars->get('mode', 'thread')) {
case 'thread':
    /* THREAD MODE: Make sure we have a valid index. */
    if (($registry->getView() == $registry::VIEW_BASIC) &&
        !$imp_mailbox->isValidIndex()) {
        $error = true;
    }
    break;

default:
    /* MSGVIEW MODE: Make sure we have a valid list of messages. */
    $imp_indices = new IMP_Indices($vars->msglist);
    $error = !count($imp_indices);
    break;
}

if ($error) {
    $actionID = 'message_missing';
    $from_message_page = true;
    $start = null;
    require IMP_BASE . '/mailbox.php';
    exit;
}

/* Run through action handlers. */
switch ($vars->actionID) {
case 'add_address':
    try {
        $contact_link = $injector->getInstance('IMP_Ui_Contacts')->addAddress($vars->address, $vars->name);
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
    switch ($registry->getView()) {
    case $registry::VIEW_BASIC:
        $index = $imp_mailbox[$imp_mailbox->getIndex()];
        $imp_indices = $imp_mailbox->getFullThread($index['u'], $index['m']);
        break;

    case $registry::VIEW_DYNAMIC:
        $imp_indices = $imp_mailbox->getFullThread(IMP::uid(), IMP::mailbox(true));
        break;
    }
}

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
            $addr = _("To:") . ' ' . htmlspecialchars(strval($envelope->to[0]), ENT_COMPAT, 'UTF-8');
        } else {
            $from = $envelope->from;
            $curr_msg['addr_to'] = false;
            $curr_msg['addr'] = $imp_ui->buildAddressLinks($from, Horde::selfUrl(true));
            $addr = htmlspecialchars(strval($from), ENT_COMPAT, 'UTF-8');
        }

        $subject_header = htmlspecialchars($envelope->subject, ENT_COMPAT, 'UTF-8');
        if (($mode == 'thread') && empty($subject)) {
            $subject = preg_replace('/^re:\s*/i', '', $subject_header);
        }

        /* Create links to current message and mailbox. */
        $curr_msg['link'] = ($mode == 'thread')
            ? Horde::widget(array('url' => '#display', 'title' => _("Thread List"), 'nocheck' => true))
            : Horde::widget(array('url' => '#display', 'title' => _("Back to Multiple Message View Index"), 'nocheck' => true));

        switch ($registry->getView()) {
        case $registry::VIEW_BASIC:
            $curr_msg['link'] .= ' | ' . Horde::widget(array('url' => IMP::mailbox()->url('message.php', $idx, $ob->mbox), 'title' => _("Go to Message"), 'nocheck' => true)) .
                ' | ' . Horde::widget(array('url' => IMP::mailbox()->url('mailbox.php')->add(array('start' => $imp_mailbox->getArrayIndex($idx))), 'title' => sprintf(_("Bac_k to %s"), $page_label)));
            break;
        }

        $curr_tree['subject'] = ($mode == 'thread')
            ? $imp_mailbox->getThreadOb($imp_mailbox->getArrayIndex($fetch_res[$idx]->getUid(), $ob->mbox) + 1)->img
            : ' ';
        $curr_tree['subject'] .= Horde::link('#i' . $idx) . Horde_String::truncate($subject_header, 60) . '</a> (' . $addr . ')';

        $msgs[] = $curr_msg;
        $tree[] = $curr_tree;
    }
}

/* Flag messages as seen. */
$injector->getInstance('IMP_Message')->flag(array(Horde_Imap_Client::FLAG_SEEN), $imp_indices);

$view = new Horde_View(array(
    'templatePath' => IMP_TEMPLATES . '/thread'
));

if ($mode == 'thread') {
    $view->subject = $subject;
    $view->thread = true;

    switch ($registry->getView()) {
    case $registry::VIEW_BASIC:
        $uid_list = $imp_indices[strval(IMP::mailbox())];
        $delete_link = IMP::mailbox()->url('mailbox.php')->add(array(
            'actionID' => 'delete_messages',
            'indices' => strval($imp_indices),
            'mailbox_token' => $injector->getInstance('Horde_Token')->get('imp.mailbox'),
            'start' => $imp_mailbox->getArrayIndex(end($uid_list))
        ));
        $view->delete = Horde::link($delete_link, _("Delete Thread"), null, null, null, null, null, array('id' => 'threaddelete'));
        $page_output->addInlineScript(array(
            '$("threaddelete").observe("click", function(e) { if (!window.confirm(' . Horde_Serialize::serialize(_("Are you sure you want to delete all messages in this thread?"), Horde_Serialize::JSON) . ')) { e.stop(); } })'
        ), true);
        break;
    }
} else {
    $view->subject = sprintf(_("%d Messages"), count($msgs));
}
$view->messages = $msgs;
$view->tree = $tree;

/* Output page. */
$page_output->addScriptFile('stripe.js', 'horde');
$page_output->noDnsPrefetch();

switch ($registry->getView()) {
case $registry::VIEW_DYNAMIC:
    $page_output->topbar = $page_output->sidebar = false;
    $page_output->header(array(
        'html_id' => 'dynamic_thread',
        'title' => _("Thread View")
    ));
    break;

default:
    IMP::header($mode == 'thread' ? _("Thread View") : _("Multiple Message View"));
    break;
}

IMP::status();
echo $view->render('thread');
$page_output->footer();
