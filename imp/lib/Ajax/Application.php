<?php
/**
 * Defines the AJAX interface for IMP.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     * Determines if notification information is sent in response.
     *
     * @var boolean
     */
    public $notify = true;

    /**
     * The mailbox (view) we are dealing with on the browser.
     *
     * @var IMP_Mailbox
     */
    protected $_mbox;

    /**
     * Queue object.
     *
     * @var IMP_Ajax_Queue
     */
    protected $_queue;

    /**
     * The list of actions that require readonly access to the session.
     *
     * @var array
     */
    protected $_readOnly = array(
        'html2Text', 'text2Html'
    );

    /**
     */
    public function __construct($app, $vars, $action = null)
    {
        parent::__construct($app, $vars, $action);

        $this->_queue = $GLOBALS['injector']->getInstance('IMP_Ajax_Queue');

        /* Bug #10462: 'view' POST parameter is base64url encoded to
         * workaround suhosin. */
        if (isset($vars->view)) {
            $this->_mbox = IMP_Mailbox::formFrom($vars->view);
        }
    }

    /**
     * Determines the HTTP response output type.
     *
     * @see Horde_Core_Ajax_Response::send().
     *
     * @return string  The output type.
     */
    public function responseType()
    {
        switch ($this->_action) {
        case 'addAttachment':
        case 'importMailbox':
            return 'js-json';
        }

        return parent::responseType();
    }

    /**
     */
    public function send()
    {
        $this->getTasks();
        parent::send();
    }

    /**
     * Get the list of tasks.
     *
     * @return array  Task list.
     */
    public function getTasks()
    {
        $this->_queue->add($this);
        return $this->tasks;
    }

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
        $mbox = IMP_Mailbox::formFrom($this->_vars->mbox);

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
        if (!isset($this->_vars->mbox)) {
            return false;
        }

        $result = false;

        try {
            $new_mbox = $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->createMailboxName(
                isset($this->_vars->parent) ? IMP_Mailbox::formFrom($this->_vars->parent) : '',
                $this->_vars->mbox
            );

            if ($new_mbox->exists) {
                $GLOBALS['notification']->push(sprintf(_("Mailbox \"%s\" already exists."), $new_mbox->display), 'horde.warning');
            } elseif ($new_mbox->create()) {
                $result = true;
                if (isset($this->_vars->parent) && $this->_vars->noexpand) {
                    $this->_queue->setMailboxOpt('noexpand', 1);
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
        $mbox = IMP_Mailbox::formFrom($this->_vars->mbox);

        if ($mbox->access_deletembox) {
            return true;
        }

        switch ($this->_vars->type) {
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
        return ($this->_vars->mbox && IMP_Mailbox::formFrom($this->_vars->mbox)->delete(array('subfolders' => !empty($this->_vars->subfolders), 'subfolders_only' => !empty($this->_vars->container))));
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
        if (!$this->_vars->old_name || !$this->_vars->new_name) {
            return false;
        }

        $result = false;

        try {
            $new_name = $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->createMailboxName(
                isset($this->_vars->new_parent) ? IMP_Mailbox::formFrom($this->_vars->new_parent) : '',
                $this->_vars->new_name
            );

            $old_name = IMP_Mailbox::formFrom($this->_vars->old_name);

            if (($old_name != $new_name) && $old_name->rename($new_name)) {
                $result = true;
                $this->_queue->poll($new_name);
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
        }

        return $result;
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
        $mbox = IMP_Mailbox::formFrom($this->_vars->mbox);

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
        if (!$this->_vars->mbox) {
            return false;
        }

        $mbox = IMP_Mailbox::formFrom($this->_vars->mbox);

        $GLOBALS['injector']->getInstance('IMP_Message')->emptyMailbox(array($mbox));

        $this->_queue->poll($mbox);

        $vp = $this->_viewPortOb($mbox);
        $vp->data_reset = 1;
        $vp->rowlist_reset = 1;
        $this->addTask('viewport', $vp);

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
        $flags = Horde_Serialize::unserialize($this->_vars->flags, Horde_Serialize::JSON);
        if (!$this->_vars->mbox || empty($flags)) {
            return false;
        }

        $mbox = IMP_Mailbox::formFrom($this->_vars->mbox);

        if (!$GLOBALS['injector']->getInstance('IMP_Message')->flagAllInMailbox($flags, array($mbox), $this->_vars->add)) {
            return false;
        }

        $this->_queue->poll($mbox);

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
        if ($this->_vars->initial) {
            $GLOBALS['session']->close();
        }

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $initreload = ($this->_vars->initial || $this->_vars->reload);

        $mask = IMP_Imap_Tree::FLIST_VFOLDER | IMP_Imap_Tree::FLIST_NOSPECIALMBOXES;
        if ($this->_vars->unsub) {
            $mask |= IMP_Imap_Tree::FLIST_UNSUB;
        }

        if (isset($this->_vars->base)) {
            $this->_queue->setMailboxOpt('base', $this->_vars->base);
        }

        if ($this->_vars->all) {
            $this->_queue->setMailboxOpt('all', 1);
        } else {
            if ($initreload) {
                $mask |= IMP_Imap_Tree::FLIST_ANCESTORS | IMP_Imap_Tree::FLIST_SAMELEVEL;
                if ($GLOBALS['prefs']->getValue('nav_expanded')) {
                    $this->_queue->setMailboxOpt('expand', 1);
                    $mask |= IMP_Imap_Tree::FLIST_EXPANDED;
                } else {
                    $mask |= IMP_Imap_Tree::FLIST_NOCHILDREN;
                }
            } else {
                $this->_queue->setMailboxOpt('expand', 1);
                $mask |= IMP_Imap_Tree::FLIST_EXPANDED | IMP_Imap_Tree::FLIST_NOBASE;
            }
        }

        if ($this->_vars->reload) {
            $imptree->init();
        }

        $imptree->showUnsubscribed($this->_vars->unsub);

        if (!empty($this->_vars->mboxes)) {
            $mboxes = IMP_Mailbox::formFrom(Horde_Serialize::unserialize($this->_vars->mboxes, Horde_Serialize::JSON));
            if ($initreload) {
                $mboxes = array_merge(array('INBOX'), array_diff($mboxes, array('INBOX')));
            }

            foreach ($mboxes as $val) {
                $imptree->setIteratorFilter($mask, $val);
                foreach ($imptree as $val2) {
                    $imptree->addEltDiff($val2);
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
            $this->_queue->poll($imptree->getPollList());
        }

        $this->_queue->quota();

        if ($this->_vars->initial) {
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
        if (!empty($this->_vars->mboxes)) {
            $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

            foreach (Horde_Serialize::unserialize($this->_vars->mboxes, Horde_Serialize::JSON) as $val) {
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

        if ($this->_vars->all) {
            $imptree->collapseAll();
        } elseif (!empty($this->_vars->mboxes)) {
            foreach (Horde_Serialize::unserialize($this->_vars->mboxes, Horde_Serialize::JSON) as $val) {
                $imptree->collapse(IMP_Mailbox::formFrom($val));
            }
        }

        return true;
    }

    /**
     * AJAX action: Poll mailboxes.
     *
     * See the list of variables needed for _changed() and _viewPortData().
     * Additional variables used:
     *   - mboxes: (string) The list of mailboxes to process (JSON encoded
     *             array; mailboxes are base64url encoded).
     *
     * @return boolean  True.
     */
    public function poll()
    {
        if (empty($this->_vars->mboxes)) {
            $this->_queue->poll($GLOBALS['injector']->getInstance('IMP_Imap_Tree')->getPollList());
        } else {
            $this->_queue->poll(IMP_Mailbox::formFrom(Horde_Serialize::unserialize($this->_vars->mboxes, Horde_Serialize::JSON)));
        }

        $this->_queue->quota();

        if ($this->_mbox && $this->_changed()) {
            $this->addTask('viewport', $this->_viewPortData(true));
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
        if (!$this->_vars->mbox) {
            return false;
        }

        $mbox = IMP_Mailbox::formFrom($this->_vars->mbox);

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

        $result = new stdClass;
        $result->add = intval($this->_vars->add);
        $result->mbox = $this->_vars->mbox;

        if ($this->_vars->add) {
            $imptree->addPollList($mbox);
            $this->_queue->poll($mbox);
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
            ? IMP_Mailbox::formFrom($this->_vars->mbox)->subscribe($this->_vars->sub)
            : false;
    }

    /**
     * AJAX action: Import a mailbox.
     *
     * Variables used:
     *   - import_mbox: (string) The mailbox to import into (base64url
     *                  encoded).
     *
     * @return object  False on failure, or an object with the following
     *                 properties:
     *   - action: (string) The action name (importMailbox).
     *   - mbox: (string) The mailbox the messages were imported to (base64url
     *           encoded).
     */
    public function importMailbox()
    {
        global $injector, $notification;

        $mbox = IMP_Mailbox::formFrom($this->_vars->import_mbox);

        try {
            $notification->push($injector->getInstance('IMP_Ui_Folder')->importMbox($mbox, 'import_file'), 'horde.success');
        } catch (Horde_Exception $e) {
            $notification->push($e);
            return false;
        }

        $result = new stdClass;
        $result->action = 'importMailbox';
        $result->mbox = $this->_vars->import_mbox;

        $this->_queue->poll($mbox);

        return $result;
    }

    /**
     * AJAX action: Output ViewPort data.
     *
     * See the list of variables needed for _changed() and _viewPortData().
     * Additional variables used:
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
        if (!$this->_mbox) {
            return false;
        }

        /* Change sort preferences if necessary. */
        if (isset($this->_vars->sortby) || isset($this->_vars->sortdir)) {
            $this->_mbox->setSort($this->_vars->sortby, $this->_vars->sortdir);
        }

        /* Toggle hide deleted preference if necessary. */
        if (isset($this->_vars->delhide)) {
            $this->_mbox->setHideDeletedMsgs($this->_vars->delhide);
        }

        $changed = $this->_changed(true);

        if (is_null($changed)) {
            $list_msg = new IMP_Views_ListMessages();
            $vp = $list_msg->getBaseOb($this->_mbox);

            $req_id = $this->_vars->requestid;
            if (!is_null($req_id)) {
                $vp->requestid = intval($req_id);
            }

            $this->addTask('viewport', $vp);
            return true;
        }

        $this->_queue->poll($this->_mbox);

        if ($changed ||
            $this->_vars->rangeslice ||
            !$this->_vars->checkcache) {
            /* Ticket #7422: Listing messages may be a long-running operation
             * so close the session while we are doing it to prevent
             * deadlocks. */
            $GLOBALS['session']->close();

            $vp = $this->_viewPortData($changed);

            /* Reopen the session. */
            $GLOBALS['session']->start();

            if (isset($this->_vars->delhide)) {
                $vp->metadata_reset = 1;
            }

            $this->addTask('viewport', $vp);
            return true;
        }

        return false;
    }

    /**
     * AJAX action: Move messages.
     *
     * See the list of variables needed for _changed(), _deleteMsgs(),
     * and _checkUidvalidity(). Additional variables used:
     *   - mboxto: (string) Mailbox to move the message to (base64url
     *             encoded).
     *   - uid: (string) Indices of the messages to move (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function moveMessages()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if ((!$this->_vars->mboxto && !$this->_vars->newmbox) ||
            !count($indices)) {
            return false;
        }

        $change = $this->_changed(true);

        if (is_null($change)) {
            return false;
        }

        if ($this->_vars->newmbox) {
            $mbox = IMP_Mailbox::prefFrom($this->_vars->newmbox);
            $newMbox = true;
        } else {
            $mbox = IMP_Mailbox::formFrom($this->_vars->mboxto);
            $newMbox = false;
        }

        $result = $GLOBALS['injector']
            ->getInstance('IMP_Message')
            ->copy($mbox, 'move', $indices, array('create' => $newMbox));

        if ($result) {
            $this->_deleteMsgs($indices, $change, true);
            $this->_queue->poll($mbox);
            return true;
        }

        $this->_checkUidvalidity();

        return false;
    }

    /**
     * AJAX action: Copy messages.
     *
     * See the list of variables needed for _checkUidvalidity(). Additional
     * variables used:
     *   - mboxto: (string) Mailbox to copy the message to (base64url
     *             encoded).
     *   - uid: (string) Indices of the messages to copy (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function copyMessages()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if ((!$this->_vars->mboxto && !$this->_vars->newmbox) ||
            !count($indices)) {
            return false;
        }

        if ($this->_vars->newmbox) {
            $mbox = IMP_Mailbox::prefFrom($this->_vars->newmbox);
            $newMbox = true;
        } else {
            $mbox = IMP_Mailbox::formFrom($this->_vars->mboxto);
            $newMbox = false;
        }

        $result = $GLOBALS['injector']
            ->getInstance('IMP_Message')
            ->copy($mbox, 'copy', $indices, array('create' => $newMbox));

        if ($result) {
            $this->_queue->poll($mbox);
            return true;
        }

        $this->_checkUidvalidity();

        return false;
    }

    /**
     * AJAX action: Flag messages.
     *
     * See the list of variables needed for _changed() and
     * _checkUidvalidity().  Additional variables used:
     *   - add: (integer) Set the flag?
     *   - flags: (string) The flags to set (JSON serialized array).
     *   - uid: (string) Indices of the messages to flag (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function flagMessages()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if (!$this->_vars->flags || !count($indices)) {
            return false;
        }

        $change = $this->_changed(true);

        if (is_null($change)) {
            return false;
        }

        $flags = Horde_Serialize::unserialize($this->_vars->flags, Horde_Serialize::JSON);

        if (!$GLOBALS['injector']->getInstance('IMP_Message')->flag($flags, $indices, $this->_vars->add)) {
            $this->_checkUidvalidity();
            return false;
        }

        if (in_array(Horde_Imap_Client::FLAG_SEEN, $flags)) {
            $this->_queue->poll(array_keys($indices->indices()));
        }

        $this->addTask('viewport', $change ? $this->_viewPortData(true) : $this->_viewPortOb());

        return true;
    }

    /**
     * AJAX action: Delete messages.
     *
     * See the list of variables needed for _changed(), _deleteMsgs(),
     * and _checkUidvalidity(). Additional variables used:
     *   - uid: (string) Indices of the messages to delete (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success, false on failure.
     */
    public function deleteMessages()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if (!count($indices)) {
            return false;
        }

        $change = $this->_changed(true);

        if ($GLOBALS['injector']->getInstance('IMP_Message')->delete($indices)) {
            $this->_deleteMsgs($indices, $change);
            return true;
        }

        if (!is_null($change)) {
            $this->_checkUidvalidity();
        }

        return false;
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
        $addr_ob = IMP_Dimp::parseDimpAddressList($this->_vars->addr);

        // TODO: Currently supports only a single, non-group contact.
        $ob = $addr_ob[0];
        if (!$ob) {
            return false;
        } elseif ($ob instanceof Horde_Mail_Rfc822_Group) {
            $GLOBALS['notification']->push(_("Adding group lists not currently supported."), 'horde.warning');
            return false;
        }

        try {
            IMP::addAddress($ob->bare_address, $ob->personal);
            $GLOBALS['notification']->push(sprintf(_("%s was successfully added to your address book."), $ob->label), 'horde.success');
            return true;
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            return false;
        }
    }

    /**
     * AJAX action: Report message as [not]spam.
     *
     * See the list of variables needed for _changed(), _deleteMsgs(),
     * and _checkUidvalidity(). Additional variables used:
     *   - spam: (integer) 1 to mark as spam, 0 to mark as innocent.
     *   - uid: (string) Indices of the messages to report (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success.
     */
    public function reportSpam()
    {
        $change = $this->_changed(true);
        $indices = new IMP_Indices_Form($this->_vars->uid);

        if (IMP_Spam::reportSpam($indices, $this->_vars->spam ? 'spam' : 'notspam')) {
            $this->_deleteMsgs($indices, $change);
            return true;
        }

        if (!is_null($change)) {
            $this->_checkUidvalidity();
        }

        return false;
    }

    /**
     * AJAX action: Blacklist/whitelist addresses from messages.
     *
     * See the list of variables needed for _changed(), _deleteMsgs(),
     * and _checkUidvalidity(). Additional variables used:
     *   - blacklist: (integer) 1 to blacklist, 0 to whitelist.
     *   - uid: (string) Indices of the messages to report (IMAP sequence
     *          string; mailboxes are base64url encoded).
     *
     * @return boolean  True on success.
     */
    public function blacklist()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if (!count($indices)) {
            return false;
        }

        if ($this->_vars->blacklist) {
            $change = $this->_changed(false);
            if (!is_null($change)) {
                try {
                    if ($GLOBALS['injector']->getInstance('IMP_Filter')->blacklistMessage($indices, false)) {
                        $this->_deleteMsgs($indices, $change);
                        return true;
                    }
                } catch (Horde_Exception $e) {
                    $this->_checkUidvalidity();
                }
            }
        } else {
            try {
                $GLOBALS['injector']->getInstance('IMP_Filter')->whitelistMessage($indices, false);
                return true;
            } catch (Horde_Exception $e) {
                $this->_checkUidvalidity();
            }
        }

        return false;
    }

    /**
     * AJAX action: Generate data necessary to display a message.
     *
     * See the list of variables needed for _changed() and
     * _checkUidvalidity().  Additional variables used:
     *   - peek: (integer) If set, don't set seen flag.
     *   - preview: (integer) If set, return preview data. Otherwise, return
     *              full data.
     *   - uid: (string) Index of the message to display (IMAP sequence
     *          string; mailbox is base64url encoded) - must be single index.
     *
     * @return object  Object with the following entries:
     *   - error: (string) On error, the error string.
     *   - errortype: (string) On error, the error type.
     *   - mbox: (string) The message mailbox (base64url encoded).
     *   - uid: (string) The message UID.
     *   - view: (string) The view ID.
     */
    public function showMessage()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        list($mbox, $uid) = $indices->getSingle();

        if (!$uid) {
            throw new IMP_Exception(_("Requested message not found."));
        }

        $result = new stdClass;
        $result->mbox = $mbox->form_to;
        $result->uid = $uid;
        $result->view = $this->_vars->view;

        try {
            $change = $this->_changed(true);
            if (is_null($change)) {
                throw new IMP_Exception(_("Could not open mailbox."));
            }

            $this->_queue->message($mbox, $uid, $this->_vars->preview, $this->_vars->peek);

            if ($this->_vars->preview) {
                if ($change) {
                    $this->addTask('viewport', $this->_viewPortData(true));
                } elseif ($this->_mbox->cacheid_date != $this->_vars->cacheid) {
                    /* Cache ID has changed due to viewing this message. So
                     * update the cacheid in the ViewPort. */
                    $this->addTask('viewport', $this->_viewPortOb());
                }

                $this->_queue->poll($mbox);
            }
        } catch (Exception $e) {
            $result->error = $e->getMessage();
            $result->errortype = 'horde.error';
        }

        return $result;
    }

    /**
     * AJAX action: Return the MIME tree representation of the message.
     *
     * See the list of variables needed for _changed() and
     * _checkUidvalidity().  Additional variables used:
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
            $imp_contents = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create(new IMP_Indices_Form($this->_vars->uid));
            $result->tree = $imp_contents->getTree()->getTree(true);
        } catch (IMP_Exception $e) {
            if (!$this->_vars->preview) {
                throw $e;
            }

            $result->preview->error = $e->getMessage();
            $result->preview->errortype = 'horde.error';
            $result->preview->mbox = $mbox->form_to;
            $result->preview->uid = $idx;
            $result->preview->view = $this->_vars->view;
        }

        return $result;
    }

    /**
     * AJAX action: Return a list of address objects used to build an address
     * header for a message.
     *
     * See the list of variables needed for _changed() and
     * _checkUidvalidity().  Additional variables used:
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
        $indices = new IMP_Indices_Form($this->_vars->uid);
        list($mbox, $idx) = $indices->getSingle();

        if (!$idx) {
            throw new IMP_Exception(_("Requested message not found."));
        }

        $show_msg = new IMP_Views_ShowMessage($mbox, $idx);

        $hdr = $this->_vars->header;

        $result = new stdClass;
        $result->hdr_data->$hdr = (object)$show_msg->getAddressHeader($this->_vars->header, null);

        return $result;
    }

    /**
     * AJAX action: Convert HTML to text (compose data).
     *
     * Variables used:
     *   - changed: (integer) Has the text changed from the original?
     *   - identity: (integer) The current identity.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - text: (string) The text to convert.
     *
     * @return object  An object with the following entries:
     *   - text: (string) The converted text.
     */
    public function html2Text()
    {
        $result = new stdClass;

        if (!$this->_vars->changed) {
            list($imp_compose, $imp_contents) = $this->_initCompose();

            switch ($imp_compose->replyType()) {
            case IMP_Compose::FORWARD_BODY:
            case IMP_Compose::FORWARD_BOTH:
                $data = $imp_compose->forwardMessageText($imp_contents, array(
                    'format' => 'text'
                ));
                $result->text = $data['body'];
                return $result;

            case IMP_Compose::REPLY_ALL:
            case IMP_Compose::REPLY_LIST:
            case IMP_Compose::REPLY_SENDER:
                $data = $imp_compose->replyMessageText($imp_contents, array(
                    'format' => 'text'
                ));
                $result->text = $data['body'];
                return $result;
            }
        }

        $result->text = $GLOBALS['injector']->getInstance('IMP_Ui_Compose')->convertComposeText($this->_vars->text, 'text', intval($this->_vars->identity));

        return $result;
    }

    /**
     * AJAX action: Convert text to HTML (compose data).
     *
     * Variables used:
     *   - changed: (integer) Has the text changed from the original?
     *   - identity: (integer) The current identity.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - text: (string) The text to convert.
     *
     * @return object  An object with the following entries:
     *   - text: (string) The converted text.
     */
    public function text2Html()
    {
        $result = new stdClass;

        if (!$this->_vars->changed) {
            list($imp_compose, $imp_contents) = $this->_initCompose();

            switch ($imp_compose->replyType()) {
            case IMP_Compose::FORWARD_BODY:
            case IMP_Compose::FORWARD_BOTH:
                $data = $imp_compose->forwardMessageText($imp_contents, array(
                    'format' => 'html'
                ));
                $result->text = $data['body'];
                return $result;

            case IMP_Compose::REPLY_ALL:
            case IMP_Compose::REPLY_LIST:
            case IMP_Compose::REPLY_SENDER:
                $data = $imp_compose->replyMessageText($imp_contents, array(
                    'format' => 'html'
                ));
                $result->text = $data['body'];
                return $result;
            }
        }

        $result->text = $GLOBALS['injector']->getInstance('IMP_Ui_Compose')->convertComposeText($this->_vars->text, 'html', intval($this->_vars->identity));

        return $result;
    }

    /**
     * AJAX action: Get forward compose data.
     *
     * See the list of variables needed for _checkUidvalidity(). Additional
     * variables used:
     *   - dataonly: (boolean) Only return data information (DEFAULT: false).
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) Forward type.
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
     *   - opts: (array) Additional options needed for DimpCompose.fillForm().
     *   - type: (string) The input 'type' value.
     */
    public function getForwardData()
    {
        try {
            list($imp_compose, $imp_contents) = $this->_initCompose();

            $fwd_map = array(
                'editasnew' => IMP_Compose::EDITASNEW,
                'forward_attach' => IMP_Compose::FORWARD_ATTACH,
                'forward_auto' => IMP_Compose::FORWARD_AUTO,
                'forward_body' => IMP_Compose::FORWARD_BODY,
                'forward_both' => IMP_Compose::FORWARD_BOTH
            );

            $fwd_msg = $imp_compose->forwardMessage($fwd_map[$this->_vars->type], $imp_contents);

            /* Can't open session read-only since we need to store the message
             * cache id. */
            $result = new stdClass;
            $result->opts = new stdClass;
            $result->opts->fwd_list = $this->_getAttachmentInfo($imp_compose);
            $result->body = $fwd_msg['body'];
            $result->type = $this->_vars->type;
            if (!$this->_vars->dataonly) {
                $result->format = $fwd_msg['format'];
                $result->header = $fwd_msg['headers'];
                $result->identity = $fwd_msg['identity'];
                $result->imp_compose = $imp_compose->getCacheId();
                if ($this->_vars->type == 'forward_auto') {
                    $result->opts->auto = array_search($fwd_msg['type'], $fwd_map);
                }
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $this->_checkUidvalidity();
            $result = false;
        }

        return $result;
    }

    /**
     * AJAX action: Get reply data.
     *
     * See the list of variables needed for _checkUidvalidity(). Additional
     * variables used:
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
        try {
            list($imp_compose, $imp_contents) = $this->_initCompose();

            $reply_map = array(
                'reply' => IMP_Compose::REPLY_SENDER,
                'reply_all' => IMP_Compose::REPLY_ALL,
                'reply_auto' => IMP_Compose::REPLY_AUTO,
                'reply_list' => IMP_Compose::REPLY_LIST
            );

            $reply_msg = $imp_compose->replyMessage($reply_map[$this->_vars->type], $imp_contents, array(
                'format' => $this->_vars->format
            ));

            /* Can't open session read-only since we need to store the message
             * cache id. */
            $result = new stdClass;
            $result->header = $reply_msg['headers'];
            $result->type = $this->_vars->type;
            if (!$this->_vars->headeronly) {
                $result->body = $reply_msg['body'];
                $result->format = $reply_msg['format'];
                $result->identity = $reply_msg['identity'];
                $result->imp_compose = $imp_compose->getCacheId();
                if ($this->_vars->type == 'reply_auto') {
                    $result->opts = array_filter(array(
                        'auto' => array_search($reply_msg['type'], $reply_map),
                        'reply_list_id' => (isset($reply_msg['reply_list_id']) ? $reply_msg['reply_list_id'] : null),
                        'reply_recip' => (isset($reply_msg['reply_recip']) ? $reply_msg['reply_recip'] : null),
                    ));
                }
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $this->_checkUidvalidity();
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
        list($imp_compose, $imp_contents) = $this->_initCompose();

        $imp_compose->redirectMessage(new IMP_Indices($imp_contents->getMailbox(), $imp_contents->getUid()));

        $ob = new stdClass;
        $ob->imp_compose = $imp_compose->getCacheId();
        $ob->type = $this->_vars->type;

        return $ob;
    }

    /**
     * AJAX action: Get resume data.
     *
     * See the list of variables needed for _checkUidvalidity(). Additional
     * variables used:
     *   - format: (string) The format to force to ('text' or 'html')
     *             (DEFAULT: Auto-determined).
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) Resume type, on of 'editasnew', 'resume', 'template'
     *           'template_edit'.
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
     *   - opts: (array) Additional options (fwd_list, priority, readreceipt).
     *   - type: (string) The input 'type' value.
     */
    public function getResumeData()
    {
        try {
            list($imp_compose, $imp_contents) = $this->_initCompose();
            $indices_ob = new IMP_Indices($imp_contents->getMailbox(), $imp_contents->getUid());

            $opts = new stdClass;

            switch ($this->_vars->type) {
            case 'editasnew':
                $resume = $imp_compose->editAsNew($indices_ob, array(
                    'format' => $this->_vars->format
                ));
                $opts->fwd_list = $this->_getAttachmentInfo($imp_compose);
                break;

            case 'resume':
                $resume = $imp_compose->resumeDraft($indices_ob, array(
                    'format' => $this->_vars->format
                ));
                break;

            case 'template':
                $resume = $imp_compose->useTemplate($indices_ob, array(
                    'format' => $this->_vars->format
                ));
                break;

            case 'template_edit':
                $resume = $imp_compose->editTemplate($indices_ob);
                break;
            }

            $result = new stdClass;
            $result->body = $resume['body'];
            $result->header = $resume['headers'];
            $result->format = $resume['format'];
            $result->identity = $resume['identity'];
            $result->imp_compose = $imp_compose->getCacheId();

            $opts->priority = $resume['priority'];
            $opts->readreceipt = $resume['readreceipt'];
            $result->opts = $opts;

            $result->type = $this->_vars->type;
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $this->_checkUidvalidity();
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
        $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->_vars->imp_compose)->destroy('cancel');
        return true;
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
        if (isset($this->_vars->atc_indices)) {
            $imp_compose = $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->_vars->imp_compose);
            foreach (Horde_Serialize::unserialize($this->_vars->atc_indices, Horde_Serialize::JSON) as $val) {
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
     * See the list of variables needed for _changed() and _deleteMsgs().
     *
     * @return boolean  True on success.
     */
    public function purgeDeleted()
    {
        global $injector;

        $change = $this->_changed(true);
        if (is_null($change)) {
            return false;
        }

        if (!$change) {
            $change = ($this->_mbox->getSort()->sortby == Horde_Imap_Client::SORT_THREAD);
        }

        $expunged = $injector->getInstance('IMP_Message')->expungeMailbox(array(strval($this->_mbox) => 1), array('list' => true));

        if (!($expunge_count = count($expunged))) {
            return false;
        }

        $GLOBALS['notification']->push(sprintf(ngettext("%d message was purged from \"%s\".", "%d messages were purged from \"%s\".", $expunge_count), $expunge_count, $this->_mbox->display), 'horde.success');

        $this->_deleteMsgs($expunged, $change, true);

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
        $indices = new IMP_Indices_Form($this->_vars->uid);
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
     * See the list of variables needed for _changed() and
     * _checkUidvalidity().  Additional variables used:
     *   - uid: (string) Index of the messages to preview (IMAP sequence
     *          string; base64url encoded) - must be single index.
     *
     * @return mixed  False on failure, or an object with these properties:
     *   - newmbox: (string) Mailbox of new message (base64url encoded).
     *   - newuid: (integer) UID of new message.
     */
    public function stripAttachment()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if (count($indices) != 1) {
            return false;
        }

        $change = $this->_changed(true);
        if (is_null($change)) {
            return false;
        }

        try {
            $new_indices = $GLOBALS['injector']->getInstance('IMP_Message')->stripPart($indices, $this->_vars->id);
        } catch (IMP_Exception $e) {
            $GLOBALS['notification']->push($e);
            return false;
        }

        $GLOBALS['notification']->push(_("Attachment successfully stripped."), 'horde.success');

        $tmp = $new_indices->getSingle();

        $result = new stdClass;
        $result->newmbox = $tmp[0]->form_to;
        $result->newuid = $tmp[1];

        $this->_queue->message($tmp[0], $tmp[1], true);
        $this->addTask('viewport', $this->_viewPortData(true));

        return $result;
    }

    /**
     * AJAX action: Add an attachment to a compose message.
     *
     * Variables used:
     *   - composeCache: (string) The IMP_Compose cache identifier.
     *
     * @return object  An object with the following entries:
     *   - atc: (integer) The attachment ID.
     *   - error: (string) An error message.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - success: (integer) 1 on success, 0 on failure.
     */
    public function addAttachment()
    {
        $imp_compose = $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->_vars->composeCache);

        $result = new stdClass;
        $result->action = 'addAttachment';
        $result->success = 0;

        if ($GLOBALS['session']->get('imp', 'file_upload') &&
            $imp_compose->addFilesFromUpload('file_')) {
            $result->atc = end($this->_getAttachmentInfo($imp_compose));
            $result->success = 1;
            $result->imp_compose = $imp_compose->getCacheId();
        }

        return $result;
    }

    /**
     * AJAX action: Auto save a draft message.
     *
     * @return object  See self::_dimpDraftAction().
     */
    public function autoSaveDraft()
    {
        return $this->_dimpDraftAction();
    }

    /**
     * AJAX action: Save a draft message.
     *
     * @return object  See self::_dimpDraftAction().
     */
    public function saveDraft()
    {
        return $this->_dimpDraftAction();
    }

    /**
     * AJAX action: Save a template message.
     *
     * @return object  See self::_dimpDraftAction().
     */
    public function saveTemplate()
    {
        return $this->_dimpDraftAction();
    }

    /**
     * AJAX action: Send a message.
     *
     * See the list of variables needed for _dimpComposeSetup(). Additional
     * variables used:
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
     *   - log: (array) Maillog information
     *   - mbox: (string) Mailbox of original message (base64url encoded).
     *   - success: (integer) 1 on success, 0 on failure.
     *   - uid: (integer) IMAP UID of original message.
     */
    public function sendMessage()
    {
        try {
            list($result, $imp_compose, $headers, $identity) = $this->_dimpComposeSetup();
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
            'encrypt' => ($GLOBALS['prefs']->isLocked('default_encrypt') ? $GLOBALS['prefs']->getValue('default_encrypt') : $this->_vars->encrypt),
            'html' => $this->_vars->html,
            'identity' => $identity,
            'priority' => $this->_vars->priority,
            'readreceipt' => $this->_vars->request_read_receipt,
            'save_attachments' => $this->_vars->save_attachments_select,
            'save_sent' => ($sm_displayed
                            ? (bool)$this->_vars->save_sent_mail
                            : $identity->getValue('save_sent_mail')),
            'sent_mail' => ($sm_displayed
                              ? (isset($this->_vars->save_sent_mail_mbox) ? IMP_Mailbox::formFrom($this->_vars->save_sent_mail_mbox) : $identity->getValue('sent_mail_folder'))
                              : $identity->getValue('sent_mail_folder'))
        );

        try {
            $imp_compose->buildAndSendMessage($this->_vars->message, $headers, $options);
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

        /* Update maillog information. */
        if (!empty($GLOBALS['conf']['maillog']['use_maillog'])) {
            $in_reply_to = $imp_compose->getMetadata('in_reply_to');
            if (!empty($in_reply_to) &&
                ($tmp = IMP_Dimp::getMsgLogInfo($in_reply_to))) {
                $result->log = $tmp;
            }
        }

        if ($reply_mbox = $imp_compose->getMetadata('mailbox')) {
            $result->mbox = $reply_mbox->form_to;
            $result->uid = $imp_compose->getMetadata('uid');
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
     *   - log: (array) TODO
     *   - success: (integer) 1 on success, 0 on failure.
     */
    public function redirectMessage()
    {
        $result = new stdClass;
        $result->action = $this->_action;
        $result->success = 1;

        $log = array();

        try {
            $imp_compose = $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->_vars->composeCache);
            $res = $imp_compose->sendRedirectMessage($this->_vars->redirect_to);

            foreach ($res as $val) {
                $subject = $val->headers->getValue('subject');
                $GLOBALS['notification']->push(empty($subject) ? _("Message redirected successfully.") : sprintf(_("Message \"%s\" redirected successfully."), Horde_String::truncate($subject)), 'horde.success');

                if (!empty($GLOBALS['conf']['maillog']['use_maillog']) &&
                    ($tmp = IMP_Dimp::getMsgLogInfo($val->headers->getValue('message-id')))) {
                    $log_ob = new stdClass;
                    $log_ob->log = $tmp;
                    $log_ob->mbox = $val->mbox->form_to;
                    $log_ob->uid = $val->uid;
                    $log[] = $log_ob;
                }
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $result->success = 0;
        }

        if (!empty($log)) {
            $result->log = $log;
        }

        return $result;
    }

    /**
     * AJAX action: Create mailbox select list for advanced search page.
     *
     * Variables used:
     *   - unsub: (integer) If set, includes unsubscribed mailboxes.
     *
     * @return object  An object with the following entries:
     *   - mbox_list: (array)
     *   - tree: (string)
     */
    public function searchMailboxList()
    {
        $ob = $GLOBALS['injector']->getInstance('IMP_Ui_Search')->getSearchMboxList($this->_vars->unsub);

        $result = new stdClass;
        $result->mbox_list = $ob->mbox_list;
        $result->tree = $ob->tree->getTree();

        return $result;
    }

    /* Protected methods. */

    /**
     * Setup environment for dimp compose actions.
     *
     * Variables used:
     *   - composeCache: (string) The IMP_Compose cache identifier.
     *   - from: (string) From address to use.
     *   - identity: (integer) The identity to use
     *
     * @return array  An array with the following values:
     *   - (object) AJAX base return object (with action and success
     *     parameters defined).
     *   - (IMP_Compose) The IMP_Compose object for the message.
     *   - (array) The list of headers for the object.
     *   - (Horde_Prefs_Identity) The identity used for the composition.
     *
     * @throws Horde_Exception
     */
    protected function _dimpComposeSetup()
    {
        global $injector, $prefs;

        /* Set up identity. */
        $identity = $injector->getInstance('IMP_Identity');
        if (isset($this->_vars->identity) &&
            !$prefs->isLocked('default_identity')) {
            $identity->setDefault($this->_vars->identity);
        }

        /* Set up the From address based on the identity. */
        $headers = array(
            'from' => strval($identity->getFromLine(null, $this->_vars->from))
        );

        $headers['to'] = $this->_vars->to;
        if ($prefs->getValue('compose_cc')) {
            $headers['cc'] = $this->_vars->cc;
        }
        if ($prefs->getValue('compose_bcc')) {
            $headers['bcc'] = $this->_vars->bcc;
        }
        $headers['subject'] = $this->_vars->subject;

        $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($this->_vars->composeCache);

        $result = new stdClass;
        $result->action = $this->_action;
        $result->success = 1;

        return array($result, $imp_compose, $headers, $identity);
    }

    /**
     * Initialize the objects needed to compose.
     *
     * @return array  An IMP_Compose object and an IMP_Contents object.
     */
    protected function _initCompose()
    {
        $imp_compose = $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->_vars->imp_compose);
        if (!($imp_contents = $imp_compose->getContentsOb())) {
            $imp_contents = $this->_vars->uid
                ? $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create(new IMP_Indices_Form($this->_vars->uid))
                : null;
        }

        return array($imp_compose, $imp_contents);
    }

    /**
     * Save a draft composed message.
     *
     * See the list of variables needed for _dimpComposeSetup(). Additional
     * variables used:
     *   - html: (integer) In HTML compose mode?
     *   - message: (string) The message text.
     *   - priority: (string) The priority of the message.
     *   - request_read_receipt: (boolean) Add request read receipt header?
     *
     * @return object  An object with the following entries:
     *   - action: (string) The AJAX action string
     *   - success: (integer) 1 on success, 0 on failure.
     */
    protected function _dimpDraftAction()
    {
        try {
            list($result, $imp_compose, $headers, $identity) = $this->_dimpComposeSetup();
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);

            $result = new stdClass;
            $result->action = $this->_action;
            $result->success = 0;
            return $result;
        }

        $opts = array(
            'html' => $this->_vars->html,
            'priority' => $this->_vars->priority,
            'readreceipt' => $this->_vars->request_read_receipt
        );

        try {
            switch ($this->_action) {
            case 'saveTemplate':
                $res = $imp_compose->saveTemplate($headers, $this->_vars->message, $opts);
                break;

            default:
                $res = $imp_compose->saveDraft($headers, $this->_vars->message, $opts);
                break;
            }

            switch ($this->_action) {
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

    /**
     * Check the UID validity of the mailbox.
     *
     * See the list of variables needed for _viewPortData().
     */
    protected function _checkUidvalidity()
    {
        try {
            $this->_mbox->uidvalid;
        } catch (IMP_Exception $e) {
            $this->addTask('viewport', $this->_viewPortData(true));
        }
    }

    /**
     * Processes delete message requests.
     *
     * See the list of variables needed for _viewPortData().
     *
     * @param IMP_Indices $indices  An indices object.
     * @param boolean $changed      If true, add full ViewPort information.
     * @param boolean $force        If true, forces addition of disappear
     *                              information.
     */
    protected function _deleteMsgs(IMP_Indices $indices, $changed,
                                   $force = false)
    {
        /* Check if we need to update thread information. */
        if (!$changed) {
            $changed = ($this->_mbox->getSort()->sortby == Horde_Imap_Client::SORT_THREAD);
        }

        if ($changed) {
            $vp = $this->_viewPortData(true);
        } else {
            $vp = $this->_viewPortOb();

            if ($force || $this->_mbox->hideDeletedMsgs(true)) {
                if ($this->_mbox->search) {
                    $disappear = array();
                    foreach ($indices as $val) {
                        foreach ($val->uids as $val2) {
                            $disappear[] = IMP_Views_ListMessages::searchUid($val->mbox, $val2);
                        }
                    }
                } else {
                    $disappear = end($indices->getSingle(true));
                }
                $vp->disappear = $disappear;
            }
        }

        $this->addTask('viewport', $vp);
        $this->_queue->poll(array_keys($indices->indices()));
    }

    /**
     * Determine if the cache information has changed.
     *
     * Variables used:
     *   - cacheid: (string) The browser (ViewPort) cache identifier.
     *   - forceUpdate: (integer) If 1, forces an update.
     *
     * @param boolean $rw  Open mailbox as READ+WRITE?
     *
     * @return boolean  True if the server state differs from the browser
     *                  state.
     */
    protected function _changed($rw = null)
    {
        /* Only update search mailboxes on forced refreshes. */
        if ($this->_mbox->search) {
            return !empty($this->_vars->forceUpdate);
        }

        /* We know we are going to be dealing with this mailbox, so select it
         * on the IMAP server (saves some STATUS calls). */
        if (!is_null($rw)) {
            try {
                $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->openMailbox($this->_mbox, $rw ? Horde_Imap_Client::OPEN_READWRITE : Horde_Imap_Client::OPEN_AUTO);
            } catch (IMP_Imap_Exception $e) {
                $e->notify();
                return null;
            }
        }

        return ($this->_mbox->cacheid_date != $this->_vars->cacheid);
    }

    /**
     * Generate the information necessary for a ViewPort request from/to the
     * browser.
     *
     * @param boolean $change  True if cache information has changed.
     *
     * @return array  See IMP_Views_ListMessages::listMessages().
     */
    protected function _viewPortData($change)
    {
        $args = array(
            'change' => $change,
            'mbox' => strval($this->_mbox)
        );

        $params = array(
            'applyfilter', 'cache', 'cacheid', 'delhide', 'initial', 'qsearch',
            'qsearchfield', 'qsearchfilter', 'qsearchflag', 'qsearchflagnot',
            'qsearchmbox', 'rangeslice', 'requestid', 'sortby', 'sortdir'
        );

        foreach ($params as $val) {
            $args[$val] = $this->_vars->$val;
        }

        if ($this->_vars->search || $args['initial']) {
            $args += array(
                'after' => intval($this->_vars->after),
                'before' => intval($this->_vars->before)
            );
        }

        if (!$this->_vars->search) {
            list($slice_start, $slice_end) = explode(':', $this->_vars->slice, 2);
            $args += array(
                'slice_start' => intval($slice_start),
                'slice_end' => intval($slice_end)
            );
        } else {
            $search = Horde_Serialize::unserialize($this->_vars->search, Horde_Serialize::JSON);
            $args += array(
                'search_uid' => isset($search->uid) ? $search->uid : null,
                'search_unseen' => isset($search->unseen) ? $search->unseen : null
            );
        }

        $list_msg = new IMP_Views_ListMessages();
        return $list_msg->listMessages($args);
    }

    /**
     * Return a basic ViewPort object.
     *
     * @param IMP_Mailbox $mbox  The mailbox view of the ViewPort request.
     *                           Defaults to current view.
     *
     * @return object  The ViewPort data object.
     */
    protected function _viewPortOb($mbox = null)
    {
        if (is_null($mbox)) {
            $mbox = $this->_mbox;
        }

        $vp = new stdClass;
        $vp->cacheid = $mbox->cacheid_date;
        $vp->view = $mbox->form_to;

        return $vp;
    }

    /**
     * Return information about the current attachments for a message.
     *
     * @param IMP_Compose $imp_compose  An IMP_Compose object.
     *
     * @return array  An array of arrays with the following keys:
     *   - name: (string) The HTML encoded attachment name
     *   - num: (integer) The current attachment number
     *   - size: (string) The size of the attachment in KB
     *   - type: (string) The MIME type of the attachment
     */
    protected function _getAttachmentInfo(IMP_Compose $imp_compose)
    {
        $fwd_list = array();

        foreach ($imp_compose as $atc_num => $data) {
            $mime = $data['part'];

            $fwd_list[] = array(
                'name' => htmlspecialchars($mime->getName(true)),
                'num' => $atc_num,
                'type' => $mime->getType(),
                'size' => $mime->getSize()
            );
        }

        return $fwd_list;
    }

}
