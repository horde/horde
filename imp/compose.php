<?php
/**
 * Compose script for traditional (IMP) view.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
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
Horde_Registry::appInit('imp', array('session_control' => 'netscape'));

$registry->setTimeZone();

/* The message headers and text. */
$header = array();
$msg = '';

$get_sig = true;
$showmenu = $spellcheck = false;
$oldrtemode = $rtemode = null;

$vars = Horde_Variables::getDefaultVariables();

/* Set the current identity. */
$identity = $injector->getInstance('IMP_Identity');
if (!$prefs->isLocked('default_identity')) {
    if (!is_null($vars->identity)) {
        $identity->setDefault($vars->identity);
    }
}

/* Catch submits if javascript is not present. */
if (!$vars->actionID) {
    foreach (array('send_message', 'save_draft', 'cancel_compose', 'add_attachment') as $val) {
        if ($vars->get('btn_' . $val)) {
            $vars->actionID = $val;
            break;
        }
    }
}

if ($vars->actionID) {
    switch ($vars->actionID) {
    case 'mailto':
    case 'mailto_link':
    case 'draft':
    case 'reply':
    case 'reply_all':
    case 'reply_auto':
    case 'reply_list':
    case 'forward_attach':
    case 'forward_auto':
    case 'forward_body':
    case 'forward_both':
    case 'redirect_compose':
    case 'fwd_digest':
        // These are all safe actions that might be invoked without a token.
        break;

    default:
        try {
            Horde::checkRequestToken('imp.compose', $vars->compose_requestToken);
        } catch (Horde_Exception $e) {
            $notification->push($e);
            $vars->actionID = null;
        }
    }
}

$save_sent_mail = $vars->save_sent_mail;
$sent_mail_folder = $identity->getValue('sent_mail_folder');

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
$readonly_drafts = $readonly_sentmail = false;
$draft = IMP::folderPref($prefs->getValue('drafts_folder'), true);
$imp_imap = $injector->getInstance('IMP_Imap')->getOb();
if (!empty($draft)) {
    $readonly_drafts = $imp_imap->isReadOnly($draft);
}
$readonly_sentmail = $imp_imap->isReadOnly($sent_mail_folder);
if ($readonly_sentmail) {
    $save_sent_mail = false;
}

/* Initialize the IMP_Compose:: object. */
$imp_compose = $injector->getInstance('IMP_Compose')->getOb($vars->composeCache);
$imp_compose->pgpAttachPubkey((bool) $vars->pgp_attach_pubkey);
$imp_compose->userLinkAttachments((bool) $vars->link_attachments);

try {
    $imp_compose->attachVCard((bool) $vars->vcard, $identity->getValue('fullname'));
} catch (IMP_Compose_Exception $e) {
    $notification->push($e);
}

/* Init IMP_Ui_Compose:: object. */
$imp_ui = new IMP_Ui_Compose();

/* Set the default charset & encoding.
 * $charset - charset to use when sending messages
 * $encoding - best guessed charset offered to the user as the default value
 *             in the charset dropdown list. */
$charset = $prefs->isLocked('sending_charset')
    ? $registry->getEmailCharset()
    : $vars->charset;
$encoding = empty($charset)
    ? $registry->getEmailCharset()
    : $charset;

/* Is this a popup window? */
$isPopup = ($prefs->getValue('compose_popup') || $vars->popup);

/* Determine the composition type - text or HTML.
   $rtemode is null if browser does not support it. */
$rtemode = null;
if ($_SESSION['imp']['rteavail']) {
    if ($prefs->isLocked('compose_html')) {
        $rtemode = $prefs->getValue('compose_html');
    } else {
        $rtemode = $vars->rtemode;
        if (is_null($rtemode)) {
            $rtemode = $prefs->getValue('compose_html');
        } else {
            $oldrtemode = $vars->oldrtemode;
            $get_sig = false;
        }
    }
}

/* Load stationery. */
$stationery_list = array();
if (!$prefs->isLocked('stationery')) {
    $stationery = null;
    $all_stationery = @unserialize($prefs->getValue('stationery', false));
    if (is_array($all_stationery)) {
        $all_stationery = Horde_String::convertCharset($all_stationery, $prefs->getCharset());
        foreach ($all_stationery as $id => $choice) {
            if (($choice['t'] == 'plain') ||
                (($choice['t'] == 'html') && $rtemode)) {
                if ($rtemode && $choice['t'] == 'plain') {
                    $choice['c'] = $imp_compose->text2html($choice['c']);
                }
                $stationery_list[$id] = $choice;
            }
        }
    }
}

/* Update the file attachment information. */
if ($_SESSION['imp']['file_upload']) {
    /* Only notify if we are reloading the compose screen. */
    $notify = !in_array($vars->actionID, array('send_message', 'save_draft'));

    $deleteList = Horde_Util::getPost('delattachments', array());

    /* Update the attachment information. */
    foreach (array_keys($imp_compose->getAttachments()) as $i) {
        if (!in_array($i, $deleteList)) {
            $description = $vars->get('file_description_' . $i);
            $imp_compose->updateAttachment($i, array('description' => $description));
        }
    }

    /* Delete attachments. */
    if (!empty($deleteList)) {
        $filenames = $imp_compose->deleteAttachment($deleteList);
        if ($notify) {
            foreach ($filenames as $val) {
                $notification->push(sprintf(_("Deleted the attachment \"%s\"."), Horde_Mime::decode($val)), 'horde.success');
            }
        }
    }

    /* Add new attachments. */
    if (!$imp_compose->addFilesFromUpload('upload_', $notify)) {
        $vars->actionID = null;
    }
}

/* Run through the action handlers. */
$title = _("New Message");
switch ($vars->actionID) {
case 'mailto':
    if (!($imp_contents = $imp_ui->getIMPContents(new IMP_Indices(IMP::$thismailbox, IMP::$uid)))) {
        break;
    }
    $imp_headers = $imp_contents->getHeaderOb();
    $header['to'] = '';
    if ($vars->mailto) {
        $header['to'] = $imp_headers->getValue('to');
    }
    if (empty($header['to'])) {
        ($header['to'] = Horde_Mime_Address::addrArray2String($imp_headers->getOb('from'), array('charset' => $registry->getCharset()))) ||
        ($header['to'] = Horde_Mime_Address::addrArray2String($imp_headers->getOb('reply-to'), array('charset' => $registry->getCharset())));
    }
    break;

case 'mailto_link':
    $args = IMP::getComposeArgs();
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
    try {
        $result = $imp_compose->resumeDraft(new IMP_Indices(IMP::$thismailbox, IMP::$uid));

        if (!is_null($rtemode)) {
            $rtemode = ($result['mode'] == 'html');
        }
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
    $get_sig = false;
    break;

case 'reply':
case 'reply_all':
case 'reply_auto':
case 'reply_list':
    if (!($imp_contents = $imp_ui->getIMPContents(new IMP_Indices(IMP::$thismailbox, IMP::$uid)))) {
        break;
    }

    $reply_msg = $imp_compose->replyMessage($vars->actionID, $imp_contents, $vars->to);
    $msg = $reply_msg['body'];
    $header = $reply_msg['headers'];
    $format = $reply_msg['format'];
    $vars->actionID = $reply_msg['type'];

    if (!is_null($rtemode)) {
        $rtemode = $rtemode || $format == 'html';
    }

    switch ($vars->actionID) {
    case 'reply':
        $title = _("Reply:");
        break;

    case 'reply_all':
        $title = _("Reply to All:");
        break;

    case 'reply_list':
        $title = _("Reply to List:");
        break;
    }
    $title .= ' ' . $header['subject'];

    $encoding = empty($charset) ? $reply_msg['encoding'] : $charset;
    break;

case 'forward_attach':
case 'forward_auto':
case 'forward_body':
case 'forward_both':
    if (!($imp_contents = $imp_ui->getIMPContents(new IMP_Indices(IMP::$thismailbox, IMP::$uid)))) {
        break;
    }

    $fwd_msg = $imp_compose->forwardMessage($vars->actionID, $imp_contents);
    $msg = $fwd_msg['body'];
    $header = $fwd_msg['headers'];
    $format = $fwd_msg['format'];
    $rtemode = ($rtemode || (!is_null($rtemode) && ($format == 'html')));
    $title = $header['title'];
    $encoding = empty($charset) ? $fwd_msg['encoding'] : $charset;
    break;

case 'redirect_compose':
    if (!($imp_contents = $imp_ui->getIMPContents(new IMP_Indices(IMP::$thismailbox, IMP::$uid)))) {
        break;
    }
    $imp_compose->redirectMessage($imp_contents);
    $title = _("Redirect");
    break;

case 'redirect_send':
    try {
        $imp_compose->sendRedirectMessage($imp_ui->getAddressList($vars->to));

        $imp_compose->destroy('send');
        if ($isPopup) {
            if ($prefs->getValue('compose_confirm')) {
                $notification->push(_("Message redirected successfully."), 'horde.success');
                $imp_ui->popupSuccess();
            } else {
                echo Horde::wrapInlineScript(array('window.close();'));
            }
        } else {
            if ($prefs->getValue('compose_confirm')) {
                $notification->push(_("Message redirected successfully."), 'horde.success');
            }
            $imp_ui->mailboxReturnUrl()->redirect();
        }
        exit;
    } catch (Horde_Exception $e) {
        $notification->push($e);
        $vars->actionID = 'redirect_compose';
        $get_sig = false;
    }
    break;

case 'auto_save_draft':
case 'save_draft':
case 'send_message':
    // Drafts readonly is handled below.
    if (($vars->actionID == 'send_message') && $compose_disable) {
        break;
    }

    try {
        $header['from'] = $identity->getFromLine(null, $vars->from);
    } catch (Horde_Exception $e) {
        $header['from'] = '';
        $get_sig = false;
        $notification->push($e);
        break;
    }

    $header['to'] = $imp_ui->getAddressList($vars->to);
    if ($prefs->getValue('compose_cc')) {
        $header['cc'] = $imp_ui->getAddressList($vars->cc);
    }
    if ($prefs->getValue('compose_bcc')) {
        $header['bcc'] = $imp_ui->getAddressList($vars->bcc);
    }

    $header['subject'] = strval($vars->subject);
    $message = strval($vars->message);

    /* Save the draft. */
    if (in_array($vars->actionID, array('auto_save_draft', 'save_draft'))) {
        if (!$readonly_drafts) {
            try {
                $result = $imp_compose->saveDraft($header, $message, $registry->getCharset(), $rtemode);

                /* Closing draft if requested by preferences. */
                if ($vars->actionID == 'save_draft') {
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
                }
            } catch (IMP_Compose_Exception $e) {
                if ($vars->actionID == 'save_draft') {
                    $notification->push($e);
                }
            }
        }

        if ($vars->actionID == 'auto_save_draft') {
            $request = new stdClass;
            $request->requestToken = Horde::getRequestToken('imp.compose');
            $request->formToken = Horde_Token::generateId('compose');
            Horde::sendHTTPResponse(Horde::prepareResponse($request), 'json');
            exit;
        }

        $get_sig = false;
        break;
    }

    $header['replyto'] = $identity->getValue('replyto_addr');

    if ($vars->sent_mail_folder) {
        $sent_mail_folder = $vars->sent_mail_folder;
    }

    $options = array(
        'encrypt' => $prefs->isLocked('default_encrypt') ? $prefs->getValue('default_encrypt') : $vars->encrypt_options,
        'identity' => $identity,
        'priority' => $vars->priority,
        'save_sent' => $save_sent_mail,
        'sent_folder' => $sent_mail_folder,
        'save_attachments' => $vars->save_attachments_select,
        'readreceipt' => $vars->request_read_receipt
    );

    try {
        $sent = $imp_compose->buildAndSendMessage($message, $header, $charset, $rtemode, $options);
        $imp_compose->destroy('send');
    } catch (IMP_Compose_Exception $e) {
        $get_sig = false;
        $code = $e->getCode();
        $notification->push($e->getMessage(), strpos($code, 'horde.') === 0 ? $code : 'horde.error');

        /* Switch to tied identity. */
        if (!is_null($e->tied_identity)) {
            $identity->setDefault($e->tied_identity);
            $notification->push(_("Your identity has been switched to the identity associated with the current recipient address. The identity will not be checked again during this compose action."));
        }

        // TODO
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
        if ($prefs->getValue('compose_confirm') || !$sent) {
            if ($sent) {
                $notification->push(_("Message sent successfully."), 'horde.success');
            }
            $imp_ui->popupSuccess();
        } else {
            echo Horde::wrapInlineScript(array('window.close();'));
        }
    } else {
        if ($prefs->getValue('compose_confirm') && $sent) {
            $notification->push(_("Message sent successfully."), 'horde.success');
        }
        $imp_ui->mailboxReturnUrl()->redirect();
    }
    exit;

case 'fwd_digest':
    if (isset($vars->fwddigest) &&
        (($subject_header = $imp_compose->attachIMAPMessage(new IMP_Indices($vars->fwddigest))) !== false)) {
        $header['subject'] = $subject_header;
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
                            $imp_compose->addMIMEPartAttachment($part);
                        } catch (IMP_Compose_Exception $e) {
                            $notification->push($e);
                        }
                    }
                }
            }
        } catch (Horde_Exception $e) {}
    }
    break;

case 'change_stationery':
    if (empty($stationery_list)) {
        break;
    }
    $stationery = $vars->stationery;
    if (strlen($stationery)) {
        $stationery = (int)$stationery;
        $stationery_content = $stationery_list[$stationery]['c'];
        $msg = strval($vars->message);
        if (strpos($stationery_content, '%s') !== false) {
            $sig = $identity->getSignature();
            if ($rtemode) {
                $sig = $imp_compose->text2html($sig);
                $stationery_content = $imp_compose->text2html($stationery_content);
            }
            $msg = str_replace(array("\r\n", $sig), array("\n", ''), $msg);
            $stationery_content = str_replace('%s', $sig, $stationery_content);
        }
        if (strpos($stationery_content, '%c') === false) {
            $msg .= $stationery_content;
        } else {
            $msg = str_replace('%c', $msg, $stationery_content);
        }
    }
    $get_sig = false;
    break;

case 'add_attachment':
    $get_sig = false;
    break;
}

/* Get the message cache ID. */
$composeCacheID = $imp_compose->getCacheId();

/* Are we in redirect mode? */
$redirect = ($vars->actionID == 'redirect_compose');

/* Attach autocompleters to the compose form elements. */
if ($browser->hasFeature('javascript')) {
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
        Horde::addScriptFile('ieescguard.js', 'horde');
    }
}

$max_attach = $imp_compose->additionalAttachmentsAllowed();
$smf_check = !empty($conf['user']['select_sentmail_folder']) && !$prefs->isLocked('sent_mail_folder');

/* Get the URL to use for the cancel action. */
$cancel_url = '';
if ($isPopup) {
    /* If the attachments cache is not empty, we must reload this page
     * and delete the attachments. */
    if ($imp_compose->numberOfAttachments()) {
        $cancel_url = Horde::selfUrl()->setRaw(true)->add(array(
            'actionID' => 'cancel_compose',
            'composeCache' => $composeCacheID,
            'popup' => 1
        ));
    }
} else {
    /* If the attachments cache is not empty, we must reload this page and
       delete the attachments. */
    if ($imp_compose->numberOfAttachments()) {
        $cancel_url = $imp_ui->mailboxReturnUrl(Horde::selfUrl()->setRaw(true))->add(array(
            'actionID' => 'cancel_compose',
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
} elseif ($get_sig) {
    $sig = $identity->getSignature($rtemode ? 'html' : 'text');
    if (!empty($sig)) {
        if ($identity->getValue('sig_first')) {
            $msg = $sig . $msg;
        } else {
            $msg .= "\n" . $sig;
        }
    }
}

/* If this is the first page load for this compose item, add auto BCC
 * addresses. */
if (!$vars->compose_formToken && ($vars->actionID != 'draft')) {
    $header['bcc'] = Horde_Mime_Address::addrArray2String($identity->getBccAddresses(), array('charset' => $registry->getCharset()));
}

foreach (array('to', 'cc', 'bcc', 'subject') as $val) {
    if (!isset($header[$val])) {
        $header[$val] = $imp_ui->getAddressList($vars->$val);
    }
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
        in_array($default_encrypt, array(IMP::PGP_ENCRYPT, IMP::PGP_SIGNENC))) {
        try {
            $addrs = $imp_compose->recipientList($header);
            if (!empty($addrs['list'])) {
                $imp_pgp = $injector->getInstance('IMP_Crypt_Pgp');
                foreach ($addrs['list'] as $val) {
                    $imp_pgp->getPublicKey($val);
                }
            }
        } catch (IMP_Compose_Exception $e) {
        } catch (Horde_Exception $e) {
            $notification->push(_("PGP encryption cannot be used by default as public keys cannot be found for all recipients."), 'horde.warning');
            $encrypt_options = ($default_encrypt == IMP::PGP_ENCRYPT) ? IMP::ENCRYPT_NONE : IMP::PGP_SIGN;
        }
    }
}

/* Define some variables used in the javascript code. */
$js_code = array(
    'IMP_Compose_Base.editor_on = ' . intval($rtemode),
    'ImpCompose.auto_save = ' . intval($prefs->getValue('auto_save_drafts')),
    'ImpCompose.cancel_url = \'' . $cancel_url . '\'',
    'ImpCompose.cursor_pos = ' .($rtemode ? 'null' : ('"' . $prefs->getValue('compose_cursor') . '"')),
    'ImpCompose.max_attachments = ' . (($max_attach === true) ? 'null' : $max_attach),
    'ImpCompose.popup = ' . intval($isPopup),
    'ImpCompose.redirect = ' . intval($redirect),
    'ImpCompose.reloaded = ' . intval($vars->compose_formToken),
    'ImpCompose.smf_check = ' . intval($smf_check),
    'ImpCompose.spellcheck = ' . intval($spellcheck && $prefs->getValue('compose_spellcheck'))
);

/* Create javascript identities array. */
if (!$redirect) {
    $js_code = array_merge($js_code, $imp_ui->identityJs());
}

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
    $t->set('token', Horde::getRequestToken('imp.compose'));

    Horde::startBuffer();
    IMP::status();
    $t->set('status', Horde::endBuffer());

    if ($registry->hasMethod('contacts/search')) {
        $t->set('abook', $blank_url->copy()->link(array(
            'class' => 'widget',
            'onclick.raw' => 'window.open("' . Horde::url('contacts.php')->add(array('formname' => 'redirect', 'to_only' => 1)) . '", "contacts", "toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes,width=550,height=300,left=100,top=100"); return false;',
            'title' => _("Address Book")
        )) . Horde::img('addressbook_browse.png') . '<br />' . _("Address Book") . '</a>');
    }

    $t->set('to', Horde::label('to', _("To")));
    $t->set('input_value', htmlspecialchars($header['to']));
    $t->set('help', Horde_Help::link('imp', 'compose-to'));

    $template_output = $t->fetch(IMP_TEMPLATES . '/imp/compose/redirect.html');
} else {
    /* Prepare the compose template. */
    $tabindex = 0;

    $t->set('file_upload', $_SESSION['imp']['file_upload']);
    $t->set('forminput', Horde_Util::formInput());

    $hidden = array(
        'actionID' => '',
        'attachmentAction' => '',
        'compose_formToken' => Horde_Token::generateId('compose'),
        'compose_requestToken' => Horde::getRequestToken('imp.compose'),
        'composeCache' => $composeCacheID,
        'mailbox' => htmlspecialchars(IMP::$mailbox),
        'oldrtemode' => $rtemode,
        'rtemode' => $rtemode,
        'user' => $registry->getAuth()
    );

    if ($_SESSION['imp']['file_upload']) {
        $hidden['MAX_FILE_SIZE'] = $_SESSION['imp']['file_upload'];
    }
    foreach (array('page', 'start', 'popup') as $val) {
        $hidden[$val] = htmlspecialchars($vars->$val);
    }

    if ($browser->hasQuirk('broken_multipart_form')) {
        $hidden['msie_formdata_is_broken'] = '';
    }

    $hidden_val = array();
    foreach ($hidden as $key => $val) {
        $hidden_val[] = array('n' => $key, 'v' => $val);
    }
    $t->set('hidden', $hidden_val);

    $t->set('title', htmlspecialchars($title));
    $t->set('send_msg_ak', Horde::getAccessKeyAndTitle(_("_Send Message")));
    if ($conf['user']['allow_folders'] && !$readonly_drafts) {
        $t->set('save_draft_ak', Horde::getAccessKeyAndTitle(_("Save _Draft")));
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
    $t->set('unlocked_charset', !$prefs->isLocked('sending_charset'));
    if ($t->get('unlocked_charset')) {
        $t->set('charset_label', Horde::label('charset', _("C_harset")));
        $t->set('charset_tabindex', ++$tabindex);
        $charset_array = array();
        asort($registry->nlsconfig['encodings']);
        foreach ($registry->nlsconfig['encodings'] as $charset => $label) {
            $charset_array[] = array('value' => $charset, 'selected' => (strtolower($charset) == strtolower($encoding)), 'label' => $label);
        }
        $t->set('charset_array', $charset_array);
    }
    if ($t->get('set_priority')) {
        $t->set('priority_label', Horde::label('priority', _("_Priority")));
        $t->set('priority_tabindex', ++$tabindex);

        $priority = isset($vars->priority) ? $vars->priority : 'normal';
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

    $t->set('stationery', !empty($stationery_list));
    if ($t->get('stationery')) {
        $t->set('stationery_label', Horde::label('stationery', _("Stationery")));
        $stationeries = array();
        foreach ($stationery_list as $id => $choice) {
            $stationeries[] = array('val' => $id, 'label' => $choice['n'], 'selected' => ($stationery === $id));
        }
        $t->set('stationeries', $stationeries);
    }

    $menu_view = $prefs->getValue('menu_view');
    $show_text = ($menu_view == 'text' || $menu_view == 'both');
    $compose_options = array();
    if ($registry->hasMethod('contacts/search')) {
        $compose_options[] = array(
            'url' => $blank_url->copy()->link(array(
                'class' => 'widget',
                'onclick.raw' => 'window.open("' . Horde::url('contacts.php') . '","contacts","toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes,width=550,height=300,left=100,top=100"); return false;'
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
    if ($_SESSION['imp']['file_upload']) {
        $url = new Horde_Url('#attachments');
        $compose_options[] = array(
            'url' => $url->link(array('class' => 'widget')),
            'img' => Horde::img('manage_attachments.png'),
            'label' => $show_text ? _("Attachments") : ''
        );
    }
    $t->set('compose_options', $compose_options);

    $t->set('ssm', ($conf['user']['allow_folders'] && !$prefs->isLocked('save_sent_mail')));
    if ($t->get('ssm')) {
        if ($readonly_sentmail) {
            $notification->push(sprintf(_("Cannot save sent-mail message to \"%s\" as that mailbox is read-only.", $sent_mail_folder), 'horde.warning'));
        }
        $t->set('ssm_selected', $vars->compose_formToken ? ($save_sent_mail == 'on') : $identity->saveSentmail());
        $t->set('ssm_label', Horde::label('ssm', _("Sa_ve a copy in ")));
        if ($vars->sent_mail_folder) {
            $sent_mail_folder = $vars->sent_mail_folder;
        }
        if (!empty($conf['user']['select_sentmail_folder']) &&
            !$prefs->isLocked('sent_mail_folder')) {
            $ssm_folder_options = array(
                'abbrev' => false,
                'filter' => array('INBOX'),
                'selected' => $sent_mail_folder
            );
            $t->set('ssm_tabindex', ++$tabindex);

            /* Check to make sure the sent-mail folder is created - it needs
             * to exist to show up in drop-down list. */
            $imp_folder = $injector->getInstance('IMP_Folder');
            if (!$imp_folder->exists($sent_mail_folder)) {
                $imp_folder->create($sent_mail_folder, true);
            }

            $t->set('ssm_folders', IMP::flistSelect($ssm_folder_options));
        } else {
            if (!empty($sent_mail_folder)) {
                $sent_mail_folder = '&quot;' . IMP::displayFolder($sent_mail_folder) . '&quot;';
            }
            $t->set('ssm_folder', $sent_mail_folder);
            $t->set('ssm_folders', false);
        }
    }

    $d_read = $prefs->getValue('disposition_request_read');
    $t->set('rrr', $conf['compose']['allow_receipts'] && ($d_read != 'never'));
    if ($t->get('rrr')) {
        $t->set('rrr_selected', ($d_read != 'ask') || ($vars->request_read_receipt == 'on'));
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

    $t->set('message_label', Horde::label('message', _("Te_xt")));
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
    if ($_SESSION['imp']['file_upload']) {
        $localeinfo = Horde_Nls::getLocaleInfo();
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
        $t->set('attach_size', number_format($imp_compose->maxAttachmentSize(), 0, $localeinfo['decimal_point'], $localeinfo['thousands_sep']));
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
                $attach_options[] = array('label' => _("Save Attachments with message in sent-mail folder?"), 'name' => 'save_attachments_select', 'select_yes' => ($save_attach_val == 1), 'select_no' => ($save_attach_val == 0), 'help' => Horde_Help::link('imp', 'compose-save-attachments'));
            }
            if ($show_link_attach) {
                $attach_options[] = array('label' => _("Link Attachments?"), 'name' => 'link_attachments', 'select_yes' => ($vars->link_attachments == 1), 'select_no' => ($vars->link_attachments == 0), 'help' => Horde_Help::link('imp', 'compose-link-attachments'));
            }
            $t->set('attach_options', $attach_options);
        }

        $t->set('numberattach', $imp_compose->numberOfAttachments());
        if ($t->get('numberattach')) {
            $atc = array();
            $v = $injector->getInstance('Horde_Mime_Viewer');
            foreach ($imp_compose->getAttachments() as $atc_num => $data) {
                $mime = $data['part'];
                $type = $mime->getType();

                $entry = array(
                    'name' => $mime->getName(true),
                    'icon' => $v->getIcon($type),
                    'number' => $atc_num,
                    'type' => $type,
                    'size' => $mime->getSize(),
                    'description' => $mime->getDescription(true)
                );

                if ($type != 'application/octet-stream') {
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
            $t->set('total_attach_size', number_format($imp_compose->sizeOfAttachments() / 1024, 2, $localeinfo['decimal_point'], $localeinfo['thousands_sep']));
            $t->set('perc_attach', ((!empty($conf['compose']['attach_size_limit'])) && ($conf['compose']['attach_size_limit'] > 0)));
            if ($t->get('perc_attach')) {
                $t->set('perc_attach', sprintf(_("%s%% of allowed size"), number_format($imp_compose->sizeOfAttachments() / $conf['compose']['attach_size_limit'] * 100, 2, $localeinfo['decimal_point'], $localeinfo['thousands_sep'])));
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
    $imp_ui->initRTE();
    Horde::addInlineScript('CKEDITOR.replace("composeMessage", IMP.ckeditor_config)', 'load');
}

if ($showmenu) {
    $menu = IMP::menu();
}
Horde::addScriptFile('compose-base.js', 'imp');
Horde::addScriptFile('compose.js', 'imp');
Horde::addScriptFile('md5.js', 'horde');
require IMP_TEMPLATES . '/common-header.inc';
Horde::addInlineScript($js_code);
if ($showmenu) {
    echo $menu;
}
echo $template_output;
require $registry->get('templates', 'horde') . '/common-footer.inc';
