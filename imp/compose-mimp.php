<?php
/**
 * Mobile (MIMP) compose display page.
 *
 * URL Parameters:
 *   'a' = (string) The action ID.
 *   'action' = (string) TODO
 *   'bcc' => (string) TODO
 *   'bcc_expand_[1-5]' => (string) TODO
 *   'cc' => (string) TODO
 *   'cc_expand_[1-5]' => (string) TODO
 *   'composeCache' = (string) TODO
 *   'from' => (string) TODO
 *   'identity' = (integer) The identity to use for composing.
 *   'message' = (string) TODO
 *   'subject' => (string) TODO
 *   'to' => (string) TODO
 *   'to_expand_[1-5]' => (string) TODO
 *   'u' => (string) Unique ID (cache buster).
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
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

/* The message text and headers. */
$expand = array();
$header = array('to' => '', 'cc' => '', 'bcc' => '');
$msg = '';
$title = _("Compose Message");

/* Get the list of headers to display. */
$display_hdrs = array('to' => _("To: "));
if ($prefs->getValue('compose_cc')) {
    $display_hdrs['cc'] = _("Cc: ");
}
if ($prefs->getValue('compose_bcc')) {
    $display_hdrs['bcc'] = ("Bcc: ");
}

/* Set the current identity. */
$identity = $injector->getInstance('IMP_Identity');
if (!$prefs->isLocked('default_identity') && isset($vars->identity)) {
    $identity->setDefault($vars->identity);
}

$draft = IMP::folderPref($prefs->getValue('drafts_folder'), true);
$sent_mail_folder = $identity->getValue('sent_mail_folder');

/* Determine if mailboxes are readonly. */
$imp_imap = $injector->getInstance('IMP_Imap')->getOb();
$readonly_drafts = empty($draft)
    ? false
    : $imp_imap->isReadOnly($draft);
$save_sent_mail = $imp_imap->isReadOnly($sent_mail_folder)
    ? false
    : $prefs->getValue('save_sent_mail');

/* Determine if compose mode is disabled. */
$compose_disable = !IMP::canCompose();

/* Initialize objects. */
$imp_compose = $injector->getInstance('IMP_Compose')->getOb($vars->composeCache);
$imp_ui = new IMP_Ui_Compose();

foreach (array_keys($display_hdrs) as $val) {
    $header[$val] = $vars->$val;

    /* If we are reloading the screen, check for expand matches. */
    if ($vars->composeCache) {
        $expanded = array();
        for ($i = 0; $i < 5; ++$i) {
            if ($tmp = $vars->get($val . '_expand_' . $i)) {
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

/* Add attachment. */
if ($_SESSION['imp']['file_upload'] &&
    !$imp_compose->addFilesFromUpload('upload_', $vars->a == _("Expand Names")) &&
    ($vars->a != _("Expand Names"))) {
    $vars->a = null;
}

/* Run through the action handlers. */
switch ($vars->a) {
// 'd' = draft
case 'd':
    try {
        $result = $imp_compose->resumeDraft(new IMP_Indices(IMP::$thismailbox, IMP::$uid));

        $msg = $result['msg'];
        $header = array_merge($header, $result['header']);
        if (!is_null($result['identity']) &&
            ($result['identity'] != $identity->getDefault()) &&
            !$prefs->isLocked('default_identity')) {
            $identity->setDefault($result['identity']);
            $sent_mail_folder = $identity->getValue('sent_mail_folder');
        }
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e);
    }
    break;

case _("Expand Names"):
    foreach (array_keys($display_hdrs) as $val) {
        if (($val == 'to') || ($vars->action != 'rc')) {
            $res = $imp_ui->expandAddresses($header[$val], $imp_compose);
            if (is_string($res)) {
                $header[$val] = $res;
            } else {
                $header[$val] = $res[0];
                $expand[$val] = array_slice($res, 1);
            }
        }
    }

    if (isset($vars->action)) {
        $vars->a = $vars->action;
    }
    break;

// 'r' = reply
// 'rl' = reply to list
// 'ra' = reply to all
case 'r':
case 'ra':
case 'rl':
    if (!($imp_contents = $imp_ui->getIMPContents(new IMP_Indices(IMP::$thismailbox, IMP::$uid)))) {
        break;
    }
    $actions = array('r' => 'reply', 'ra' => 'reply_all', 'rl' => 'reply_list');
    $reply_msg = $imp_compose->replyMessage($actions[$vars->a], $imp_contents, $header['to']);
    $header = $reply_msg['headers'];

    $notification->push(_("Reply text will be automatically appended to your outgoing message."), 'horde.message');
    $title = _("Reply");
    break;

// 'f' = forward
case 'f':
    if (!($imp_contents = $imp_ui->getIMPContents(new IMP_Indices(IMP::$thismailbox, IMP::$uid)))) {
        break;
    }
    $fwd_msg = $imp_compose->forwardMessage('forward_attach', $imp_contents, false);
    $header = $fwd_msg['headers'];

    $notification->push(_("Forwarded message will be automatically added to your outgoing message."), 'horde.message');
    $title = _("Forward");
    break;

// 'rc' = redirect compose
case 'rc':
    $title = _("Redirect");
    if (!($imp_contents = $imp_ui->getIMPContents(new IMP_Indices(IMP::$thismailbox, IMP::$uid)))) {
        // TODO: Error message
        break;
    }
    $imp_compose->redirectMessage($imp_contents);
    break;

case _("Redirect"):
    try {
        $imp_compose->sendRedirectMessage($imp_ui->getAddressList($header['to']));
        $imp_compose->destroy('send');

        if ($prefs->getValue('compose_confirm')) {
            $notification->push(_("Message redirected successfully."), 'horde.success');
        }
        require IMP_BASE . '/mailbox-mimp.php';
        exit;
    } catch (Horde_Exception $e) {
        $vars->a = 'rc';
        $notification->push($e);
    }
    break;

case _("Save Draft"):
case _("Send"):
    switch ($vars->a) {
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

    $message = strval($vars->message);
    $f_to = $header['to'];
    $f_cc = $f_bcc = null;
    $old_header = $header;
    $header = array();

    if ($ctype = $imp_compose->getMetadata('reply_type')) {
        if (!($imp_contents = $imp_ui->getIMPContents(new IMP_Indices($imp_compose->getMetadata('mailbox'), $imp_compose->getMetadata('uid'))))) {
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
        $header['from'] = $identity->getFromLine(null, $vars->from);
    } catch (Horde_Exception $e) {
        $header['from'] = '';
    }
    $header['replyto'] = $identity->getValue('replyto_addr');
    $header['subject'] = strval($vars->subject);

    foreach ($display_hdrs as $val) {
        $header[$val] = $imp_ui->getAddressList($old_header[$val]);
    }

    switch ($vars->a) {
    case _("Save Draft"):
        try {
            $notification->push($imp_compose->saveDraft($header, $message, $registry->getCharset(), false), 'horde.success');
            if ($prefs->getValue('close_draft')) {
                $imp_compose->destroy('save_draft');
                require IMP_BASE . '/mailbox-mimp.php';
                exit;
            }
        } catch (IMP_Compose_Exception $e) {
            $notification->push($e);
        }
        break;

    case _("Save"):
        $sig = $identity->getSignature();
        if (!empty($sig)) {
            $message .= "\n" . $sig;
        }

        $options = array(
            'identity' => $identity,
            'readreceipt' => ($conf['compose']['allow_receipts'] && ($prefs->getValue('disposition_request_read') == 'always')),
            'save_sent' => $save_sent_mail,
            'sent_folder' => $sent_mail_folder
        );

        try {
            if ($imp_compose->buildAndSendMessage($message, $header, $GLOBALS['registry']->getEmailCharset(), false, $options)) {
                $imp_compose->destroy('send');

                $notification->push(_("Message sent successfully."), 'horde.success');
                require IMP_BASE . '/mailbox-mimp.php';
                exit;
            }
        } catch (IMP_Compose_Exception $e) {
            $notification->push($e);

            /* Switch to tied identity. */
            if (!is_null($e->tied_identity)) {
                $identity->setDefault($e->tied_identity);
                $notification->push(_("Your identity has been switched to the identity associated with the current recipient address. The identity will not be checked again during this compose action."));
            }
        }
        break;
    }
    break;

case _("Cancel"):
    $imp_compose->destroy('cancel');
    require IMP_BASE . '/mailbox-mimp.php';
    exit;
}

/* Initialize Horde_Template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);

/* Grab any data that we were supplied with. */
if (empty($msg)) {
    $msg = strval($vars->message);
}
if (empty($header['subject'])) {
    $header['subject'] = strval($vars->subject);
}

$t->set('cacheid', htmlspecialchars($imp_compose->getCacheId()));
$t->set('menu', $injector->getInstance('IMP_Ui_Mimp')->getMenu('compose'));
$t->set('to', htmlspecialchars($header['to']));
$t->set('url', Horde::applicationUrl('compose-mimp.php'));

if ($vars->a == 'rc') {
    $t->set('redirect', true);
    unset($display_hdrs['cc'], $display_hdrs['bcc']);
} else {
    $t->set('compose_enable', !$compose_disable);
    $t->set('msg', htmlspecialchars($msg));
    $t->set('save_draft', $conf['user']['allow_folders'] && !$readonly_drafts);
    $t->set('subject', htmlspecialchars($header['subject']));

    if (!$prefs->isLocked('default_identity')) {
        $tmp = array();
        foreach ($identity->getSelectList() as $key => $val) {
            $tmp[] = array(
                'key' => $key,
                'sel' => ($key == $identity->getDefault()),
                'val' => $val
            );
        }
        $t->set('identities', $tmp);
    }

    $title = _("Message Composition");
}

$hdrs = array();
foreach ($display_hdrs as $key => $val) {
    $tmp = array(
        'key' => $key,
        'label' => htmlspecialchars($val),
        'val' => $header[$key]
    );

    if (isset($expand[$key])) {
        $tmp['matchlabel'] = (count($expand[$key][1]) > 5)
            ? sprintf(_("Ambiguous matches for \"%s\" (first 5 matches displayed):"), $expand[$key][0])
            : sprintf(_("Ambiguous matches for \"%s\":"), $expand[$key][0]);

        $tmp['match'] = array();
        foreach (array_slice($expand[$key][1], 0, 5) as $key2 => $val2) {
            $tmp['match'][] = array(
                'id' => $key . '_expand_' . $key2,
                'val' => htmlspecialchars($val2)
            );
        }
    }

    $hdrs[] = $tmp;
}

$t->set('hdrs', $hdrs);
$t->set('title', $title);

/* Activate advanced compose attachments UI? */
if ($_SESSION['imp']['file_upload']) {
    try {
        if (Horde::callHook('mimp_advanced', array('compose_attach'), 'imp')) {
            $t->set('attach', true);
            if ($atc_list = $imp_compose->getAttachments()) {
                $imp_ui_mbox = new IMP_Ui_Mailbox();
                $t->set('attach_data', sprintf("%s [%s] - %s", htmlspecialchars($atc_list[0]['part']->getName()), htmlspecialchars($atc_list[0]['part']->getType()), $imp_ui_mbox->getSize($atc_list[0]['part']->getBytes())));
            }
        }
    } catch (Horde_Exception_HookNotSet $e) {}
}

require IMP_TEMPLATES . '/common-header.inc';
IMP::status();
echo $t->fetch(IMP_TEMPLATES . '/mimp/compose/compose.html');
