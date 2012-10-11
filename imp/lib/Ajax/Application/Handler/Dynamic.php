<?php
/**
 * Defines AJAX actions used exclusively in the IMP dynamic view.
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
class IMP_Ajax_Application_Handler_Dynamic extends Horde_Core_Ajax_Application_Handler
{
    /**
     * The list of actions that require readonly access to the session.
     *
     * @var array
     */
    protected $_readOnly = array(
        'html2Text', 'text2Html'
    );

    /**
     * AJAX action: Check access rights for creation of a submailbox.
     *
     * Variables used:
     *   - mbox: (string) The name of the mailbox to check (base64url
     *           encoded).
     *
     * @return boolean  True if submailboxes can be created.
     */
    public function createMailboxPrepare()
    {
        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);

        if ($mbox->access_creatembox) {
            return true;
        }

        $GLOBALS['notification']->push(sprintf(_("You may not create child mailboxes in \"%s\"."), $mbox->display), 'horde.error');
        return false;
    }

    /**
     * AJAX action: Create a mailbox.
     *
     * Variables used:
     *   - mbox: (string) The name of the new mailbox.
     *   - noexpand: (integer) Submailbox is not yet expanded.
     *   - parent: (string) The parent mailbox (base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function createMailbox()
    {
        if (!isset($this->vars->mbox)) {
            return false;
        }

        $result = false;

        try {
            $new_mbox = $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->createMailboxName(
                isset($this->vars->parent) ? IMP_Mailbox::formFrom($this->vars->parent) : '',
                $this->vars->mbox
            );

            if ($new_mbox->exists) {
                $GLOBALS['notification']->push(sprintf(_("Mailbox \"%s\" already exists."), $new_mbox->display), 'horde.warning');
            } elseif ($new_mbox->create()) {
                $result = true;
                if (isset($this->vars->parent) && $this->vars->noexpand) {
                    $this->_base->queue->setMailboxOpt('noexpand', 1);
                }
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
        }

        return $result;
    }

    /**
     * AJAX action: Check access rights for deletion/rename of mailbox.
     *
     * Variables used:
     *   - mbox: (string) The name of the mailbox to check (base64url
     *           encoded).
     *   - type: (string) Either 'delete' or 'rename'.
     *
     * @return boolean  True if mailbox can be deleted/renamed.
     */
    public function deleteMailboxPrepare()
    {
        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);

        if ($mbox->access_deletembox) {
            return true;
        }

        switch ($this->vars->type) {
        case 'delete':
            $GLOBALS['notification']->push(sprintf(_("You may not delete \"%s\"."), $mbox->display), 'horde.error');
            break;

        case 'rename':
            $GLOBALS['notification']->push(sprintf(_("You may not rename \"%s\"."), $mbox->display), 'horde.error');
            break;
        }

        return false;
    }

    /**
     * AJAX action: Delete a mailbox.
     *
     * Variables used:
     *   - container: (boolean) True if base element is a container.
     *   - mbox: (string) The full mailbox name to delete (base64url encoded).
     *   - subfolders: (boolean) Delete all subfolders?
     *
     * @return boolean  True on success, false on failure.
     */
    public function deleteMailbox()
    {
        return ($this->vars->mbox && IMP_Mailbox::formFrom($this->vars->mbox)->delete(array(
            'subfolders' => !empty($this->vars->subfolders),
            'subfolders_only' => !empty($this->vars->container)
        )));
    }

    /**
     * AJAX action: Rename a mailbox.
     *
     * Variables used:
     *   - new_name: (string) New mailbox name (child node) (UTF-8).
     *   - new_parent: (string) New parent name (UTF-8; base64url encoded).
     *   - old_name: (string) Full name of old mailbox (base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function renameMailbox()
    {
        if (!$this->vars->old_name || !$this->vars->new_name) {
            return false;
        }

        try {
            $new_name = $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->createMailboxName(
                isset($this->vars->new_parent) ? IMP_Mailbox::formFrom($this->vars->new_parent) : '',
                $this->vars->new_name
            );

            $old_name = IMP_Mailbox::formFrom($this->vars->old_name);

            if (($old_name != $new_name) && $old_name->rename($new_name)) {
                $this->_base->queue->poll($new_name);
                $this->_base->queue->setMailboxOpt('switch', $new_name->form_to);
                return true;
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
        }

        return false;
    }

    /**
     * AJAX action: Check access rights for a mailbox, and provide number of
     * messages to be emptied.
     *
     * Variables used:
     *   - mbox: (string) The name of the mailbox to check (base64url
     *           encoded).
     *
     * @return integer  The number of messages to be deleted.
     */
    public function emptyMailboxPrepare()
    {
        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);

        if (!$mbox->access_empty) {
            $GLOBALS['notification']->push(sprintf(_("The mailbox \"%s\" may not be emptied."), $mbox->display), 'horde.error');
            return 0;
        }

        $poll_info = $mbox->poll_info;
        if (empty($poll_info->msgs)) {
            $GLOBALS['notification']->push(sprintf(_("The mailbox \"%s\" is already empty."), $mbox->display), 'horde.message');
            return 0;
        }

        return $poll_info->msgs;
    }

    /**
     * AJAX action: Empty a mailbox.
     *
     * Variables used:
     *   - mbox: (string) The full mailbox name to empty (base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function emptyMailbox()
    {
        if (!$this->vars->mbox) {
            return false;
        }

        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);

        $GLOBALS['injector']->getInstance('IMP_Message')->emptyMailbox(array($mbox));

        $this->_base->queue->poll($mbox);

        $vp = $this->_base->viewPortOb($mbox);
        $vp->data_reset = 1;
        $vp->rowlist_reset = 1;
        $this->_base->addTask('viewport', $vp);

        return true;
    }

    /**
     * AJAX action: Flag all messages in a mailbox.
     *
     * Variables used:
     *   - add: (integer) Add the flags?
     *   - flags: (string) The IMAP flags to add/remove (JSON serialized
     *            array).
     *   - mbox: (string) The full mailbox name (base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function flagAll()
    {
        $flags = Horde_Serialize::unserialize($this->vars->flags, Horde_Serialize::JSON);
        if (!$this->vars->mbox || empty($flags)) {
            return false;
        }

        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);

        if (!$GLOBALS['injector']->getInstance('IMP_Message')->flagAllInMailbox($flags, array($mbox), $this->vars->add)) {
            return false;
        }

        $this->_base->queue->poll($mbox);

        return true;
    }

    /**
     * AJAX action: List mailboxes.
     *
     * Variables used:
     *   - all: (integer) 1 to show all mailboxes.
     *   - base: (string) The base mailbox.
     *   - initial: (string) 1 to indicate the initial request for mailbox
     *              list.
     *   - mboxes: (string) The list of mailboxes to process (JSON encoded
     *             array; mailboxes are base64url encoded).
     *   - reload: (integer) 1 to force reload of mailboxes.
     *   - unsub: (integer) 1 to show unsubscribed mailboxes.
     *
     * @return boolean  True.
     */
    public function listMailboxes()
    {
        /* This might be a long running operation. */
        if ($this->vars->initial) {
            $GLOBALS['session']->close();
        }

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $initreload = ($this->vars->initial || $this->vars->reload);

        $mask = IMP_Imap_Tree::FLIST_VFOLDER;
        if ($this->vars->unsub) {
            $mask |= IMP_Imap_Tree::FLIST_UNSUB;
        }

        if (isset($this->vars->base)) {
            $this->_base->queue->setMailboxOpt('base', $this->vars->base);
        }

        if ($this->vars->all) {
            $this->_base->queue->setMailboxOpt('all', 1);
        } else {
            if ($initreload) {
                $mask |= IMP_Imap_Tree::FLIST_ANCESTORS | IMP_Imap_Tree::FLIST_SAMELEVEL;
                if ($GLOBALS['prefs']->getValue('nav_expanded')) {
                    $this->_base->queue->setMailboxOpt('expand', 1);
                    $mask |= IMP_Imap_Tree::FLIST_EXPANDED;
                } else {
                    $mask |= IMP_Imap_Tree::FLIST_NOCHILDREN;
                }
            } else {
                $this->_base->queue->setMailboxOpt('expand', 1);
                $mask |= IMP_Imap_Tree::FLIST_EXPANDED | IMP_Imap_Tree::FLIST_NOBASE;
            }
        }

        if ($this->vars->reload) {
            $imptree->init();
        }

        $imptree->showUnsubscribed($this->vars->unsub);

        if (!empty($this->vars->mboxes)) {
            $mboxes = IMP_Mailbox::formFrom(Horde_Serialize::unserialize($this->vars->mboxes, Horde_Serialize::JSON));
            if ($initreload) {
                $mboxes = array_merge(array('INBOX'), array_diff($mboxes, array('INBOX')));
            }

            foreach ($mboxes as $val) {
                $imptree->setIteratorFilter($mask, $val);
                foreach ($imptree as $val2) {
                    $imptree->addEltDiff($val2);
                    $this->_base->queue->poll($val2);
                }

                if (!$initreload) {
                    $imptree->expand($val);
                }
            }
        }

        /* Add special mailboxes explicitly to the initial folder list, since
         * they are ALWAYS displayed, may appear outside of the folder
         * slice requested, and need to be sorted logically. */
        if ($initreload) {
            foreach (IMP_Mailbox::getSpecialMailboxesSort() as $val) {
                if ($imptree[$val]) {
                    $imptree->addEltDiff($val);
                }
            }

            /* Poll all mailboxes on initial display. */
            $this->_base->queue->poll($imptree->getPollList());
        }

        $this->_base->queue->quota();

        if ($this->vars->initial) {
            $GLOBALS['session']->start();
        }

        return true;
    }

    /**
     * AJAX action: Expand mailboxes (saves expanded state in prefs).
     *
     * Variables used:
     *   - mboxes: (string) The list of mailboxes to process (JSON encoded
     *             array; mailboxes are base64url encoded).
     *
     * @return boolean  True.
     */
    public function expandMailboxes()
    {
        if (!empty($this->vars->mboxes)) {
            $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

            foreach (Horde_Serialize::unserialize($this->vars->mboxes, Horde_Serialize::JSON) as $val) {
                $imptree->expand(IMP_Mailbox::formFrom($val));
            }
        }

        return true;
    }

    /**
     * AJAX action: Collapse mailboxes.
     *
     * Variables used:
     *   - all: (integer) 1 to show all mailboxes.
     *   - mboxes: (string) The list of mailboxes to process (JSON encoded
     *             array; mailboxes are base64url encoded) if 'all' is 0.
     *
     * @return boolean  True.
     */
    public function collapseMailboxes()
    {
        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

        if ($this->vars->all) {
            $imptree->collapseAll();
        } elseif (!empty($this->vars->mboxes)) {
            foreach (Horde_Serialize::unserialize($this->vars->mboxes, Horde_Serialize::JSON) as $val) {
                $imptree->collapse(IMP_Mailbox::formFrom($val));
            }
        }

        return true;
    }

    /**
     * AJAX action: Modify list of polled mailboxes.
     *
     * Variables used:
     *   - add: (integer) 1 to add to the poll list, 0 to remove.
     *   - mbox: (string) The full mailbox name to modify (base64url encoded).
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - add: (integer) 1 if added to the poll list, 0 if removed.
     *   - mbox: (string) The full mailbox name modified.
     */
    public function modifyPoll()
    {
        if (!$this->vars->mbox) {
            return false;
        }

        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

        $result = new stdClass;
        $result->add = intval($this->vars->add);
        $result->mbox = $this->vars->mbox;

        if ($this->vars->add) {
            $imptree->addPollList($mbox);
            $this->_base->queue->poll($mbox);
            $GLOBALS['notification']->push(sprintf(_("\"%s\" mailbox now polled for new mail."), $mbox->display), 'horde.success');
        } else {
            $imptree->removePollList($mbox);
            $GLOBALS['notification']->push(sprintf(_("\"%s\" mailbox no longer polled for new mail."), $mbox->display), 'horde.success');
        }

        return $result;
    }

    /**
     * AJAX action: [un]Subscribe to a mailbox.
     *
     * Variables used:
     *   - mbox: (string) The full mailbox name to [un]subscribe to (base64url
     *           encoded).
     *   - sub: (integer) 1 to subscribe, empty to unsubscribe.
     *
     * @return boolean  True on success, false on failure.
     */
    public function subscribe()
    {
        return $GLOBALS['prefs']->getValue('subscribe')
            ? IMP_Mailbox::formFrom($this->vars->mbox)->subscribe($this->vars->sub)
            : false;
    }

    /**
     * AJAX action: Import a mailbox.
     *
     * Variables used:
     *   - import_mbox: (string) The mailbox to import into (base64url
     *                  encoded).
     *
     * @return object  Returns response object to display JSON HTML-encoded.
     *                 Embedded data: false on failure, or an object with the
     *                 following properties:
     *   - action: (string) The action name (importMailbox).
     *   - mbox: (string) The mailbox the messages were imported to (base64url
     *           encoded).
     */
    public function importMailbox()
    {
        global $injector, $notification;

        $mbox = IMP_Mailbox::formFrom($this->vars->import_mbox);

        try {
            $notification->push($injector->getInstance('IMP_Ui_Folder')->importMbox($mbox, 'import_file'), 'horde.success');
        } catch (Horde_Exception $e) {
            $notification->push($e);
            return false;
        }

        $result = new stdClass;
        $result->action = 'importMailbox';
        $result->mbox = $this->vars->import_mbox;

        $this->_base->queue->poll($mbox);

        return new Horde_Core_Ajax_Response_HordeCore_JsonHtml($result);
    }

    /**
     * AJAX action: Flag messages.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed() and
     * IMP_Ajax_Application#checkUidvalidity().  Additional variables used:
     *   - add: (integer) Set the flag?
     *   - flags: (string) The flags to set (JSON serialized array).
     *   - uid: (string) Indices of the messages to flag (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function flagMessages()
    {
        $indices = new IMP_Indices_Form($this->vars->uid);
        if (!$this->vars->flags || !count($indices)) {
            return false;
        }

        $change = $this->_base->changed(true);

        if (is_null($change)) {
            return false;
        }

        $flags = Horde_Serialize::unserialize($this->vars->flags, Horde_Serialize::JSON);

        if (!$GLOBALS['injector']->getInstance('IMP_Message')->flag($flags, $indices, $this->vars->add)) {
            $this->_base->checkUidvalidity();
            return false;
        }

        if (in_array(Horde_Imap_Client::FLAG_SEEN, $flags)) {
            $this->_base->queue->poll(array_keys($indices->indices()));
        }

        $this->_base->addTask('viewport', $change ? $this->_base->viewPortData(true) : $this->_base->viewPortOb());

        return true;
    }

    /**
     * AJAX action: Add contact.
     *
     * Variables used:
     *   - addr: (string) [JSON array] Address list.
     *
     * @return boolean  True on success, false on failure.
     */
    public function addContact()
    {
        global $injector, $notification;

        $addr_ob = $injector->getInstance('IMP_Dynamic_AddressList')->parseAddressList($this->vars->addr);

        // TODO: Currently supports only a single, non-group contact.
        $ob = $addr_ob[0];
        if (!$ob) {
            return false;
        } elseif ($ob instanceof Horde_Mail_Rfc822_Group) {
            $notification->push(_("Adding group lists not currently supported."), 'horde.warning');
            return false;
        }

        try {
            $injector->getInstance('IMP_Ui_Contacts')->addAddress($ob->bare_address, $ob->personal);
            $notification->push(sprintf(_("%s was successfully added to your address book."), $ob->label), 'horde.success');
            return true;
        } catch (Horde_Exception $e) {
            $notification->push($e);
            return false;
        }
    }

    /**
     * AJAX action: Blacklist/whitelist addresses from messages.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed(),
     * IMP_Ajax_Application#deleteMsgs(), and
     * IMP_Ajax_Application#checkUidvalidity(). Additional variables used:
     *   - blacklist: (integer) 1 to blacklist, 0 to whitelist.
     *   - uid: (string) Indices of the messages to report (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success.
     */
    public function blacklist()
    {
        $indices = new IMP_Indices_Form($this->vars->uid);
        if (!count($indices)) {
            return false;
        }

        if ($this->vars->blacklist) {
            $change = $this->_base->changed(false);
            if (!is_null($change)) {
                try {
                    if ($GLOBALS['injector']->getInstance('IMP_Filter')->blacklistMessage($indices, false)) {
                        $this->_base->deleteMsgs($indices, $change);
                        return true;
                    }
                } catch (Horde_Exception $e) {
                    $this->_base->checkUidvalidity();
                }
            }
        } else {
            try {
                $GLOBALS['injector']->getInstance('IMP_Filter')->whitelistMessage($indices, false);
                return true;
            } catch (Horde_Exception $e) {
                $this->_base->checkUidvalidity();
            }
        }

        return false;
    }

    /**
     * AJAX action: Generate data necessary to display a message.
     *
     * @see IMP_Ajax_Application#showMessage
     */
    public function showMessage()
    {
        return $this->_base->showMessage();
    }

    /**
     * AJAX action: Return the MIME tree representation of the message.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed() and
     * IMP_Ajax_Application#checkUidvalidity().  Additional variables used:
     *   - preview: (integer) If set, return preview data. Otherwise, return
     *              full data.
     *   - uid: (string) Index of the messages to display (IMAP sequence
     *          string; mailbox is base64url encoded) - must be single index.
     *
     * @return mixed  On error will return null.
     *                Otherwise an object with the following entries:
     *   - tree: (string) The MIME tree representation of the message.
     *           If viewing preview, on error this object will contain error
     *           and errortype properties.
     */
    public function messageMimeTree()
    {
        $result = new stdClass;

        try {
            $imp_contents = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create(new IMP_Indices_Form($this->vars->uid));
            $result->tree = $imp_contents->getTree()->getTree(true);
        } catch (IMP_Exception $e) {
            if (!$this->vars->preview) {
                throw $e;
            }

            $result->preview->error = $e->getMessage();
            $result->preview->errortype = 'horde.error';
            $result->preview->mbox = $this->vars->mbox;
            $result->preview->uid = $this->vars->uid;
            $result->preview->view = $this->vars->view;
        }

        return $result;
    }

    /**
     * AJAX action: Return a list of address objects used to build an address
     * header for a message.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed() and
     * IMP_Ajax_Application#checkUidvalidity().  Additional variables used:
     *   - header: (integer) If set, return preview data. Otherwise, return
     *              full data.
     *   - uid: (string) Index of the messages to display (IMAP sequence
     *          string; mailbox is base64url encoded) - must be single index.
     *
     * @return object  An object with the following entries:
     *   - hdr_data: (object) Contains header names as keys and lists of
     *               address objects as values.
     * @throws IMP_Exception
     */
    public function addressHeader()
    {
        $indices = new IMP_Indices_Form($this->vars->uid);
        list($mbox, $idx) = $indices->getSingle();

        if (!$idx) {
            throw new IMP_Exception(_("Requested message not found."));
        }

        $show_msg = new IMP_Ajax_Application_ShowMessage($mbox, $idx);

        $hdr = $this->vars->header;

        $result = new stdClass;
        $result->hdr_data->$hdr = (object)$show_msg->getAddressHeader($this->vars->header, null);

        return $result;
    }

    /**
     * AJAX action: Get forward compose data.
     *
     * @see IMP_Ajax_Application#getForwardData()
     */
    public function getForwardData()
    {
        return $this->_base->getForwardData();
    }

    /**
     * AJAX action: Delete an attachment from compose data.
     *
     * Variables used:
     *   - atc_indices: (string) [JSON array] Attachment IDs to delete.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *
     * @return boolean  True.
     */
    public function deleteAttach()
    {
        if (isset($this->vars->atc_indices)) {
            $imp_compose = $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->vars->imp_compose);
            foreach (Horde_Serialize::unserialize($this->vars->atc_indices, Horde_Serialize::JSON) as $val) {
                if ($part = $imp_compose[$val]['part']) {
                    $GLOBALS['notification']->push(sprintf(_("Deleted attachment \"%s\"."), Horde_Mime::decode($part->getName(true))), 'horde.success');
                }
                unset($imp_compose[$val]);
            }
        }

        return true;
    }

    /**
     * AJAX action: Purge deleted messages.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed() and
     * IMP_Ajax_Application#deleteMsgs().
     *
     * @return boolean  True on success.
     */
    public function purgeDeleted()
    {
        global $injector;

        $change = $this->_base->changed(true);
        if (is_null($change)) {
            return false;
        }

        if (!$change) {
            $change = ($this->_base->mbox->getSort()->sortby == Horde_Imap_Client::SORT_THREAD);
        }

        $expunged = $injector->getInstance('IMP_Message')->expungeMailbox(array(strval($this->_base->mbox) => 1), array('list' => true));

        if (!($expunge_count = count($expunged))) {
            return false;
        }

        $GLOBALS['notification']->push(sprintf(ngettext("%d message was purged from \"%s\".", "%d messages were purged from \"%s\".", $expunge_count), $expunge_count, $this->_base->mbox->display), 'horde.success');

        $this->_base->deleteMsgs($expunged, $change, true);

        return true;
    }

    /**
     * AJAX action: Send a Message Disposition Notification (MDN).
     *
     * Variables used:
     *   - uid: (string) Index of the messages to send MDN for (IMAP sequence
     *          string; mailbox is base64url encoded) - must be single index.
     *
     * @return mixed  False on failure, or an object with these properties:
     *   - mbox: (string) Mailbox of message (base64url encoded).
     *   - uid: (integer) UID of message.
     */
    public function sendMDN()
    {
        $indices = new IMP_Indices_Form($this->vars->uid);
        if (count($indices) != 1) {
            return false;
        }

        try {
            $contents = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($indices);
        } catch (IMP_Imap_Exception $e) {
            $e->notify(_("The Message Disposition Notification was not sent. This is what the server said") . ': ' . $e->getMessage());
            return false;
        }

        list($mbox, $uid) = $indices->getSingle();
        $imp_ui = new IMP_Ui_Message();
        $imp_ui->MDNCheck($mbox, $uid, $contents->getHeaderAndMarkAsSeen(), true);

        $GLOBALS['notification']->push(_("The Message Disposition Notification was sent successfully."), 'horde.success');

        $result = new stdClass;
        $result->mbox = $mbox->form_to;
        $result->uid = $uid;

        return $result;
    }

    /**
     * AJAX action: strip attachment.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed() and
     * IMP_Ajax_Application#checkUidvalidity().  Additional variables used:
     *   - uid: (string) Index of the messages to preview (IMAP sequence
     *          string; base64url encoded) - must be single index.
     *
     * @return mixed  False on failure, or an object with these properties:
     *   - newmbox: (string) Mailbox of new message (base64url encoded).
     *   - newuid: (integer) UID of new message.
     */
    public function stripAttachment()
    {
        $indices = new IMP_Indices_Form($this->vars->uid);
        if (count($indices) != 1) {
            return false;
        }

        $change = $this->_base->changed(true);
        if (is_null($change)) {
            return false;
        }

        try {
            $new_indices = $GLOBALS['injector']->getInstance('IMP_Message')->stripPart($indices, $this->vars->id);
        } catch (IMP_Exception $e) {
            $GLOBALS['notification']->push($e);
            return false;
        }

        $GLOBALS['notification']->push(_("Attachment successfully stripped."), 'horde.success');

        $tmp = $new_indices->getSingle();

        $result = new stdClass;
        $result->newmbox = $tmp[0]->form_to;
        $result->newuid = $tmp[1];

        $this->_base->queue->message($tmp[0], $tmp[1], true);
        $this->_base->addTask('viewport', $this->_base->viewPortData(true));

        return $result;
    }

    /**
     * AJAX action: Auto save a draft message.
     *
     * @return object  See _draftAction().
     */
    public function autoSaveDraft()
    {
        return $this->_draftAction('autoSaveDraft');
    }

    /**
     * AJAX action: Save a draft message.
     *
     * @return object  See _draftAction().
     */
    public function saveDraft()
    {
        return $this->_draftAction('saveDraft');
    }

    /**
     * AJAX action: Save a template message.
     *
     * @return object  See _draftAction().
     */
    public function saveTemplate()
    {
        return $this->_draftAction('saveTemplate');
    }

    /**
     * AJAX action: Convert HTML to text (compose data).
     *
     * Variables used:
     *   - changed: (integer) Has the text changed from the original?
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - text: (string) The text to convert.
     *
     * @return object  An object with the following entries:
     *   - text: (string) The converted text.
     */
    public function html2Text()
    {
        $result = new stdClass;

        if (!$this->vars->changed) {
            $compose = $this->_base->initCompose();

            switch ($compose->compose->replyType()) {
            case IMP_Compose::FORWARD_BODY:
            case IMP_Compose::FORWARD_BOTH:
                $data = $compose->compose->forwardMessageText($compose->contents, array(
                    'format' => 'text'
                ));
                $result->text = $data['body'];
                return $result;

            case IMP_Compose::REPLY_ALL:
            case IMP_Compose::REPLY_LIST:
            case IMP_Compose::REPLY_SENDER:
                $data = $compose->compose->replyMessageText($compose->contents, array(
                    'format' => 'text'
                ));
                $result->text = $data['body'];
                return $result;
            }
        }

        $result->text = $GLOBALS['injector']->getInstance('IMP_Ui_Compose')->convertComposeText($this->vars->text, 'text');

        return $result;
    }

    /**
     * AJAX action: Add an attachment to a compose message.
     *
     * Variables used:
     *   - composeCache: (string) The IMP_Compose cache identifier.
     *
     * @return object  Returns response object to display JSON HTML-encoded.
     *                 Embedded data: false on failure, or an object with the
     *                 following properties:
     *   - atc: (integer) The attachment ID.
     *   - error: (string) An error message.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - success: (integer) 1 on success, 0 on failure.
     */
    public function addAttachment()
    {
        $imp_compose = $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->vars->composeCache);

        $result = new stdClass;
        $result->action = 'addAttachment';
        $result->success = 0;

        if ($GLOBALS['session']->get('imp', 'file_upload') &&
            $imp_compose->addFilesFromUpload('file_')) {
            $ajax_compose = new IMP_Ajax_Application_Compose($imp_compose);
            $result->atc = end($ajax_compose->getAttachmentInfo());
            $result->success = 1;
            $result->imp_compose = $imp_compose->getCacheId();
        }

        return new Horde_Core_Ajax_Response_HordeCore_JsonHtml($result);
    }

    /**
     * AJAX action: Convert text to HTML (compose data).
     *
     * Variables used:
     *   - changed: (integer) Has the text changed from the original?
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - text: (string) The text to convert.
     *
     * @return object  An object with the following entries:
     *   - text: (string) The converted text.
     */
    public function text2Html()
    {
        $result = new stdClass;

        if (!$this->vars->changed) {
            $compose = $this->_base->initCompose();

            switch ($compose->compose->replyType()) {
            case IMP_Compose::FORWARD_BODY:
            case IMP_Compose::FORWARD_BOTH:
                $data = $compose->compose->forwardMessageText($compose->contents, array(
                    'format' => 'html'
                ));
                $result->text = $data['body'];
                return $result;

            case IMP_Compose::REPLY_ALL:
            case IMP_Compose::REPLY_LIST:
            case IMP_Compose::REPLY_SENDER:
                $data = $compose->compose->replyMessageText($compose->contents, array(
                    'format' => 'html'
                ));
                $result->text = $data['body'];
                return $result;
            }
        }

        $result->text = $GLOBALS['injector']->getInstance('IMP_Ui_Compose')->convertComposeText($this->vars->text, 'html');

        return $result;
    }

    /* Protected methods. */

    /**
     * Save a draft composed message.
     *
     * See the list of variables needed for
     * IMP_Ajax_Application#composeSetup(). Additional variables used:
     *   - html: (integer) In HTML compose mode?
     *   - message: (string) The message text.
     *   - priority: (string) The priority of the message.
     *   - request_read_receipt: (boolean) Add request read receipt header?
     *
     * @param string $action  AJAX action.
     *
     * @return object  An object with the following entries:
     *   - action: (string) The AJAX action string
     *   - success: (integer) 1 on success, 0 on failure.
     */
    protected function _draftAction($action)
    {
        try {
            list($result, $imp_compose, $headers, ) = $this->_base->composeSetup();
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);

            $result = new stdClass;
            $result->action = $action;
            $result->success = 0;
            return $result;
        }

        $opts = array(
            'html' => $this->vars->html,
            'priority' => $this->vars->priority,
            'readreceipt' => $this->vars->request_read_receipt
        );

        try {
            switch ($action) {
            case 'saveTemplate':
                $res = $imp_compose->saveTemplate($headers, $this->vars->message, $opts);
                break;

            default:
                $res = $imp_compose->saveDraft($headers, $this->vars->message, $opts);
                break;
            }

            switch ($action) {
            case 'autoSaveDraft':
                $GLOBALS['notification']->push(_("Draft automatically saved."), 'horde.message');
                break;

            case 'saveDraft':
                if ($GLOBALS['prefs']->getValue('close_draft')) {
                    $imp_compose->destroy('save_draft');
                }
                // Fall-through

            default:
                $GLOBALS['notification']->push($res);
                break;
            }
        } catch (IMP_Compose_Exception $e) {
            $result->success = 0;
            $GLOBALS['notification']->push($e);
        }

        return $result;
    }

}
