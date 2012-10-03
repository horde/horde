<?php
/**
 * Compose page for minimal view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl21 GPL
 * @package  IMP
 */
class IMP_Minimal_Compose extends IMP_Minimal_Base
{
    /**
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
     */
    protected function _init()
    {
        global $injector, $notification, $prefs, $session;

        /* The message text and headers. */
        $expand = array();
        $header = array(
            'to' => '',
            'cc' => '',
            'bcc' => ''
        );
        $msg = '';
        $this->title = _("Compose Message");

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
        if (!$prefs->isLocked('default_identity') &&
            isset($this->vars->identity)) {
            $identity->setDefault($this->vars->identity);
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
        $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($this->vars->composeCache);
        $imp_ui = new IMP_Ui_Compose();

        foreach (array_keys($display_hdrs) as $val) {
            $header[$val] = $this->vars->$val;

            /* If we are reloading the screen, check for expand matches. */
            if ($this->vars->composeCache) {
                $expanded = array();
                for ($i = 0; $i < 5; ++$i) {
                    if ($tmp = $this->vars->get($val . '_expand_' . $i)) {
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
            !$imp_compose->addFilesFromUpload('upload_', $this->vars->a == _("Expand Names")) &&
            ($this->vars->a != _("Expand Names"))) {
            $this->vars->a = null;
        }

        /* Run through the action handlers. */
        switch ($this->vars->a) {
        // 'd' = draft
        // 'en' = edit as new
        // 't' = template
        case 'd':
        case 'en':
        case 't':
            try {
                $indices_ob = IMP::mailbox(true)->getIndicesOb(IMP::uid());

                switch ($this->vars->a) {
                case 'd':
                    $result = $imp_compose->resumeDraft($indices_ob, array(
                        'format' => 'text'
                    ));
                    break;

                case 'en':
                    $result = $imp_compose->editAsNew($indices_ob, array(
                        'format' => 'text'
                    ));
                    break;

                case 't':
                    $result = $imp_compose->useTemplate($indices_ob, array(
                        'format' => 'text'
                    ));
                    break;
                }

                $msg = $result['body'];
                $header = array_merge($header, $result['headers']);
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
                if (($val == 'to') || ($this->vars->action != 'rc')) {
                    $res = $imp_ui->expandAddresses($header[$val]);
                    if (is_string($res)) {
                        $header[$val] = $res;
                    } else {
                        $header[$val] = $res[0];
                        $expand[$val] = array_slice($res, 1);
                    }
                }
            }

            if (isset($this->vars->action)) {
                $this->vars->a = $this->vars->action;
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

            $reply_msg = $imp_compose->replyMessage($actions[$this->vars->a], $imp_contents, array(
                'format' => 'text',
                'to' => $header['to']
            ));
            $header = $reply_msg['headers'];

            $notification->push(_("Reply text will be automatically appended to your outgoing message."), 'horde.message');
            $this->title = _("Reply");
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
            $this->title = _("Forward");
            break;

        // 'rc' = redirect compose
        case 'rc':
            $imp_compose->redirectMessage($imp_ui->getIndices());
            $this->title = _("Redirect");
            break;

        case _("Redirect"):
            try {
                $num_msgs = $imp_compose->sendRedirectMessage($header['to']);
                $imp_compose->destroy('send');

                $notification->push(ngettext("Message redirected successfully.", "Messages redirected successfully.", count($num_msgs)), 'horde.success');
                IMP_Minimal_Mailbox::url()->redirect();
            } catch (Horde_Exception $e) {
                $this->vars->a = 'rc';
                $notification->push($e);
            }
            break;

        case _("Save Draft"):
        case _("Send"):
            switch ($this->vars->a) {
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

            $message = strval($this->vars->message);
            $f_to = $header['to'];
            $old_header = $header;
            $header = array();

            switch ($imp_compose->replyType(true)) {
            case IMP_Compose::REPLY:
                $reply_msg = $imp_compose->replyMessage(IMP_Compose::REPLY_SENDER, $imp_compose->getContentsOb(), array(
                    'to' => $f_to
                ));
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
                $header['from'] = strval($identity->getFromLine(null, $this->vars->from));
            } catch (Horde_Exception $e) {
                $header['from'] = '';
            }
            $header['replyto'] = $identity->getValue('replyto_addr');
            $header['subject'] = strval($this->vars->subject);

            foreach (array_keys($display_hdrs) as $val) {
                $header[$val] = $old_header[$val];
            }

            switch ($this->vars->a) {
            case _("Save Draft"):
                try {
                    $notification->push($imp_compose->saveDraft($header, $message), 'horde.success');
                    if ($prefs->getValue('close_draft')) {
                        $imp_compose->destroy('save_draft');
                        IMP_Minimal_Mailbox::url()->redirect();
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
                    $this->redirect('mailbox')->redirect();
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
            IMP_Minimal_Mailbox::url()->redirect();
            exit;
        }

        /* Grab any data that we were supplied with. */
        if (empty($msg)) {
            $msg = strval($this->vars->message);
        }
        if (empty($header['subject'])) {
            $header['subject'] = strval($this->vars->subject);
        }

        $this->view->cacheid = $imp_compose->getCacheId();
        $this->view->menu = $this->getMenu('compose');
        $this->view->url = self::url();

        switch ($this->vars->a) {
        case 'rc':
            $this->_pages[] = 'redirect';
            $this->_pages[] = 'menu';
            unset($display_hdrs['cc'], $display_hdrs['bcc']);
            break;

        default:
            $this->_pages[] = 'compose';
            $this->_pages[] = 'menu';

            $this->view->compose_enable = !$compose_disable;
            $this->view->msg = $msg;
            $this->view->save_draft = ($injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS) && !$readonly_drafts);
            $this->view->subject = $header['subject'];

            $select_list = $identity->getSelectList();
            $default_identity = $identity->getDefault();

            if ($prefs->isLocked('default_identity')) {
                $select_list = array(
                    $default_identity => $select_list[$default_identity]
                );
            }

            $tmp = array();
            foreach ($select_list as $key => $val) {
                $tmp[] = array(
                    'key' => $key,
                    'sel' => ($key == $default_identity),
                    'val' => $val
                );
            }
            $this->view->identities = $tmp;

            /* Activate advanced compose attachments UI? */
            if ($session->get('imp', 'file_upload')) {
                try {
                    if (Horde::callHook('mimp_advanced', array('compose_attach'), 'imp')) {
                        $this->view->attach = true;
                        if (count($imp_compose)) {
                            $imp_ui_mbox = new IMP_Ui_Mailbox();
                            $this->view->attach_name = $atc_list[0]['part']->getName();
                            $this->view->attach_type = $atc_list[0]['part']->getType();
                            $this->view->attach_size = $imp_ui_mbox->getSize($atc_list[0]['part']->getBytes());
                        }
                    }
                } catch (Horde_Exception_HookNotSet $e) {}
            }

            $this->title = _("Message Composition");
        }

        $hdrs = array();
        foreach ($display_hdrs as $key => $val) {
            $tmp = array(
                'key' => $key,
                'label' => $val,
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
                        'val' => $val2
                    );
                }
            }

            $hdrs[] = $tmp;
        }

        $this->view->hdrs = $hdrs;
        $this->view->title = $this->title;
    }

    /**
     */
    static public function url(array $opts = array())
    {
        return Horde::url('minimal.php')->add('page', 'compose')->unique();
    }

}
