<?php
/**
 * Minimalist (mimp) compose display page.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp');

Horde_Nls::setTimeZone();

/* The message text and headers. */
$expand = array();
$header = array('to' => '', 'cc' => '', 'bcc' => '');
$msg = '';

/* Get the list of headers to display. */
$display_hdrs = array('to' => _("To: "));
if ($prefs->getValue('compose_cc')) {
    $display_hdrs['cc'] = _("Cc: ");
}
if ($prefs->getValue('compose_bcc')) {
    $display_hdrs['bcc'] = ("Bcc: ");
}

/* Set the current identity. */
$identity = Horde_Prefs_Identity::singleton(array('imp', 'imp'));
if (!$prefs->isLocked('default_identity')) {
    $identity_id = Horde_Util::getFormData('identity');
    if (!is_null($identity_id)) {
        $identity->setDefault($identity_id);
    }
}

$save_sent_mail = $prefs->getValue('save_sent_mail');
$sent_mail_folder = $identity->getValue('sent_mail_folder');
$thismailbox = Horde_Util::getFormData('thismailbox');
$uid = Horde_Util::getFormData('uid');

/* Determine if mailboxes are readonly. */
$draft = IMP::folderPref($prefs->getValue('drafts_folder'), true);
$readonly_drafts = empty($draft) ? false : $imp_imap->isReadOnly($draft);
if ($imp_imap->isReadOnly($sent_mail_folder)) {
    $save_sent_mail = false;
}

/* Determine if compose mode is disabled. */
$compose_disable = !IMP::canCompose();

/* Initialize objects. */
$composeCache = Horde_Util::getFormData('composeCache');
$imp_compose = IMP_Compose::singleton($composeCache);
$imp_ui = new IMP_Ui_Compose();

foreach (array_keys($display_hdrs) as $val) {
    $header[$val] = Horde_Util::getFormData($val);

    /* If we are reloading the screen, check for expand matches. */
    if ($composeCache) {
        $expanded = array();
        for ($i = 0; $i < 5; ++$i) {
            if ($tmp = Horde_Util::getFormData($val . '_expand_' . $i)) {
                $expanded[] = $tmp;
            }
        }
        if (!empty($expanded)) {
            $header['to'] = strlen($header['to'])
                ? implode(', ', $expanded) . ', ' . $header['to']
                : implode(', ', $expanded);
        }
    }
}

/* Run through the action handlers. */
$actionID = Horde_Util::getFormData('a');
switch ($actionID) {
// 'd' = draft
case 'd':
    try {
        $result = $imp_compose->resumeDraft($uid . IMP::IDX_SEP . $thismailbox);

        $msg = $result['msg'];
        $header = array_merge($header, $result['header']);
        if (!is_null($result['identity']) &&
            ($result['identity'] != $identity->getDefault()) &&
            !$prefs->isLocked('default_identity')) {
            $identity->setDefault($result['identity']);
            $sent_mail_folder = $identity->getValue('sent_mail_folder');
        }
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e, 'horde.error');
    }
    break;

case _("Expand Names"):
    $action = Horde_Util::getFormData('action');

    foreach (array_keys($display_hdrs) as $val) {
        if (($val == 'to') || ($action != 'rc')) {
            $res = $imp_ui->expandAddresses($header[$val], $imp_compose);
            if (is_string($res)) {
                $header[$val] = $res;
            } else {
                $header[$val] = $res[0];
                $expand[$val] = array_slice($res, 1);
            }
        }
    }

    if (!is_null($action)) {
        $actionID = $action;
    }
    break;

// 'r' = reply
// 'rl' = reply to list
// 'ra' = reply to all
case 'r':
case 'ra':
case 'rl':
    if (!($imp_contents = $imp_ui->getIMPContents($uid, $thismailbox))) {
        break;
    }
    $actions = array('r' => 'reply', 'ra' => 'reply_all', 'rl' => 'reply_list');
    $reply_msg = $imp_compose->replyMessage($actions[$actionID], $imp_contents, $header['to']);
    $header = $reply_msg['headers'];

    $notification->push(_("Reply text will be automatically appended to your outgoing message."), 'horde.message');
    break;

// 'f' = forward
case 'f':
    if (!($imp_contents = $imp_ui->getIMPContents($uid, $thismailbox))) {
        break;
    }
    $fwd_msg = $imp_compose->forwardMessage('forward_attach', $imp_contents, false);
    $header = $fwd_msg['headers'];

    $notification->push(_("Forwarded message will be automatically added to your outgoing message."), 'horde.message');
    break;

case _("Redirect"):
    if (!($imp_contents = $imp_ui->getIMPContents($uid, $thismailbox))) {
        break;
    }

    $f_to = $imp_ui->getAddressList($header['to']);

    try {
        $imp_ui->redirectMessage($f_to, $imp_compose, $imp_contents);
        if ($prefs->getValue('compose_confirm')) {
            $notification->push(_("Message redirected successfully."), 'horde.success');
        }
        require IMP_BASE . '/mailbox-mimp.php';
        exit;
    } catch (Horde_Exception $e) {
        $actionID = 'rc';
        $notification->push($e, 'horde.error');
    }
    break;

case _("Save Draft"):
case _("Send"):
    switch ($actionID) {
    case _("Save Draft"):
        if ($readonly_drafts) {
            break 2;
        }
        break;

    case _("Send"):
        if ($compose_disable) {
            break 2;
        }
        break;
    }

    $message = Horde_Util::getFormData('message', '');
    $f_to = $header['to'];
    $f_cc = $f_bcc = null;
    $old_header = $header;
    $header = array();

    $thismailbox = $imp_compose->getMetadata('mailbox');
    $uid = $imp_compose->getMetadata('uid');

    if ($ctype = $imp_compose->getMetadata('reply_type')) {
        if (!($imp_contents = $imp_ui->getIMPContents($uid, $thismailbox))) {
            break;
        }

        switch ($ctype) {
        case 'reply':
            $reply_msg = $imp_compose->replyMessage('reply', $imp_contents, $f_to);
            $msg = $reply_msg['body'];
            $message .= "\n" . $msg;
            break;

        case 'forward':
            $fwd_msg = $imp_compose->forwardMessage('forward_attach', $imp_contents);
            $msg = $fwd_msg['body'];
            $message .= "\n" . $msg;
            break;
        }
    }

    try {
        $header['from'] = $identity->getFromLine(null, Horde_Util::getFormData('from'));
    } catch (Horde_Exception $e) {
        $header['from'] = '';
    }
    $header['replyto'] = $identity->getValue('replyto_addr');
    $header['subject'] = Horde_Util::getFormData('subject');

    foreach ($display_hdrs as $val) {
        $header[$val] = $imp_ui->getAddressList($old_header[$val]);
    }

    switch ($actionID) {
    case _("Save Draft"):
        try {
            $notification->push($imp_compose->saveDraft($header, $message, Horde_Nls::getCharset(), false), 'horde.success');
            if ($prefs->getValue('close_draft')) {
                $imp_compose->destroy();
                require IMP_BASE . '/mailbox-mimp.php';
                exit;
            }
        } catch (IMP_Compose_Exception $e) {
            $notification->push($e, 'horde.error');
        }
        break;

    case _("Save"):
        $sig = $identity->getSignature();
        if (!empty($sig)) {
            $message .= "\n" . $sig;
        }

        $options = array(
            'save_sent' => $save_sent_mail,
            'sent_folder' => $sent_mail_folder,
            'readreceipt' => Horde_Util::getFormData('request_read_receipt')
        );

        try {
            if ($imp_compose->buildAndSendMessage($message, $header, Horde_Nls::getEmailCharset(), false, $options)) {
                $imp_compose->destroy();

                $notification->push(_("Message sent successfully."), 'horde.success');
                require IMP_BASE . '/mailbox-mimp.php';
                exit;
            }
        } catch (IMP_Compose_Exception $e) {
            $notification->push($e, 'horde.error');
        }
        break;
    }
    break;

case _("Cancel"):
    $imp_compose->destroy(false);
    require IMP_BASE . '/mailbox-mimp.php';
    exit;
}

/* Get the message cache ID. */
$cacheID = $imp_compose->getCacheId();

$title = _("Message Composition");
$mimp_render = new Horde_Mobile();
$mimp_render->set('title', $title);

$select_list = $identity->getSelectList();

/* Grab any data that we were supplied with. */
if (empty($msg)) {
    $msg = Horde_Util::getFormData('message', '');
}
if (empty($header['subject'])) {
    $header['subject'] = Horde_Util::getFormData('subject');
}

$menu = new Horde_Mobile_card('o', _("Menu"));
$mset = $menu->add(new Horde_Mobile_linkset());
IMP_Mimp::addMIMPMenu($mset, 'compose');

if ($actionID == 'rc') {
    require IMP_TEMPLATES . '/compose/redirect-mimp.inc';
} else {
    require IMP_TEMPLATES . '/compose/compose-mimp.inc';
}
