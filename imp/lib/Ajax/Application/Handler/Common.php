<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines common (i.e. used in dynamic and smartmobile views) AJAX actions
 * used in IMP.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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

        $this->_base->queue->quota($this->_base->indices->mailbox);

        if ($this->_base->changed()) {
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
     *   - sortby: (integer) The Horde_Imap_Client sort constant.
     *   - sortdir: (integer) 0 for ascending, 1 for descending.
     *
     * @return boolean  True on success, false on failure.
     */
    public function viewPort()
    {
        if (!$this->_base->indices->mailbox) {
            return false;
        }

        $vp_vars = $this->vars->viewport;

        /* Change sort preferences if necessary. */
        if (isset($vp_vars->sortby) || isset($vp_vars->sortdir)) {
            $this->_base->indices->mailbox->setSort(
                isset($vp_vars->sortby) ? $vp_vars->sortby : null,
                isset($vp_vars->sortdir) ? $vp_vars->sortdir : null
            );
        }

        /* Toggle hide deleted preference if necessary. */
        if (isset($vp_vars->delhide)) {
            $this->_base->indices->mailbox->setHideDeletedMsgs($vp_vars->delhide);
        }

        $changed = $this->_base->changed(true);

        if (is_null($changed)) {
            $this->_base->addTask('viewport', $GLOBALS['injector']->getInstance('IMP_Ajax_Application_ListMessages')->getBaseOb($this->_base->indices->mailbox));
            return true;
        }

        $this->_base->queue->poll($this->_base->indices->mailbox);

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
     * IMP_Ajax_Application#checkUidvalidity(). Mailbox/indices form
     * parameters needed. Additional variables used:
     *   - mboxto: (string) Mailbox to move the message to (base64url
     *             encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function moveMessages()
    {
        if ((!isset($this->vars->mboxto) && !isset($this->vars->newmbox)) ||
            !count($this->_base->indices)) {
            return false;
        }

        $change = $this->_base->changed(true);

        if (is_null($change)) {
            return false;
        }

        if (isset($this->vars->newmbox)) {
            $mbox = IMP_Mailbox::prefFrom($this->vars->newmbox);
            $newMbox = true;
        } else {
            $mbox = IMP_Mailbox::formFrom($this->vars->mboxto);
            $newMbox = false;
        }

        $result = $GLOBALS['injector']
            ->getInstance('IMP_Message')
            ->copy($mbox, 'move', $this->_base->indices, array('create' => $newMbox));

        if ($result) {
            $this->_base->deleteMsgs($this->_base->indices, $change, true);
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
     * IMP_Ajax_Application#_checkUidvalidity(). Mailbox/indices form
     * parameters needed. Additional variables used:
     *   - mboxto: (string) Mailbox to copy the message to (base64url
     *             encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function copyMessages()
    {
        if ((!isset($this->vars->mboxto) && !isset($this->vars->newmbox)) ||
            !count($this->_base->indices)) {
            return false;
        }

        if (isset($this->vars->newmbox)) {
            $mbox = IMP_Mailbox::prefFrom($this->vars->newmbox);
            $newMbox = true;
        } else {
            $mbox = IMP_Mailbox::formFrom($this->vars->mboxto);
            $newMbox = false;
        }

        $result = $GLOBALS['injector']
            ->getInstance('IMP_Message')
            ->copy($mbox, 'copy', $this->_base->indices, array('create' => $newMbox));

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
     * IMP_Ajax_Application@checkUidvalidity(). Mailbox/indices form
     * parameters needed.
     *
     * @return boolean  True on success, false on failure.
     */
    public function deleteMessages()
    {
        if (!count($this->_base->indices)) {
            return false;
        }

        $change = $this->_base->changed(true);

        if ($GLOBALS['injector']->getInstance('IMP_Message')->delete($this->_base->indices)) {
            $this->_base->deleteMsgs($this->_base->indices, $change);
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
     * IMP_Ajax_Application#checkUidvalidity(). Mailbox/indices form
     * parameters needed. Additional variables used:
     *   - spam: (integer) 1 to mark as spam, 0 to mark as innocent.
     *
     * @return boolean  True on success.
     */
    public function reportSpam()
    {
        $change = $this->_base->changed(true);

        if (IMP_Spam::reportSpam($this->_base->indices, $this->vars->spam ? 'spam' : 'notspam')) {
            $this->_base->deleteMsgs($this->_base->indices, $change);
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
     * IMP_Ajax_Application#checkUidvalidity(). Mailbox/indices form
     * parameters needed. Additional variables used:
     *   - headeronly: (boolean) Only return header information (DEFAULT:
     *                 false).
     *   - format: (string) The format to force to ('text' or 'html')
     *             (DEFAULT: Auto-determined).
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) See IMP_Compose::replyMessage().
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - body: (string) The body text of the message.
     *   - format: (string) Either 'text' or 'html'.
     *   - header: (array) The headers of the message.
     *   - identity: (integer) The identity ID to use for this message.
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
     * Get forward compose data.
     *
     * See the list of variables needed for checkUidvalidity().
     * Mailbox/indices form parameters needed.  Additional variables used:
     *   - dataonly: (boolean) Only return data information (DEFAULT: false).
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) Forward type.
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - body: (string) The body text of the message.
     *   - format: (string) Either 'text' or 'html'.
     *   - header: (array) The headers of the message.
     *   - identity: (integer) The identity ID to use for this message.
     *   - type: (string) The input 'type' value.
     */
    public function getForwardData()
    {
        global $notification;

        /* Can't open session read-only since we need to store the message
         * cache id. */

        try {
            $compose = $this->_base->initCompose();

            $fwd_msg = $compose->compose->forwardMessage($compose->ajax->forward_map[$this->vars->type], $compose->contents);

            if ($this->vars->dataonly) {
                $result = $compose->ajax->getBaseResponse();
                $result->body = $fwd_msg['body'];
                $result->format = $fwd_msg['format'];
            } else {
                $result = $compose->ajax->getResponse($fwd_msg);
            }

            $this->_base->queue->attachment($compose->ajax, $fwd_msg['type']);
        } catch (Horde_Exception $e) {
            $notification->push($e);
            $this->_base->checkUidvalidity();
            $result = false;
        }

        return $result;
    }

    /**
     * AJAX action: Get compose redirect data.
     *
     * Mailbox/indices form parameters needed.
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) The input 'type' value.
     */
    public function getRedirectData()
    {
        $compose = $this->_base->initCompose();

        $compose->compose->redirectMessage($compose->contents->getIndicesOb());

        $ob = new stdClass;
        $ob->type = $this->vars->type;

        return $ob;
    }

    /**
     * AJAX action: Get resume data.
     *
     * See the list of variables needed for
     * IMP_Ajax_Application#checkUidvalidity(). Mailbox/indices form
     * parameters needed. Additional variables used:
     *   - format: (string) The format to force to ('text' or 'html')
     *             (DEFAULT: Auto-determined).
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) Resume type: one of 'editasnew', 'resume',
     *           'template', 'template_edit'.
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - body: (string) The body text of the message.
     *   - format: (string) Either 'text' or 'html'.
     *   - header: (array) The headers of the message.
     *   - identity: (integer) The identity ID to use for this message.
     *   - opts: (array) Additional options (atc, priority, readreceipt).
     *   - type: (string) The input 'type' value.
     */
    public function getResumeData()
    {
        try {
            $compose = $this->_base->initCompose();

            switch ($this->vars->type) {
            case 'editasnew':
                $resume = $compose->compose->editAsNew($compose->contents->getIndicesOb(), array(
                    'format' => $this->vars->format
                ));
                break;

            case 'resume':
                $resume = $compose->compose->resumeDraft($compose->contents->getIndicesOb(), array(
                    'format' => $this->vars->format
                ));
                break;

            case 'template':
                $resume = $compose->compose->useTemplate($compose->contents->getIndicesOb(), array(
                    'format' => $this->vars->format
                ));
                break;

            case 'template_edit':
                $resume = $compose->compose->editTemplate($compose->contents->getIndicesOb());
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
     *   - discard: (boolean) If true, discard draft.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *
     * @return boolean  True.
     */
    public function cancelCompose()
    {
        $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->vars->imp_compose)->destroy($this->vars->discard ? 'discard' : 'cancel');
        return true;
    }

    /**
     * AJAX action: Send a message.
     *
     * See the list of variables needed for
     * IMP_Ajax_Application#composeSetup(). Additional variables used:
     *   - encrypt: (integer) The encryption method to use (IMP ENCRYPT
     *              constants).
     *   - html: (integer) In HTML compose mode?
     *   - message: (string) The message text.
     *   - pgp_attach_pubkey: (boolean) True if PGP public key should be
     *                        attached to the message.
     *   - priority: (string) The priority of the message.
     *   - request_read_receipt: (boolean) Add request read receipt header?
     *   - save_attachments_select: (boolean) Whether to save attachments.
     *   - save_sent_mail: (boolean) True if saving sent mail.
     *   - save_sent_mail_mbox: (string) base64url encoded version of sent
     *                          mail mailbox to use.
     *   - vcard_attach: (boolean) Attach user's vCard to the message?
     *
     * @return object  An object with the following entries:
     *   - action: (string) The AJAX action string
     *   - draft_delete: (integer) If set, remove auto-saved drafts.
     *   - encryptjs: (array) Javascript to run after encryption failure.
     *   - flag: (array) See IMP_Ajax_Queue::add().
     *   - identity: (integer) If set, this is the identity that is tied to
     *               the current recipient address.
     *   - success: (integer) 1 on success, 0 on failure.
     */
    public function sendMessage()
    {
        global $conf, $injector, $notification, $page_output, $prefs;

        try {
            list($result, $imp_compose, $headers, $identity) = $this->_base->composeSetup('sendMessage');
            if (!IMP_Compose::canCompose()) {
                $result->success = 0;
                return $result;
            }
        } catch (Horde_Exception $e) {
            $notification->push($e);

            $result = new stdClass;
            $result->action = 'sendMessage';
            $result->success = 0;
            return $result;
        }

        $headers['replyto'] = $identity->getValue('replyto_addr');

        $sm_displayed = !$prefs->isLocked('sent_mail_folder');

        $options = array(
            'add_signature' => $identity->getDefault(),
            'encrypt' => ($prefs->isLocked('default_encrypt') ? $prefs->getValue('default_encrypt') : $this->vars->encrypt),
            'html' => $this->vars->html,
            'identity' => $identity,
            'pgp_attach_pubkey' => $this->vars->pgp_attach_pubkey,
            'priority' => $this->vars->priority,
            'readreceipt' => $this->vars->request_read_receipt,
            'save_attachments' => $this->vars->save_attachments_select,
            'save_sent' => ($sm_displayed
                            ? (bool)$this->vars->save_sent_mail
                            : $identity->getValue('save_sent_mail')),
            'sent_mail' => ($sm_displayed
                              ? (isset($this->vars->save_sent_mail_mbox) ? IMP_Mailbox::formFrom($this->vars->save_sent_mail_mbox) : $identity->getValue('sent_mail_folder'))
                              : $identity->getValue('sent_mail_folder')),
            'vcard_attach' => ($this->vars->vcard_attach ? $identity->getValue('fullname') : null)
        );

        try {
            $imp_compose->buildAndSendMessage($this->vars->message, $headers, $options);
            $notification->push(empty($headers['subject']) ? _("Message sent successfully.") : sprintf(_("Message \"%s\" sent successfully."), Horde_String::truncate($headers['subject'])), 'horde.success');
        } catch (IMP_Compose_Exception $e) {
            $result->success = 0;

            if (!is_null($e->tied_identity)) {
                $result->identity = $e->tied_identity;
            }

            if ($e->encrypt) {
                $imp_ui = $injector->getInstance('IMP_Compose_Ui');
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
                $page_output->outputInlineScript(true);
                if ($js_inline = Horde::endBuffer()) {
                    $result->encryptjs = array($js_inline);
                }
            } else {
                /* Don't push notification if showing passphrase dialog -
                 * passphrase dialog contains the necessary information. */
                $notification->push($e);
            }

            return $result;
        }

        /* Remove any auto-saved drafts. */
        if ($imp_compose->hasDrafts()) {
            $result->draft_delete = 1;
        }

        if ($indices = $imp_compose->getMetadata('indices')) {
            /* Update maillog information. */
            $this->_base->queue->maillog($indices, $imp_compose->getMetadata('in_reply_to'));
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
        $result->action = 'redirectMessage';
        $result->success = 1;

        try {
            $imp_compose = $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->vars->composeCache);
            $res = $imp_compose->sendRedirectMessage($this->vars->redirect_to);

            foreach ($res as $val) {
                $subject = $val->headers->getValue('subject');
                $GLOBALS['notification']->push(empty($subject) ? _("Message redirected successfully.") : sprintf(_("Message \"%s\" redirected successfully."), Horde_String::truncate($subject)), 'horde.success');

                $this->_base->queue->maillog(new IMP_Indices($val->mbox, $val->uid), $val->headers->getValue('message-id'));
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $result->success = 0;
        }

        return $result;
    }

    /**
     * Generate data necessary to display a message.
     *
     * See the list of variables needed for changed() and
     * checkUidvalidity(). Mailbox/indices form parameters needed. Additional
     * variables used:
     *   - peek: (integer) If set, don't set seen flag.
     *   - preview: (integer) If set, return preview data. Otherwise, return
     *              full data.
     *
     * @return object  Object with the following entries:
     *   - buid: (integer) The message BUID.
     *   - error: (string) On error, the error string.
     *   - errortype: (string) On error, the error type.
     *   - view: (string) The view ID.
     */
    public function showMessage()
    {
        $result = new stdClass;
        $result->buid = intval($this->vars->buid);
        $result->view = $this->vars->view;

        try {
            $change = $this->_base->changed(true);
            if (is_null($change)) {
                throw new IMP_Exception(_("Could not open mailbox."));
            }

            /* Explicitly load the message here; non-existent messages are
             * ignored when the Ajax queue is processed. */
            $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($this->_base->indices);
            $this->_base->queue->message($this->_base->indices, $this->vars->preview, $this->vars->peek);
        } catch (Exception $e) {
            $result->error = $e->getMessage();
            $result->errortype = 'horde.error';

            $change = true;
        }

        if ($this->vars->preview || $this->vars->viewport->force) {
            if ($change) {
                $this->_base->addTask('viewport', $this->_base->viewPortData(true));
            } elseif ($this->_base->indices->mailbox->cacheid_date != $this->vars->viewport->cacheid) {
                /* Cache ID has changed due to viewing this message. So update
                 * the cacheid in the ViewPort. */
                $this->_base->addTask('viewport', $this->_base->viewPortOb());
            }

            if ($this->vars->preview) {
                $this->_base->queue->poll(array_keys($this->_base->indices->indices()));
            }
        }

        return $result;
    }

}
