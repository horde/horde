<?php
/**
 * Compose script for traditional (IMP) view.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
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
    'impmode' => Horde_Registry::VIEW_BASIC,
    'session_control' => 'netscape'
));

$vars = $injector->getInstance('Horde_Variables');

/* Mailto link handler: redirect based on current view. */
// TODO: preserve state
if ($vars->actionID == 'mailto_link') {
    switch ($registry->getView()) {
    case Horde_Registry::VIEW_DYNAMIC:
        IMP_Dynamic_Compose::url()->redirect();
        exit;

    case Horde_Registry::VIEW_MINIMAL:
        IMP_Minimal_Compose::url()->redirect();
        exit;
    }
}

$registry->setTimeZone();

/* The message headers and text. */
$header = array();
$msg = '';

$redirect = $showmenu = $spellcheck = false;
$oldrtemode = $rtemode = null;

/* Set the current identity. */
$identity = $injector->getInstance('IMP_Identity');
if (!$prefs->isLocked('default_identity') && !is_null($vars->identity)) {
    $identity->setDefault($vars->identity);
}

if ($vars->actionID) {
    switch ($vars->actionID) {
    case 'draft':
    case 'editasnew':
    case 'forward_attach':
    case 'forward_auto':
    case 'forward_body':
    case 'forward_both':
    case 'fwd_digest':
    case 'mailto':
    case 'mailto_link':
    case 'reply':
    case 'reply_all':
    case 'reply_auto':
    case 'reply_list':
    case 'redirect_compose':
    case 'template':
    case 'template_edit':
    case 'template_new':
        // These are all safe actions that might be invoked without a token.
        break;

    default:
        try {
            $injector->getInstance('Horde_Token')->validate($vars->compose_requestToken, 'imp.compose');
        } catch (Horde_Token_Exception $e) {
            $notification->push($e);
            $vars->actionID = null;
        }
    }
}

/* Check for duplicate submits. */
if ($vars->compose_formToken) {
    $tokenSource = $injector->getInstance('Horde_Token');

    try {
        if (!$tokenSource->verify($vars->compose_formToken)) {
            $notification->push(_("You have already submitted this page."), 'horde.error');
            $vars->actionID = null;
        }
    } catch (Horde_Token_Exception $e) {
        $notification->push($e->getMessage());
        $vars->actionID = null;
    }
}

/* Determine if compose mode is disabled. */
$compose_disable = !IMP::canCompose();

/* Determine if mailboxes are readonly. */
$draft = IMP_Mailbox::getPref('drafts_folder');
$readonly_drafts = $draft && $draft->readonly;

$save_sent_mail = $vars->save_sent_mail;
$sent_mail = $identity->getValue('sent_mail_folder');
if ($readonly_sentmail = ($sent_mail && $sent_mail->readonly)) {
    $save_sent_mail = false;
}

/* Initialize the IMP_Compose:: object. */
$imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($vars->composeCache);
$imp_compose->pgpAttachPubkey((bool) $vars->pgp_attach_pubkey);
$imp_compose->userLinkAttachments((bool) $vars->link_attachments);
if ($vars->vcard) {
    $imp_compose->attachVCard($identity->getValue('fullname'));
}

/* Init objects. */
$imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
$imp_ui = new IMP_Ui_Compose();

/* Is this a popup window? */
$isPopup = ($prefs->getValue('compose_popup') || $vars->popup);

/* Determine the composition type - text or HTML.
   $rtemode is null if browser does not support it. */
if ($session->get('imp', 'rteavail')) {
    if ($prefs->isLocked('compose_html')) {
        $rtemode = $prefs->getValue('compose_html');
    } else {
        $rtemode = $vars->rtemode;
        if (is_null($rtemode)) {
            $rtemode = $prefs->getValue('compose_html');
        } else {
            $rtemode = intval($rtemode);
            $oldrtemode = intval($vars->oldrtemode);
        }
    }
}

/* Update the file attachment information. */
if ($session->get('imp', 'file_upload')) {
    /* Only notify if we are reloading the compose screen. */
    $notify = !in_array($vars->actionID, array('send_message', 'save_draft'));

    $deleteList = Horde_Util::getPost('delattachments', array());

    /* Update the attachment information. */
    foreach ($imp_compose as $key => $val) {
        if (!in_array($key, $deleteList)) {
            $val['part']->setDescription($vars->filter('file_description_' . $key));
            $imp_compose[$key] = $val;
        }
    }

    /* Delete attachments. */
    foreach ($deleteList as $val) {
        if ($notify) {
            $notification->push(sprintf(_("Deleted attachment \"%s\"."), Horde_Mime::decode($imp_compose[$val]['part']->getName(true))), 'horde.success');
        }
        unset($imp_compose[$val]);
    }

    /* Add new attachments. */
    if (!$imp_compose->addFilesFromUpload('upload_', $notify)) {
        $vars->actionID = null;
    }
}

/* Get message priority. */
$priority = isset($vars->priority)
    ? $vars->priority
    : 'normal';

/* Request read receipt? */
$request_read_receipt = (bool)$vars->request_read_receipt;

/* Run through the action handlers. */
$title = _("New Message");
switch ($vars->actionID) {
case 'mailto':
    try {
        $contents = $imp_ui->getContents();
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e, 'horde.error');
        break;
    }

    $imp_headers = $contents->getHeader();
    $header['to'] = '';
    if ($vars->mailto) {
        $header['to'] = $imp_headers->getValue('to');
    }
    if (empty($header['to'])) {
        ($header['to'] = strval($imp_headers->getOb('from'))) ||
        ($header['to'] = strval($imp_headers->getOb('reply-to')));
    }
    break;

case 'mailto_link':
    $args = IMP::getComposeArgs($vars);
    if (isset($args['body'])) {
        $msg = $args['body'];
    }
    foreach (array('to', 'cc', 'bcc', 'subject') as $val) {
        if (isset($args[$val])) {
            $header[$val] = $args[$val];
        }
    }
    break;

case 'draft':
case 'editasnew':
case 'template':
case 'template_edit':
    try {
        $indices_ob = IMP::mailbox(true)->getIndicesOb(IMP::uid());

        switch ($vars->actionID) {
        case 'editasnew':
            $result = $imp_compose->editAsNew($indices_ob);
            break;

        case 'resume':
            $result = $imp_compose->resumeDraft($indices_ob);
            break;

        case 'template':
            $result = $imp_compose->useTemplate($indices_ob);
            break;

        case 'template_edit':
            $result = $imp_compose->editTemplate($imp_ui->getIndices($vars));
            $vars->template_mode = true;
            break;
        }

        if (!is_null($rtemode)) {
            $rtemode = ($result['format'] == 'html');
        }
        $msg = $result['body'];
        $header = array_merge($header, $result['headers']);
        if (!is_null($result['identity']) &&
            ($result['identity'] != $identity->getDefault()) &&
            !$prefs->isLocked('default_identity')) {
            $identity->setDefault($result['identity']);
            $sent_mail = $identity->getValue('sent_mail_folder');
        }
        $priority = $result['priority'];
        $request_read_receipt = $result['readreceipt'];
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e);
    }
    break;

case 'reply':
case 'reply_all':
case 'reply_auto':
case 'reply_list':
    try {
        $contents = $imp_ui->getContents();
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e, 'horde.error');
        break;
    }

    $reply_map = array(
        'reply' => IMP_Compose::REPLY_SENDER,
        'reply_all' => IMP_Compose::REPLY_ALL,
        'reply_auto' => IMP_Compose::REPLY_AUTO,
        'reply_list' => IMP_Compose::REPLY_LIST
    );

    $reply_msg = $imp_compose->replyMessage($reply_map[$vars->actionID], $contents, array(
        'to' => $vars->to
    ));
    $msg = $reply_msg['body'];
    $header = $reply_msg['headers'];
    $format = $reply_msg['format'];

    switch ($reply_msg['type']) {
    case IMP_Compose::REPLY_SENDER:
        $vars->actionID = 'reply';
        $title = _("Reply:");
        break;

    case IMP_Compose::REPLY_ALL:
        if ($vars->actionID == 'reply_auto') {
            $recip_list = $imp_compose->recipientList($header);
            if (!empty($recip_list['list'])) {
                $replyauto_all = count($recip_list['list']);
            }
        }

        $vars->actionID = 'reply_all';
        $title = _("Reply to All:");
        break;

    case IMP_Compose::REPLY_LIST:
        if ($vars->actionID == 'reply_auto') {
            $replyauto_list = true;
            if (($parse_list = $injector->getInstance('Horde_ListHeaders')->parse('list-id', $contents->getHeader()->getValue('list-id'))) &&
                !is_null($parse_list->label)) {
                $replyauto_list_id = $parse_list->label;
            }
        }

        $vars->actionID = 'reply_list';
        $title = _("Reply to List:");
        break;
    }

    if (!empty($reply_msg['lang'])) {
        $reply_lang = array_values($reply_msg['lang']);
    }

    $title .= ' ' . $header['subject'];

    if (!is_null($rtemode)) {
        $rtemode = ($rtemode || ($format == 'html'));
    }
    break;

case 'replyall_revert':
case 'replylist_revert':
    $reply_msg = $imp_compose->replyMessage(IMP_Compose::REPLY_SENDER, $imp_compose->getContentsOb());
    $header = $reply_msg['headers'];
    break;

case 'forward_attach':
case 'forward_auto':
case 'forward_body':
case 'forward_both':
    try {
        $contents = $imp_ui->getContents();
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e, 'horde.error');
        break;
    }

    $fwd_map = array(
        'forward_attach' => IMP_Compose::FORWARD_ATTACH,
        'forward_auto' => IMP_Compose::FORWARD_AUTO,
        'forward_body' => IMP_Compose::FORWARD_BODY,
        'forward_both' => IMP_Compose::FORWARD_BOTH
    );

    $fwd_msg = $imp_compose->forwardMessage($fwd_map[$vars->actionID], $contents);
    $msg = $fwd_msg['body'];
    $header = $fwd_msg['headers'];
    $format = $fwd_msg['format'];
    $rtemode = ($rtemode || (!is_null($rtemode) && ($format == 'html')));
    $title = $header['title'];
    break;

case 'redirect_compose':
    try {
        $indices = $imp_ui->getIndices($vars);
        $imp_compose->redirectMessage($indices);
        $redirect = true;
        $title = ngettext(_("Redirect"), _("Redirect Messages"), count($indices));
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e, 'horde.error');
    }
    break;

case 'redirect_send':
    try {
        $num_msgs = $imp_compose->sendRedirectMessage($vars->to);
        $imp_compose->destroy('send');
        if ($isPopup) {
            if ($prefs->getValue('compose_confirm')) {
                $notification->push(ngettext("Message redirected successfully.", "Messages redirected successfully", count($num_msgs)), 'horde.success');
                $imp_ui->popupSuccess();
            } else {
                echo Horde::wrapInlineScript(array('window.close();'));
            }
        } else {
            $notification->push(ngettext("Message redirected successfully.", "Messages redirected successfully", count($num_msgs)), 'horde.success');
            $imp_ui->mailboxReturnUrl()->redirect();
        }
        exit;
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $vars->actionID = 'redirect_compose';
    }
    break;

case 'auto_save_draft':
case 'save_draft':
case 'save_template':
case 'send_message':
    // Drafts readonly is handled below.
    if (($vars->actionID == 'send_message') && $compose_disable) {
        break;
    }

    try {
        $header['from'] = strval($identity->getFromLine(null, $vars->from));
    } catch (Horde_Exception $e) {
        $header['from'] = '';
        $notification->push($e);
        break;
    }

    $header['to'] = $vars->to;
    if ($prefs->getValue('compose_cc')) {
        $header['cc'] = $vars->cc;
    }
    if ($prefs->getValue('compose_bcc')) {
        $header['bcc'] = $vars->bcc;
    }

    $header['subject'] = strval($vars->subject);
    $message = strval($vars->message);

    /* Save the draft. */
    if (in_array($vars->actionID, array('auto_save_draft', 'save_draft', 'save_template'))) {
        if (!$readonly_drafts || ($vars->actionID == 'save_template')) {
            $save_opts = array(
                'html' => $rtemode,
                'priority' => $priority,
                'readreceipt' => $request_read_receipt
            );

            try {
                switch ($vars->actionID) {
                case 'save_template':
                    $result = $imp_compose->saveTemplate($header, $message, $save_opts);
                    break;

                default:
                    $result = $imp_compose->saveDraft($header, $message, $save_opts);
                    break;
                }

                /* Closing draft if requested by preferences. */
                switch ($vars->actionID) {
                case 'save_draft':
                    if ($isPopup) {
                        if ($prefs->getValue('close_draft')) {
                            $imp_compose->destroy('save_draft');
                            echo Horde::wrapInlineScript(array('window.close();'));
                            exit;
                        } else {
                            $notification->push($result, 'horde.success');
                        }
                    } else {
                        $notification->push($result, 'horde.success');
                        if ($prefs->getValue('close_draft')) {
                            $imp_compose->destroy('save_draft');
                            $imp_ui->mailboxReturnUrl()->redirect();
                        }
                    }
                    break;

                case 'save_template':
                    if ($isPopup) {
                        echo Horde::wrapInlineScript(array('window.close();'));
                    } else {
                        $notification->push($result, 'horde.success');
                        $imp_ui->mailboxReturnUrl()->redirect();
                    }
                    break;
                }
            } catch (IMP_Compose_Exception $e) {
                if ($vars->actionID == 'save_draft') {
                    $notification->push($e);
                }
            }
        }

        if ($vars->actionID == 'auto_save_draft') {
            $request = new stdClass;
            $request->requestToken = $injector->getInstance('Horde_Token')->get('imp.compose');
            $request->formToken = Horde_Token::generateId('compose');

            $response = new Horde_Core_Ajax_Response_HordeCore($request);
            $response->sendAndExit();
        }

        break;
    }

    $header['replyto'] = $identity->getValue('replyto_addr');

    if ($vars->sent_mail) {
        $sent_mail = IMP_Mailbox::formFrom($vars->sent_mail);
    }

    $options = array(
        'add_signature' => $identity->getDefault(),
        'encrypt' => $prefs->isLocked('default_encrypt') ? $prefs->getValue('default_encrypt') : $vars->encrypt_options,
        'html' => $rtemode,
        'identity' => $identity,
        'priority' => $priority,
        'save_sent' => $save_sent_mail,
        'sent_mail' => $sent_mail,
        'save_attachments' => $vars->save_attachments_select,
        'readreceipt' => $request_read_receipt
    );

    try {
        $imp_compose->buildAndSendMessage($message, $header, $options);
        $imp_compose->destroy('send');
    } catch (IMP_Compose_Exception $e) {
        $code = $e->getCode();
        $notification->push($e->getMessage(), strpos($code, 'horde.') === 0 ? $code : 'horde.error');

        /* Switch to tied identity. */
        if (!is_null($e->tied_identity)) {
            $identity->setDefault($e->tied_identity);
            $notification->push(_("Your identity has been switched to the identity associated with the current recipient address. The identity will not be checked again during this compose action."));
        }

        switch ($e->encrypt) {
        case 'pgp_symmetric_passphrase_dialog':
            $imp_ui->passphraseDialog('pgp_symm', $imp_compose->getCacheId());
            break;

        case 'pgp_passphrase_dialog':
            $imp_ui->passphraseDialog('pgp');
            break;

        case 'smime_passphrase_dialog':
            $imp_ui->passphraseDialog('smime');
            break;
        }
        break;
    }

    if ($isPopup) {
        if ($prefs->getValue('compose_confirm')) {
            $notification->push(_("Message sent successfully."), 'horde.success');
            $imp_ui->popupSuccess();
        } else {
            echo Horde::wrapInlineScript(array('window.close();'));
        }
    } else {
        $notification->push(_("Message sent successfully."), 'horde.success');
        $imp_ui->mailboxReturnUrl()->redirect();
    }
    exit;

case 'fwd_digest':
    $indices = $imp_ui->getIndices($vars);
    if (count($indices)) {
        try {
            $header['subject'] = $imp_compose->attachImapMessage($indices);
            $fwd_msg = array('type' => IMP_Compose::FORWARD_ATTACH);
        } catch (IMP_Compose_Exception $e) {
            $notification->push($e, 'horde.error');
        }
    }
    break;

case 'cancel_compose':
    $imp_compose->destroy('cancel');
    if ($isPopup) {
        echo Horde::wrapInlineScript(array('window.close();'));
    } else {
        $imp_ui->mailboxReturnUrl()->redirect();
    }
    exit;

case 'selectlist_process':
    if ($vars->selectlist_selectid &&
        $registry->hasMethod('files/selectlistResults') &&
        $registry->hasMethod('files/returnFromSelectlist')) {
        try {
            $filelist = $registry->call('files/selectlistResults', array($vars->selectlist_selectid));
            if ($filelist) {
                $i = 0;
                foreach ($filelist as $val) {
                    $data = $registry->call('files/returnFromSelectlist', array($vars->selectlist_selectid, $i++));
                    if ($data) {
                        $part = new Horde_Mime_Part();
                        $part->setName(reset($val));
                        $part->setContents($data);
                        try {
                            $imp_compose->addMimePartAttachment($part);
                        } catch (IMP_Compose_Exception $e) {
                            $notification->push($e);
                        }
                    }
                }
            }
        } catch (Horde_Exception $e) {}
    }
    break;

case 'template_new':
    $vars->template_mode = true;
    break;
}

/* Get the message cache ID. */
$composeCacheID = filter_var($imp_compose->getCacheId(), FILTER_SANITIZE_STRING);

/* Attach autocompleters to the compose form elements. */
if ($redirect) {
    $imp_ui->attachAutoCompleter(array('to'));
} else {
    $auto_complete = array('to');
    foreach (array('cc', 'bcc') as $val) {
        if ($prefs->getValue('compose_' . $val)) {
            $auto_complete[] = $val;
        }
    }
    $imp_ui->attachAutoCompleter($auto_complete);

    if (!empty($conf['spell']['driver'])) {
        try {
            Horde_SpellChecker::factory($conf['spell']['driver'], array());
            $spellcheck = true;
            $imp_ui->attachSpellChecker();
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
        }
    }

    $page_output->addScriptFile('ieescguard.js', 'horde');
}

$max_attach = $imp_compose->additionalAttachmentsAllowed();
$sm_check = !empty($conf['user']['select_sentmail_folder']) && !$prefs->isLocked('sent_mail_folder');

/* Get the URL to use for the cancel action. */
$cancel_url = '';
if ($isPopup) {
    /* If the attachments cache is not empty, we must reload this page
     * and delete the attachments. */
    if (count($imp_compose)) {
        $cancel_url = Horde::selfUrl()->setRaw(true)->add(array(
            'actionID' => 'cancel_compose',
            'compose_requestToken' => $injector->getInstance('Horde_Token')->get('imp.compose'),
            'composeCache' => $composeCacheID,
            'popup' => 1
        ));
    }
} else {
    /* If the attachments cache is not empty, we must reload this page and
       delete the attachments. */
    if (count($imp_compose)) {
        $cancel_url = $imp_ui->mailboxReturnUrl(Horde::selfUrl()->setRaw(true))->add(array(
            'actionID' => 'cancel_compose',
            'compose_requestToken' => $injector->getInstance('Horde_Token')->get('imp.compose'),
            'composeCache' => $composeCacheID
        ));
    } else {
        $cancel_url = $imp_ui->mailboxReturnUrl(false)->setRaw(false);
    }
    $showmenu = true;
}

/* Grab any data that we were supplied with. */
if (empty($msg)) {
    $msg = isset($vars->message) ? $vars->message : strval($vars->body);
    if ($browser->hasQuirk('double_linebreak_textarea')) {
        $msg = preg_replace('/(\r?\n){3}/', '$1', $msg);
    }
    $msg = "\n" . $msg;
}

/* Convert from Text -> HTML or vice versa if RTE mode changed. */
if (!is_null($oldrtemode) && ($oldrtemode != $rtemode)) {
    $msg = $imp_ui->convertComposeText($msg, $rtemode ? 'html' : 'text', $identity->getDefault());
}

/* If this is the first page load for this compose item, add auto BCC
 * addresses. */
if (!$vars->compose_formToken && ($vars->actionID != 'draft')) {
    $header['bcc'] = strval($identity->getBccAddresses());
}

foreach (array('to', 'cc', 'bcc') as $val) {
    if (!isset($header[$val])) {
        $header[$val] = $vars->$val;
    }
}

if (!isset($header['subject'])) {
    $header['subject'] = $vars->subject;
}

/* If PGP encryption is set by default, and we have a recipient list on first
 * load, make sure we have public keys for all recipients. */
$encrypt_options = $prefs->isLocked('default_encrypt')
      ? $prefs->getValue('default_encrypt')
      : $vars->encrypt_options;
if ($prefs->getValue('use_pgp') &&
    !$prefs->isLocked('default_encrypt') &&
    $prefs->getValue('pgp_reply_pubkey')) {
    $default_encrypt = $prefs->getValue('default_encrypt');
    if (!$vars->compose_formToken &&
        in_array($default_encrypt, array(IMP_Crypt_Pgp::ENCRYPT, IMP_Crypt_Pgp::SIGNENC))) {
        $addrs = $imp_compose->recipientList($header);
        if (!empty($addrs['list'])) {
            $imp_pgp = $injector->getInstance('IMP_Crypt_Pgp');
            try {
                foreach ($addrs['list'] as $val) {
                    $imp_pgp->getPublicKey(strval($val));
                }
            } catch (Horde_Exception $e) {
                $notification->push(_("PGP encryption cannot be used by default as public keys cannot be found for all recipients."), 'horde.warning');
                $encrypt_options = ($default_encrypt == IMP_Crypt_Pgp::ENCRYPT) ? IMP::ENCRYPT_NONE : IMP_Crypt_Pgp::SIGN;
            }
        }
    }
}

/* Define some variables used in the javascript code. */
$js_vars = array(
    'ImpComposeBase.editor_on' => $rtemode,
    'ImpCompose.auto_save' => intval($prefs->getValue('auto_save_drafts')),
    'ImpCompose.cancel_url' => strval($cancel_url),
    'ImpCompose.cursor_pos' => ($rtemode ? null : $prefs->getValue('compose_cursor')),
    'ImpCompose.max_attachments' => (($max_attach === true) ? null : $max_attach),
    'ImpCompose.popup' => intval($isPopup),
    'ImpCompose.redirect' => intval($redirect),
    'ImpCompose.reloaded' => intval($vars->compose_formToken),
    'ImpCompose.sm_check' => intval($sm_check),
    'ImpCompose.spellcheck' => intval($spellcheck && $prefs->getValue('compose_spellcheck')),
    'ImpCompose.text' => array(
        'cancel' => _("Cancelling this message will permanently discard its contents.") . "\n" . _("Are you sure you want to do this?"),
        'discard' => _("Doing so will discard this message permanently."),
        'file' => _("File"),
        'nosubject' => _("The message does not have a Subject entered.") . "\n" . _("Send message without a Subject?"),
        'recipient' => _("You must specify a recipient.")
    )
);

/* Set up the base template now. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);
$t->set('post_action', Horde::url('compose.php')->unique());
$t->set('allow_compose', !$compose_disable);

$blank_url = new Horde_Url('#');

if ($redirect) {
    /* Prepare the redirect template. */
    $t->set('cacheid', $composeCacheID);
    $t->set('title', htmlspecialchars($title));
    $t->set('token', $injector->getInstance('Horde_Token')->get('imp.compose'));

    Horde::startBuffer();
    IMP::status();
    $t->set('status', Horde::endBuffer());

    if ($session->get('imp', 'csearchavail')) {
        $t->set('abook', $blank_url->copy()->link(array(
            'class' => 'widget',
            'id' => 'redirect_abook',
            'title' => _("Address Book")
        )) . Horde::img('addressbook_browse.png') . '<br />' . _("Address Book") . '</a>');
        $js_vars['ImpCompose.redirect_contacts'] = strval(Horde::url('contacts.php')->add(array('to_only' => 1))->setRaw(true));
    }

    $t->set('to', Horde::label('to', _("To")));
    $t->set('input_value', htmlspecialchars($header['to']));
    $t->set('help', Horde_Help::link('imp', 'compose-to'));

    $template_output = $t->fetch(IMP_TEMPLATES . '/imp/compose/redirect.html');
} else {
    /* Prepare the compose template. */
    $tabindex = 0;

    $t->set('file_upload', $session->get('imp', 'file_upload'));
    $t->set('forminput', Horde_Util::formInput());

    $hidden = array(
        'actionID' => '',
        'attachmentAction' => '',
        'compose_formToken' => Horde_Token::generateId('compose'),
        'compose_requestToken' => $injector->getInstance('Horde_Token')->get('imp.compose'),
        'composeCache' => $composeCacheID,
        'mailbox' => IMP::mailbox(true)->form_to,
        'oldrtemode' => $rtemode,
        'rtemode' => $rtemode,
        'user' => $registry->getAuth()
    );

    if ($session->exists('imp', 'file_upload')) {
        $hidden['MAX_FILE_SIZE'] = $session->get('imp', 'file_upload');
    }
    foreach (array('page', 'start', 'popup', 'template_mode') as $val) {
        $hidden[$val] = htmlspecialchars($vars->$val);
    }

    $hidden_val = array();
    foreach ($hidden as $key => $val) {
        $hidden_val[] = array('n' => $key, 'v' => $val);
    }
    $t->set('hidden', $hidden_val);

    $t->set('title', htmlspecialchars($title));

    if (!$vars->template_mode) {
        $t->set('send_msg_ak', Horde::getAccessKeyAndTitle(_("_Send Message")));
        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS) && !$readonly_drafts) {
            $t->set('save_draft_ak', Horde::getAccessKeyAndTitle(_("Save _Draft")));
        }
    }

    $t->set('help', Horde_Help::link('imp', 'compose-buttons'));
    $t->set('di_locked', $prefs->isLocked('default_identity'));
    if ($t->get('di_locked')) {
        $t->set('fromaddr_locked', $prefs->isLocked('from_addr'));
        try {
            $t->set('from', htmlspecialchars($identity->getFromLine(null, $vars->from)));
        } catch (Horde_Exception $e) {
            $t->set('from', '');
        }
        if (!$t->get('fromaddr_locked')) {
            $t->set('fromaddr_tabindex', ++$tabindex);
        }
    } else {
        $select_list = $identity->getSelectList();
        $t->set('identity_label', Horde::label('identity', _("_Identity")));
        $t->set('last_identity', $identity->getDefault());
        $t->set('count_select_list', count($select_list) > 1);
        if (count($select_list) > 1) {
            $t->set('selectlist_tabindex', ++$tabindex);
            $t_select_list = array();
            foreach ($select_list as $key => $select) {
                $t_select_list[] = array('value' => $key, 'selected' => ($key == $identity->getDefault()), 'label' => htmlspecialchars($select));
            }
            $t->set('select_list', $t_select_list);
        } else {
            $t->set('identity_default', $identity->getDefault());
            $t->set('identity_text', htmlspecialchars($select_list[0]));
        }
    }
    $t->set('label_to', Horde::label('to', _("_To")));

    $addr_array = array('to' => _("_To"));
    if ($prefs->getValue('compose_cc')) {
        $addr_array['cc'] = _("_Cc");
    }
    if ($prefs->getValue('compose_bcc')) {
        $addr_array['bcc'] = _("_Bcc");
    }

    $address_array = array();
    foreach ($addr_array as $val => $label) {
        $address_array[] = array(
            'id' => $val,
            'input_tabindex' => ++$tabindex,
            'input_value' => htmlspecialchars($header[$val]),
            'label' => Horde::label($val, $label)
        );
    }
    $t->set('addr', $address_array);

    $t->set('subject_label', Horde::label('subject', _("S_ubject")));
    $t->set('subject_tabindex', ++$tabindex);
    $t->set('subject', htmlspecialchars($header['subject']));
    $t->set('help-subject', Horde_Help::link('imp', 'compose-subject'));

    $t->set('set_priority', $prefs->getValue('set_priority'));
    if ($t->get('set_priority')) {
        $t->set('priority_label', Horde::label('priority', _("_Priority")));
        $t->set('priority_tabindex', ++$tabindex);

        $priorities = array(
            'high' => _("High"),
            'normal' => _("Normal"),
            'low' => _("Low")
        );
        $priority_option = array();
        foreach ($priorities as $key => $val) {
            $priority_option[] = array('val' => $key, 'label' => $val, 'selected' => ($priority == $key));
        }
        $t->set('pri_opt', $priority_option);
    }

    $menu_view = $prefs->getValue('menu_view');
    $show_text = ($menu_view == 'text' || $menu_view == 'both');
    $compose_options = array();
    if ($session->get('imp', 'csearchavail')) {
        $compose_options[] = array(
            'url' => $blank_url->copy()->link(array(
                'class' => 'widget',
                'onclick.raw' => 'window.open(\'' . Horde::url('contacts.php') . '\',\'contacts\',\'toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes,width=550,height=300,left=100,top=100\'); return false;'
            )),
            'img' => Horde::img('addressbook_browse.png'),
            'label' => $show_text ? _("Address Book") : ''
        );
    }
    if ($spellcheck) {
        $compose_options[] = array(
            'url' => $blank_url->copy()->link(array(
                'class' => 'widget',
                'id' => 'spellcheck'
            )),
            'img' => '',
            'label' => ''
        );
    }
    if ($session->get('imp', 'file_upload')) {
        $url = new Horde_Url('#attachments');
        $compose_options[] = array(
            'url' => $url->link(array('class' => 'widget')),
            'img' => Horde::img('attachment.png'),
            'label' => $show_text ? _("Attachments") : ''
        );
    }
    $t->set('compose_options', $compose_options);

    $t->set('ssm', ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS) && !$prefs->isLocked('save_sent_mail')));
    if ($t->get('ssm')) {
        if ($readonly_sentmail) {
            $notification->push(sprintf(_("Cannot save sent-mail message to \"%s\" as that mailbox is read-only.", $sent_mail->display), 'horde.warning'));
        }
        $t->set('ssm_selected', $vars->compose_formToken ? ($save_sent_mail == 'on') : $sent_mail && $identity->saveSentmail());
        $t->set('ssm_label', Horde::label('ssm', _("Sa_ve a copy in ")));
        if ($vars->sent_mail) {
            $sent_mail = IMP_Mailbox::formFrom($vars->sent_mail);
        }
        if (!empty($conf['user']['select_sentmail_folder']) &&
            !$prefs->isLocked('sent_mail_folder')) {
            $ssm_options = array(
                'abbrev' => false,
                'basename' => true,
                'filter' => array('INBOX'),
                'selected' => $sent_mail
            );
            $t->set('ssm_tabindex', ++$tabindex);

            /* Check to make sure the sent-mail mailbox is created - it needs
             * to exist to show up in drop-down list. */
            if ($sent_mail) {
                $sent_mail->create();
            }

            $t->set('ssm_mboxes', IMP::flistSelect($ssm_options));
        } else {
            if ($sent_mail) {
                $sent_mail = '&quot;' . $sent_mail->display_html . '&quot;';
            }
            $t->set('ssm_mbox', $sent_mail);
            $t->set('ssm_mboxes', false);
        }
    }

    $d_read = $prefs->getValue('request_mdn');
    $t->set('rrr', ($d_read != 'never'));
    if ($t->get('rrr')) {
        $t->set('rrr_selected', ($d_read != 'ask') || $request_read_receipt);
        $t->set('rrr_label', Horde::label('rrr', _("Request a _Read Receipt")));
    }

    $t->set('compose_html', (!is_null($rtemode) && !$prefs->isLocked('compose_html')));
    if ($t->get('compose_html')) {
        $t->set('html_img', Horde::img('compose.png', _("Switch Composition Method")));
        $t->set('html_switch', $blank_url->copy()->link(array(
            'onclick.raw' => "$('rtemode').value='" . ($rtemode ? 0 : 1) . "';ImpCompose.uniqSubmit('toggle_editor');return false;",
            'title' => _("Switch Composition Method")
        )));
        $t->set('rtemode', $rtemode);
    }

    if (isset($replyauto_all)) {
        $t->set('replyauto_all', $replyauto_all);
    } elseif (isset($replyauto_list)) {
        $t->set('replyauto_list', true);
        if (isset($replyauto_list_id)) {
            $t->set('replyauto_list_id', $replyauto_list_id);
        }
    }

    if (isset($reply_lang)) {
        $t->set('reply_lang', implode(',', $reply_lang));
    }

    $t->set('message_label', Horde::label('composeMessage', _("Te_xt")));
    $t->set('message_tabindex', ++$tabindex);
    $t->set('message', htmlspecialchars($msg));

    $t->set('use_encrypt', ($prefs->getValue('use_pgp') || $prefs->getValue('use_smime')));
    if ($t->get('use_encrypt')) {
        if ($prefs->isLocked('default_encrypt')) {
            $t->set('use_encrypt', false);
        } else {
            $t->set('encrypt_label', Horde::label('encrypt_options', _("Encr_yption Options")));
            $t->set('encrypt_options', IMP::encryptList($encrypt_options));
            $t->set('help-encrypt', Horde_Help::link('imp', 'compose-options-encrypt'));
        }
        $t->set('pgp_options', ($prefs->getValue('use_pgp') && $prefs->getValue('pgp_public_key')));
        if ($t->get('pgp_options')) {
            $t->set('pgp_attach_pubkey', isset($vars->pgp_attach_pubkey) ? $vars->pgp_attach_pubkey : $prefs->getValue('pgp_attach_pubkey'));
            $t->set('pap', Horde::label('pap', _("Attach a copy of your PGP public key to the message?")));
            $t->set('help-pubkey', Horde_Help::link('imp', 'pgp-compose-attach-pubkey'));
        }
    }
    if ($registry->hasMethod('contacts/ownVCard')) {
        $t->set('vcard', Horde::label('vcard', _("Attach your contact information to the message?")));
        $t->set('attach_vcard', $vars->vcard);
    }
    if ($session->get('imp', 'file_upload')) {
        try {
            $t->set('selectlistlink', $registry->call('files/selectlistLink', array(_("Attach Files"), 'widget', 'compose', true)));
        } catch (Horde_Exception $e) {
            $t->set('selectlistlink', null);
        }
        $t->set('maxattachsize', !$imp_compose->maxAttachmentSize());
        if (!$t->get('maxattachsize')) {
            $t->set('maxattachmentnumber', !$max_attach);
            if (!$t->get('maxattachmentnumber')) {
                $t->set('file_tabindex', ++$tabindex);
            }
        }
        $t->set('attach_size', IMP::numberFormat($imp_compose->maxAttachmentSize(), 0));
        $t->set('help-attachments', Horde_Help::link('imp', 'compose-attachments'));

        $save_attach = $prefs->getValue('save_attachments');
        $show_link_attach = ($conf['compose']['link_attachments'] && !$conf['compose']['link_all_attachments']);
        $show_save_attach = ($t->get('ssm') && (strpos($save_attach, 'prompt') === 0)
                             && (!$conf['compose']['link_attachments'] || !$conf['compose']['link_all_attachments']));
        $t->set('show_link_save_attach', ($show_link_attach || $show_save_attach));
        if ($t->get('show_link_save_attach')) {
            $attach_options = array();
            if ($show_save_attach) {
                $save_attach_val = isset($vars->save_attachments_select) ? $vars->save_attachments_select : ($save_attach == 'prompt_yes');
                $attach_options[] = array('label' => _("Save Attachments with message in sent-mail mailbox?"), 'name' => 'save_attachments_select', 'select_yes' => ($save_attach_val == 1), 'select_no' => ($save_attach_val == 0), 'help' => Horde_Help::link('imp', 'compose-save-attachments'));
            }
            if ($show_link_attach) {
                $attach_options[] = array('label' => _("Link Attachments?"), 'name' => 'link_attachments', 'select_yes' => ($vars->link_attachments == 1), 'select_no' => ($vars->link_attachments == 0), 'help' => Horde_Help::link('imp', 'compose-link-attachments'));
            }
            $t->set('attach_options', $attach_options);
        }

        $t->set('numberattach', count($imp_compose));
        if ($t->get('numberattach')) {
            $atc = array();
            $v = $injector->getInstance('Horde_Core_Factory_MimeViewer');
            foreach ($imp_compose as $atc_num => $data) {
                $mime = $data['part'];
                $type = $mime->getType();

                $entry = array(
                    'fwdattach' => (isset($fwd_msg) && ($fwd_msg['type'] != IMP_Compose::FORWARD_BODY)),
                    'name' => $mime->getName(true),
                    'icon' => $v->getIcon($type),
                    'number' => $atc_num,
                    'type' => $type,
                    'size' => $mime->getSize(),
                    'description' => $mime->getDescription(true)
                );

                if (empty($entry['fwdattach']) &&
                    ($type != 'application/octet-stream')) {
                    $preview_url = Horde::url('view.php')->add(array(
                        'actionID' => 'compose_attach_preview',
                        'composeCache' => $composeCacheID,
                        'id' => $atc_num
                    ));
                    $entry['name'] = $preview_url->link(array(
                        'class' => 'link',
                        'target' => 'compose_preview_window',
                        'title' => _("Preview")
                    )) . $entry['name'] . '</a>';
                }

                $atc[] = $entry;
            }
            $t->set('atc', $atc);
            $t->set('total_attach_size', IMP::numberFormat($imp_compose->sizeOfAttachments() / 1024, 2));
            $t->set('perc_attach', ((!empty($conf['compose']['attach_size_limit'])) && ($conf['compose']['attach_size_limit'] > 0)));
            if ($t->get('perc_attach')) {
                $t->set('perc_attach', sprintf(_("%s%% of allowed size"), IMP::numberFormat($imp_compose->sizeOfAttachments() / $conf['compose']['attach_size_limit'] * 100, 2)));
            }
            $t->set('help-current-attachments', Horde_Help::link('imp', 'compose-current-attachments'));
        }
    }

    Horde::startBuffer();
    IMP::status();
    $t->set('status', Horde::endBuffer());

    $template_output = $t->fetch(IMP_TEMPLATES . '/imp/compose/compose.html');
}

if ($rtemode && !$redirect) {
    IMP_Ui_Editor::init(false, 'composeMessage');
}

if ($showmenu) {
    $menu = IMP::menu();
}
$page_output->addScriptFile('compose-base.js');
$page_output->addScriptFile('compose.js');
$page_output->addScriptFile('md5.js', 'horde');
$page_output->addInlineJsVars($js_vars);
if (!$redirect) {
    $imp_ui->addIdentityJs();
}
IMP::header($title);
if ($showmenu) {
    echo $menu;
}
echo $template_output;
$page_output->footer();
