<?php
/**
 * Defines common (i.e. used in dynamic and smartmobile views) AJAX actions
 * used in IMP.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ajax_Application_Handler_Common extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Poll mailboxes.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed() and
     * IMP_Ajax_Application#viewPortData().
     *
     * @return boolean  True.
     */
    public function poll()
    {
        /* Actual polling handled by the global 'poll' handler. Still need
         * separate poll action because there are other tasks done when
         * specifically requesting a poll. */

        $this->_base->queue->quota();

        if ($this->_base->mbox && $this->_base->changed()) {
            $this->_base->addTask('viewport', $this->_base->viewPortData(true));
        }

        return true;
    }

    /**
     * AJAX action: Output ViewPort data.
     *
     * See the list of variables needed for IMP_Ajax_Appication#changed() and
     * IMP_Ajax_Application#viewPortData().
     * Additional variables used (contained in 'viewport' parameter):
     *   - checkcache: (integer) If 1, only send data if cache has been
     *                 invalidated.
     *   - rangeslice: (string) Range slice. See js/viewport.js.
     *   - requestid: (string) Request ID. See js/viewport.js.
     *   - sortby: (integer) The Horde_Imap_Client sort constant.
     *   - sortdir: (integer) 0 for ascending, 1 for descending.
     *
     * @return boolean  True on success, false on failure.
     */
    public function viewPort()
    {
        if (!$this->_base->mbox) {
            return false;
        }

        $vp_vars = $this->vars->viewport;

        /* Change sort preferences if necessary. */
        if (isset($vp_vars->sortby) || isset($vp_vars->sortdir)) {
            $this->_base->mbox->setSort(
                isset($vp_vars->sortby) ? $vp_vars->sortby : null,
                isset($vp_vars->sortdir) ? $vp_vars->sortdir : null
            );
        }

        /* Toggle hide deleted preference if necessary. */
        if (isset($vp_vars->delhide)) {
            $this->_base->mbox->setHideDeletedMsgs($vp_vars->delhide);
        }

        $changed = $this->_base->changed(true);

        if (is_null($changed)) {
            $vp = $GLOBALS['injector']->getInstance('IMP_Ajax_Application_ListMessages')->getBaseOb($this->_base->mbox);

            if (isset($vp_vars->requestid)) {
                $vp->requestid = intval($vp_vars->requestid);
            }

            $this->_base->addTask('viewport', $vp);
            return true;
        }

        $this->_base->queue->poll($this->_base->mbox);

        if ($changed || $vp_vars->rangeslice || !$vp_vars->checkcache) {
            /* Ticket #7422: Listing messages may be a long-running operation
             * so close the session while we are doing it to prevent
             * deadlocks. */
            $GLOBALS['session']->close();

            $vp = $this->_base->viewPortData($changed);

            /* Reopen the session. */
            $GLOBALS['session']->start();

            if (isset($vp_vars->delhide)) {
                $vp->metadata_reset = 1;
            }

            $this->_base->addTask('viewport', $vp);
            return true;
        }

        return false;
    }

    /**
     * AJAX action: Move messages.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed(),
     * IMP_Ajax_Application#deleteMsgs(), and
     * IMP_Ajax_Application#checkUidvalidity(). Additional variables used:
     *   - mboxto: (string) Mailbox to move the message to (base64url
     *             encoded).
     *   - uid: (string) Indices of the messages to move (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function moveMessages()
    {
        $indices = new IMP_Indices_Form($this->vars->uid);
        if ((!$this->vars->mboxto && !$this->vars->newmbox) ||
            !count($indices)) {
            return false;
        }

        $change = $this->_base->changed(true);

        if (is_null($change)) {
            return false;
        }

        if ($this->vars->newmbox) {
            $mbox = IMP_Mailbox::prefFrom($this->vars->newmbox);
            $newMbox = true;
        } else {
            $mbox = IMP_Mailbox::formFrom($this->vars->mboxto);
            $newMbox = false;
        }

        $result = $GLOBALS['injector']
            ->getInstance('IMP_Message')
            ->copy($mbox, 'move', $indices, array('create' => $newMbox));

        if ($result) {
            $this->_base->deleteMsgs($indices, $change, true);
            $this->_base->queue->poll($mbox);
            return true;
        }

        $this->_base->checkUidvalidity();

        return false;
    }

    /**
     * AJAX action: Copy messages.
     *
     * See the list of variables needed for
     * IMP_Ajax_Application#_checkUidvalidity(). Additional variables used:
     *   - mboxto: (string) Mailbox to copy the message to (base64url
     *             encoded).
     *   - uid: (string) Indices of the messages to copy (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function copyMessages()
    {
        $indices = new IMP_Indices_Form($this->vars->uid);
        if ((!$this->vars->mboxto && !$this->vars->newmbox) ||
            !count($indices)) {
            return false;
        }

        if ($this->vars->newmbox) {
            $mbox = IMP_Mailbox::prefFrom($this->vars->newmbox);
            $newMbox = true;
        } else {
            $mbox = IMP_Mailbox::formFrom($this->vars->mboxto);
            $newMbox = false;
        }

        $result = $GLOBALS['injector']
            ->getInstance('IMP_Message')
            ->copy($mbox, 'copy', $indices, array('create' => $newMbox));

        if ($result) {
            $this->_base->queue->poll($mbox);
            return true;
        }

        $this->_base->checkUidvalidity();

        return false;
    }

    /**
     * AJAX action: Delete messages.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed(),
     * IMP_Ajax_Application#deleteMsgs(), and
     * IMP_Ajax_Application@checkUidvalidity(). Additional variables used:
     *   - uid: (string) Indices of the messages to delete (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function deleteMessages()
    {
        $indices = new IMP_Indices_Form($this->vars->uid);
        if (!count($indices)) {
            return false;
        }

        $change = $this->_base->changed(true);

        if ($GLOBALS['injector']->getInstance('IMP_Message')->delete($indices)) {
            $this->_base->deleteMsgs($indices, $change);
            return true;
        }

        if (!is_null($change)) {
            $this->_base->checkUidvalidity();
        }

        return false;
    }

    /**
     * AJAX action: Report message as [not]spam.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed(),
     * IMP_Ajax_Application#deleteMsgs(), and
     * IMP_Ajax_Application#checkUidvalidity(). Additional variables used:
     *   - spam: (integer) 1 to mark as spam, 0 to mark as innocent.
     *   - uid: (string) Indices of the messages to report (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success.
     */
    public function reportSpam()
    {
        $change = $this->_base->changed(true);
        $indices = new IMP_Indices_Form($this->vars->uid);

        if (IMP_Spam::reportSpam($indices, $this->vars->spam ? 'spam' : 'notspam')) {
            $this->_base->deleteMsgs($indices, $change);
            return true;
        }

        if (!is_null($change)) {
            $this->_base->checkUidvalidity();
        }

        return false;
    }

    /**
     * AJAX action: Get reply data.
     *
     * See the list of variables needed for
     * IMP_Ajax_Application#checkUidvalidity(). Additional variables used:
     *   - headeronly: (boolean) Only return header information (DEFAULT:
     *                 false).
     *   - format: (string) The format to force to ('text' or 'html')
     *             (DEFAULT: Auto-determined).
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) See IMP_Compose::replyMessage().
     *   - uid: (string) Indices of the messages to reply to (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - body: (string) The body text of the message.
     *   - format: (string) Either 'text' or 'html'.
     *   - header: (array) The headers of the message.
     *   - identity: (integer) The identity ID to use for this message.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - opts: (array) Additional options needed for DimpCompose.fillForm().
     *   - type: (string) The input 'type' value.
     */
    public function getReplyData()
    {
        /* Can't open session read-only since we need to store the message
         * cache id. */

        try {
            $compose = $this->_base->initCompose();

            $reply_msg = $compose->compose->replyMessage($compose->ajax->reply_map[$this->vars->type], $compose->contents, array(
                'format' => $this->vars->format
            ));

            if ($this->vars->headeronly) {
                $result = $compose->ajax->getBaseResponse();
                $result->header = $reply_msg['headers'];
            } else {
                $result = $compose->ajax->getResponse($reply_msg);
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $this->_base->checkUidvalidity();
            $result = false;
        }

        return $result;
    }

    /**
     * AJAX action: Get compose redirect data.
     *
     * Variables used:
     *   - uid: (string) Index of the message to redirect (IMAP sequence
     *          string; mailbox is base64url encoded).
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) The input 'type' value.
     */
    public function getRedirectData()
    {
        $compose = $this->_base->initCompose();

        $compose->compose->redirectMessage(new IMP_Indices($compose->contents->getMailbox(), $compose->contents->getUid()));

        $ob = new stdClass;
        $ob->imp_compose = $compose->compose->getCacheId();
        $ob->type = $this->vars->type;

        return $ob;
    }

    /**
     * AJAX action: Get resume data.
     *
     * See the list of variables needed for
     * IMP_Ajax_Application#checkUidvalidity(). Additional variables used:
     *   - format: (string) The format to force to ('text' or 'html')
     *             (DEFAULT: Auto-determined).
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) Resume type: one of 'editasnew', 'resume',
     *           'template', 'template_edit'.
     *   - uid: (string) Indices of the messages to forward (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - body: (string) The body text of the message.
     *   - format: (string) Either 'text' or 'html'.
     *   - header: (array) The headers of the message.
     *   - identity: (integer) The identity ID to use for this message.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - opts: (array) Additional options (atc, priority, readreceipt).
     *   - type: (string) The input 'type' value.
     */
    public function getResumeData()
    {
        try {
            $compose = $this->_base->initCompose();
            $indices_ob = new IMP_Indices($compose->contents->getMailbox(), $compose->contents->getUid());

            switch ($this->vars->type) {
            case 'editasnew':
                $resume = $compose->compose->editAsNew($indices_ob, array(
                    'format' => $this->vars->format
                ));
                break;

            case 'resume':
                $resume = $compose->compose->resumeDraft($indices_ob, array(
                    'format' => $this->vars->format
                ));
                break;

            case 'template':
                $resume = $compose->compose->useTemplate($indices_ob, array(
                    'format' => $this->vars->format
                ));
                break;

            case 'template_edit':
                $resume = $compose->compose->editTemplate($indices_ob);
                break;
            }

            $result = $compose->ajax->getResponse($resume);
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $this->_base->checkUidvalidity();
            $result = false;
        }

        return $result;
    }

    /**
     * AJAX action: Cancel compose.
     *
     * Variables used:
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *
     * @return boolean  True.
     */
    public function cancelCompose()
    {
        $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->vars->imp_compose)->destroy('cancel');
        return true;
    }

    /**
     * AJAX action: Send a message.
     *
     * See the list of variables needed for
     * IMP_Ajax_Application#composeSetup(). Additionalvariables used:
     *   - encrypt: (integer) The encryption method to use (IMP ENCRYPT
     *              constants).
     *   - html: (integer) In HTML compose mode?
     *   - message: (string) The message text.
     *   - priority: (string) The priority of the message.
     *   - request_read_receipt: (boolean) Add request read receipt header?
     *   - save_attachments_select: (boolean) Whether to save attachments.
     *   - save_sent_mail: (boolean) True if saving sent mail.
     *   - save_sent_mail_mbox: (string) base64url encoded version of sent
     *                          mail mailbox to use.
     *
     * @return object  An object with the following entries:
     *   - action: (string) The AJAX action string
     *   - draft_delete: (integer) If set, remove auto-saved drafts.
     *   - encryptjs: (array) Javascript to run after encryption failure.
     *   - flag: (array) See IMP_Ajax_Queue::add().
     *   - identity: (integer) If set, this is the identity that is tied to
     *               the current recipient address.
     *   - mbox: (string) Mailbox of original message (base64url encoded).
     *   - success: (integer) 1 on success, 0 on failure.
     *   - uid: (integer) IMAP UID of original message.
     */
    public function sendMessage()
    {
        try {
            list($result, $imp_compose, $headers, $identity) = $this->_base->composeSetup();
            if (!IMP::canCompose()) {
                $result->success = 0;
                return $result;
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);

            $result = new stdClass;
            $result->action = $this->_action;
            $result->success = 0;
            return $result;
        }

        $headers['replyto'] = $identity->getValue('replyto_addr');

        $sm_displayed = !empty($GLOBALS['conf']['user']['select_sentmail_folder']) && !$GLOBALS['prefs']->isLocked('sent_mail_folder');

        $options = array(
            'add_signature' => $identity->getDefault(),
            'encrypt' => ($GLOBALS['prefs']->isLocked('default_encrypt') ? $GLOBALS['prefs']->getValue('default_encrypt') : $this->vars->encrypt),
            'html' => $this->vars->html,
            'identity' => $identity,
            'priority' => $this->vars->priority,
            'readreceipt' => $this->vars->request_read_receipt,
            'save_attachments' => $this->vars->save_attachments_select,
            'save_sent' => ($sm_displayed
                            ? (bool)$this->vars->save_sent_mail
                            : $identity->getValue('save_sent_mail')),
            'sent_mail' => ($sm_displayed
                              ? (isset($this->vars->save_sent_mail_mbox) ? IMP_Mailbox::formFrom($this->vars->save_sent_mail_mbox) : $identity->getValue('sent_mail_folder'))
                              : $identity->getValue('sent_mail_folder'))
        );

        try {
            $imp_compose->buildAndSendMessage($this->vars->message, $headers, $options);
            $GLOBALS['notification']->push(empty($headers['subject']) ? _("Message sent successfully.") : sprintf(_("Message \"%s\" sent successfully."), Horde_String::truncate($headers['subject'])), 'horde.success');
        } catch (IMP_Compose_Exception $e) {
            $result->success = 0;

            if (!is_null($e->tied_identity)) {
                $result->identity = $e->tied_identity;
            }

            if ($e->encrypt) {
                $imp_ui = $GLOBALS['injector']->getInstance('IMP_Ui_Compose');
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

                Horde::startBuffer();
                $GLOBALS['page_output']->outputInlineScript(true);
                if ($js_inline = Horde::endBuffer()) {
                    $result->encryptjs = array($js_inline);
                }
            } else {
                /* Don't push notification if showing passphrase dialog -
                 * passphrase dialog contains the necessary information. */
                $GLOBALS['notification']->push($e);
            }

            return $result;
        }

        /* Remove any auto-saved drafts. */
        if ($imp_compose->hasDrafts()) {
            $result->draft_delete = 1;
        }

        if ($reply_mbox = $imp_compose->getMetadata('mailbox')) {
            $result->mbox = $reply_mbox->form_to;
            $result->uid = $imp_compose->getMetadata('uid');

            /* Update maillog information. */
            $this->_base->queue->maillog($reply_mbox, $result->uid, $imp_compose->getMetadata('in_reply_to'));
        }

        $imp_compose->destroy('send');

        return $result;
    }

    /**
     * Redirect the message.
     *
     * Variables used:
     *   - composeCache: (string) The IMP_Compose cache identifier.
     *   - redirect_to: (string) The address(es) to redirect to.
     *
     * @return object  An object with the following entries:
     *   - action: (string) 'redirectMessage'.
     *   - success: (integer) 1 on success, 0 on failure.
     */
    public function redirectMessage()
    {
        $result = new stdClass;
        $result->action = $this->_action;
        $result->success = 1;

        try {
            $imp_compose = $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->vars->composeCache);
            $res = $imp_compose->sendRedirectMessage($this->vars->redirect_to);

            foreach ($res as $val) {
                $subject = $val->headers->getValue('subject');
                $GLOBALS['notification']->push(empty($subject) ? _("Message redirected successfully.") : sprintf(_("Message \"%s\" redirected successfully."), Horde_String::truncate($subject)), 'horde.success');

                $this->_base->queue->maillog($val->mbox, $val->uid, $val->headers->getValue('message-id'));
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $result->success = 0;
        }

        return $result;
    }

}
