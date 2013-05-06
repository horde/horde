<?php
/**
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 1999-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl21 GPL
 * @package   IMP
 */

/**
 * Compose page for the basic view.
 *
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 1999-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl21 GPL
 * @package   IMP
 */
class IMP_Basic_Compose extends IMP_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $browser, $conf, $injector, $notification, $page_output, $prefs, $registry, $session;

        /* Mailto link handler: redirect based on current view. */
        // TODO: preserve state
        if ($this->vars->actionID == 'mailto_link') {
            switch ($registry->getView()) {
            case Horde_Registry::VIEW_DYNAMIC:
                IMP_Dynamic_Compose::url()->add($_GET)->redirect();
                exit;

            case Horde_Registry::VIEW_MINIMAL:
                IMP_Minimal_Compose::url()->add($_GET)->redirect();
                exit;
            }
        }

        /* The message headers and text. */
        $header = array();
        $msg = '';

        $redirect = $resume = $showmenu = $spellcheck = false;
        $oldrtemode = $rtemode = null;

        /* Set the current identity. */
        $identity = $injector->getInstance('IMP_Identity');
        if (!$prefs->isLocked('default_identity') &&
            !is_null($this->vars->identity)) {
            $identity->setDefault($this->vars->identity);
        }

        $horde_token = $injector->getInstance('Horde_Token');

        if ($this->vars->actionID) {
            switch ($this->vars->actionID) {
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
                /* These are all safe actions that might be invoked without a
                 * token. */
                break;

            default:
                try {
                    $horde_token->validate($this->vars->compose_requestToken, 'imp.compose');
                } catch (Horde_Token_Exception $e) {
                    $notification->push($e);
                    $this->vars->actionID = null;
                }
            }
        }

        /* Check for duplicate submits. */
        if ($reload = $this->vars->compose_formToken) {
            try {
                if (!$horde_token->verify($reload)) {
                    $notification->push(_("You have already submitted this page."), 'horde.error');
                    $this->vars->actionID = null;
                }
            } catch (Horde_Token_Exception $e) {
                $notification->push($e->getMessage());
                $this->vars->actionID = null;
            }
        }

        /* Determine if compose mode is disabled. */
        $compose_disable = !IMP_Compose::canCompose();

        /* Determine if mailboxes are readonly. */
        $draft = IMP_Mailbox::getPref(IMP_Mailbox::MBOX_DRAFTS);
        $readonly_drafts = $draft && $draft->readonly;

        $sent_mail = $identity->getValue(IMP_Mailbox::MBOX_SENT);
        if (!$sent_mail) {
            $readonly_sentmail = $save_sent_mail = false;
        } elseif ($sent_mail->readonly) {
            $readonly_sentmail = true;
            $save_sent_mail = false;
        } else {
            $readonly_sentmail = false;
            $save_sent_mail = $reload
                ? (bool)$this->vars->save_sent_mail
                : true;
        }

        /* Initialize the IMP_Compose:: object. */
        $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($this->vars->composeCache);

        /* Init objects. */
        $imp_imap = $injector->getInstance('IMP_Imap');
        $imp_ui = new IMP_Compose_Ui();

        /* Is this a popup window? */
        $isPopup = ($prefs->getValue('compose_popup') || $this->vars->popup);

        /* Determine the composition type - text or HTML.
           $rtemode is null if browser does not support it. */
        if ($session->get('imp', 'rteavail')) {
            if ($prefs->isLocked('compose_html')) {
                $rtemode = $prefs->getValue('compose_html');
            } else {
                $rtemode = $this->vars->rtemode;
                if (is_null($rtemode)) {
                    $rtemode = $prefs->getValue('compose_html');
                } else {
                    $rtemode = intval($rtemode);
                    $oldrtemode = intval($this->vars->oldrtemode);
                }
            }
        }

        /* Update the file attachment information. */
        $attach_upload = $imp_compose->canUploadAttachment();
        if ($attach_upload) {
            /* Only notify if we are reloading the compose screen. */
            $notify = !in_array($this->vars->actionID, array('send_message', 'save_draft'));

            $deleteList = Horde_Util::getPost('delattachments', array());

            /* Update the attachment information. */
            foreach ($imp_compose as $key => $val) {
                if (!in_array($key, $deleteList)) {
                    $val->getPart()->setDescription($this->vars->filter('file_description_' . $key));
                    $imp_compose[$key] = $val;
                }
            }

            /* Delete attachments. */
            foreach ($deleteList as $val) {
                if ($notify) {
                    $notification->push(sprintf(_("Deleted attachment \"%s\"."), $imp_compose[$val]->getPart()->getName(true)), 'horde.success');
                }
                unset($imp_compose[$val]);
            }

            /* Add attachments. */
            for ($i = 1, $fcount = count($_FILES); $i <= $fcount; ++$i) {
                if (isset($_FILES['upload_' . $i]) &&
                    strlen($_FILES['upload_' . $i]['name'])) {
                    try {
                        $atc_ob = $imp_compose->addAttachmentFromUpload($this->vars, 'upload_' . $i);
                        if ($notify) {
                            $notification->push(sprintf(_("Added \"%s\" as an attachment."), $atc_ob->getPart()->getName()), 'horde.success');
                        }
                    } catch (IMP_Compose_Exception $e) {
                        /* Any error will cancel the current action. */
                        $this->vars->actionID = null;
                        $notification->push($e, 'horde.error');
                    }
                }
            }
        }

        /* Get message priority. */
        $priority = $this->vars->get('priority', 'normal');

        /* Request read receipt? */
        $request_read_receipt = (bool)$this->vars->request_read_receipt;

        /* Run through the action handlers. */
        $this->title = _("New Message");
        switch ($this->vars->actionID) {
        case 'mailto':
            try {
                $contents = $this->_getContents();
            } catch (IMP_Compose_Exception $e) {
                $notification->push($e, 'horde.error');
                break;
            }

            $imp_headers = $contents->getHeader();
            $header['to'] = '';
            if ($this->vars->mailto) {
                $header['to'] = $imp_headers->getValue('to');
            }
            if (empty($header['to'])) {
                ($header['to'] = strval($imp_headers->getOb('from'))) ||
                ($header['to'] = strval($imp_headers->getOb('reply-to')));
            }
            break;

        case 'mailto_link':
            $clink = new IMP_Compose_Link($this->vars);
            if (isset($clink->args['body'])) {
                $msg = $clink->args['body'];
            }
            foreach (array('to', 'cc', 'bcc', 'subject') as $val) {
                if (isset($clink->args[$val])) {
                    $header[$val] = $clink->args[$val];
                }
            }
            break;

        case 'draft':
        case 'editasnew':
        case 'template':
        case 'template_edit':
            try {
                switch ($this->vars->actionID) {
                case 'draft':
                    $result = $imp_compose->resumeDraft($this->indices);
                    $resume = true;
                    break;

                case 'editasnew':
                    $result = $imp_compose->editAsNew($this->indices);
                    break;

                case 'template':
                    $result = $imp_compose->useTemplate($this->indices);
                    break;

                case 'template_edit':
                    $result = $imp_compose->editTemplate($this->indices);
                    $this->vars->template_mode = true;
                    break;
                }

                if (!is_null($rtemode)) {
                    $rtemode = ($result['format'] == 'html');
                }
                $msg = $result['body'];
                $header = array_merge(
                    $header,
                    $this->_convertToHeader($result)
                );
                if (!is_null($result['identity']) &&
                    ($result['identity'] != $identity->getDefault()) &&
                    !$prefs->isLocked('default_identity')) {
                    $identity->setDefault($result['identity']);
                    $sent_mail = $identity->getValue(IMP_Mailbox::MBOX_SENT);
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
                $contents = $this->_getContents();
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

            $reply_msg = $imp_compose->replyMessage($reply_map[$this->vars->actionID], $contents, array(
                'to' => $this->vars->to
            ));
            $msg = $reply_msg['body'];
            $header = $this->_convertToHeader($reply_msg);
            $format = $reply_msg['format'];

            switch ($reply_msg['type']) {
            case IMP_Compose::REPLY_SENDER:
                $this->vars->actionID = 'reply';
                $this->title = _("Reply:");
                break;

            case IMP_Compose::REPLY_ALL:
                if ($this->vars->actionID == 'reply_auto') {
                    $recip_list = $imp_compose->recipientList($header);
                    if (!empty($recip_list['list'])) {
                        $replyauto_all = count($recip_list['list']);
                    }
                }

                $this->vars->actionID = 'reply_all';
                $this->title = _("Reply to All:");
                break;

            case IMP_Compose::REPLY_LIST:
                if ($this->vars->actionID == 'reply_auto') {
                    $replyauto_list = true;
                    if (($parse_list = $injector->getInstance('Horde_ListHeaders')->parse('list-id', $contents->getHeader()->getValue('list-id'))) &&
                        !is_null($parse_list->label)) {
                        $replyauto_list_id = $parse_list->label;
                    }
                }

                $this->vars->actionID = 'reply_list';
                $this->title = _("Reply to List:");
                break;
            }

            if (!empty($reply_msg['lang'])) {
                $reply_lang = array_values($reply_msg['lang']);
            }

            $this->title .= ' ' . $header['subject'];

            if (!is_null($rtemode)) {
                $rtemode = ($rtemode || ($format == 'html'));
            }
            break;

        case 'replyall_revert':
        case 'replylist_revert':
            $reply_msg = $imp_compose->replyMessage(IMP_Compose::REPLY_SENDER, $imp_compose->getContentsOb());
            $header = $this->_convertToHeader($reply_msg);
            break;

        case 'forward_attach':
        case 'forward_auto':
        case 'forward_body':
        case 'forward_both':
            try {
                $contents = $this->_getContents();
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

            $fwd_msg = $imp_compose->forwardMessage($fwd_map[$this->vars->actionID], $contents);
            $msg = $fwd_msg['body'];
            $header = $this->_convertToHeader($fwd_msg);
            $format = $fwd_msg['format'];
            $rtemode = ($rtemode || (!is_null($rtemode) && ($format == 'html')));
            $this->title = $fwd_msg['title'];
            break;

        case 'redirect_compose':
            try {
                $imp_compose->redirectMessage($this->indices);
                $redirect = true;
                $this->title = ngettext(_("Redirect"), _("Redirect Messages"), count($this->indices));
            } catch (IMP_Compose_Exception $e) {
                $notification->push($e, 'horde.error');
            }
            break;

        case 'redirect_send':
            try {
                $num_msgs = $imp_compose->sendRedirectMessage($this->vars->to);
                $imp_compose->destroy('send');
                if ($isPopup) {
                    if ($prefs->getValue('compose_confirm')) {
                        $notification->push(ngettext("Message redirected successfully.", "Messages redirected successfully", count($num_msgs)), 'horde.success');
                        $this->_popupSuccess();
                        return;
                    }
                    echo Horde::wrapInlineScript(array('window.close();'));
                } else {
                    $notification->push(ngettext("Message redirected successfully.", "Messages redirected successfully", count($num_msgs)), 'horde.success');
                    $this->_mailboxReturnUrl()->redirect();
                }
                exit;
            } catch (Horde_Exception $e) {
                $notification->push($e);
                $this->vars->actionID = 'redirect_compose';
            }
            break;

        case 'auto_save_draft':
        case 'save_draft':
        case 'save_template':
        case 'send_message':
            // Drafts readonly is handled below.
            if ($compose_disable &&
                ($this->vars->actionID == 'send_message')) {
                break;
            }

            try {
                $header['from'] = strval($identity->getFromLine(null, $this->vars->from));
            } catch (Horde_Exception $e) {
                $header['from'] = '';
                $notification->push($e);
                break;
            }

            $header['to'] = $this->vars->to;
            $header['cc'] = $this->vars->cc;
            $header['bcc'] = $this->vars->bcc;

            $header['subject'] = strval($this->vars->subject);
            $message = strval($this->vars->message);

            /* Save the draft. */
            switch ($this->vars->actionID) {
            case 'auto_save_draft':
            case 'save_draft':
            case 'save_template':
                if (!$readonly_drafts ||
                    ($this->vars->actionID == 'save_template')) {
                    $save_opts = array(
                        'html' => $rtemode,
                        'priority' => $priority,
                        'readreceipt' => $request_read_receipt
                    );

                    try {
                        switch ($this->vars->actionID) {
                        case 'save_template':
                            $result = $imp_compose->saveTemplate($header, $message, $save_opts);
                            break;

                        default:
                            $result = $imp_compose->saveDraft($header, $message, $save_opts);
                            break;
                        }

                        /* Closing draft if requested by preferences. */
                        switch ($this->vars->actionID) {
                        case 'save_draft':
                            if ($isPopup) {
                                if ($prefs->getValue('close_draft')) {
                                    $imp_compose->destroy('save_draft');
                                    echo Horde::wrapInlineScript(array('window.close();'));
                                    exit;
                                }
                                $notification->push($result, 'horde.success');
                            } else {
                                $notification->push($result, 'horde.success');
                                if ($prefs->getValue('close_draft')) {
                                    $imp_compose->destroy('save_draft');
                                    $this->_mailboxReturnUrl()->redirect();
                                }
                            }
                            break;

                        case 'save_template':
                            if ($isPopup) {
                                echo Horde::wrapInlineScript(array('window.close();'));
                                exit;
                            }

                            $notification->push($result, 'horde.success');
                            $this->_mailboxReturnUrl()->redirect();
                            break;
                        }
                    } catch (IMP_Compose_Exception $e) {
                        if ($this->vars->actionID == 'save_draft') {
                            $notification->push($e);
                        }
                    }
                }

                if ($this->vars->actionID == 'auto_save_draft') {
                    $r = new stdClass;
                    $r->requestToken = $horde_token->get('imp.compose');
                    $r->formToken = Horde_Token::generateId('compose');

                    $response = new Horde_Core_Ajax_Response_HordeCore($r);
                    $response->sendAndExit();
                }
                break;

            default:
                $header['replyto'] = $identity->getValue('replyto_addr');

                if ($this->vars->sent_mail) {
                    $sent_mail = IMP_Mailbox::formFrom($this->vars->sent_mail);
                }

                $options = array(
                    'add_signature' => $identity->getDefault(),
                    'encrypt' => $prefs->isLocked('default_encrypt') ? $prefs->getValue('default_encrypt') : $this->vars->encrypt_options,
                    'html' => $rtemode,
                    'identity' => $identity,
                    'pgp_attach_pubkey' => $this->vars->pgp_attach_pubkey,
                    'priority' => $priority,
                    'save_sent' => $save_sent_mail,
                    'sent_mail' => $sent_mail,
                    'save_attachments' => $this->vars->save_attachments_select,
                    'readreceipt' => $request_read_receipt,
                    'vcard_attach' => $this->vars->vcard ? $identity->getValue('fullname') : null
                );

                try {
                    $imp_compose->buildAndSendMessage($message, $header, $options);
                    $imp_compose->destroy('send');

                    if ($isPopup) {
                        if ($prefs->getValue('compose_confirm')) {
                            $notification->push(_("Message sent successfully."), 'horde.success');
                            $this->_popupSuccess();
                            return;
                        }
                        echo Horde::wrapInlineScript(array('window.close();'));
                    } else {
                        $notification->push(_("Message sent successfully."), 'horde.success');
                        $this->_mailboxReturnUrl()->redirect();
                    }
                    exit;
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
                }
                break;
            }
            break;

        case 'fwd_digest':
            if (count($this->indices)) {
                try {
                    $header['subject'] = $imp_compose->attachImapMessage($this->indices);
                    $fwd_msg = array('type' => IMP_Compose::FORWARD_ATTACH);
                } catch (IMP_Compose_Exception $e) {
                    $notification->push($e, 'horde.error');
                }
            }
            break;

        case 'cancel_compose':
        case 'discard_compose':
            $imp_compose->destroy($this->vars->actionID == 'cancel_compose' ? 'cancel' : 'discard');
            if ($isPopup) {
                echo Horde::wrapInlineScript(array('window.close();'));
            } else {
                $this->_mailboxReturnUrl()->redirect();
            }
            exit;

        case 'template_new':
            $this->vars->template_mode = true;
            break;
        }

        /* Get the message cache ID. */
        $composeCacheID = filter_var($imp_compose->getCacheId(), FILTER_SANITIZE_STRING);

        /* Attach autocompleters to the compose form elements. */
        if ($redirect) {
            $imp_ui->attachAutoCompleter(array('to'));
        } else {
            $imp_ui->attachAutoCompleter(array('to', 'cc', 'bcc'));

            if (!empty($conf['spell']['driver'])) {
                $spellcheck = true;
                $imp_ui->attachSpellChecker();
            }

            $page_output->addScriptFile('ieescguard.js', 'horde');
        }

        $max_attach = $imp_compose->additionalAttachmentsAllowed();

        /* Get the URL to use for the cancel action. If the attachments cache
         * is not empty, or this is the resume drafts page, we must reload
         * this page and delete the attachments and/or the draft message. */
        if ($isPopup) {
            if ($resume || count($imp_compose)) {
                $cancel_url = self::url()->setRaw(true)->add(array(
                    'compose_requestToken' => $horde_token->get('imp.compose'),
                    'composeCache' => $composeCacheID,
                    'popup' => 1
                ));
            } else {
                $cancel_url = '';
            }
        } else {
            if ($resume || count($imp_compose)) {
                $cancel_url = $this->_mailboxReturnUrl(self::url()->setRaw(true))->add(array(
                    'compose_requestToken' => $horde_token->get('imp.compose'),
                    'composeCache' => $composeCacheID
                ));
            } else {
                $cancel_url = $this->_mailboxReturnUrl(false)->setRaw(false);
            }
            $showmenu = true;
        }

        /* Grab any data that we were supplied with. */
        if (!strlen($msg)) {
            $msg = $this->vars->get('message', strval($this->vars->body));
            if ($browser->hasQuirk('double_linebreak_textarea')) {
                $msg = preg_replace('/(\r?\n){3}/', '$1', $msg);
            }
            $msg = "\n" . $msg;
        }

        /* Convert from Text -> HTML or vice versa if RTE mode changed. */
        if (!is_null($oldrtemode) && ($oldrtemode != $rtemode)) {
            $msg = $imp_ui->convertComposeText($msg, $rtemode ? 'html' : 'text');
        }

        /* If this is the first page load for this compose item, add auto BCC
         * addresses. */
        if (!$reload && !$resume) {
            $header['bcc'] = strval($identity->getBccAddresses());
        }

        foreach (array('to', 'cc', 'bcc') as $val) {
            if (!isset($header[$val])) {
                $header[$val] = $this->vars->$val;
            }
        }

        if (!isset($header['subject'])) {
            $header['subject'] = $this->vars->subject;
        }

        /* If PGP encryption is set by default, and we have a recipient list
         * on first load, make sure we have public keys for all recipients. */
        $encrypt_options = $prefs->isLocked('default_encrypt')
            ? $prefs->getValue('default_encrypt')
            : $this->vars->encrypt_options;
        if ($prefs->getValue('use_pgp') &&
            !$prefs->isLocked('default_encrypt') &&
            $prefs->getValue('pgp_reply_pubkey')) {
            $default_encrypt = $prefs->getValue('default_encrypt');
            if (!$reload &&
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
            'ImpCompose.reloaded' => intval($reload),
            'ImpCompose.sm_check' => intval(!$prefs->isLocked(IMP_Mailbox::MBOX_SENT)),
            'ImpCompose.spellcheck' => intval($spellcheck && $prefs->getValue('compose_spellcheck')),
            'ImpCompose.text' => array(
                'cancel' => _("Cancelling this message will permanently discard its contents.") . "\n" . _("Are you sure you want to do this?"),
                'discard' => _("Doing so will discard this message permanently."),
                'file' => _("File"),
                'nosubject' => _("The message does not have a Subject entered.") . "\n" . _("Send message without a Subject?"),
                'recipient' => _("You must specify a recipient.")
            )
        );

        /* Set up the base view now. */
        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/basic/compose'
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Horde_Core_View_Helper_Accesskey');
        $view->addHelper('Horde_Core_View_Helper_Help');
        $view->addHelper('Horde_Core_View_Helper_Image');
        $view->addHelper('Horde_Core_View_Helper_Label');
        $view->addHelper('Tag');

        $view->allow_compose = !$compose_disable;
        $view->post_action = self::url();

        $blank_url = new Horde_Url('#');

        if ($redirect) {
            /* Prepare the redirect template. */
            $view->cacheid = $composeCacheID;
            $view->title = $this->title;
            $view->token = $horde_token->get('imp.compose');

            if ($registry->hasMethod('contacts/search')) {
                $view->abook = $blank_url->copy()->link(array(
                    'class' => 'widget',
                    'id' => 'redirect_abook',
                    'title' => _("Address Book")
                ));
                $js_vars['ImpCompose.redirect_contacts'] = strval(IMP_Basic_Contacts::url()->add(array('to_only' => 1))->setRaw(true));
            }

            $view->input_value = $header['to'];

            $this->output = $view->render('redirect');
        } else {
            /* Prepare the compose template. */
            $view->file_upload = $attach_upload;

            $hidden = array(
                'actionID' => '',
                'attachmentAction' => '',
                'compose_formToken' => Horde_Token::generateId('compose'),
                'compose_requestToken' => $horde_token->get('imp.compose'),
                'composeCache' => $composeCacheID,
                'oldrtemode' => $rtemode,
                'rtemode' => $rtemode,
                'user' => $registry->getAuth()
            );

            if ($attach_upload) {
                $hidden['MAX_FILE_SIZE'] = $session->get('imp', 'file_upload');
            }
            foreach (array('page', 'start', 'popup', 'template_mode') as $val) {
                $hidden[$val] = $this->vars->$val;
            }

            $view->hidden = $hidden;
            $view->tabindex = 1;
            $view->title = $this->title;

            if (!$this->vars->template_mode) {
                $view->send_msg = true;
                $view->save_draft = ($imp_imap->access(IMP_Imap::ACCESS_DRAFTS) && !$readonly_drafts);
            }

            $view->resume = $resume;

            $view->di_locked = $prefs->isLocked('default_identity');
            if ($view->di_locked) {
                $view->fromaddr_locked = $prefs->isLocked('from_addr');
                try {
                    $view->from = $identity->getFromLine(null, $this->vars->from);
                } catch (Horde_Exception $e) {}
            } else {
                $select_list = $identity->getSelectList();
                $view->last_identity = $identity->getDefault();

                if (count($select_list) > 1) {
                    $view->count_select_list = true;
                    $t_select_list = array();
                    foreach ($select_list as $key => $select) {
                        $t_select_list[] = array(
                            'label' => $select,
                            'selected' => ($key == $identity->getDefault()),
                            'value' => $key
                        );
                    }
                    $view->select_list = $t_select_list;
                } else {
                    $view->identity_default = $identity->getDefault();
                    $view->identity_text = $select_list[0];
                }
            }

            $addr_array = array(
                'to' => _("_To"),
                'cc' => _("_Cc"),
                'bcc' => _("_Bcc")
            );

            $address_array = array();
            foreach ($addr_array as $val => $label) {
                $address_array[] = array(
                    'id' => $val,
                    'label' => $label,
                    'val' => $header[$val]
                );
            }
            $view->addr = $address_array;

            $view->subject = $header['subject'];

            if ($prefs->getValue('set_priority')) {
                $view->set_priority = true;

                $priorities = array(
                    'high' => _("High"),
                    'normal' => _("Normal"),
                    'low' => _("Low")
                );

                $priority_option = array();
                foreach ($priorities as $key => $val) {
                    $priority_option[] = array(
                        'label' => $val,
                        'selected' => ($priority == $key),
                        'val' => $key
                    );
                }
                $view->pri_opt = $priority_option;
            }

            $compose_options = array();

            if ($registry->hasMethod('contacts/search')) {
                $compose_options[] = array(
                    'url' => $blank_url->copy()->link(array(
                        'class' => 'widget',
                        'id' => 'addressbook_popup'
                    )),
                    'img' => Horde::img('addressbook_browse.png'),
                    'label' => _("Address Book")
                );
                $js_vars['ImpCompose.contacts_url'] = strval(IMP_Basic_Contacts::url()->setRaw(true));
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
            if ($attach_upload) {
                $url = new Horde_Url('#attachments');
                $compose_options[] = array(
                    'url' => $url->link(array('class' => 'widget')),
                    'img' => Horde::img('attachment.png'),
                    'label' => _("Attachments")
                );
            }
            $view->compose_options = $compose_options;

            if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS) &&
                !$prefs->isLocked('save_sent_mail')) {
                $view->ssm = true;
                if ($readonly_sentmail) {
                    $notification->push(sprintf(_("Cannot save sent-mail message to \"%s\" as that mailbox is read-only.", $sent_mail->display), 'horde.warning'));
                }
                $view->ssm_selected = $reload
                    ? $save_sent_mail
                    : ($sent_mail && $identity->saveSentmail());
                if ($this->vars->sent_mail) {
                    $sent_mail = IMP_Mailbox::formFrom($this->vars->sent_mail);
                }
                if (!$prefs->isLocked(IMP_Mailbox::MBOX_SENT)) {
                    $ssm_options = array(
                        'abbrev' => false,
                        'basename' => true,
                        'filter' => array('INBOX'),
                        'selected' => $sent_mail
                    );

                    /* Check to make sure the sent-mail mailbox is created -
                     * it needs to exist to show up in drop-down list. */
                    if ($sent_mail) {
                        $sent_mail->create();
                    }

                    $view->ssm_mboxes = IMP::flistSelect($ssm_options);
                } else {
                    if ($sent_mail) {
                        $sent_mail = '&quot;' . $sent_mail->display_html . '&quot;';
                    }
                    $view->ssm_mbox = $sent_mail;
                }
            }

            $view->rrr_selected = $prefs->isLocked('request_mdn')
                ? null
                : (($prefs->getValue('request_mdn') == 'always') || $request_read_receipt);

            if (!is_null($rtemode) && !$prefs->isLocked('compose_html')) {
                $view->compose_html = true;
                $view->html_switch = $blank_url->copy()->link(array(
                    'id' => 'rte_toggle',
                    'title' => _("Switch Composition Method")
                ));
                $view->rtemode = $rtemode;
            }

            if (isset($replyauto_all)) {
                $view->replyauto_all = $replyauto_all;
            } elseif (isset($replyauto_list)) {
                $view->replyauto_list = true;
                if (isset($replyauto_list_id)) {
                    $view->replyauto_list_id = $replyauto_list_id;
                }
            }

            if (isset($reply_lang)) {
                $view->reply_lang = implode(',', $reply_lang);
            }

            $view->message = $msg;

            if ($prefs->getValue('use_pgp') || $prefs->getValue('use_smime')) {
                if ($prefs->isLocked('default_encrypt')) {
                    $view->use_encrypt = false;
                } else {
                    $view->use_encrypt = true;
                    $view->encrypt_options = IMP::encryptList($encrypt_options);
                }

                if ($prefs->getValue('use_pgp') && $prefs->getValue('pgp_public_key')) {
                    $view->pgp_options = true;
                    $view->pgp_attach_pubkey = $reload
                        ? $this->vars->pgp_attach_pubkey
                        : $prefs->getValue('pgp_attach_pubkey');
                }
            }

            if ($registry->hasMethod('contacts/ownVCard')) {
                $view->vcard = true;
                $view->attach_vcard = $this->vars->vcard;
            }

            if ($attach_upload) {
                $view->attach_size = IMP::numberFormat($imp_compose->maxAttachmentSize(), 0);
                $view->maxattachmentnumber = !$max_attach;

                $save_attach = $prefs->getValue('save_attachments');

                if ($view->ssm && !$prefs->isLocked('save_attachments')) {
                    $view->show_link_save_attach = true;
                    $view->attach_options = array(array(
                        'label' => _("Save attachments with message in sent-mail mailbox?"),
                        'name' => 'save_attachments_select',
                        'val' => ($reload ? $this->vars->save_attachments_select : ($save_attach == 'always'))
                    ));
                }

                if (count($imp_compose)) {
                    $view->numberattach = true;

                    $atc = array();
                    $v = $injector->getInstance('Horde_Core_Factory_MimeViewer');
                    foreach ($imp_compose as $data) {
                        $mime = $data->getPart();
                        $type = $mime->getType();

                        $entry = array(
                            'name' => $mime->getName(true),
                            'icon' => $v->getIcon($type),
                            'number' => $data->id,
                            'type' => $type,
                            'size' => $mime->getSize(),
                            'description' => $mime->getDescription(true)
                        );

                        if (!(isset($fwd_msg) &&
                              ($fwd_msg['type'] != IMP_Compose::FORWARD_BODY)) &&
                            ($type != 'application/octet-stream')) {
                            $entry['name'] = $data->viewUrl()->link(array(
                                'class' => 'link',
                                'target' => 'compose_preview_window',
                                'title' => _("Preview")
                            )) . htmlspecialchars($entry['name']) . '</a>';
                        }

                        $atc[] = $entry;
                    }
                    $view->atc = $atc;
                }
            }

            $this->output = $view->render('compose');
        }

        if ($rtemode && !$redirect) {
            $injector->getInstance('IMP_Editor')->init(false, 'composeMessage');
        }

        if (!$showmenu) {
            $page_output->topbar = $page_output->sidebar = false;
        }
        $page_output->addScriptPackage('IMP_Script_Package_ComposeBase');
        $page_output->addScriptFile('compose.js');
        $page_output->addScriptFile('murmurhash3.js');
        $page_output->addInlineJsVars($js_vars);
        if (!$redirect) {
            $imp_ui->addIdentityJs();
        }
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('basic.php')->add('page', 'compose')->unique();
    }

    /**
     * Create the IMP_Contents objects needed to create a message.
     *
     * @return IMP_Contents  The IMP_Contents object.
     * @throws IMP_Exception
     */
    protected function _getContents()
    {
        $ob = null;

        if (count($this->indices)) {
            try {
                $ob = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($this->indices);
            } catch (Horde_Exception $e) {}
        }

        if (!is_null($ob)) {
            return $ob;
        }

        $this->vars->buid = null;
        $this->vars->type = 'new';
        throw new IMP_Exception(_("Could not retrieve message data from the mail server."));
    }

    /**
     * Generate mailbox return URL.
     *
     * @param string $url  The URL to use instead of the default.
     *
     * @return string  The mailbox return URL.
     */
    protected function _mailboxReturnUrl($url = null)
    {
        $url = $this->indices->mailbox->url('mailbox');

        foreach (array('start', 'page') as $key) {
            if (isset($vars->$key)) {
                $url->add($key, $vars->$key);
            }
        }

        return $url;
    }

    /**
     * Generate a popup success window.
     */
    protected function _popupSuccess()
    {
        global $page_output;

        $page_output->topbar = $page_output->sidebar = false;
        $page_output->addInlineScript(array(
            '$("close_success").observe("click", function() { window.close(); })'
        ), true);

        $this->title =_("Message Successfully Sent");

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/basic/compose'
        ));

        $view->close = Horde::widget(array(
            'id' => 'close_success',
            'url' => new Horde_Url('#'),
            'title' => _("Close this window")
        ));
        $view->new = Horde::widget(array(
            'url' => self::url(),
            'title' => _("New Message")
        ));

        $this->output = $view->render('success');
    }

    /**
     * Convert a compose response object to header values.
     *
     * @param array $in  Compose response object.
     *
     * @return array  Header entry.
     */
    protected function _convertToHeader($in)
    {
        $out = array();

        if (isset($in['addr'])) {
            $out['to'] = strval($in['addr']['to']);
            $out['cc'] = strval($in['addr']['cc']);
            $out['bcc'] = strval($in['addr']['bcc']);
        }

        if (isset($in['subject'])) {
            $out['subject'] = $in['subject'];
        }

        return $out;
    }

}
