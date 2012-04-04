<?php
/**
 * Mobile (MIMP) compose display page.
 *
 * URL Parameters:
 *   - a: (string) The action ID.
 *   - action: (string) The action ID (used on redirect page).
 *   - bcc: (string) BCC address(es).
 *   - bcc_expand_[1-5]: (string) Expand matches for BCC addresses.
 *   - cc: (string) CC address(es).
 *   - cc_expand_[1-5]: (string) Expand matches for BCC addresses.
 *   - composeCache: (string) Compose object cache ID.
 *   - from: (string) From address to use.
 *   - identity: (integer) The identity to use for composing.
 *   - message: (string) Message text.
 *   - subject: (string) Message subject.
 *   - to: (string) To address(es).
 *   - to_expand_[1-5]: (string) Expand matches for To addresses.
 *   - u: (string) Unique ID (cache buster).
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
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

/* Determine if mailboxes are readonly. */
$drafts = IMP_Mailbox::getPref('drafts_folder');
$readonly_drafts = $drafts && $drafts->readonly;
$sent_mail = $identity->getValue('sent_mail_folder');
$save_sent_mail = ($sent_mail && $sent_mail->readonly)
    ? false
    : $prefs->getValue('save_sent_mail');

/* Determine if compose mode is disabled. */
$compose_disable = !IMP::canCompose();

/* Initialize objects. */
$imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($vars->composeCache);
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
if ($session->get('imp', 'file_upload') &&
    !$imp_compose->addFilesFromUpload('upload_', $vars->a == _("Expand Names")) &&
    ($vars->a != _("Expand Names"))) {
    $vars->a = null;
}

/* Run through the action handlers. */
switch ($vars->a) {
// 'd' = draft
// 'en' = edit as new
// 't' = template
case 'd':
case 'en':
case 't':
    try {
        $indices_ob = IMP::mailbox(true)->getIndicesOb(IMP::uid());

        switch ($vars->a) {
        case 'd':
            $result = $imp_compose->resumeDraft($indices_ob);
            break;

        case 'en':
            $result = $imp_compose->editAsNew($indices_ob);
            break;

        case 't':
            $result = $imp_compose->useTemplate($indices_ob);
            break;
        }

        $msg = $result['msg'];
        $header = array_merge($header, $result['header']);
        if (!is_null($result['identity']) &&
            ($result['identity'] != $identity->getDefault()) &&
            !$prefs->isLocked('default_identity')) {
            $identity->setDefault($result['identity']);
            $sent_mail = $identity->getValue('sent_mail_folder');
        }
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e);
    }
    break;

case _("Expand Names"):
    foreach (array_keys($display_hdrs) as $val) {
        if (($val == 'to') || ($vars->action != 'rc')) {
            $res = $imp_ui->expandAddresses($header[$val]);
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
    try {
        $imp_contents = $imp_ui->getContents();
    } catch (IMP_Exception $e) {
        $notification->push($e, 'horde.error');
        break;
    }

    $actions = array(
        'r' => IMP_Compose::REPLY_SENDER,
        'ra' => IMP_Compose::REPLY_ALL,
        'rl' => IMP_Compose::REPLY_LIST
    );

    $reply_msg = $imp_compose->replyMessage($actions[$vars->a], $imp_contents, $header['to']);
    $header = $reply_msg['headers'];

    $notification->push(_("Reply text will be automatically appended to your outgoing message."), 'horde.message');
    $title = _("Reply");
    break;

// 'f' = forward
case 'f':
    try {
        $imp_contents = $imp_ui->getContents();
    } catch (IMP_Exception $e) {
        $notification->push($e, 'horde.error');
        break;
    }

    $fwd_msg = $imp_compose->forwardMessage(IMP_Compose::FORWARD_ATTACH, $imp_contents, false);
    $header = $fwd_msg['headers'];

    $notification->push(_("Forwarded message will be automatically added to your outgoing message."), 'horde.message');
    $title = _("Forward");
    break;

// 'rc' = redirect compose
case 'rc':
    $imp_compose->redirectMessage($imp_ui->getIndices());
    $title = _("Redirect");
    break;

case _("Redirect"):
    try {
        $num_msgs = $imp_compose->sendRedirectMessage($header['to']);
        $imp_compose->destroy('send');

        $notification->push(ngettext("Message redirected successfully.", "Messages redirected successfully.", count($num_msgs)), 'horde.success');
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

    switch ($imp_compose->replyType(true)) {
    case IMP_Compose::REPLY:
        $reply_msg = $imp_compose->replyMessage(IMP_Compose::REPLY_SENDER, $imp_compose->getContentsOb(), $f_to);
        $msg = $reply_msg['body'];
        $message .= "\n" . $msg;
        break;

    case IMP_Compose::FORWARD:
        $fwd_msg = $imp_compose->forwardMessage(IMP_Compose::FORWARD_ATTACH, $imp_compose->getContentsOb());
        $msg = $fwd_msg['body'];
        $message .= "\n" . $msg;
        break;
    }

    try {
        $header['from'] = strval($identity->getFromLine(null, $vars->from));
    } catch (Horde_Exception $e) {
        $header['from'] = '';
    }
    $header['replyto'] = $identity->getValue('replyto_addr');
    $header['subject'] = strval($vars->subject);

    foreach (array_keys($display_hdrs) as $val) {
        $header[$val] = $old_header[$val];
    }

    switch ($vars->a) {
    case _("Save Draft"):
        try {
            $notification->push($imp_compose->saveDraft($header, $message), 'horde.success');
            if ($prefs->getValue('close_draft')) {
                $imp_compose->destroy('save_draft');
                require IMP_BASE . '/mailbox-mimp.php';
                exit;
            }
        } catch (IMP_Compose_Exception $e) {
            $notification->push($e);
        }
        break;

    case _("Send"):
        $options = array(
            'add_signature' => $identity->getDefault(),
            'identity' => $identity,
            'readreceipt' => ($prefs->getValue('request_mdn') == 'always'),
            'save_sent' => $save_sent_mail,
            'sent_mail' => $sent_mail
        );

        try {
            $imp_compose->buildAndSendMessage($message . $identity->getSignature(), $header, $options);
            $imp_compose->destroy('send');

            $notification->push(_("Message sent successfully."), 'horde.success');
            require IMP_BASE . '/mailbox-mimp.php';
            exit;
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
$t->set('url', Horde::url('compose-mimp.php'));

if ($vars->a == 'rc') {
    $t->set('redirect', true);
    unset($display_hdrs['cc'], $display_hdrs['bcc']);
} else {
    $t->set('compose_enable', !$compose_disable);
    $t->set('msg', htmlspecialchars($msg));
    $t->set('save_draft', $injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS) && !$readonly_drafts);
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
if ($session->get('imp', 'file_upload')) {
    try {
        if (Horde::callHook('mimp_advanced', array('compose_attach'), 'imp')) {
            $t->set('attach', true);
            if (count($imp_compose)) {
                $imp_ui_mbox = new IMP_Ui_Mailbox();
                $t->set('attach_data', sprintf("%s [%s] - %s", htmlspecialchars($atc_list[0]['part']->getName()), htmlspecialchars($atc_list[0]['part']->getType()), $imp_ui_mbox->getSize($atc_list[0]['part']->getBytes())));
            }
        }
    } catch (Horde_Exception_HookNotSet $e) {}
}

IMP::header($title);
IMP::status();
echo $t->fetch(IMP_TEMPLATES . '/mimp/compose/compose.html');
