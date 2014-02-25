<?php
/**
 * Copyright 1999-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 1999-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Basic view message page.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 1999-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Basic_Message extends IMP_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $conf, $injector, $notification, $page_output, $prefs, $registry, $session;

        $mailbox = $this->indices->mailbox;
        $imp_imap = $mailbox->imp_imap;

        /* We know we are going to be exclusively dealing with this mailbox,
         * so select it on the IMAP server (saves some STATUS calls). Open R/W
         * to clear the RECENT flag. */
        $imp_imap->openMailbox($mailbox, Horde_Imap_Client::OPEN_READWRITE);

        /* Make sure we have a valid index. */
        $imp_mailbox = $mailbox->list_ob;
        $imp_mailbox->setIndex($this->indices);
        if (!$imp_mailbox->isValidIndex()) {
            $this->_returnToMailbox(null, 'message_missing');
            return;
        }

        $imp_flags = $injector->getInstance('IMP_Flags');
        $imp_identity = $injector->getInstance('IMP_Identity');
        $imp_message = $injector->getInstance('IMP_Message');
        $imp_ui = $injector->getInstance('IMP_Message_Ui');

        /* Run through action handlers. */
        if ($this->vars->actionID) {
            try {
                $session->getToken($this->vars->token);
            } catch (Horde_Exception $e) {
                $notification->push($e);
                $this->vars->actionID = null;
            }
        }

        $readonly = $mailbox->readonly;
        $peek = false;

        switch ($this->vars->actionID) {
        case 'blacklist':
        case 'whitelist':
            if ($this->vars->actionID == 'blacklist') {
                $injector->getInstance('IMP_Filter')->blacklistMessage($this->indices);
            } else {
                $injector->getInstance('IMP_Filter')->whitelistMessage($this->indices);
            }
            break;

        case 'delete_message':
            $imp_message->delete(
                $this->indices,
                array('mailboxob' => $imp_mailbox)
            );
            if ($prefs->getValue('mailbox_return')) {
                $this->_returnToMailbox($imp_mailbox->getIndex());
                return;
            }
            if ($imp_ui->moveAfterAction($mailbox)) {
                $imp_mailbox->setIndex(1);
            }
            break;

        case 'undelete_message':
            $imp_message->undelete($this->indices);
            break;

        case 'move_message':
        case 'copy_message':
            if (isset($this->vars->targetMbox) &&
                (!$readonly || ($this->vars->actionID == 'copy_message'))) {
                if ($this->vars->newMbox) {
                    $targetMbox = IMP_Mailbox::prefFrom($this->vars->targetMbox);
                    $newMbox = true;
                } else {
                    $targetMbox = IMP_Mailbox::formFrom($this->vars->targetMbox);
                    $newMbox = false;
                }
                $imp_message->copy(
                    $targetMbox,
                    ($this->vars->actionID == 'move_message') ? 'move' : 'copy',
                    $this->indices,
                    array(
                        'create' => $newMbox,
                        'mailboxob' => $imp_mailbox
                    )
                );
                if ($prefs->getValue('mailbox_return')) {
                    $this->_returnToMailbox($imp_mailbox->getIndex());
                    return;
                }
            }
            break;

        case 'innocent_report':
        case 'spam_report':
            $res = $injector->getInstance('IMP_Factory_Spam')->create(
                ($this->vars->actionID == 'spam_report') ? IMP_Spam::SPAM : IMP_Spam::INNOCENT
            )->report(
                $this->indices,
                array('mailbox' => $imp_mailbox)
            );
            switch ($res) {
            case 1:
                if ($imp_ui->moveAfterAction($mailbox)) {
                    $imp_mailbox->setIndex(1);
                }
                break;
            }
            if ($prefs->getValue('mailbox_return')) {
                $this->_returnToMailbox($imp_mailbox->getIndex());
                return;
            }
            break;

        case 'flag_message':
            if (!$readonly &&
                isset($this->vars->flag) &&
                count($this->indices)) {
                $peek = true;
                $flag = $imp_flags->parseFormId($this->vars->flag);
                $imp_message->flag(array(
                    ($flag['set'] ? 'add' : 'remove') => array($flag['flag'])
                ), $this->indices);
                if ($prefs->getValue('mailbox_return')) {
                    $this->_returnToMailbox($imp_mailbox->getIndex());
                    return;
                }
            }
            break;

        case 'add_address':
            try {
                $contact_link = $injector->getInstance('IMP_Contacts')->addAddress($this->vars->address, $this->vars->name);
                $notification->push(sprintf(_("Entry \"%s\" was successfully added to the address book"), $contact_link), 'horde.success', array('content.raw'));
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }
            break;

        case 'strip_all':
        case 'strip_attachment':
            if (!$readonly) {
                try {
                    $this->indices = new IMP_Indices_Mailbox(
                        $this->indices->mailbox,
                        $imp_message->stripPart(
                            $this->indices,
                            ($this->vars->actionID == 'strip_all') ? null : $this->vars->imapid,
                            array(
                                'mailboxob' => $imp_mailbox
                            )
                        )
                    );
                    $notification->push(_("Attachment successfully stripped."), 'horde.success');
                } catch (Horde_Exception $e) {
                    $notification->push($e);
                }
            }
            break;
        }

        /* We may have done processing that has taken us past the end of the
         * message array, so we will return to the mailbox. */
        if (!$imp_mailbox->isValidIndex()) {
            $this->_returnToMailbox(count($imp_mailbox));
            return;
        }

        /* Now that we are done processing, get the index and array index of
         * the current message. */
        $msg_index = $imp_mailbox[$imp_mailbox->getIndex()];

        /* Parse the message. */
        try {
            $imp_contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices($imp_mailbox));
        } catch (IMP_Exception $e) {
            $imp_mailbox->removeMsgs(true);
            $this->_returnToMailbox(null, 'message_missing');
            return;
        }

        /* Get envelope/flag/header information. */
        try {
            /* Need to fetch flags before HEADERTEXT, because SEEN flag might
             * be set before we can grab it. */
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->flags();
            $flags_ret = $imp_imap->fetch($msg_index['m'], $query, array(
                'ids' => $imp_imap->getIdsOb($msg_index['u'])
            ));

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->envelope();
            $fetch_ret = $imp_imap->fetch($msg_index['m'], $query, array(
                'ids' => $imp_imap->getIdsOb($msg_index['u'])
            ));
        } catch (IMP_Imap_Exception $e) {
            $this->_returnToMailbox(null, 'message_missing');
            return;
        }

        $envelope = $fetch_ret->first()->getEnvelope();
        $flags = $flags_ret->first()->getFlags();
        $mime_headers = $peek
            ? $imp_contents->getHeader()
            : $imp_contents->getHeaderAndMarkAsSeen();

        /* Get the title/mailbox label of the mailbox page. */
        $page_label = $mailbox->label;

        /* Generate the link to ourselves. */
        $buid = $imp_mailbox->getBuid($msg_index['m'], $msg_index['u']);
        $msgindex = $imp_mailbox->getIndex();
        $message_url = Horde::url('basic.php')->add('page', 'message');
        $token = $session->getToken();
        $self_link = self::url(array(
            'buid' => $buid,
            'mailbox' => $mailbox
        ))->add(array(
            'token' => $token,
            'start' => $msgindex
        ));

        /* Develop the list of headers to display. */
        $basic_headers = $imp_ui->basicHeaders();
        $display_headers = $msgAddresses = array();

        $format_date = $imp_ui->getLocalTime($envelope->date);
        if (!empty($format_date)) {
            $display_headers['date'] = $format_date;
        }

        /* Build From address links. */
        $display_headers['from'] = $imp_ui->buildAddressLinks($envelope->from, $self_link);

        /* Add country/flag image. */
        if (!empty($envelope->from)) {
            $contacts_img = new IMP_Contacts_Image($envelope->from[0]);
            try {
                $res = $contacts_img->getImage($contacts_img::FLAG);
                $display_headers['from'] .= '&nbsp;' . Horde_Themes_Image::tag(
                    $res['url'],
                    array(
                        'alt' => $res['desc'],
                        'fullsrc' => true
                    )
                );
            } catch (IMP_Exception $e) {}
        }

        /* Look for Face information. */
        if ($face = $mime_headers->getValue('face')) {
            $display_headers['from'] .= '&nbsp;<img src="' .
                Horde_Url_Data::create('image/png', base64_decode($face))
                . '">';
        }

        /* Build To/Cc/Bcc links. */
        foreach (array('to', 'cc', 'bcc') as $val) {
            $msgAddresses[] = $mime_headers->getValue($val);
            if (($val == 'to') || count($envelope->$val)) {
                $display_headers[$val] = $imp_ui->buildAddressLinks($envelope->$val, $self_link);
            }
        }

        /* Process the subject now. */
        if ($subject = $mime_headers->getValue('subject')) {
            $this->title = sprintf(_("%s: %s"), $page_label, $subject);
            $shortsub = Horde_String::truncate($subject, 100);
        } else {
            $shortsub = _("[No Subject]");
            $this->title = sprintf(_("%s: %s"), $page_label, $shortsub);
        }

        /* See if the priority has been set. */
        switch ($injector->getInstance('IMP_Mime_Headers')->getPriority($mime_headers)) {
        case 'high':
            $basic_headers['priority'] = _("Priority");
            $display_headers['priority'] = '<div class="iconImg msgflags flagHighpriority" title="' . htmlspecialchars(_("High Priority")) . '"></div>&nbsp;' . _("High");
            break;

        case 'low':
            $basic_headers['priority'] = _("Priority");
            $display_headers['priority'] = '<div class="iconImg msgflags flagLowpriority" title="' . htmlspecialchars(_("Low Priority")) . '"></div>&nbsp;' . _("Low");
            break;
        }

        /* Build Reply-To address link. */
        if (!empty($envelope->reply_to) &&
            ($envelope->from[0]->bare_address != $envelope->reply_to[0]->bare_address)  &&
            ($reply_to = $imp_ui->buildAddressLinks($envelope->reply_to, $self_link))) {
            $display_headers['reply-to'] = $reply_to;
        }

        /* Determine if all/list/user-requested headers needed. */
        $all_headers = $this->vars->show_all_headers;
        $user_hdrs = $imp_ui->getUserHeaders();

        /* Check for the presence of mailing list information. */
        $list_info = $imp_ui->getListInformation($mime_headers);

        /* Display all headers or, optionally, the user-specified headers for
         * the current identity. */
        $full_headers = array();
        if ($all_headers) {
            $header_array = $mime_headers->toArray();
            foreach ($header_array as $head => $val) {
                $lc_head = strtolower($head);

                /* Skip the header if we have already dealt with it. */
                if (!isset($display_headers[$lc_head]) &&
                    (!in_array($lc_head, array('importance', 'x-priority')) ||
                    !isset($display_headers['priority']))) {
                    $full_headers[$lc_head] = $val;
                }
            }
        } elseif (!empty($user_hdrs)) {
            foreach ($user_hdrs as $user_hdr) {
                $user_val = $mime_headers->getValue($user_hdr);
                if (!empty($user_val)) {
                    $full_headers[$user_hdr] = $user_val;
                }
            }
        }
        ksort($full_headers);

        /* For the self URL link, we can't trust the index in the query string
         * as it may have changed if we deleted/copied/moved messages. We may
         * need other stuff in the query string, so we need to do an
         * add/remove of uid info. */
        $selfURL = $mailbox->url(Horde::selfUrlParams()->remove(array('actionID')), $buid)->add('token', $token);
        $headersURL = $selfURL->copy()->remove(array('show_all_headers'));

        /* Generate previous/next links. */
        $prev_msg = $imp_mailbox[$imp_mailbox->getIndex() - 1];
        if ($prev_msg) {
            $prev_url = self::url(array(
                'buid' => $imp_mailbox->getBuid($prev_msg['m'], $prev_msg['u']),
                'mailbox' => $mailbox
            ))->setRaw(true);
            $page_output->addLinkTag(array(
                'href' => $prev_url,
                'id' => 'prev',
                'rel' => 'Previous',
                'type' => null
            ));
        } else {
            $prev_url = null;
        }

        $next_msg = $imp_mailbox[$imp_mailbox->getIndex() + 1];
        if ($next_msg) {
            $next_url = self::url(array(
                'buid' => $imp_mailbox->getBuid($next_msg['m'], $next_msg['u']),
                'mailbox' => $mailbox
            ))->setRaw(true);
            $page_output->addLinkTag(array(
                'href' => $next_url,
                'id' => 'next',
                'rel' => 'Next',
                'type' => null
            ));
        } else {
            $next_url = null;
        }

        /* Generate the mailbox link. */
        $mailbox_url = $mailbox->url('mailbox')->add('start', $msgindex);

        /* Everything below here is related to preparing the output. */

        $js_vars = array(
            'ImpMessage.text' => array(
                'innocent_report' => _("Are you sure you wish to report this message as innocent?"),
                'moveconfirm' => _("Are you sure you want to move the message(s)? (Some message information might get lost, like message headers, text formatting or attachments!)"),
                'newmbox' => _("You are copying/moving to a new mailbox.") . "\n" . _("Please enter a name for the new mailbox:") . "\n",
                'spam_report' => _("Are you sure you wish to report this message as spam?"),
                'target_mbox' => _("You must select a target mailbox first.")
            )
        );

        /* Set the status information of the message. */
        $msgAddresses[] = $mime_headers->getValue('from');
        $identity = $match_identity = $imp_identity->getMatchingIdentity($msgAddresses);
        if (is_null($identity)) {
            $identity = $imp_identity->getDefault();
        }

        $flag_parse = $imp_flags->parse(array(
            'flags' => $flags,
            'personal' => $match_identity
        ));

        $status = '';
        foreach ($flag_parse as $val) {
            if ($val instanceof IMP_Flag_User) {
                $status .= '<span class="' . $val->css . '" style="' . ($val->bgdefault ? '' : 'background:' . htmlspecialchars($val->bgcolor) . ';') . 'color:' . htmlspecialchars($val->fgcolor) . '">' . htmlspecialchars($val->label) . '</span>';
            } else {
                $status .= $val->span;
            }
        }

        /* If this is a search mailbox, display a link to the parent mailbox
         * of the message in the header. */
        $h_page_label = htmlspecialchars($page_label);
        $header_label = $h_page_label;
        if ($mailbox->search) {
            $header_label .= ' [' . $msg_index['m']->url('mailbox')->link() . $msg_index['m']->display_html . '</a>]';
        }

        /* Prepare the navbar top template. */
        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/basic/message'
        ));
        $view->addHelper('FormTag');
        $view->addHelper('Tag');

        $t_view = clone $view;
        $t_view->buid = $buid;
        $t_view->message_url = $message_url;
        $t_view->mailbox = $mailbox->form_to;
        $t_view->start = $msgindex;
        $t_view->token = $token;

        /* Prepare the navbar navigate template. */
        $n_view = clone $view;
        $n_view->readonly = $readonly;
        $n_view->id = 1;

        if ($mailbox->access_flags) {
            $n_view->mailbox = $mailbox->form_to;

            $args = array(
                'imap' => true,
                'mailbox' => $mailbox
            );

            $form_set = $form_unset = array();
            foreach ($imp_flags->getList($args) as $val) {
                if ($val->canset) {
                    $form_set[] = array(
                        'f' => $val->form_set,
                        'l' => $val->label
                    );
                    $form_unset[] = array(
                        'f' => $val->form_unset,
                        'l' => $val->label
                    );
                }
            }

            $n_view->flaglist_set = $form_set;
            $n_view->flaglist_unset = $form_unset;
        }

        if ($imp_imap->access(IMP_Imap::ACCESS_FOLDERS)) {
            $n_view->move = Horde::widget(array(
                'url' => '#',
                'class' => 'moveAction',
                'title' => _("Move"),
                'nocheck' => true
            ));
            $n_view->copy = Horde::widget(array(
                'url' => '#',
                'class' => 'copyAction',
                'title' => _("Copy"),
                'nocheck' => true
            ));
            $n_view->options = new IMP_Ftree_Select(array(
                'heading' => _("This message to"),
                'inc_tasklists' => true,
                'inc_notepads' => true,
                'iterator' => new IMP_Ftree_IteratorFilter_Nonimap(IMP_Ftree_IteratorFilter::create(IMP_Ftree_IteratorFilter::UNSUB_PREF)),
                'new_mbox' => true
            ));
        }

        $n_view->back_to = Horde::widget(array(
            'url' => $mailbox_url,
            'title' => sprintf(_("Bac_k to %s"), $h_page_label),
            'nocheck' => true
        ));

        if ($prev_url) {
            $n_view->prev = Horde::link($prev_url, _("Previous Message"));
            $n_view->prev_img = 'navleftImg';
        } else {
            $n_view->prev_img = 'navleftgreyImg';
        }

        if ($next_url) {
            $n_view->next = Horde::link($next_url, _("Next Message"));
            $n_view->next_img = 'navrightImg';
        } else {
            $n_view->next_img = 'navrightgreyImg';
        }

        /* Prepare the navbar actions template. */
        $a_view = clone $view;
        $compose_params = array(
            'buid' => $buid,
            'identity' => $identity,
            'mailbox' => IMP_Mailbox::formTo($mailbox),
        );
        if (!$prefs->getValue('compose_popup')) {
            $compose_params['start'] = $msgindex;
        }

        if ($msg_index['m']->access_deletemsgs) {
            if (in_array(Horde_Imap_Client::FLAG_DELETED, $flags)) {
                $a_view->delete = Horde::widget(array(
                    'url' => $self_link->copy()->add('actionID', 'undelete_message'),
                    'title' => _("Undelete"),
                    'nocheck' => true
                ));
            } else {
                $a_view->delete = Horde::widget(array(
                    'url' => $self_link->copy()->add('actionID', 'delete_message'),
                    'title' => _("_Delete"),
                    'nocheck' => true
                ));
                if (!$msg_index['m']->is_imap) {
                    $js_vars['ImpMessage.pop3delete'] = _("Are you sure you want to PERMANENTLY delete these messages?");
                }
            }
        }

        $disable_compose = !IMP_Compose::canCompose();

        if (!$disable_compose) {
            $clink_ob = new IMP_Compose_Link();
            $clink = $clink_ob->link()->add($compose_params);

            $a_view->reply = Horde::widget(array(
                'url' => $clink->add(array('actionID' => 'reply_auto')),
                'class' => 'horde-hasmenu',
                'title' => _("_Reply"),
                'nocheck' => true
            ));
            $a_view->reply_sender = Horde::widget(array(
                'url' => $clink->add(array('actionID' => 'reply')),
                'title' => _("To Sender"),
                'nocheck' => true
            ));

            if ($list_info['reply_list']) {
                $a_view->reply_list = Horde::widget(array(
                    'url' => $clink->add(array('actionID' => 'reply_list')),
                    'title' => _("To _List"),
                    'nocheck' => true
                ));
            }

            $addr_ob = clone $envelope->to;
            $addr_ob->add($envelope->cc);
            $addr_ob->setIteratorFilter(0, $imp_identity->getAllFromAddresses());

            if (count($addr_ob)) {
                $a_view->show_reply_all = Horde::widget(array(
                    'url' => $clink->add(array('actionID' => 'reply_all')),
                    'title' => _("To _All"),
                    'nocheck' => true
                ));
            }

            $fwd_locked = $prefs->isLocked('forward_default');
            $a_view->forward = Horde::widget(array(
                'url' => $clink->add(array('actionID' => 'forward_auto')),
                'class' => $fwd_locked ? '' : ' horde-hasmenu',
                'title' => _("Fo_rward"),
                'nocheck' => true
            ));
            if (!$fwd_locked) {
                $a_view->forward_attach = Horde::widget(array(
                    'url' => $clink->add(array('actionID' => 'forward_attach')),
                    'title' => _("As Attachment"),
                    'nocheck' => true
                ));
                $a_view->forward_body = Horde::widget(array(
                    'url' => $clink->add(array('actionID' => 'forward_body')),
                    'title' => _("In Body Text"),
                    'nocheck' => true
                ));
                $a_view->forward_both = Horde::widget(array(
                    'url' => $clink->add(array('actionID' => 'forward_both')),
                    'title' => _("Attachment and Body Text"),
                    'nocheck' => true
                ));
            }

            $a_view->redirect = Horde::widget(array(
                'url' => $clink->add(array('actionID' => 'redirect_compose')),
                'title' => _("Redirec_t"),
                'nocheck' => true
            ));

            $a_view->editasnew = Horde::widget(array(
                'url' => $clink->add(array('actionID' => 'editasnew')),
                'title' => _("Edit as New"),
                'nocheck' => true
            ));
        }

        if ($mailbox->access_sortthread) {
            $a_view->show_thread = Horde::widget(array(
                'url' => $mailbox->url(IMP_Basic_Thread::url(), $buid)->add(array('start' => $msgindex)),
                'title' => _("_View Thread"),
                'nocheck' => true
            ));
        }

        if (!$readonly && $registry->hasMethod('mail/blacklistFrom')) {
            $a_view->blacklist = Horde::widget(array(
                'url' => $self_link->copy()->add('actionID', 'blacklist'),
                'title' => _("_Blacklist"),
                'nocheck' => true
            ));
        }

        if (!$readonly && $registry->hasMethod('mail/whitelistFrom')) {
            $a_view->whitelist = Horde::widget(array(
                'url' => $self_link->copy()->add('actionID', 'whitelist'),
                'title' => _("_Whitelist"),
                'nocheck' => true
            ));
        }

        if (!empty($conf['user']['allow_view_source'])) {
            $a_view->view_source = $imp_contents->linkViewJS($imp_contents->getMIMEMessage(), 'view_source', _("_Message Source"), array(
                'css' => '',
                'jstext' => _("Message Source"),
                'widget' => true
            ));
        }

        if (!$disable_compose &&
            (in_array(Horde_Imap_Client::FLAG_DRAFT, $flags) || $msg_index['m']->drafts)) {
            $a_view->resume = Horde::widget(array(
                'url' => $clink->add(array('actionID' => 'draft')),
                'title' => _("Resume"),
                'nocheck' => true
            ));
        }

        $imp_params = $mailbox->urlParams($buid);
        $a_view->save_as = Horde::widget(array(
            'url' => IMP_Contents_View::downloadUrl($subject, array_merge(array('actionID' => 'save_message'), $imp_params)),
            'title' => _("Sa_ve as"),
            'nocheck' => true
        ));

        if ($msg_index['m']->spam_show) {
            $a_view->spam = Horde::widget(array(
                'url' => '#',
                'class' => 'spamAction',
                'title' => _("Report as Spam"),
                'nocheck' => true
            ));
        }

        if ($msg_index['m']->innocent_show) {
            $a_view->innocent = Horde::widget(array(
                'url' => '#',
                'class' => 'innocentAction',
                'title' => _("Report as Innocent"),
                'nocheck' => true
            ));
        }

        if (!$disable_compose) {
            $a_view->redirect = Horde::widget(array(
                'url' => $clink->add(array('actionID' => 'redirect_compose')),
                'title' => _("Redirec_t"),
                'nocheck' => true
            ));
        }

        $a_view->headers = Horde::widget(array(
            'url' => '#',
            'class' => 'horde-hasmenu',
            'title' => _("Headers"),
            'nocheck' => true
        ));
        if ($all_headers) {
            $a_view->common_headers = Horde::widget(array(
                'url' => $headersURL,
                'title' => _("Show Common Headers"),
                'nocheck' => true
            ));
        }
        if (!$all_headers) {
            $a_view->all_headers = Horde::widget(array(
                'url' => $headersURL->copy()->add('show_all_headers', 1),
                'title' => _("Show All Headers"),
                'nocheck' => true
            ));
        }
        if ($list_info['exists']) {
            $a_view->list_headers = Horde::widget(array(
                'onclick' => Horde::popupJs(IMP_Basic_Listinfo::url(array(
                                 'buid' => $buid,
                                 'mailbox' => $mailbox
                             )), array(
                                 'urlencode' => true
                             )),
                'title' => _("Show Mailing List Information"),
                'nocheck' => true
            ));
        }

        $hdrs = array();

        /* Prepare the main message template. */
        if (!$all_headers) {
            foreach ($display_headers as $head => $val) {
                $hdrs[] = array(
                    'name' => $basic_headers[$head],
                    'val' => $val
                );
            }
        }
        foreach ($full_headers as $head => $val) {
            if (is_array($val)) {
                $hdrs[] = array(
                    'name' => $head,
                    'val' => '<ul style="margin:0;padding-left:15px"><li>' . implode("</li>\n<li>", array_map('htmlspecialchars', $val)) . '</li></ul>'
                );
            } else {
                $hdrs[] = array(
                    'name' => $head,
                    'val' => htmlspecialchars($val)
                );
            }
        }

        /* Determine the fields that will appear in the MIME info entries. */
        $part_info = $part_info_display = array('icon', 'description', 'size');
        $part_info_action = array('download', 'download_zip', 'img_save', 'strip');
        $part_info_bodyonly = array('print');

        $show_parts = isset($this->vars->show_parts)
            ? $this->vars->show_parts
            : $prefs->getValue('parts_display');

        $part_info_display = array_merge($part_info_display, $part_info_action, $part_info_bodyonly);
        $contents_mask = IMP_Contents::SUMMARY_BYTES |
            IMP_Contents::SUMMARY_SIZE |
            IMP_Contents::SUMMARY_ICON |
            IMP_Contents::SUMMARY_DESCRIP_LINK |
            IMP_Contents::SUMMARY_DOWNLOAD |
            IMP_Contents::SUMMARY_DOWNLOAD_ZIP |
            IMP_Contents::SUMMARY_IMAGE_SAVE |
            IMP_Contents::SUMMARY_PRINT;

        /* Do MDN processing now. */
        $mdntext = $imp_ui->MDNCheck($msg_index['m'], $buid, $mime_headers, $this->vars->mdn_confirm)
            ? strval(new IMP_Mime_Status(array(
                _("The sender of this message is requesting a notification from you when you have read this message."),
                sprintf(_("Click %s to send the notification message."), Horde::link($selfURL->copy()->add('mdn_confirm', 1)) . _("HERE") . '</a>')
            )))
            : '';

        /* Build body text. This needs to be done before we build the
         * attachment list that lives in the header. */
        $inlineout = $imp_contents->getInlineOutput(array(
            'mask' => $contents_mask,
            'part_info_display' => $part_info_display,
            'show_parts' => $show_parts
        ));

        /* Build the Attachments menu. */
        $show_atc = false;
        switch ($show_parts) {
        case 'atc':
            $a_view->show_parts_all = Horde::widget(array(
                'url' => $headersURL->copy()->add(array('show_parts' => 'all')),
                'title' => _("Show All Parts"),
                'nocheck' => true
            ));
            $show_atc = true;
            break;

        case 'all':
            if ($prefs->getValue('strip_attachments')) {
                $js_vars['ImpMessage.text']['stripwarn'] = _("Are you sure you wish to PERMANENTLY delete this attachment?");
            }
            break;
        }

        if (count($inlineout['atc_parts']) > 2) {
            $a_view->download_all = Horde::widget(array(
                'url' => $imp_contents->urlView($imp_contents->getMIMEMessage(), 'download_all'),
                'title' => _("Download All Attachments (in .zip file)"),
                'nocheck' => true
            ));
            if ($prefs->getValue('strip_attachments')) {
                $a_view->strip_all = Horde::widget(array(
                    'url' => Horde::selfUrlParams()->add(array(
                        'actionID' => 'strip_all',
                        'token' => $token
                    )),
                    'class' => 'stripAllAtc',
                    'title' => _("Strip All Attachments"),
                    'nocheck' => true
                ));
                $js_vars['ImpMessage.text']['stripallwarn'] = _("Are you sure you want to PERMANENTLY delete all attachments?");
            }

            $show_atc = true;
        }

        if ($show_atc) {
            $a_view->atc = Horde::widget(array(
                'url' => '#',
                'class' => 'horde-hasmenu',
                'title' => _("Attachments"),
                'nocheck' => true
            ));
        }

        /* Show attachment information in headers? 'atc_parts' will be empty if
         * 'parts_display' pref is 'none'. */
        if (!empty($inlineout['atc_parts'])) {
            if ($show_parts == 'all') {
                $val = $imp_contents->getTree()->getTree(true);
            } else {
                $tmp = array();

                foreach ($inlineout['atc_parts'] as $id) {
                    $summary = $imp_contents->getSummary($id, $contents_mask);

                    $tmp[] = '<tr>';
                    foreach ($part_info as $val) {
                        $tmp[] = '<td>' . $summary[$val] . '</td>';
                    }
                    $tmp[] = '<td>';
                    foreach ($part_info_action as $val) {
                        $tmp[] = $summary[$val];
                    }
                    $tmp[] = '</td></tr>';
                }

                $val = '<table>' . implode('', $tmp) . '</table>';
            }

            $hdrs[] = array(
                'class' => 'msgheaderParts',
                'name' => ($show_parts == 'all') ? _("Parts") : _("Attachments"),
                'val' => $val
            );
        }

        $m_view = clone $view;
        $m_view->label = $shortsub;
        $m_view->headers = $hdrs;
        $m_view->msgtext = $mdntext . $inlineout['msgtext'];

        $subinfo = new IMP_View_Subinfo(array('mailbox' => $mailbox));
        $subinfo->label = $header_label;
        $subinfo->value = sprintf(_("(%d of %d)"), $msgindex, count($imp_mailbox)) . $status;
        $injector->getInstance('Horde_View_Topbar')->subinfo = $subinfo->render();

        /* Output message page now. */
        $page_output->addInlineJsVars($js_vars, array('top' => true));
        $page_output->addInlineScript($inlineout['js_onload'], true);
        $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
        $page_output->addScriptFile('hordecore.js', 'horde');
        $page_output->addScriptFile('message.js');
        $page_output->addScriptFile('stripe.js', 'horde');
        $page_output->addScriptPackage('IMP_Script_Package_Imp');

        if (!empty($conf['tasklist']['use_notepad']) ||
            !empty($conf['tasklist']['use_tasklist'])) {
            $page_output->addScriptPackage('Horde_Core_Script_Package_Dialog');
        }

        $page_output->noDnsPrefetch();

        if ($maillog = $injector->getInstance('IMP_Maillog')) {
            Horde::startBuffer();
            $maillog->displayLog($envelope->message_id);
            $this->output = Horde::endBuffer();
        }

        $this->output .= $t_view->render('navbar_top') .
            $n_view->render('navbar_navigate') .
            $a_view->render('navbar_actions') .
            $m_view->render('message') .
            $a_view->render('navbar_actions');

        $n_view->id = 2;
        $n_view->isbottom = true;
        $this->output .= $n_view->render('navbar_navigate');
    }

    /**
     * @param array $opts  Options:
     *   - buid: (string) BUID of message.
     *   - mailbox: (string) Mailbox of message.
     */
    static public function url(array $opts = array())
    {
        return IMP_Mailbox::get($opts['mailbox'])->url('basic')->add(array(
            'buid' => $opts['buid'],
            'page' => 'message'
        ));
    }

    /**
     */
    protected function _returnToMailbox($start = null, $actID = null)
    {
        $this->vars->actionID = $actID;
        $this->vars->start = $start;

        $ob = new IMP_Basic_Mailbox($this->vars);
        $this->output = $ob->output;
    }

}
