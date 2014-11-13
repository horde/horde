<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used exclusively in the IMP dynamic view.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_Dynamic
extends Horde_Core_Ajax_Application_Handler
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
     * @return object  Object with the following properties:
     *   - result: (boolean) True if submailboxes can be created.
     */
    public function createMailboxPrepare()
    {
        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);
        $ret = new stdClass;
        $ret->result = true;

        if (!$mbox->access_creatembox) {
            $GLOBALS['notification']->push(sprintf(_("You may not create child mailboxes in \"%s\"."), $mbox->display), 'horde.error');
            $ret->result = false;
        }

        return $ret;
    }

    /**
     * AJAX action: Create a mailbox.
     *
     * Variables used:
     *   - create_poll: (boolean) If true, add new mailbox to poll list.
     *   - mbox: (string) The name of the new mailbox.
     *   - parent: (string) The parent mailbox (base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function createMailbox()
    {
        global $injector, $notification;

        if (!isset($this->vars->mbox)) {
            return false;
        }

        $result = false;

        $parent = isset($this->vars->parent)
            ? IMP_Mailbox::formFrom($this->vars->parent)
            : IMP_Mailbox::get(IMP_Ftree::BASE_ELT);
        $new_mbox = $parent->createMailboxName($this->vars->mbox);

        if ($new_mbox->exists) {
            $notification->push(sprintf(_("Mailbox \"%s\" already exists."), $new_mbox->display), 'horde.warning');
        } elseif ($new_mbox->create()) {
            $result = true;

            if ($this->vars->create_poll) {
                $injector->getInstance('IMP_Ftree')->poll->addPollList($new_mbox);
                $this->_base->queue->poll($new_mbox);
            }
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
     * @return object  Object with the following properties:
     *   - result: (boolean) True if mailbox can be deleted/renamed.
     */
    public function deleteMailboxPrepare()
    {
        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);
        $ret = new stdClass;

        if ($mbox->access_deletembox) {
            $ret->result = true;
            return $ret;
        }

        switch ($this->vars->type) {
        case 'delete':
            $GLOBALS['notification']->push(sprintf(_("You may not delete \"%s\"."), $mbox->display), 'horde.error');
            break;

        case 'rename':
            $GLOBALS['notification']->push(sprintf(_("You may not rename \"%s\"."), $mbox->display), 'horde.error');
            break;
        }

        $ret->result = false;
        return $ret;
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
     *                 If not present, uses old parent.
     *   - old_name: (string) Full name of old mailbox (base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function renameMailbox()
    {
        if (!$this->vars->old_name || !$this->vars->new_name) {
            return false;
        }

        $old_name = IMP_Mailbox::formFrom($this->vars->old_name);
        if (isset($this->vars->new_parent)) {
            $parent = strlen($this->vars->new_parent)
                ? IMP_Mailbox::formFrom($this->vars->new_parent)
                : IMP_Mailbox::get(IMP_Ftree::BASE_ELT);
        } else {
            $parent = IMP_Mailbox::get($old_name->parent);
        }

        if ($parent) {
            $new_name = $parent->createMailboxName($this->vars->new_name);

            if (($old_name != $new_name) && $old_name->rename($new_name)) {
                $this->_base->queue->setMailboxOpt('switch', $new_name->form_to);
                return true;
            }
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
     * @return object  Object with the following properties:
     *   - result: (integer) The number of messages to be deleted.
     */
    public function emptyMailboxPrepare()
    {
        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);
        $res = new stdClass;
        $res->result = 0;

        if (!$mbox->access_empty) {
            $GLOBALS['notification']->push(sprintf(_("The mailbox \"%s\" may not be emptied."), $mbox->display), 'horde.error');
        } else {
            $poll_info = $mbox->poll_info;
            if (!($res->result = $poll_info->msgs)) {
                $GLOBALS['notification']->push(sprintf(_("The mailbox \"%s\" is already empty."), $mbox->display), 'horde.message');
            }
        }

        return $res;
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

        $mbox->emptyMailbox();

        $this->_base->queue->poll($mbox);

        $vp = new IMP_Ajax_Application_Viewport($mbox);
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
        $flags = json_decode($this->vars->flags);
        if (!$this->vars->mbox || empty($flags)) {
            return false;
        }

        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);

        if (!$mbox->flagAll($flags, $this->vars->add)) {
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
     *   - expall: (boolean) 1 to expand all (requires 'all').
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
        global $injector, $prefs, $session;

        $ftree = $injector->getInstance('IMP_Ftree');
        $iterator = new AppendIterator();

        /* This might be a long running operation. */
        if ($this->vars->initial) {
            $session->close();
            $ftree->eltdiff->clear();

            /* @todo: Correctly handle unsubscribed mailboxes in ftree. */
            if ($ftree->unsubscribed_loaded && !$this->vars->reload) {
                $ftree->init();
            }
        }

        if ($this->vars->reload) {
            $ftree->init();
        }

        $filter = new IMP_Ftree_IteratorFilter($ftree);
        if ($this->vars->unsub) {
            $ftree->loadUnsubscribed();
            $filter->remove($filter::UNSUB);
        }

        if (isset($this->vars->base)) {
            $this->_base->queue->setMailboxOpt('base', $this->vars->base);
        }

        if ($this->vars->all) {
            $this->_base->queue->setMailboxOpt('all', 1);
            $iterator->append($filter);
            if ($this->vars->expall) {
                $this->vars->action = 'expand';
                $this->_base->callAction('toggleMailboxes');
            }
        } elseif ($this->vars->initial || $this->vars->reload) {
            $special = new ArrayIterator();
            $special->append($ftree['INBOX']);

            /* Add special mailboxes explicitly to the initial folder list,
             * since they are ALWAYS displayed, may appear outside of the
             * folder slice requested, and need to be sorted logically. */
            $s_elts = array();
            foreach (IMP_Mailbox::getSpecialMailboxesSort() as $val) {
                if (isset($ftree[$val])) {
                    $special->append($val);
                    $s_elts[] = $ftree[$val];
                }
            }
            $iterator->append($special);

            /* Go through and find any parent elements that contain only
             * special mailbox children - this need to be suppressed in
             * display. */
            $filter2 = clone $filter;
            $filter2->add(array($filter2::CONTAINERS, $filter2::SPECIALMBOXES));
            $no_children = array();

            foreach (array_unique($s_elts) as $val) {
                while (($val = $val->parent) && !$val->base_elt) {
                    $filter2->iterator = new IMP_Ftree_Iterator($val);
                    foreach ($filter2 as $val) {
                        /* If we found at least one viewable mailbox, this
                         * element needs its children to be displayed. */
                        break 2;
                    }
                    $no_children[] = strval($val);
                }
            }

            if (!empty($no_children)) {
                $this->_base->queue->ftreeCallback = function($id, $ob) use ($no_children) {
                    if (in_array($id, $no_children)) {
                        unset($ob->ch);
                    }
                };
            }

            /* Add regular mailboxes. */
            $no_mbox = false;

            switch ($prefs->getValue('nav_expanded')) {
            case IMP_Ftree_Prefs_Expanded::NO:
                $filter->add($filter::CHILDREN);
                break;

            case IMP_Ftree_Prefs_Expanded::YES:
                $this->_base->queue->setMailboxOpt('expand', 1);
                $no_mbox = true;
                break;

            case IMP_Ftree_Prefs_Expanded::LAST:
                $filter->add($filter::EXPANDED);
                $this->_base->queue->setMailboxOpt('expand', 1);
                break;
            }

            $filter->mboxes = array('INBOX');
            $iterator->append($filter);

            if (!$no_mbox) {
                $mboxes = IMP_Mailbox::formFrom(json_decode($this->vars->mboxes));
                foreach ($mboxes as $val) {
                    if (!$val->inbox) {
                        $ancestors = new IMP_Ftree_IteratorFilter(
                            new IMP_Ftree_Iterator_Ancestors($val->tree_elt)
                        );
                        if ($this->vars->unsub) {
                            $ancestors->remove($ancestors::UNSUB);
                        }
                        $iterator->append($ancestors);
                    }
                }
            }
        } else {
            $filter->add($filter::EXPANDED);
            $this->_base->queue->setMailboxOpt('expand', 1);

            foreach (array_filter(IMP_Mailbox::formFrom(json_decode($this->vars->mboxes))) as $val) {
                $filter->iterator = new IMP_Ftree_Iterator($val->tree_elt);
                $iterator->append($filter);
                $ftree->expand($val);
            }
        }

        array_map(
            array($ftree->eltdiff, 'add'),
            array_unique(iterator_to_array($iterator, false))
        );

        if ($this->vars->initial) {
            $session->start();

            /* We need at least 1 changed mailbox. If not, something went
             * wrong and we should reinitialize the folder list. */
            if (!$ftree->eltdiff->changed_elts) {
                $this->vars->reload = true;
                $this->listMailboxes();
                $this->vars->reload = false;
            }
        }

        return true;
    }

    /**
     * AJAX action: Initialize dynamic view.
     *
     * @see IMP_Ajax_Application_Handler_Common#viewPort()
     * @see listMailboxes()
     *
     * @return boolean  True.
     */
    public function dynamicInit()
    {
        $this->_base->callAction('viewPort');

        $this->vars->initial = 1;
        $this->vars->mboxes = json_encode(array($this->vars->mailbox));
        $this->listMailboxes();

        $this->_base->queue->flagConfig(Horde_Registry::VIEW_DYNAMIC);

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
        global $injector;

        if (!$this->vars->mbox) {
            return false;
        }

        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);

        $result = new stdClass;
        $result->add = intval($this->vars->add);
        $result->mbox = $this->vars->mbox;

        if ($this->vars->add) {
            $injector->getInstance('IMP_Ftree')->poll->addPollList($mbox);
            $this->_base->queue->poll($mbox);
            $GLOBALS['notification']->push(sprintf(_("\"%s\" mailbox now polled for new mail."), $mbox->display), 'horde.success');
        } else {
            $injector->getInstance('IMP_Ftree')->poll->removePollList($mbox);
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
     *   - subfolders: (boolean) [Un]subscribe to all subfolders?
     *
     * @return boolean  True on success, false on failure.
     */
    public function subscribe()
    {
        return IMP_Mailbox::formFrom($this->vars->mbox)->subscribe($this->vars->sub, array(
            'subfolders' => !empty($this->vars->subfolders)
        ));
    }

    /**
     * AJAX action: Import a mailbox.
     *
     * Variables used:
     *   - import_mbox: (string) The mailbox to import into (base64url
     *                  encoded).
     *
     * @return object  Returns response object to display JSON HTML-encoded:
     *   - action: (string) The action name (importMailbox).
     *   - mbox: (string) The mailbox the messages were imported to (base64url
     *           encoded).
     */
    public function importMailbox()
    {
        global $injector, $notification;

        $mbox = IMP_Mailbox::formFrom($this->vars->import_mbox);

        try {
            $notification->push($injector->getInstance('IMP_Mbox_Import')->import($mbox, 'import_file'), 'horde.success');
            $this->_base->queue->poll($mbox);
        } catch (Horde_Exception $e) {
            $notification->push($e);
        }

        $result = new stdClass;
        $result->action = 'importMailbox';
        $result->mbox = $this->vars->import_mbox;

        return new Horde_Core_Ajax_Response_HordeCore_JsonHtml($result);
    }

    /**
     * AJAX action: Flag messages.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed() and
     * IMP_Ajax_Application#checkUidvalidity(). Mailbox/indices form
     * parameters needed. Additional variables used:
     *   - add: (integer) Set the flag?
     *   - flags: (string) The flags to set (JSON serialized array).
     *
     * @return boolean  True on success, false on failure.
     */
    public function flagMessages()
    {
        global $injector;

        if (!$this->vars->flags || !count($this->_base->indices)) {
            return false;
        }

        $change = $this->_base->changed(true);

        if (is_null($change)) {
            return false;
        }

        $flags = json_decode($this->vars->flags);

        /* Check for non-system flags. If we find any, and the server supports
         * CONDSTORE, we should make sure that these flags are only updated if
         * nobody else has altered the flags. */
        $system_flags = array(
            Horde_Imap_Client::FLAG_ANSWERED,
            Horde_Imap_Client::FLAG_DELETED,
            Horde_Imap_Client::FLAG_DRAFT,
            Horde_Imap_Client::FLAG_FLAGGED,
            Horde_Imap_Client::FLAG_RECENT,
            Horde_Imap_Client::FLAG_SEEN
        );

        $unchangedsince = null;
        if (!$this->_base->indices->mailbox->search &&
            $this->vars->viewport->cacheid &&
            array_diff($flags, $system_flags)) {
            $imp_imap = $this->_base->indices->mailbox->imp_imap;
            $parsed = $imp_imap->parseCacheId($this->vars->viewport->cacheid);

            try {
                $unchangedsince[strval($this->_base->indices->mailbox)] = $imp_imap->sync($this->_base->indices->mailbox, $parsed['token'], array(
                    'criteria' => Horde_Imap_Client::SYNC_UIDVALIDITY
                ))->highestmodseq;
            } catch (Horde_Imap_Client_Exception_Sync $e) {}
        }

        $res = $injector->getInstance('IMP_Message')->flag(array(
            ($this->vars->add ? 'add' : 'remove') => $flags
        ), $this->_base->indices, array(
            'unchangedsince' => $unchangedsince
        ));

        if (!$res) {
            $this->_base->checkUidvalidity();
            return false;
        }

        if (in_array(Horde_Imap_Client::FLAG_SEEN, $flags)) {
            $this->_base->queue->poll(array_keys($this->_base->indices->indices()));
        }

        $this->_base->addTask('viewport', $change ? $this->_base->viewPortData(true) : new IMP_Ajax_Application_Viewport($this->_base->indices->mailbox));

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

        $ob = $injector->getInstance('IMP_Dynamic_AddressList')->parseAddressList($this->vars->addr)->first();

        // TODO: Currently supports only a single, non-group contact.
        if (!$ob) {
            return false;
        } elseif ($ob instanceof Horde_Mail_Rfc822_Group) {
            $notification->push(_("Adding group lists not currently supported."), 'horde.warning');
            return false;
        }

        try {
            $injector->getInstance('IMP_Contacts')->addAddress($ob->bare_address, $ob->personal);
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
     * IMP_Ajax_Application#checkUidvalidity(). Mailbox/indices form
     * parameters needed. Additional variables used:
     *   - blacklist: (integer) 1 to blacklist, 0 to whitelist.
     *
     * @return boolean  True on success.
     */
    public function blacklist()
    {
        if (!count($this->_base->indices)) {
            return false;
        }

        if ($this->vars->blacklist) {
            $change = $this->_base->changed(false);
            if (!is_null($change)) {
                try {
                    if ($GLOBALS['injector']->getInstance('IMP_Filter')->blacklistMessage($this->_base->indices, false)) {
                        $this->_base->deleteMsgs($this->_base->indices, $change);
                        return true;
                    }
                } catch (Horde_Exception $e) {
                    $this->_base->checkUidvalidity();
                }
            }
        } else {
            try {
                $GLOBALS['injector']->getInstance('IMP_Filter')->whitelistMessage($this->_base->indices, false);
                return true;
            } catch (Horde_Exception $e) {
                $this->_base->checkUidvalidity();
            }
        }

        return false;
    }

    /**
     * AJAX action: Return the MIME tree representation of the message.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed() and
     * IMP_Ajax_Application#checkUidvalidity(). Mailbox/indices form
     * parameters needed. Additional variables used:
     *   - preview: (integer) If set, return preview data. Otherwise, return
     *              full data.
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
            $imp_contents = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($this->_base->indices);
            $result->tree = $imp_contents->getTree()->getTree(true);
        } catch (IMP_Exception $e) {
            if (!$this->vars->preview) {
                throw $e;
            }

            $result->preview->error = $e->getMessage();
            $result->preview->errortype = 'horde.error';
            $result->preview->buid = $this->vars->buid;
            $result->preview->view = $this->vars->view;
        }

        return $result;
    }

    /**
     * AJAX action: Return a list of address objects used to build an
     * address header for a message.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed() and
     * IMP_Ajax_Application#checkUidvalidity(). Mailbox/indices form
     * parameters needed. Additional variables used:
     *   - header: (string) The header to return.
     *
     * @return object  An object with the following entries:
     *   - hdr_data: (object) Contains header names as keys and lists of
     *               address objects as values.
     */
    public function addressHeader()
    {
        $show_msg = new IMP_Ajax_Application_ShowMessage($this->_base->indices);

        $hdr = $this->vars->header;

        $result = new stdClass;
        $result->hdr_data->$hdr = (object)$show_msg->getAddressHeader($hdr, null);

        return $result;
    }

    /**
     * AJAX action: Return the inline display text for a given MIME ID of
     * a message.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed() and
     * IMP_Ajax_Application#checkUidvalidity(). Mailbox/indices form
     * parameters needed. Additional variables used:
     *   - mimeid: (string) The MIME ID to return.
     *
     * @return object  An object with the following entries:
     * <pre>
     *   - buid: (integer) BUID of message.
     *   - mbox: (string) Mailbox of message (base64url encoded).
     *   - mimeid: (string) The base MIME ID of the text.
     *   - text: (string) Inline Message text of the part.
     * </pre>
     */
    public function inlineMessageOutput()
    {
        $result = new stdClass;

        $show_msg = new IMP_Ajax_Application_ShowMessage($this->_base->indices);
        $msg_output = $show_msg->getInlineOutput($this->vars->mimeid);

        list($mbox,) = $this->_base->indices->getSingle();

        $result = new stdClass;
        $result->buid = $this->vars->buid;
        $result->mbox = $mbox->form_to;
        $result->mimeid = $this->vars->mimeid;
        $result->text = $msg_output['msgtext'];

        return $result;
    }

    /**
     * AJAX action: Delete an attachment from compose data.
     *
     * Variables used:
     *   - atc_indices: (string) [JSON array] Attachment IDs to delete.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - quiet: (boolean) If true, don't output notifications.
     *
     * @return array  The list of attchment IDs that were deleted.
     */
    public function deleteAttach()
    {
        global $injector, $notification;

        $result = array();

        if (isset($this->vars->atc_indices)) {
            $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($this->vars->imp_compose);
            foreach (json_decode($this->vars->atc_indices) as $val) {
                if (isset($imp_compose[$val])) {
                    if (empty($this->vars->quiet)) {
                        $notification->push(sprintf(_("Deleted attachment \"%s\"."), Horde_Mime::decode($imp_compose[$val]->getPart()->getName(true))), 'horde.success');
                    }
                    unset($imp_compose[$val]);
                    $result[] = $val;
                    $this->_base->queue->compose($imp_compose);
                }
            }
        }

        if (empty($result) && empty($this->vars->quiet)) {
            $notification->push(_("At least one attachment could not be deleted."), 'horde.error');
        }

        return $result;
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
        $change = $this->_base->changed(true);
        if (is_null($change)) {
            return false;
        }

        if (!$change) {
            $change = ($this->_base->indices->mailbox->getSort()->sortby == Horde_Imap_Client::SORT_THREAD);
        }

        $expunged = $this->_base->indices->mailbox->expunge(null, array(
            'list' => true
        ));

        if (!($expunge_count = count($expunged))) {
            return false;
        }

        $GLOBALS['notification']->push(sprintf(ngettext("%d message was purged from \"%s\".", "%d messages were purged from \"%s\".", $expunge_count), $expunge_count, $this->_base->indices->mailbox->display), 'horde.success');

        $indices = new IMP_Indices_Mailbox();
        $indices->buids = $this->_base->indices->mailbox->toBuids($expunged);
        $indices->mailbox = $this->_base->indices->mailbox;
        $indices->indices = $expunged;

        $this->_base->deleteMsgs($indices, $change, true);
        $this->_base->queue->poll($this->_base->indices->mailbox);

        return true;
    }

    /**
     * AJAX action: Send a Message Disposition Notification (MDN).
     *
     * Mailbox/indices form parameters needed.
     *
     * @return mixed  False on failure, or an object with these properties:
     *   - buid: (integer) BUID of message.
     *   - mbox: (string) Mailbox of message (base64url encoded).
     */
    public function sendMDN()
    {
        global $injector, $notification;

        if (count($this->_base->indices) != 1) {
            return false;
        }

        try {
            $contents = $injector->getInstance('IMP_Factory_Contents')->create($this->_base->indices);
        } catch (IMP_Imap_Exception $e) {
            $e->notify(_("The Message Disposition Notification was not sent. This is what the server said") . ': ' . $e->getMessage());
            return false;
        }

        list($mbox, $uid) = $this->_base->indices->getSingle();
        $injector->getInstance('IMP_Message_Ui')->MDNCheck(
            new IMP_Indices($mbox, $uid),
            $contents->getHeaderAndMarkAsSeen(),
            true
        );

        $notification->push(_("The Message Disposition Notification was sent successfully."), 'horde.success');

        $result = new stdClass;
        $result->buid = $this->_base->vars->buid;
        $result->mbox = $mbox->form_to;

        return $result;
    }

    /**
     * AJAX action: strip attachment.
     *
     * See the list of variables needed for IMP_Ajax_Application#changed() and
     * IMP_Ajax_Application#checkUidvalidity(). Mailbox/indices form
     * parameters needed.
     *
     * @return mixed  False on failure, or an object with these properties:
     *   - newbuid: (integer) BUID of new message.
     *   - newmbox: (string) Mailbox of new message (base64url encoded).
     */
    public function stripAttachment()
    {
        global $injector, $notification;

        if (count($this->_base->indices) != 1) {
            return false;
        }

        $change = $this->_base->changed(true);
        if (is_null($change)) {
            return false;
        }

        try {
            $this->_base->indices = new IMP_Indices_Mailbox(
                $this->_base->indices->mailbox,
                $injector->getInstance('IMP_Message')->stripPart($this->_base->indices, $this->vars->id)
            );
        } catch (IMP_Exception $e) {
            $notification->push($e);
            return false;
        }

        $notification->push(_("Attachment successfully stripped."), 'horde.success');

        $result = new stdClass;
        list($result->newmbox, $result->newbuid) = $this->_base->indices->getSingle();
        $result->newmbox = $result->newmbox->form_to;

        $this->_base->queue->message($this->_base->indices, true);
        $this->_base->addTask('viewport', $this->_base->viewPortData(true));

        return $result;
    }

    /**
     * AJAX action: Convert HTML to text (compose data).
     *
     * Variables used:
     *   - data: (string) [JSON array] List of data to convert. Keys are UIDs
     *           used to idetify the return values. Values are arrays with
     *           these keys:
     *     - changed: (integer) Has the text changed from the original?
     *     - text: (string) The text to convert.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *
     * @return object  An object with the following entries:
     *   - text: (array) Array with keys as UIDs and values as the converted
     *           text string.
     */
    public function html2Text()
    {
        return $this->_convertText('text');
    }

    /**
     * AJAX action: Convert text to HTML (compose data).
     *
     * Variables used:
     *   - data: (string) [JSON array] List of data to convert. Keys are UIDs
     *           used to idetify the return values. Values are arrays with
     *           these keys:
     *     - changed: (integer) Has the text changed from the original?
     *     - text: (string) The text to convert.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *
     * @return object  An object with the following entries:
     *   - text: (array) Array with keys as UIDs and values as the converted
     *           text string.
     */
    public function text2Html()
    {
        return $this->_convertText('html');
    }

    /**
     * Helper for html2Text() and text2Html().
     *
     * @internal
     */
    protected function _convertText($mode)
    {
        global $injector;

        $compose = null;

        $result = new stdClass;
        $result->text = array();

        foreach (json_decode($this->vars->data, true) as $key => $val) {
            $tmp = null;

            if (empty($val['changed'])) {
                if (!$compose) {
                    $compose = $this->_base->initCompose();
                }

                switch ($compose->compose->replyType()) {
                case IMP_Compose::FORWARD_BODY:
                case IMP_Compose::FORWARD_BOTH:
                    $data = $compose->compose->forwardMessageText($compose->contents, array(
                        'format' => $mode
                    ));
                    $tmp = $data['body'];
                    break;

                case IMP_Compose::REPLY_ALL:
                case IMP_Compose::REPLY_LIST:
                case IMP_Compose::REPLY_SENDER:
                    $data = $compose->compose->replyMessageText($compose->contents, array(
                        'format' => $mode
                    ));
                    $tmp = $data['body'];
                    break;
                }
            }

            if (is_null($tmp)) {
                switch ($mode) {
                case 'html':
                    $tmp = IMP_Compose::text2html($val['text']);
                    break;

                case 'text':
                    $tmp = $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($val['text'], 'Html2text', array(
                        'wrap' => false
                    ));
                    break;
                }
            }

            $result->text[$key] = $tmp;
        }

        return $result;
    }

    /**
     * AJAX action: Add an attachment to a compose message (from the ckeditor
     * plugin).
     *
     * Variables used:
     *   - CKEditorFuncNum: (integer) CKEditor function identifier to call
     *                      when returning URL data
     *   - composeCache: (string) The IMP_Compose cache identifier.
     *
     * @return Horde_Core_Ajax_Response_Raw  text/html return containing
     *                                       javascript code to update the
     *                                       URL parameter in CKEditor.
     */
    public function addAttachmentCkeditor()
    {
        global $injector;

        $data = $url = null;

        if (isset($this->vars->composeCache)) {
            $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($this->vars->composeCache);

            if ($imp_compose->canUploadAttachment()) {
                try {
                    $atc_ob = $imp_compose->addAttachmentFromUpload('upload');
                    if ($atc_ob[0] instanceof IMP_Compose_Exception) {
                        throw $atc_ob[0];
                    }

                    $atc_ob[0]->related = true;

                    $data = array(
                        IMP_Compose::RELATED_ATTR => 'src;' . $atc_ob[0]->id
                    );
                    $url = strval($atc_ob[0]->viewUrl());
                } catch (IMP_Compose_Exception $e) {
                    $data = $e->getMessage();
                }
            } else {
                $data = _("Uploading attachments has been disabled on this server.");
            }
        } else {
            $data = _("Your attachment was not uploaded. Most likely, the file exceeded the maximum size allowed by the server configuration.");
        }

        return new Horde_Core_Ajax_Response_Raw(
            '<html>' .
                Horde::wrapInlineScript(array(
                    'window.parent.CKEDITOR.tools.callFunction(' . $this->vars->CKEditorFuncNum . ',' . json_encode($url) . ',' . json_encode($data) . ')'
                )) .
            '</html>',
            'text/html'
        );
    }

    /**
     * AJAX action: Is the given mailbox fixed? Called dynamically to delay
     * retrieval of ACLs of all visible mailboxes at initialization.
     *
     * Variables used:
     *   - mbox: (integer) The mailbox name.
     *
     * @return object  An object with the following entires:
     *   - fixed: (boolean) True if the mailbox is fixed.
     */
    public function isFixedMbox()
    {
        $result = new stdClass;
        $result->fixed = !(IMP_Mailbox::formFrom($this->vars->mbox)->access_deletembox);
        return $result;
    }

    /**
     * AJAX action: Create an IMAP flag.
     *
     * Variables used:
     *   - flagcolor: (string) Background color for flag label.
     *   - flagname: (string) Flag name.
     *
     * @return object  An object with the following properties:
     *   - success: (boolean) True if successful.
     */
    public function createFlag()
    {
        global $injector, $notification;

        $ret = new stdClass;
        $ret->success = true;

        $imp_flags = $injector->getInstance('IMP_Flags');

        try {
            $imapflag = $imp_flags->addFlag($this->vars->flagname);
        } catch (IMP_Exception $e) {
            $notification->push($e, 'horde.error');
            $ret->success = false;
            return $ret;
        }

        if (!empty($this->vars->flagcolor)) {
            $imp_flags->updateFlag($this->vars->flagname, 'bgcolor', $this->vars->flagcolor);
        }

        $this->vars->add = true;
        $this->vars->flags = json_encode(array($imapflag));
        $this->flagMessages();

        $this->_base->queue->flagConfig(Horde_Registry::VIEW_DYNAMIC);

        $name = 'imp:viewport';
        if ($this->_base->tasks->$name) {
            $this->_base->tasks->$name->addFlagMetadata();
        }

        return $ret;
    }

    /**
     * AJAX action: Generate the sent-mail select list.
     *
     * Variables used: NONE
     *
     * @return object  An object with the following properties:
     *   - flist: (array) TODO
     */
    public function sentMailList()
    {
        global $injector;

        /* Check to make sure the sent-mail mailboxes are created; they need
         * to exist to show up in drop-down list. */
        $identity = $injector->getInstance('IMP_Identity');
        foreach ($identity->getAllSentmail() as $mbox) {
            $mbox->create();
        }

        $flist = array();
        $iterator = new IMP_Ftree_IteratorFilter($injector->getInstance('IMP_Ftree'));
        $iterator->add($iterator::NONIMAP);

        foreach ($iterator as $val) {
            $mbox_ob = $val->mbox_ob;
            $tmp = array(
                'f' => $mbox_ob->display,
                'l' => Horde_String::abbreviate(str_repeat(' ', 2 * $val->level) . $mbox_ob->basename, 30),
                'v' => $val->container ? '' : $mbox_ob->form_to
            );
            if ($tmp['f'] == $tmp['v']) {
                unset($tmp['f']);
            }
            $flist[] = $tmp;
        }

        $ret = new stdClass;
        $ret->flist = $flist;

        return $ret;
    }

    /**
     * AJAX action: Redirect to the filter edit page and pre-populate with
     * an e-mail address.
     *
     * Requires EITHER 'addr' -or- mailbox/indices from form params.
     *
     * Variables used:
     *   - addr: (string) The e-mail address to use.
     *
     * @return Horde_Core_Ajax_Response_HordeCore_Reload  Object with URL to
     *                                                    redirect to.
     */
    public function newFilter()
    {
        global $injector, $notification, $registry;

        if (isset($this->vars->addr)) {
            $ob = $injector->getInstance('IMP_Dynamic_AddressList')->parseAddressList($this->vars->addr)->first();
        } else {
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->envelope();

            $imp_imap = $this->_base->indices->mailbox->imp_imap;
            list($mbox, $uid) = $this->_base->indices->getSingle();
            $ret = $imp_imap->fetch($mbox, $query, array(
                'ids' => $imp_imap->getIdsOb($uid)
            ));

            $ob = $ret[$uid]->getEnvelope()->from->first();
        }

        // TODO: Currently supports only a single, non-group contact.
        if (!$ob) {
            return false;
        } elseif ($ob instanceof Horde_Mail_Rfc822_Group) {
            $notification->push(_("Editing group lists not currently supported."), 'horde.warning');
            return false;
        }

        try {
            return new Horde_Core_Ajax_Response_HordeCore_Reload(
                $registry->link('mail/newEmailFilter', array(
                    'email' => $ob->bare_address
                ))
            );
        } catch (Horde_Exception $e) {
            return false;
        }
    }

    /**
     * AJAX action: Return the contacts images for a given e-mail address.
     *
     * Variables used:
     *   - addr: (string) The e-mail address.
     *
     * @return object  An object with the following properties:
     *   - avatar: (string) The URL of the avatar image.
     *   - flag: (string) The URL of the sender's country flag image.
     *   - flagname: (string) The name of the country of the sender.
     */
    public function getContactsImage()
    {
        $contacts_img = new IMP_Contacts_Image($this->vars->addr);
        $out = new stdClass;

        try {
            $res = $contacts_img->getImage($contacts_img::AVATAR);
            $out->avatar = strval($res['url']);
        } catch (IMP_Exception $e) {}

        try {
            $res = $contacts_img->getImage($contacts_img::FLAG);
            $out->flag = strval($res['url']);
            $out->flagname = $res['desc'];
        } catch (IMP_Exception $e) {}

        return $out;
    }

    /**
     * AJAX action: Determine the size of a mailbox.
     *
     * Variables used:
     *   - mbox: (string) The name of the mailbox to check (base64url
     *           encoded).
     *
     * @return object  An object with the following properties:
     *   - size: (string) Formatted size string.
     */
    public function mailboxSize()
    {
        $mbox = IMP_Mailbox::formFrom($this->vars->mbox);

        $ret = new stdClass;
        $ret->size = $mbox->size;

        return $ret;
    }

    /**
     * AJAX Action: Do an autocomplete search.
     *
     * Variables used:
     *   - limit: (integer) If set, limits to this many results.
     *   - search: (string) Search string.
     *   - type: (string) Autocomplete search type.
     *
     * @return object  An object with a single property: 'results'.
     *                 The format of 'results' depends on the search type.
     *   - type = 'email'
     *     Results is an array with the following keys for each result:
     *     - g: (array) List of addresses in the group (in same 'results'
     *          format as type = 'email').
     *     - l: (string) Full label.
     *     - s: (string) Short display string.
     *     - v: (string) Value.
     */
    public function autocompleteSearch()
    {
        $out = new stdClass;
        $out->results = array();

        switch ($this->vars->type) {
        case 'email':
            $addr = $GLOBALS['injector']->getInstance('IMP_Contacts')->searchEmail(
                $this->vars->search,
                array('levenshtein' => true)
            );

            $out->results = $this->_autocompleteSearchEmail(
                $addr,
                $this->vars->limit
            );
            break;
        }

        return $out;
    }

    /**
     * Creates the output list for the 'email' autocomplete search.
     *
     * @param Horde_Mail_Rfc822_List $alist  Address list.
     * @param integer $limit                 Limit to this many entries.
     *
     * @return array  See autocompleteSearch().
     */
    protected function _autocompleteSearchEmail(
        Horde_Mail_Rfc822_List $alist, $limit = 0
    )
    {
        $i = 0;
        $limit = intval($limit);
        $out = array();

        foreach ($alist as $val) {
            $tmp = array('v' => strval($val));
            $l = $val->writeAddress(array('noquote' => true));
            $s = $val->label;

            if ($l !== $tmp['v']) {
                $tmp['l'] = $l;
            }

            if ($val instanceof Horde_Mail_Rfc822_Group) {
                $tmp['g'] = $this->_autocompleteSearchEmail($val->addresses);
                $tmp['s'] = sprintf(
                    _("%s [%d addresses]"),
                    $s,
                    count($val)
                );
            } elseif ($s !== $tmp['v']) {
                $tmp['s'] = $s;
            }

            $out[] = $tmp;

            if ($limit && (++$i > $limit)) {
                break;
            }
        }

        return $out;
    }

}
