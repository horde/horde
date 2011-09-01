<?php
/**
 * Defines the AJAX interface for IMP.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
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
     * @see Horde::sendHTTPResponse().
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
     * May add the following entries to the output object:
     *   - flag: (array) See IMP_Ajax_Queue::generate().
     *   - poll: (array) See IMP_Ajax_Queue::generate().
     *   - quota: (array) See IMP_Ajax_Queue::generate().
     */
    public function doAction()
    {
        $res = parent::doAction();

        if (is_object($res)) {
            foreach ($this->_queue->generate() as $key => $val) {
                $res->$key = $val;
            }
        }

        return $res;
    }

    /**
     * AJAX action: Check access rights for creation of a sub mailbox.
     *
     * Variables used:
     *   - mbox: (string) The name of the mailbox to check.
     *
     * @return boolean  True if sub mailboxes can be created
     */
    public function createMailboxPrepare()
    {
        $mbox = IMP_Mailbox::get($this->_vars->mbox);

        if (!$mbox->access_creatembox) {
            $GLOBALS['notification']->push(sprintf(_("You may not create child folders in \"%s\"."), $mbox->display), 'horde.error');
            return false;
        }

        return true;
    }

    /**
     * AJAX action: Create a mailbox.
     *
     * Variables used:
     *   - mbox: (string) The name of the new mailbox.
     *   - noexpand: (integer) Submailbox is not yet expanded.
     *   - parent: (string) The parent mailbox.
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - mailbox: (object) Mailboxes that were altered. Contains the
     *              following properties:
     *       a: (array) Mailboxes that were added.
     *       c: (array) Mailboxes that were changed.
     *       d: (array) Mailboxes that were deleted.
     */
    public function createMailbox()
    {
        if (!$this->_vars->mbox) {
            return false;
        }

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imptree->eltDiffStart();

        try {
            $result = $imptree->createMailboxName(
                $this->_vars->parent,
                Horde_String::convertCharset($this->_vars->mbox, 'UTF-8', 'UTF7-IMAP')
            )->create();

            if ($result) {
                $result = new stdClass;
                $result->mailbox = $this->_getMailboxResponse($imptree);
                if (isset($this->_vars->parent) && $this->_vars->noexpand) {
                    $result->mailbox['noexpand'] = 1;
                }
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $result = false;
        }

        return $result;
    }

    /**
     * AJAX action: Check access rights for deletion/rename of mailbox.
     *
     * Variables used:
     *   - mbox: (string) The name of the mailbox to check.
     *   - type: (string) Either 'delete' or 'rename'.
     *
     * @return boolean  True if sub mailboxes can be created.
     */
    public function deleteMailboxPrepare()
    {
        $mbox = IMP_Mailbox::get($this->_vars->mbox);

        if (!$mbox->fixed && $mbox->access_deletembox) {
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
     * <pre>
     * 'mbox' - (string) The full mailbox name to delete.
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - mailbox: (object) Mailboxes that were altered. Contains the
     *              following properties:
     *     a: (array) Mailboxes that were added.
     *     c: (array) Mailboxes that were changed.
     *     d: (array) Mailboxes that were deleted.
     */
    public function deleteMailbox()
    {
        if (!$this->_vars->mbox) {
            return false;
        }

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imptree->eltDiffStart();

        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');

        if ($imp_search->isVFolder($this->_vars->mbox, true)) {
            $GLOBALS['notification']->push(sprintf(_("Deleted Virtual Folder \"%s\"."), $imp_search[$this->_vars->mbox]->label), 'horde.success');
            unset($imp_search[$this->_vars->mbox]);
            $result = true;
        } else {
            $result = $GLOBALS['injector']->getInstance('IMP_Folder')->delete(array($this->_vars->mbox));
        }

        if ($result) {
            $result = new stdClass;
            $result->mailbox = $this->_getMailboxResponse($imptree);
        }

        return $result;
    }

    /**
     * AJAX action: Rename a mailbox.
     *
     * Variables used:
     * <pre>
     * new_name: (string) New mailbox name (child node) (UTF-8).
     * new_parent: (string) New parent name (UTF7-IMAP).
     * old_name: (string) Full name of old mailbox.
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - mailbox: (object) Mailboxes that were altered. Contains the
     *              following properties:
     *     a: (array) Mailboxes that were added.
     *     c: (array) Mailboxes that were changed.
     *     d: (array) Mailboxes that were deleted.
     */
    public function renameMailbox()
    {
        if (!$this->_vars->old_name || !$this->_vars->new_name) {
            return false;
        }

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imptree->eltDiffStart();

        $result = false;

        try {
            $new = $imptree->createMailboxName(
                $this->_vars->new_parent,
                Horde_String::convertCharset($this->_vars->new_name, 'UTF-8', 'UTF7-IMAP')
            );

            if (($this->_vars->old_name != $new) &&
                $GLOBALS['injector']->getInstance('IMP_Folder')->rename($this->_vars->old_name, $new)) {
                $result = new stdClass;
                $result->mailbox = $this->_getMailboxResponse($imptree);

                $this->_queue->poll($new);
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
     *   - mbox: (string) The name of the mailbox to check.
     *
     * @return integer  The number of messages to be deleted.
     */
    public function emptyMailboxPrepare()
    {
        $mbox = IMP_Mailbox::get($this->_vars->mbox);

        if (!$mbox->access_deletemsgs || !$mbox->access_expunge) {
            $GLOBALS['notification']->push(sprintf(_("The folder \"%s\" may not be emptied."), $mbox->display), 'horde.error');
            return 0;
        }

        $poll_info = $mbox->poll_info;
        if (empty($poll_info->msgs)) {
            $GLOBALS['notification']->push(sprintf(_("The folder \"%s\" is already empty."), $mbox->display), 'horde.message');
            return 0;
        }

        return $poll_info->msgs;
    }

    /**
     * AJAX action: Empty a mailbox.
     *
     * Variables used:
     * <pre>
     * 'mbox' - (string) The full mailbox name to empty.
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - mbox: (string) The mailbox that was emptied.
     */
    public function emptyMailbox()
    {
        if (!$this->_vars->mbox) {
            return false;
        }

        $GLOBALS['injector']->getInstance('IMP_Message')->emptyMailbox(array($this->_vars->mbox));

        $this->_queue->poll($this->_vars->mbox);

        $result = new stdClass;
        $result->mbox = $this->_vars->mbox;

        return $result;
    }

    /**
     * AJAX action: Flag all messages in a mailbox.
     *
     * Variables used:
     *   - add: (integer) Add the flags?
     *   - flags: (string) The IMAP flags to add/remove (JSON serialized
     *            array).
     *   - mbox: (string) The full mailbox name.
     *
     * @return mixed  False on failure, or an object on success.
     */
    public function flagAll()
    {
        $flags = Horde_Serialize::unserialize($this->_vars->flags, Horde_Serialize::JSON);
        if (!$this->_vars->mbox || empty($flags)) {
            return false;
        }

        if (!$GLOBALS['injector']->getInstance('IMP_Message')->flagAllInMailbox($flags, array($this->_vars->mbox), $this->_vars->add)) {
            return false;
        }

        $this->_queue->poll($this->_vars->mbox);

        return new stdClass;
    }

    /**
     * AJAX action: List mailboxes.
     *
     * Variables used:
     *   - all: (integer) 1 to show all mailboxes.
     *   - initial: (string) 1 to indicate the initial request for mailbox
     *              list.
     *   - mboxes: (string) The list of mailboxes to process (JSON encoded
     *             array).
     *   - reload: (integer) 1 to force reload of mailboxes.
     *   - unsub: (integer) 1 to show unsubscribed mailboxes.
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - expand: (integer) Expand subfolders on load.
     *   - mailbox: (object) Mailboxes that were altered. Contains the
     *              following properties:
     *     a: (array) Mailboxes that were added.
     *     c: (array) Mailboxes that were changed.
     *     d: (array) Mailboxes that were deleted.
     */
    public function listMailboxes()
    {
        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

        /* This might be a long running operation. */
        if ($this->_vars->initial) {
            $GLOBALS['session']->close();
        }

        $initreload = ($this->_vars->initial || $this->_vars->reload);
        $result = new stdClass;

        $mask = IMP_Imap_Tree::FLIST_VFOLDER;
        if ($this->_vars->unsub) {
            $mask |= IMP_Imap_Tree::FLIST_UNSUB;
        }

        if (!$this->_vars->all) {
            if ($initreload) {
                $mask |= IMP_Imap_Tree::FLIST_ANCESTORS | IMP_Imap_Tree::FLIST_SAMELEVEL;
                if ($GLOBALS['prefs']->getValue('nav_expanded')) {
                    $result->expand = 1;
                    $mask |= IMP_Imap_Tree::FLIST_EXPANDED;
                } else {
                    $mask |= IMP_Imap_Tree::FLIST_NOCHILDREN;
                }
            } else {
                $mask |= IMP_Imap_Tree::FLIST_NOCHILDREN | IMP_Imap_Tree::FLIST_NOBASE;
            }
        }

        if ($this->_vars->reload) {
            $imptree->init();
        }

        $imptree->showUnsubscribed($this->_vars->unsub);

        $folder_list = array();
        if (!empty($this->_vars->mboxes)) {
            foreach (IMP_Mailbox::formFrom(Horde_Serialize::unserialize($this->_vars->mboxes, Horde_Serialize::JSON)) as $val) {
                $imptree->setIteratorFilter($mask, $val);
                $folder_list += iterator_to_array($imptree);

                if (!$initreload) {
                    $imptree->expand($val);
                }
            }

            if ($initreload && empty($folder_list)) {
                $imptree->setIteratorFilter($mask, 'INBOX');
                $folder_list += iterator_to_array($imptree);
            }
        }

        /* Add special folders explicitly to the initial folder list, since
         * they are ALWAYS displayed and may appear outside of the folder
         * slice requested. */
        if ($initreload) {
            foreach (IMP_Mailbox::getSpecialMailboxes() as $val) {
                if (!is_array($val)) {
                    $val = array($val);
                }

                foreach (array_map('strval', $val) as $val2) {
                    if (!isset($folder_list[$val2]) &&
                        ($tmp = $imptree[$val2])) {
                        $folder_list[$val2] = $tmp;
                    }
                }
            }
        }

        $result->mailbox = $this->_getMailboxResponse($imptree, array(
            'a' => array_values($folder_list),
            'c' => array(),
            'd' => array()
        ));

        $this->_queue->quota();

        if ($this->_vars->initial) {
            $GLOBALS['session']->start();
        }

        return $result;
    }

    /**
     * AJAX action: Expand mailboxes (saves expanded state in prefs).
     *
     * Variables used:
     *   - encoded: (integer) 1 if mboxes is base64url encoded.
     *   - mboxes: (string) The list of mailboxes to process (JSON encoded
     *             array) if 'all' is 0.
     *
     * @return boolean  True.
     */
    public function expandMailboxes()
    {
        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

        if (!empty($this->_vars->mboxes)) {
            foreach (Horde_Serialize::unserialize($this->_vars->mboxes, Horde_Serialize::JSON) as $val) {
                $imptree->expand($this->_vars->encoded ? IMP_Mailbox::formFrom($val) : $val);
            }
        }

        return true;
    }

    /**
     * AJAX action: Collapse mailboxes.
     *
     * Variables used:
     *   - all: (integer) 1 to show all mailboxes.
     *   - encoded: (integer) 1 if mboxes is base64url encoded.
     *   - mboxes: (string) The list of mailboxes to process (JSON encoded
     *             array) if 'all' is 0.
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
                $imptree->collapse($this->_vars->encoded ? IMP_Mailbox::formFrom($val) : $val);
            }
        }

        return true;
    }

    /**
     * AJAX action: Poll mailboxes.
     *
     * See the list of variables needed for _changed() and _viewPortData().
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - ViewPort: (object) See _viewPortData().
     */
    public function poll()
    {
        $result = new stdClass;

        $this->_queue->poll($GLOBALS['injector']->getInstance('IMP_Imap_Tree')->getPollList());
        $this->_queue->quota();

        if ($this->_mbox && $this->_changed()) {
            $result->ViewPort = $this->_viewPortData(true);
        }

        return $result;
    }

    /**
     * AJAX action: Modify list of polled mailboxes.
     *
     * Variables used:
     *   - add: (integer) 1 to add to the poll list, 0 to remove.
     *   - mbox: (string) The full mailbox name to modify.
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

        $display = IMP_Mailbox::get($this->_vars->mbox)->display;

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

        $result = new stdClass;
        $result->add = intval($this->_vars->add);
        $result->mbox = $this->_vars->mbox;

        if ($this->_vars->add) {
            $imptree->addPollList($this->_vars->mbox);
            $this->_queue->poll($this->_vars->mbox);
            $GLOBALS['notification']->push(sprintf(_("\"%s\" mailbox now polled for new mail."), $display), 'horde.success');
        } else {
            $imptree->removePollList($this->_vars->mbox);
            $GLOBALS['notification']->push(sprintf(_("\"%s\" mailbox no longer polled for new mail."), $display), 'horde.success');
        }

        return $result;
    }

    /**
     * AJAX action: [un]Subscribe to a mailbox.
     *
     * Variables used:
     *   - mbox: (string) The full mailbox name to [un]subscribe to.
     *   - sub: (integer) 1 to subscribe, empty to unsubscribe.
     *
     * @return boolean  True on success, false on failure.
     */
    public function subscribe()
    {
        if (!$GLOBALS['prefs']->getValue('subscribe')) {
            return false;
        }

        $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');
        return $this->_vars->sub
            ? $imp_folder->subscribe(array($this->_vars->mbox))
            : $imp_folder->unsubscribe(array($this->_vars->mbox));
    }

    /**
     * AJAX action: Import a mailbox.
     *
     * Variables used:
     *   - import_mbox: (string) The mailbox to import into.
     *
     * @return object  False on failure, or an object with the following
     *                 properties:
     *   - action: (string) The action name (importMailbox).
     *   - mbox: (string) The mailbox the messages were imported to.
     */
    public function importMailbox()
    {
        global $injector, $notification;

        try {
            $notification->push($injector->getInstance('IMP_Ui_Folder')->importMbox($this->_vars->import_mbox, 'import_file'), 'horde.success');
        } catch (Horde_Exception $e) {
            $notification->push($e);
            return false;
        }

        $result = new stdClass;
        $result->action = 'importMailbox';
        $result->mbox = $this->_vars->import_mbox;

        $this->_queue->poll($this->_vars->import_mbox);

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
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - ViewPort: (object) See _viewPortData().
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

        $changed = $this->_changed(false);

        if (is_null($changed)) {
            $list_msg = new IMP_Views_ListMessages();
            $result = new stdClass;
            $result->ViewPort = $list_msg->getBaseOb($this->_mbox);

            $req_id = $this->_vars->requestid;
            if (!is_null($req_id)) {
                $result->ViewPort->requestid = intval($req_id);
            }

            return $result;
        }

        $this->_queue->poll($this->_mbox);

        if ($changed ||
            $this->_vars->rangeslice ||
            !$this->_vars->checkcache) {
            /* Ticket #7422: Listing messages may be a long-running operation
             * so close the session while we are doing it to prevent
             * deadlocks. */
            $GLOBALS['session']->close();

            $result = new stdClass;
            $result->ViewPort = $this->_viewPortData($changed);

            /* Reopen the session. */
            $GLOBALS['session']->start();

            if (isset($this->_vars->delhide)) {
                $result->ViewPort->resetmd = 1;
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * AJAX action: Move messages.
     *
     * See the list of variables needed for _changed(),
     * _generateDeleteResult(), and _checkUidvalidity(). Additional variables
     * used:
     *   - mboxto: (string) Mailbox to move the message to.
     *   - uid: (string) Indices of the messages to move (IMAP sequence
     *          string).
     *
     * @return mixed  False on failure, or an object (see
     *                _generateDeleteResult() for format).
     */
    public function moveMessages()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if (!$this->_vars->mboxto || !count($indices)) {
            return false;
        }

        $change = $this->_changed(true);

        if (is_null($change)) {
            return false;
        }

        $result = $GLOBALS['injector']->getInstance('IMP_Message')->copy($this->_vars->mboxto, 'move', $indices);

        if ($result) {
            $result = $this->_generateDeleteResult($indices, $change);
            $this->_queue->poll($this->_vars->mboxto);
        } else {
            $result = $this->_checkUidvalidity();
        }

        return $result;
    }

    /**
     * AJAX action: Copy messages.
     *
     * See the list of variables needed for _checkUidvalidity(). Additional
     * variables used:
     *   - mboxto: (string) Mailbox to move the message to.
     *   - uid: (string) Indices of the messages to copy (IMAP sequence
     *          string).
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - ViewPort: (object) See _viewPortData().
     */
    public function copyMessages()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if (!$this->_vars->mboxto || !count($indices)) {
            return false;
        }

        if ($result = $GLOBALS['injector']->getInstance('IMP_Message')->copy($this->_vars->mboxto, 'copy', $indices)) {
            $this->_queue->poll($this->_vars->mboxto);
        } else {
            $result = $this->_checkUidvalidity();
        }

        return $result;
    }

    /**
     * AJAX action: Flag messages.
     *
     * See the list of variables needed for _changed() and
     * _checkUidvalidity().  Additional variables used:
     *   - add: (integer) Set the flag?
     *   - flags: (string) The flags to set (JSON serialized array).
     *   - uid: (string) Indices of the messages to flag (IMAP sequence
     *          string).
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - ViewPort: (object) See _viewPortData().
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
            return $this->_checkUidvalidity();
        }

        if (in_array(Horde_Imap_Client::FLAG_SEEN, $flags)) {
            $this->_queue->poll(array_keys($indices->indices()));
        }

        $result = new stdClass;
        if ($change) {
            $result->ViewPort = $this->_viewPortData(true);
        } else {
            $result->ViewPort = new stdClass;
            $result->ViewPort->cacheid = $this->_mbox->cacheid;
            $result->ViewPort->update = 1;
            $result->ViewPort->view = $this->_mbox->form_to;
        }

        return $result;
    }

    /**
     * AJAX action: Delete messages.
     *
     * See the list of variables needed for _changed(),
     * _generateDeleteResult(), and _checkUidvalidity(). Additional variables
     * used:
     *   - uid: (string) Indices of the messages to delete (IMAP sequence
     *          string).
     *
     * @return mixed  False on failure, or an object (see
     *                _generateDeleteResult() for format).
     */
    public function deleteMessages()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if (!count($indices)) {
            return false;
        }

        $change = $this->_changed(true);

        if ($GLOBALS['injector']->getInstance('IMP_Message')->delete($indices)) {
            return $this->_generateDeleteResult($indices, $change, true);
        }

        return is_null($change)
            ? false
            : $this->_checkUidvalidity();
    }

    /**
     * AJAX action: Add contact.
     *
     * Variables used:
     *   - email: (string) The email address to name.
     *   - name: (string) The name associated with the email address.
     *
     * @return boolean  True on success, false on failure.
     */
    public function addContact()
    {
        // Allow name to be empty.
        if (!$this->_vars->email) {
            return false;
        }

        try {
            IMP::addAddress($this->_vars->email, $this->_vars->name);
            $GLOBALS['notification']->push(sprintf(_("%s was successfully added to your address book."), $this->_vars->name ? $this->_vars->name : $this->_vars->email), 'horde.success');
            return true;
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            return false;
        }
    }

    /**
     * AJAX action: Report message as [not]spam.
     *
     * See the list of variables needed for _changed(),
     * _generateDeleteResult(), and _checkUidvalidity(). Additional variables
     * used:
     *   - spam: (integer) 1 to mark as spam, 0 to mark as innocent.
     *   - uid: (string) Indices of the messages to report (IMAP sequence
     *          string).
     *
     * @return mixed  If messages were deleted, data as returned by
     *                _generateDeleteResult(). Else, true.
     */
    public function reportSpam()
    {
        $change = $this->_changed(false);
        $indices = new IMP_Indices_Form($this->_vars->uid);
        $result = true;

        if (IMP_Spam::reportSpam($indices, $this->_vars->spam ? 'spam' : 'notspam')) {
            $result = $this->_generateDeleteResult($indices, $change);
        } elseif (!is_null($change)) {
            $result = $this->_checkUidvalidity(true);
        }

        return $result;
    }

    /**
     * AJAX action: Blacklist/whitelist addresses from messages.
     *
     * See the list of variables needed for _changed(),
     * _generateDeleteResult(), and _checkUidvalidity(). Additional variables
     * used:
     *   - blacklist: (integer) 1 to blacklist, 0 to whitelist.
     *   - uid: (string) Indices of the messages to report (IMAP sequence
     *          string).
     *
     * @return mixed  False on failure, or an object (see
     *                _generateDeleteResult() for format).
     */
    public function blacklist()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if (!count($indices)) {
            return false;
        }

        $result = false;

        if ($this->_vars->blacklist) {
            $change = $this->_changed(false);
            if (!is_null($change)) {
                try {
                    if ($GLOBALS['injector']->getInstance('IMP_Filter')->blacklistMessage($indices, false)) {
                        $result = $this->_generateDeleteResult($indices, $change);
                    }
                } catch (Horde_Exception $e) {
                    $result = $this->_checkUidvalidity();
                }
            }
        } else {
            try {
                $GLOBALS['injector']->getInstance('IMP_Filter')->whitelistMessage($indices, false);
            } catch (Horde_Exception $e) {
                $result = $this->_checkUidvalidity();
            }
        }

        return $result;
    }

    /**
     * AJAX action: Generate data necessary to display a complete message.
     *
     * See the list of variables needed for _changed() and
     * _checkUidvalidity().  Additional variables used:
     *   - uid: (string) Index of the messages to preview (IMAP sequence
     *          string) - must be single index.
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - message: (object) Return from IMP_View_ShowMessage::showMessage().
     */
    public function showMessage()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        list($mbox, $idx) = $indices->getSingle();
        if (!$idx) {
            return false;
        }

        $change = $this->_changed(false);
        if (is_null($change)) {
            return false;
        }

        $args = array(
            'mailbox' => $mbox,
            'uid' => $idx
        );
        $result = new stdClass;

        try {
            $show_msg = new IMP_Views_ShowMessage();
            $result->message = (object)$show_msg->showMessage($args);
            if (isset($result->message->error)) {
                $result = $this->_checkUidvalidity($result);
            } else {
                $result->message->view = $this->_vars->view;
            }
        } catch (IMP_Imap_Exception $e) {
            $result->message = new stdClass;
            $result->message->error = $e->getMessage();
            $result->message->errortype = 'horde.error';
            $result->message->mailbox = $args['mailbox'];
            $result->message->uid = $args['uid'];
            $result->message->view = $this->_vars->view;
        }

        return $result;
    }

    /**
     * AJAX action: Generate data necessary to display preview message.
     *
     * See the list of variables needed for _changed() and
     * _checkUidvalidity().  Additional variables used:
     *   - uid: (string) Index of the messages to preview (IMAP sequence
     *          string) - must be single index.
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - preview: (object) Return from IMP_View_ShowMessage::showMessage().
     *   - ViewPort: (object) See _viewPortData(). (Only updated cacheid
     *                        entry; don't do mailbox poll here).
     */
    public function showPreview()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        list($mbox, $idx) = $indices->getSingle();
        if (!$idx) {
            return false;
        }

        $change = $this->_changed(false);
        if (is_null($change)) {
            return false;
        }

        $args = array(
            'mailbox' => $mbox,
            'preview' => true,
            'uid' => $idx
        );
        $result = new stdClass;
        $result->preview = new stdClass;

        try {
            $show_msg = new IMP_Views_ShowMessage();
            $result->preview = (object)$show_msg->showMessage($args);
            if (isset($result->preview->error)) {
                $result = $this->_checkUidvalidity($result);
            } else {
                $result->preview->view = $this->_vars->view;

                if ($change) {
                    $result->ViewPort = $this->_viewPortData(true);
                } else {
                    /* Cache ID has changed due to viewing this message. So
                     * update the cacheid in the ViewPort. */
                    $cacheid = $this->_mbox->cacheid;
                    if ($cacheid != $this->_vars->cacheid) {
                        $result->ViewPort = new stdClass;
                        $result->ViewPort->cacheid = $cacheid;
                        $result->ViewPort->update = 1;
                        $result->ViewPort->view = $this->_mbox->form_to;
                    }
                }

                $this->_queue->poll($mbox);
            }
        } catch (IMP_Imap_Exception $e) {
            $result->preview->error = $e->getMessage();
            $result->preview->errortype = 'horde.error';
            $result->preview->mailbox = $args['mailbox'];
            $result->preview->uid = $args['uid'];
            $result->preview->view = $this->_vars->view;
        }

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
     *          string).
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - body: (string) The body text of the message.
     *   - format: (string) Either 'text' or 'html'.
     *   - fwd_list: (array) See IMP_Dimp::getAttachmentInfo().
     *   - header: (array) The headers of the message.
     *   - identity: (integer) The identity ID to use for this message.
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - opts: (array) Additional options needed for DimpCompose.fillForm().
     *   - type: (string) The input 'type' value.
     *   - ViewPort: (object) See _viewPortData().
     */
    public function getForwardData()
    {
        try {
            list($imp_compose, $imp_contents) = $this->_initCompose();

            $fwd_map = array(
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
            $result->opts->fwd_list = IMP_Dimp::getAttachmentInfo($imp_compose);
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
            $result = $this->_checkUidvalidity();
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
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) See IMP_Compose::replyMessage().
     *   - uid: (string) Indices of the messages to reply to (IMAP sequence
     *          string).
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
     *   - ViewPort: (object) See _viewPortData().
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

            $reply_msg = $imp_compose->replyMessage($reply_map[$this->_vars->type], $imp_contents);

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
            $result = $this->_checkUidvalidity();
        }

        return $result;
    }

    /**
     * AJAX action: Get compose redirect data.
     *
     * Variables used:
     *   - uid: (string) Index of the message to redirect (IMAP sequence
     *          string).
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *   - type: (string) The input 'type' value.
     */
    public function getRedirectData()
    {
        list($imp_compose, $imp_contents) = $this->_initCompose();

        $imp_compose->redirectMessage($imp_contents);

        $ob = new stdClass;
        $ob->imp_compose = $imp_compose->getCacheId();
        $ob->type = $this->_vars->type;

        return $ob;
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
        $imp_compose = $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->_vars->imp_compose);
        $imp_compose->destroy('cancel');

        return true;
    }

    /**
     * AJAX action: Delete a draft.
     *
     * Variables used:
     *   - imp_compose: (string) The IMP_Compose cache identifier.
     *
     * @return boolean  True.
     */
    public function deleteDraft()
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
                $GLOBALS['notification']->push(sprintf(_("Deleted attachment \"%s\"."), Horde_Mime::decode($imp_compose[$val]['part']->getName(true), 'UTF-8')), 'horde.success');
                unset($imp_compose[$val]);
            }
        }

        return true;
    }

    /**
     * AJAX action: Purge deleted messages.
     *
     * See the list of variables needed for _changed(), and
     * _generateDeleteResult().  Additional variables used:
     *   - uid: (string) Indices of the messages to purge (IMAP sequence
     *          string).
     *
     * @return mixed  False on failure, or an object (see
     *                _generateDeleteResult() for format).
     */
    public function purgeDeleted()
    {
        global $injector;

        $change = $this->_changed(true);
        if (is_null($change)) {
            return false;
        }

        if (!$change) {
            $sort = $this->_mbox->getSort();
            $change = ($sort['by'] == Horde_Imap_Client::SORT_THREAD);
        }

        $expunged = $injector->getInstance('IMP_Message')->expungeMailbox(array(strval($this->_mbox) => 1), array('list' => true));

        if (!($expunge_count = count($expunged))) {
            return false;
        }

        if ($expunge_count == 1) {
            $GLOBALS['notification']->push(sprintf(_("1 message was purged from \"%s\"."), $this->_mbox->display), 'horde.success');
        } else {
            $GLOBALS['notification']->push(sprintf(_("%s messages were purged from \"%s\"."), $expunge_count, $this->_mbox->display), 'horde.success');
        }

        return $this->_generateDeleteResult($expunged, $change);
    }

    /**
     * AJAX action: Send a Message Disposition Notification (MDN).
     *
     * Variables used:
     *   - uid: (string) Index of the messages to preview (IMAP sequence
     *          string) - must be single index.
     *
     * @return mixed  False on failure, or an object with these properties:
     *   - mbox: (string) Mailbox of message.
     *   - uid: (integer) UID of message.
     */
    public function sendMDN()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if (count($indices) != 1) {
            return false;
        }

        list($mbox, $uid) = $indices->getSingle();

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->headerText(array(
            'peek' => false
        ));

        try {
            $fetch_ret = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->fetch($mbox, $query, array(
                'ids' => new Horde_Imap_Client_Ids($uid)
            ));
        } catch (IMP_Imap_Exception $e) {
            $e->notify(_("The Message Disposition Notification was not sent. This is what the server said") . ': ' . $e->getMessage());
            return false;
        }

        $imp_ui = new IMP_Ui_Message();
        $imp_ui->MDNCheck($mbox, $uid, $fetch_ret[$uid]->getHeaderText(0, Horde_Imap_Client_Data_Fetch::HEADER_PARSE), true);

        $GLOBALS['notification']->push(_("The Message Disposition Notification was sent successfully."), 'horde.success');

        $result = new stdClass;
        $result->mbox = strval($mbox);
        $result->uid = $uid;

        return $result;
    }

    /**
     * AJAX action: strip attachment.
     *
     * See the list of variables needed for _changed() and
     * _checkUidvalidity().  Additional variables used:
     *   - uid: (string) Index of the messages to preview (IMAP sequence
     *          string) - must be single index.
     *
     * @return mixed  False on failure, the return from showPreview() on
     *                success along with these properties:
     *   - newuid: (integer) UID of new message.
     *   - oldmbox: (string) Mailbox of old message.
     *   - olduid: (integer) UID of old message.
     *   - ViewPort: (object) See _viewPortData().
     */
    public function stripAttachment()
    {
        $indices = new IMP_Indices_Form($this->_vars->uid);
        if (count($indices) != 1) {
            return false;
        }

        $change = $this->_changed(false);
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

        $this->_vars->uid = strval($new_indices);

        $result = $this->showPreview();
        $new_indices_list = $new_indices->getSingle();
        $result->newuid = $new_indices_list[1];
        $old_indices_list = $indices->getSingle();
        $result->oldmbox = strval($old_indices_list[0]);
        $result->olduid = $old_indices_list[1];
        $result->ViewPort = $this->_viewPortData(true);

        return $result;
    }

    /**
     * AJAX action: Add an attachment to a compose message.
     *
     * Variables used:
     *   - composeCache: (string) The IMP_Compose cache identifier.
     *
     * @return object  An object with the following entries:
     *   - atc: TODO
     *   - error: (string) An error message.
     *   - imp_compose: TODO
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
            $result->atc = end(IMP_Dimp::getAttachmentInfo($imp_compose));
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
     *   - save_sent_mail_folder: (string) base64url encoded version of
     *                            sent mailbox to use.
     *
     * @return object  An object with the following entries:
     *   - action: (string) The AJAX action string
     *   - draft_delete: (integer) If set, remove auto-saved drafts.
     *   - encryptjs: (array) Javascript to run after encryption failure.
     *   - flag: (array) See IMP_Ajax_Queue::generate().
     *   - identity: (integer) If set, this is the identity that is tied to
     *               the current recipient address.
     *   - log: (array) Maillog information
     *   - mailbox: (array) See _getMailboxResponse().
     *   - mbox: (string) Mailbox of original message.
     *   - success: (integer) 1 on success, 0 on failure.
     *   - uid: (integer) IMAP UID of original message.
     */
    public function sendMessage()
    {
        list($result, $imp_compose, $headers, $identity) = $this->_dimpComposeSetup();
        if (!IMP::canCompose()) {
            $result->success = 0;
        }
        if (!$result->success) {
            return $result;
        }

        $headers['replyto'] = $identity->getValue('replyto_addr');

        /* Use IMP_Tree to determine whether the sent mail folder was
         * created. */
        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imptree->eltDiffStart();

        $sm_displayed = !empty($GLOBALS['conf']['user']['select_sentmail_folder']) && !$GLOBALS['prefs']->isLocked('sent_mail_folder');

        $options = array(
            'encrypt' => ($GLOBALS['prefs']->isLocked('default_encrypt') ? $GLOBALS['prefs']->getValue('default_encrypt') : $this->_vars->encrypt),
            'html' => $this->_vars->html,
            'identity' => $identity,
            'priority' => $this->_vars->priority,
            'readreceipt' => $this->_vars->request_read_receipt,
            'save_attachments' => $this->_vars->save_attachments_select,
            'save_sent' => ($sm_displayed
                            ? (bool)$this->_vars->save_sent_mail
                            : $identity->getValue('save_sent_mail')),
            'sent_folder' => ($sm_displayed
                              ? (isset($this->_vars->save_sent_mail_folder) ? IMP_Mailbox::formFrom($this->_vars->save_sent_mail_folder) : $identity->getValue('sent_mail_folder'))
                              : $identity->getValue('sent_mail_folder'))
        );

        try {
            $sent = $imp_compose->buildAndSendMessage($this->_vars->message, $headers, $options);
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
                Horde::outputInlineScript(true);
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

        if ($sent) {
            $GLOBALS['notification']->push(empty($headers['subject']) ? _("Message sent successfully.") : sprintf(_("Message \"%s\" sent successfully."), Horde_String::truncate($headers['subject'])), 'horde.success');
        }

        /* Update maillog information. */
        if (!empty($GLOBALS['conf']['maillog']['use_maillog'])) {
            $in_reply_to = $imp_compose->getMetadata('in_reply_to');
            if (!empty($in_reply_to) &&
                ($tmp = IMP_Dimp::getMsgLogInfo($in_reply_to))) {
                $result->log = $tmp;
            }
        }

        $result->mbox = strval($imp_compose->getMetadata('mailbox'));
        $result->uid = $imp_compose->getMetadata('uid');

        $imp_compose->destroy('send');

        $result->mailbox = $this->_getMailboxResponse($imptree);

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
     *   - log: (array) TODO
     *   - mbox: (array) TODO
     *   - success: (integer) 1 on success, 0 on failure.
     *   - uid: (integer) TODO
     */
    public function redirectMessage()
    {
        $result = new stdClass;
        $result->action = $this->_action;
        $result->success = 1;

        try {
            $imp_compose = $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->_vars->composeCache);
            $imp_compose->sendRedirectMessage($this->_vars->redirect_to);

            $result->mbox = strval($imp_compose->getMetadata('mailbox'));
            $result->uid = $imp_compose->getMetadata('uid');

            $contents = $imp_compose->getContentsOb();
            $headers = $contents->getHeaderOb();

            $subject = $headers->getValue('subject');
            $GLOBALS['notification']->push(empty($subject) ? _("Message redirected successfully.") : sprintf(_("Message \"%s\" redirected successfully."), Horde_String::truncate($subject)), 'horde.success');

            if (!empty($GLOBALS['conf']['maillog']['use_maillog']) &&
                ($tmp = IMP_Dimp::getMsgLogInfo($headers->getValue('message-id')))) {
                $result->log = $tmp;
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $result->success = 0;
        }

        return $result;
    }

    /**
     * Setup environment for dimp compose actions.
     *
     * Variables used:
     *   - composeCache: (string) The IMP_Compose cache identifier.
     *   - from: (string) TODO
     *   - identity: (integer) The identity to use
     *
     * @return array  An array with the following values:
     * <pre>
     * [0] (object) AJAX base return object (with action and success
     *     parameters defined).
     * [1] (IMP_Compose) The IMP_Compose object for the message.
     * [2] (array) The list of headers for the object.
     * [3] (Horde_Prefs_Identity) The identity used for the composition.
     * </pre>
     */
    protected function _dimpComposeSetup()
    {
        global $injector, $notification, $prefs;

        $result = new stdClass;
        $result->action = $this->_action;
        $result->success = 1;

        /* Set up identity. */
        $identity = $injector->getInstance('IMP_Identity');
        if (isset($this->_vars->identity) &&
            !$prefs->isLocked('default_identity')) {
            $identity->setDefault($this->_vars->identity);
        }

        /* Set up the From address based on the identity. */
        $headers = array();
        try {
            $headers['from'] = $identity->getFromLine(null, $this->_vars->from);
        } catch (Horde_Exception $e) {
            $notification->push($e);
            $result->success = 0;
            return array($result);
        }

        $imp_ui = $injector->getInstance('IMP_Ui_Compose');
        $headers['to'] = $imp_ui->getAddressList($this->_vars->to);
        if ($prefs->getValue('compose_cc')) {
            $headers['cc'] = $imp_ui->getAddressList($this->_vars->cc);
        }
        if ($prefs->getValue('compose_bcc')) {
            $headers['bcc'] = $imp_ui->getAddressList($this->_vars->bcc);
        }
        $headers['subject'] = $this->_vars->subject;

        $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($this->_vars->composeCache);

        return array($result, $imp_compose, $headers, $identity);
    }

    /**
     * TODO
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
        list($result, $imp_compose, $headers, $identity) = $this->_dimpComposeSetup();
        if (!$result->success) {
            return $result;
        }

        try {
            $res = $imp_compose->saveDraft($headers, $this->_vars->message, array(
                'html' => $this->_vars->html,
                'priority' => $this->_vars->priority,
                'readreceipt' => $this->_vars->request_read_receipt
            ));
            if ($this->_action == 'autoSaveDraft') {
                $GLOBALS['notification']->push(_("Draft automatically saved."), 'horde.message');
            } else {
                $GLOBALS['notification']->push($res);
                if ($GLOBALS['prefs']->getValue('close_draft')) {
                    $imp_compose->destroy('save_draft');
                }
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
     *
     * @return mixed  The JSON result, possibly with ViewPort information
     *                added if UID validity has changed.
     */
    protected function _checkUidvalidity($result = false)
    {
        try {
            $this->_mbox->uidvalid;
        } catch (IMP_Exception $e) {
            if (!is_object($result)) {
                $result = new stdClass;
            }
            $result->ViewPort = $this->_viewPortData(true);
        }

        return $result;
    }

    /**
     * Generates the delete data needed for dimpbase.js.
     *
     * See the list of variables needed for _viewPortData().
     *
     * @param IMP_Indices $indices  An indices object.
     * @param boolean $changed      If true, add ViewPort information.
     * @param boolean $nothread     Skip thread sort check if not hiding
     *                              messages.
     *
     * @return object  An object with the following entries:
     *   - ViewPort: (object) See _viewPortData().
     */
    protected function _generateDeleteResult($indices, $change,
                                             $nothread = false)
    {
        /* Check if we need to update thread information. */
        if (!$change && (!$nothread || !empty($del->remove))) {
            $sort = $this->_mbox->getSort();
            $change = ($sort['by'] == Horde_Imap_Client::SORT_THREAD);
        }

        $result = new stdClass;

        if ($change) {
            $result->ViewPort = $this->_viewPortData(true);
        } else {
            $result->ViewPort = new stdClass;
            $result->ViewPort->cacheid = $this->_mbox->cacheid;
            if ($this->_mbox->hideDeletedMsgs(true)) {
                if ($this->_mbox->search) {
                    $disappear = array();
                    foreach ($indices as $key => $val) {
                        $disappear[] = IMP::base64urlEncode($key . IMP_View_ListMessages::IDX_SEP . $val);
                    }
                } else {
                    $disappear = end($indices->getSingle(true));
                }
                $result->ViewPort->disappear = $disappear;
            }
            $result->ViewPort->update = 1;
            $result->ViewPort->view = $this->_mbox->form_to;
        }

        $this->_queue->poll(array_keys($indices->indices()));

        return $result;
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

        return ($this->_mbox->cacheid != $this->_vars->cacheid);
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
            'applyfilter' => $this->_vars->applyfilter,
            'cache' => $this->_vars->cache,
            'cacheid' => $this->_vars->cacheid,
            'change' => $change,
            'initial' => $this->_vars->initial,
            'mbox' => strval($this->_mbox),
            'qsearch' => $this->_vars->qsearch,
            'qsearchfilter' => $this->_vars->qsearchfilter,
            'qsearchflag' => $this->_vars->qsearchflag,
            'qsearchflagnot' => $this->_vars->qsearchflagnot,
            'qsearchmbox' => $this->_vars->qsearchmbox,
            'rangeslice' => $this->_vars->rangeslice,
            'requestid' => $this->_vars->requestid,
            'sortby' => $this->_vars->sortby,
            'sortdir' => $this->_vars->sortdir
        );

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
     * Formats the response to send to javascript code when dealing with
     * mailbox operations.
     *
     * @param IMP_Tree $imptree  An IMP_Tree object.
     * @param array $changes     An array with three sub arrays - to be used
     *                           instead of the return from
     *                           $imptree->eltDiff():
     *                           'a' - a list of mailboxes/objects to add
     *                           'c' - a list of changed mailboxes
     *                           'd' - a list of mailboxes to delete
     *
     * @return array  The object used by the JS code to update the folder
     *                tree.
     */
    protected function _getMailboxResponse($imptree, $changes = null)
    {
        if (is_null($changes)) {
            $changes = $imptree->eltDiff();
        }
        if (empty($changes)) {
            return false;
        }

        $result = array();

        if (!empty($changes['a'])) {
            $result['a'] = array();
            foreach ($changes['a'] as $val) {
                $result['a'][] = $this->_createMailboxElt(is_object($val) ? $val : $imptree[$val]);
            }
        }

        if (!empty($changes['c'])) {
            $result['c'] = array();
            foreach ($changes['c'] as $val) {
                // Skip the base element, since any change there won't ever be
                // updated on-screen.
                if ($val != IMP_Imap_Tree::BASE_ELT) {
                    $result['c'][] = $this->_createMailboxElt($imptree[$val]);
                }
            }
        }

        if (!empty($changes['d'])) {
            $result['d'] = array_map('strval', array_reverse($changes['d']));
        }

        return $result;
    }

    /**
     * Create an object used by DimpCore to generate the folder tree.
     *
     * @param IMP_Mailbox $elt  A mailbox object.
     *
     * @return stdClass  The element object. Contains the following items:
     *   - ch: (boolean) [children] Does the mailbox contain children?
     *         DEFAULT: no
     *   - cl: (string) [class] The CSS class.
     *         DEFAULT: 'base'
     *   - co: (boolean) [container] Is this mailbox a container element?
     *         DEFAULT: no
     *   - i: (string) [icon] A user defined icon to use.
     *        DEFAULT: none
     *   - l: (string) [label] The mailbox display label.
     *        DEFAULT: 'm' val
     *   - m: (string) [mbox] The mailbox value (base64url encoded).
     *   - n: (boolean) [non-imap] A non-IMAP element?
     *        DEFAULT: no
     *   - pa: (string) [parent] The parent element.
     *         DEFAULT: DIMP.conf.base_mbox
     *   - po: (boolean) [polled] Is the element polled?
     *         DEFAULT: no
     *   - s: (boolean) [special] Is this a "special" element?
     *        DEFAULT: no
     *   - t: (string) [title] Mailbox title.
     *        DEFAULT: 'm' val
     *   - un: (boolean) [unsubscribed] Is this mailbox unsubscribed?
     *         DEFAULT: no
     *   - v: (integer) [virtual] Virtual folder? 0 = not vfolder, 1 = system
     *        vfolder, 2 = user vfolder
     *        DEFAULT: 0
     */
    protected function _createMailboxElt(IMP_Mailbox $elt)
    {
        $ob = new stdClass;

        if ($elt->children) {
            $ob->ch = 1;
        }
        $ob->m = $elt->form_to;

        $label = $elt->label;
        if ($ob->m != $label) {
            $ob->t = $label;
        }

        $tmp = htmlspecialchars($elt->abbrev_label);
        if ($ob->m != $tmp) {
            $ob->l = $tmp;
        }

        $parent = $elt->parent;
        if ($parent != IMP_Imap_Tree::BASE_ELT) {
            $ob->pa = $parent->form_to;
        }
        if ($elt->vfolder) {
            $ob->v = $elt->editvfolder ? 2 : 1;
        }
        if (!$elt->sub) {
            $ob->un = 1;
        }

        if ($elt->container) {
            $ob->cl = 'exp';
            $ob->co = 1;
            if ($elt->nonimap) {
                $ob->n = 1;
            }
        } else {
            if ($elt->polled) {
                $ob->po = 1;
                $this->_queue->poll($elt);
            }

            if ($elt->special) {
                $ob->s = 1;
            } elseif (empty($ob->v) && $elt->children) {
                $ob->cl = 'exp';
            }
        }

        $icon = $elt->icon;
        if ($icon->user_icon) {
            $ob->cl = 'customimg';
            $ob->i = strval($icon->icon);
        } else {
            $ob->cl = $icon->class;
        }

        return $ob;
    }

}
