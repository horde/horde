<?php
/**
 * Defines the AJAX interface for IMP.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Ajax_Application extends Horde_Ajax_Application_Base
{
    /**
     * The list of actions that require readonly access to the session.
     *
     * @var array
     */
    protected $_readOnly = array(
        'GetReplyData', 'Html2Text', 'Text2Html'
    );

    /**
     * Returns a notification handler object to use to output any
     * notification messages triggered by the AJAX action.
     *
     * @return Horde_Notification_Handler_Base  The notification handler.
     */
    public function notificationHandler()
    {
        return $GLOBALS['imp_notify'];
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
        return ($this->_action == 'AddAttachment')
            ? 'js-json'
            : parent::responseType();
    }

    /**
     * AJAX action: Create a mailbox.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'mbox' - (string) The name of the new mailbox.
     * 'parent' - (string) The parent mailbox.
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'mailbox' - (object) Mailboxes that were altered. Contains the
     *             following properties:
     *   'a' - (array) Mailboxes that were added.
     *   'c' - (array) Mailboxes that were changed.
     *   'd' - (array) Mailboxes that were deleted.
     * </pre>
     */
    public function CreateMailbox($vars)
    {
        if (!$vars->mbox) {
            return false;
        }

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imptree->eltDiffStart();

        $new = Horde_String::convertCharset($vars->mbox, Horde_Nls::getCharset(), 'UTF7-IMAP');
        try {
            $new = $imptree->createMailboxName($vars->parent, $new);

            if ($result =$GLOBALS['injector']->getInstance('IMP_Folder')->create($new, $GLOBALS['prefs']->getValue('subscribe'))) {
                $result = new stdClass;
                $result->mailbox = $this->_getMailboxResponse($imptree);
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            $result = false;
        }

        return $result;
    }

    /**
     * AJAX action: Delete a mailbox.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'mbox' - (string) The full mailbox name to delete.
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'mailbox' - (object) Mailboxes that were altered. Contains the
     *             following properties:
     *   'a' - (array) Mailboxes that were added.
     *   'c' - (array) Mailboxes that were changed.
     *   'd' - (array) Mailboxes that were deleted.
     * </pre>
     */
    public function DeleteMailbox($vars)
    {
        if (!$vars->mbox) {
            return false;
        }

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imptree->eltDiffStart();

        if ($GLOBALS['imp_search']->isEditableVFolder($vars->mbox)) {
            $GLOBALS['notification']->push(sprintf(_("Deleted Virtual Folder \"%s\"."), $GLOBALS['imp_search']->getLabel($vars->mbox)), 'horde.success');
            $GLOBALS['imp_search']->deleteSearchQuery($vars->mbox);
            $result = true;
        } else {
            $result = $GLOBALS['injector']->getInstance('IMP_Folder')->delete(array($vars->mbox));
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
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'new_name' - (string) New mailbox name (child node).
     * 'new_parent' - (string) New parent name.
     * 'old_name' - (string) Full name of old mailbox.
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'mailbox' - (object) Mailboxes that were altered. Contains the
     *             following properties:
     *   'a' - (array) Mailboxes that were added.
     *   'c' - (array) Mailboxes that were changed.
     *   'd' - (array) Mailboxes that were deleted.
     * </pre>
     */
    public function RenameMailbox($vars)
    {
        if (!$vars->old_name || !$vars->new_name) {
            return false;
        }

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imptree->eltDiffStart();

        $result = false;

        try {
            $new = Horde_String::convertCharset($imptree->createMailboxName($vars->new_parent, $vars->new_name), Horde_Nls::getCharset(), 'UTF7-IMAP');

            if (($vars->old_name != $new) &&
                $GLOBALS['injector']->getInstance('IMP_Folder')->rename($vars->old_name, $new)) {
                $result = new stdClass;
                $result->mailbox = $this->_getMailboxResponse($imptree);
            }
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
        }

        return $result;
    }

    /**
     * AJAX action: Empty a mailbox.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'mbox' - (string) The full mailbox name to empty.
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'mbox' - (string) The mailbox that was emptied.
     * </pre>
     */
    public function EmptyMailbox($vars)
    {
        if (!$vars->mbox) {
            return false;
        }

        $GLOBALS['injector']->getInstance('IMP_Message')->emptyMailbox(array($vars->mbox));

        $result = new stdClass;
        $result->mbox = $vars->mbox;

        return $result;
    }

    /**
     * AJAX action: Flag all messages in a mailbox.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'flags' - (string) The flags to set (JSON serialized array).
     * 'mbox' - (string) The full malbox name.
     * 'set' - (integer) Set the flags?
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'flags' - (array) The list of flags that were set.
     * 'mbox' - (string) The full mailbox name.
     * 'poll' - (array) Mailbox names as the keys, number of unseen messages
     *          as the values.
     * 'set' - (integer) 1 if the flag was set. Unset otherwise.
     * </pre>
     */
    public function FlagAll($vars)
    {
        $flags = Horde_Serialize::unserialize($vars->flags, Horde_Serialize::JSON);
        if (!$vars->mbox || empty($flags)) {
            return false;
        }

        $result = $GLOBALS['injector']->getInstance('IMP_Message')->flagAllInMailbox($flags, array($vars->mbox), $vars->set);

        if ($result) {
            $result = new stdClass;
            $result->flags = $flags;
            $result->mbox = $vars->mbox;
            if ($vars->set) {
                $result->set = 1;
            }

            $poll = $this->_getPollInformation($vars->mbox);
            if (!empty($poll)) {
                $result->poll = array($vars->mbox => $poll[$vars->mbox]);
            }
        }

        return $result;
    }

    /**
     * AJAX action: List mailboxes.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'all' - (integer) 1 to show all mailboxes.
     * 'initial' - (string) 1 to indicate the initial request for mailbox
     *             list.
     * 'mboxes' - (string) The list of mailboxes to process (JSON encoded
     *            array).
     * 'reload' - (integer) 1 to force reload of mailboxes.
     * 'unsub' - (integer) 1 to show unsubscribed mailboxes.
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'mailbox' - (object) Mailboxes that were altered. Contains the
     *             following properties:
     *   'a' - (array) Mailboxes that were added.
     *   'c' - (array) Mailboxes that were changed.
     *   'd' - (array) Mailboxes that were deleted.
     * 'quota' - (array) See _getQuota().
     * </pre>
     */
    public function ListMailboxes($vars)
    {
        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $mask = IMP_Imap_Tree::FLIST_CONTAINER | IMP_Imap_Tree::FLIST_VFOLDER | IMP_Imap_Tree::FLIST_ELT;
        if ($vars->unsub) {
            $mask |= IMP_Imap_Tree::FLIST_UNSUB;
        }

        if (!$vars->all) {
            $mask |= IMP_Imap_Tree::FLIST_NOCHILDREN;
            if ($vars->initial || $vars->reload) {
                $mask |= IMP_Imap_Tree::FLIST_ANCESTORS | IMP_Imap_Tree::FLIST_SAMELEVEL;
            }
        }

        $folder_list = array();
        foreach (Horde_Serialize::unserialize($vars->mboxes, Horde_Serialize::JSON) as $val) {
            $folder_list += $imptree->folderList($mask, $val);
        }

        /* Add special folders explicitly to the initial folder list, since
         * they are ALWAYS displayed and may appear outside of the folder
         * slice requested. */
        if ($vars->initial) {
            foreach ($imptree->getSpecialMailboxes() as $val) {
                if (!is_array($val)) {
                    $val = array($val);
                }

                foreach ($val as $val2) {
                    if (!isset($folder_list[$val2]) &&
                        ($elt = $imptree->element($val2))) {
                        $folder_list[$val2] = $elt;
                    }
                }
            }
        }

        $result = new stdClass;
        $result->mailbox = $this->_getMailboxResponse($imptree, array(
            'a' => array_values($folder_list),
            'c' => array(),
            'd' => array()
        ));

        $quota = $this->_getQuota();
        if (!is_null($quota)) {
            $result['quota'] = $quota;
        }

        return $result;
    }

    /**
     * AJAX action: Poll mailboxes.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _changed() and _viewPortData().
     *                               Additional variables used:
     * <pre>
     * 'view' - (string) The current view (mailbox).
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'poll' - (array) Mailbox names as the keys, number of unseen messages
     *          as the values.
     * 'quota' - (array) See _getQuota().
     * 'ViewPort' - (object) See _viewPortData().
     * </pre>
     */
    public function Poll($vars)
    {
        $changed = false;

        $result = new stdClass;
        $result->poll = array();

        foreach ($GLOBALS['imp_imap']->ob()->statusMultiple($GLOBALS['injector']->getInstance('IMP_Imap_Tree')->getPollList(), Horde_Imap_Client::STATUS_UNSEEN) as $key => $val) {
            $result->poll[$key] = intval($val['unseen']);
        }

        if ($vars->view &&
            ($changed = $this->_changed($vars))) {
            $result->ViewPort = $this->_viewPortData($vars, true);
        }

        if (!is_null($changed)) {
            $quota = $this->_getQuota();
            if (!is_null($quota)) {
                $result->quota = $quota;
            }
        }

        return $result;
    }

    /**
     * AJAX action: Modify list of polled mailboxes.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'add' - (integer) 1 to add to the poll list, 0 to remove.
     * 'mbox' - (string) The full mailbox name to modify.
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'add' - (integer) 1 if added to the poll list, 0 if removed.
     * 'mbox' - (string) The full mailbox name modified.
     * 'poll' - (array) Mailbox names as the keys, number of unseen messages
     *          as the values.
     * </pre>
     */
    public function ModifyPoll($vars)
    {
        if (!$vars->mbox) {
            return false;
        }

        $display_folder = IMP::displayFolder($vars->mbox);

        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

        $result = new stdClass;
        $result->add = intval($vars->add);
        $result->mbox = $vars->mbox;

        if ($vars->add) {
            $imptree->addPollList($vars->view);
            try {
                if ($info = $GLOBALS['imp_imap']->ob()->status($vars->view, Horde_Imap_Client::STATUS_UNSEEN)) {
                    $result->poll = array($vars->view => intval($info['unseen']));
                }
            } catch (Horde_Imap_Client_Exception $e) {}
            $GLOBALS['notification']->push(sprintf(_("\"%s\" mailbox now polled for new mail."), $display_folder), 'horde.success');
        } else {
            $imptree->removePollList($vars->view);
            $GLOBALS['notification']->push(sprintf(_("\"%s\" mailbox no longer polled for new mail."), $display_folder), 'horde.success');
        }

        return $result;
    }

    /**
     * AJAX action: [un]Subscribe to a mailbox.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'mbox' - (string) The full mailbox name to [un]subscribe to.
     * 'sub' - (integer) 1 to subscribe, empty to unsubscribe.
     * </pre>
     *
     * @return boolean  True on success, false on failure.
     */
    public function Subscribe($vars)
    {
        if (!$GLOBALS['prefs']->getValue('subscribe')) {
            return false;
        }

        $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');
        return $vars->sub
            ? $imp_folder->subscribe(array($vars->mbox))
            : $imp_folder->unsubscribe(array($vars->mbox));
    }

    /**
     * AJAX action: Output ViewPort data.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _changed() and _viewPortData().
     *                               Additional variables used:
     * <pre>
     * 'checkcache' - (integer) If 1, only send data if cache has been
     *                invalidated.
     * 'rangeslice' - (string) Range slice. See js/ViewPort.js.
     * 'requestid' - (string) Request ID. See js/ViewPort.js.
     * 'sortby' - (integer) The Horde_Imap_Client sort constant.
     * 'sortdir' - (integer) 0 for ascending, 1 for descending.
     * 'view' - (string) The current full mailbox name.
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'ViewPort' - (object) See _viewPortData().
     * </pre>
     */
    public function ViewPort($vars)
    {
        if (!$vars->view) {
            return false;
        }

        /* Change sort preferences if necessary. */
        if (isset($vars->sortby) || isset($vars->sortdir)) {
            IMP::setSort($vars->sortby, $vars->sortdir, $vars->view);
        }

        $changed = $this->_changed($vars, false);

        if (is_null($changed)) {
            $list_msg = new IMP_Views_ListMessages();
            $result = new stdClass;
            $result->ViewPort = $list_msg->getBaseOb($vars->view);

            $req_id = $vars->requestid;
            if (!is_null($req_id)) {
                $result->ViewPort->requestid = intval($req_id);
            }
        } elseif ($changed || $vars->rangeslice || !$vars->checkcache) {
            $result = new stdClass;
            $result->ViewPort = $this->_viewPortData($vars, $changed);
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * AJAX action: Move messages.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _changed(), _generateDeleteResult(), and
     *                               _checkUidvalidity(). Additional variables
     *                               used:
     * <pre>
     * 'mboxto' - (string) Mailbox to move the message to.
     * 'uid' - (string) Indices of the messages to move (IMAP sequence
     *         string).
     * </pre>
     *
     * @return mixed  False on failure, or an object (see
     *                _generateDeleteResult() for format).
     */
    public function MoveMessages($vars)
    {
        $indices = $GLOBALS['imp_imap']->ob()->utils->fromSequenceString($vars->uid);
        if (!$vars->mboxto || empty($indices)) {
            return false;
        }

        $change = $this->_changed($vars, true);

        if (is_null($change)) {
            return false;
        }

        $result = $GLOBALS['injector']->getInstance('IMP_Message')->copy($vars->mboxto, 'move', $indices);

        if ($result) {
            $result = $this->_generateDeleteResult($vars, $indices, $change);
            /* Need to manually set remove to true since we want to remove
             * message from the list no matter the current pref
             * settings. */
            $result->deleted->remove = 1;

            /* Update poll information for destination folder if necessary.
             * Poll information for current folder will be added by
             * _generateDeleteResult() call above. */
            if ($poll = $this->_getPollInformation($vars->mboxto)) {
                $result->poll = array_merge(isset($result->poll) ? $result->poll : array(), $poll);
            }
        } else {
            $result = $this->_checkUidvalidity($vars);
        }

        return $result;
    }

    /**
     * AJAX action: Copy messages.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _checkUidvalidity(). Additional variables
     *                               used:
     * <pre>
     * 'mboxto' - (string) Mailbox to move the message to.
     * 'uid' - (string) Indices of the messages to copy (IMAP sequence
     *         string).
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'ViewPort' - (object) See _viewPortData().
     * 'poll' - (array) Mailbox names as the keys, number of unseen messages
     *          as the values.
     * </pre>
     */
    public function CopyMessages($vars)
    {
        $indices = $GLOBALS['imp_imap']->ob()->utils->fromSequenceString($vars->uid);
        if (!$vars->mboxto || empty($indices)) {
            return false;
        }

        if ($result = $GLOBALS['injector']->getInstance('IMP_Message')->copy($vars->mboxto, 'copy', $indices)) {
            if ($poll = $this->_getPollInformation($vars->mboxto)) {
                $result->poll = array_merge(isset($result->poll) ? $result->poll : array(), $poll);
            }
        } else {
            $result = $this->_checkUidvalidity($vars);
        }

        return $result;
    }

    /**
     * AJAX action: Flag messages.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _checkUidvalidity(). Additional variables
     *                               used:
     * <pre>
     * 'flags' - (string) The flags to set (JSON serialized array).
     * 'uid' - (string) Indices of the messages to flag (IMAP sequence
     *         string).
     * </pre>
     *
     * @return mixed  True on success, on failure an object with these
     *                entries:
     * <pre>
     * 'ViewPort' - (object) See _viewPortData().
     * </pre>
     */
    public function FlagMessages($vars)
    {
        $indices = $GLOBALS['imp_imap']->ob()->utils->fromSequenceString($vars->uid);
        if (!$vars->flags || empty($indices)) {
            return false;
        }

        $flags = Horde_Serialize::unserialize($vars->flags, Horde_Serialize::JSON);
        $result = false;
        $set = $notset = array();

        foreach ($flags as $val) {
            if ($val[0] == '-') {
                $notset[] = substr($val, 1);
            } else {
                $set[] = $val;
            }
        }

        if (!empty($set)) {
            $result = $GLOBALS['injector']->getInstance('IMP_Message')->flag($set, $indices, true);
        }

        if (!empty($notset)) {
            $result = $GLOBALS['injector']->getInstance('IMP_Message')->flag($notset, $indices, false);
        }

        return $result
            ? true
            : $this->_checkUidvalidity($vars);
    }

    /**
     * AJAX action: Delete messages.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _changed(), _generateDeleteResult(), and
     *                               _checkUidvalidity(). Additional variables
     *                               used:
     * <pre>
     * 'uid' - (string) Indices of the messages to delete (IMAP sequence
     *         string).
     * </pre>
     *
     * @return mixed  False on failure, or an object (see
     *                _generateDeleteResult() for format).
     */
    public function DeleteMessages($vars)
    {
        $indices = $GLOBALS['imp_imap']->ob()->utils->fromSequenceString($vars->uid);
        if (empty($indices)) {
            return false;
        }

        $change = $this->_changed($vars, true);

        if ($GLOBALS['injector']->getInstance('IMP_Message')->delete($indices)) {
            return $this->_generateDeleteResult($vars, $indices, $change, !$GLOBALS['prefs']->getValue('hide_deleted') && !$GLOBALS['prefs']->getValue('use_trash'));
        }

        return is_null($change)
            ? false
            : $this->_checkUidvalidity($vars);
    }

    /**
     * AJAX action: Add contact.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'email' - (string) The email address to name.
     * 'name' - (string) The name associated with the email address.
     * </pre>
     *
     * @return boolean  True on success, false on failure.
     */
    public function AddContact($vars)
    {
        // Allow name to be empty.
        if (!$vars->email) {
            return false;
        }

        try {
            IMP::addAddress($vars->email, $vars->name);
            $GLOBALS['notification']->push(sprintf(_("%s was successfully added to your address book."), $vars->name ? $vars->name : $vars->email), 'horde.success');
            return true;
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            return false;
        }
    }

    /**
     * AJAX action: Report message as [not]spam.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _changed(), _generateDeleteResult(), and
     *                               _checkUidvalidity(). Additional variables
     *                               used:
     * <pre>
     * 'spam' - (integer) 1 to mark as spam, 0 to mark as innocent.
     * 'uid' - (string) Indices of the messages to report (IMAP sequence
     *         string).
     * </pre>
     *
     * @return mixed  False on failure, or an object (see
     *                _generateDeleteResult() for format).
     */
    public function ReportSpam($vars)
    {
        $change = $this->_changed($vars, false);
        $indices = $GLOBALS['imp_imap']->ob()->utils->fromSequenceString($vars->uid);
        $result = false;

        if (IMP_Spam::reportSpam($indices, $vars->spam ? 'spam' : 'notspam')) {
            $result = $this->_generateDeleteResult($vars, $indices, $change);
            /* If result of reportSpam() is non-zero, then we know the message
             * has been removed from the current mailbox. */
            $result->deleted->remove = 1;
        } elseif (!is_null($change)) {
            $result = $this->_checkUidvalidity($vars);
        }

        return $result;
    }

    /**
     * AJAX action: Blacklist/whitelist addresses from messages.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _changed(), _generateDeleteResult(), and
     *                               _checkUidvalidity(). Additional variables
     *                               used:
     * <pre>
     * 'blacklist' - (integer) 1 to blacklist, 0 to whitelist.
     * 'uid' - (string) Indices of the messages to report (IMAP sequence
     *         string).
     * </pre>
     *
     * @return mixed  False on failure, or an object (see
     *                _generateDeleteResult() for format).
     */
    public function Blacklist($vars)
    {
        $indices = $GLOBALS['imp_imap']->ob()->utils->fromSequenceString($vars->uid);
        if (empty($indices)) {
            return false;
        }

        $imp_filter = new IMP_Filter();
        $result = false;

        if ($vars->blacklist) {
            $change = $this->_changed($vars, false);
            if (!is_null($change)) {
                try {
                    if ($imp_filter->blacklistMessage($indices, false)) {
                        $result = $this->_generateDeleteResult($vars, $indices, $change);
                    }
                } catch (Horde_Exception $e) {
                    $result = $this->_checkUidvalidity($vars);
                }
            }
        } else {
            try {
                $imp_filter->whitelistMessage($indices, false);
            } catch (Horde_Exception $e) {
                $result = $this->_checkUidvalidity($vars);
            }
        }

        return $result;
    }

    /**
     * AJAX action: Generate data necessary to display preview message.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _checkUidvalidity(). Additional variables
     *                               used:
     * <pre>
     * 'uid' - (string) Index of the messages to preview (IMAP sequence
     *         string) - must be single index.
     * </pre>
     *
     * @return mixed  False on failure, or an object with the 'preview'
     *                property containing the return value from
     *                IMP_View_ShowMessage::showMessage().
     */
    public function ShowPreview($vars)
    {
        $indices = $GLOBALS['imp_imap']->ob()->utils->fromSequenceString($vars->uid);
        if (count($indices) != 1) {
            return false;
        }

        $ptr = each($indices);
        $args = array(
            'mailbox' => $ptr['key'],
            'preview' => true,
            'uid' => intval(reset($ptr['value']))
        );
        $result = new stdClass;
        $result->preview = new stdClass;

        try {
            /* We know we are going to be exclusively dealing with this
             * mailbox, so select it on the IMAP server (saves some STATUS
             * calls). Open R/W to clear the RECENT flag. */
            $GLOBALS['imp_imap']->ob()->openMailbox($ptr['key'], Horde_Imap_Client::OPEN_READWRITE);
            $show_msg = new IMP_Views_ShowMessage();
            $result->preview = (object)$show_msg->showMessage($args);
            if (isset($result->preview->error)) {
                $result = $this->_checkUidvalidity($vars, $result);
            }
        } catch (Horde_Imap_Client_Exception $e) {
            $result->preview->error = $e->getMessage();
            $result->preview->errortype = 'horde.error';
            $result->preview->mailbox = $args['mailbox'];
            $result->preview->uid = $args['uid'];
        }

        return $result;
    }

    /**
     * AJAX action: Convert HTML to text.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'text' - (string) The text to convert.
     * </pre>
     *
     * @return object  An object with the following entries:
     * <pre>
     * 'text' - (string) The converted text.
     * </pre>
     */
    public function Html2Text($vars)
    {
        $result = new stdClass;
        // Need to replace line endings or else IE won't display line endings
        // properly.
        $result->text = str_replace("\n", "\r\n", Horde_Text_Filter::filter($vars->text, 'html2text', array('charset' => Horde_Nls::getCharset())));

        return $result;
    }

    /**
     * AJAX action: Convert text to HTML.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'text' - (string) The text to convert.
     * </pre>
     *
     * @return object  An object with the following entries:
     * <pre>
     * 'text' - (string) The converted text.
     * </pre>
     */
    public function Text2Html($vars)
    {
        $result = new stdClass;
        $result->text = Horde_Text_Filter::filter($vars->text, 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL));

        return $result;
    }

    /**
     * AJAX action: Get forward compose data.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _checkUidvalidity(). Additional variables
     *                               used:
     * <pre>
     * 'imp_compose' - (string) The IMP_Compose cache identifier.
     * 'type' - (string) See IMP_Compose::forwardMessage().
     * 'uid' - (string) Indices of the messages to forward (IMAP sequence
     *         string).
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'body' - (string) The body text of the message.
     * 'format' - (string) Either 'text' or 'html'.
     * 'fwd_list' - (array) See IMP_Dimp::getAttachmentInfo().
     * 'header' - (array) The headers of the message.
     * 'identity' - (integer) The identity ID to use for this message.
     * 'imp_compose'- (string) The IMP_Compose cache identifier.
     * 'ViewPort' - (object) See _viewPortData().
     * </pre>
     */
    public function GetForwardData($vars)
    {
        $header = array();
        $msg = $header = null;
        $indices = $GLOBALS['imp_imap']->ob()->utils->fromSequenceString($vars->uid);

        $i = each($indices);

        try {
            $imp_contents = IMP_Contents::singleton(reset($i['value']) . IMP::IDX_SEP . $i['key']);
            $imp_compose = IMP_Compose::singleton($vars->imp_compose);
            $fwd_msg = $imp_compose->forwardMessage($vars->type, $imp_contents);
            $header = $fwd_msg['headers'];
            $header['replytype'] = 'forward';

            $result = new stdClass;
            /* Can't open session read-only since we need to store the message
             * cache id. */
            $result->imp_compose = $imp_compose->getCacheId();
            $result->fwd_list = IMP_Dimp::getAttachmentInfo($imp_compose);
            $result->body = $fwd_msg['body'];
            $result->header = $header;
            $result->format = $fwd_msg['format'];
            $result->identity = $fwd_msg['identity'];
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            $result = $this->_checkUidvalidity($vars);
        }

        return $result;
    }

    /**
     * AJAX action: Get reply data.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _checkUidvalidity(). Additional variables
     *                               used:
     * <pre>
     * 'imp_compose' - (string) The IMP_Compose cache identifier.
     * 'type' - (string) See IMP_Compose::replyMessage().
     * 'uid' - (string) Indices of the messages to reply to (IMAP sequence
     *         string).
     * </pre>
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'body' - (string) The body text of the message.
     * 'format' - (string) Either 'text' or 'html'.
     * 'header' - (array) The headers of the message.
     * 'identity' - (integer) The identity ID to use for this message.
     * 'imp_compose'- (string) The IMP_Compose cache identifier.
     * 'ViewPort' - (object) See _viewPortData().
     * </pre>
     */
    public function GetReplyData($vars)
    {
        $indices = $GLOBALS['imp_imap']->ob()->utils->fromSequenceString($vars->uid);
        $i = each($indices);

        try {
            $imp_contents = IMP_Contents::singleton(reset($i['value']) . IMP::IDX_SEP . $i['key']);
            $imp_compose = IMP_Compose::singleton($vars->imp_compose);
            $reply_msg = $imp_compose->replyMessage($vars->type, $imp_contents);
            $header = $reply_msg['headers'];
            $header['replytype'] = 'reply';

            $result = new stdClass;
            $result->imp_compose = $imp_compose->getCacheId();
            $result->format = $reply_msg['format'];
            $result->body = $reply_msg['body'];
            $result->header = $header;
            $result->identity = $reply_msg['identity'];
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            $result = $this->_checkUidvalidity($vars);
        }

        return $result;
    }

    /**
     * AJAX action: Cancel compose.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'imp_compose' - (string) The IMP_Compose cache identifier.
     * </pre>
     *
     * @return boolean  True.
     */
    public function CancelCompose($vars)
    {
        $imp_compose = IMP_Compose::singleton($vars->imp_compose);
        $imp_compose->destroy(false);

        return true;
    }

    /**
     * AJAX action: Delete a draft.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'imp_compose' - (string) The IMP_Compose cache identifier.
     * </pre>
     *
     * @return boolean  True.
     */
    public function DeleteDraft($vars)
    {
        $imp_compose = IMP_Compose::singleton($vars->imp_compose);
        $imp_compose->destroy(false);

        if ($draft_uid = $imp_compose->getMetadata('draft_uid')) {
            $idx_array = array($draft_uid . IMP::IDX_SEP . IMP::folderPref($GLOBALS['prefs']->getValue('drafts_folder'), true));
            $GLOBALS['injector']->getInstance('IMP_Message')->delete($idx_array, array('nuke' => true));
        }

        return true;
    }

    /**
     * AJAX action: Delete an attachment from compose data.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'atc_indices' - (string) Attachment IDs to delete.
     * 'imp_compose' - (string) The IMP_Compose cache identifier.
     * </pre>
     *
     * @return boolean  True.
     */
    public function DeleteAttach($vars)
    {
        if ($vars->atc_indices) {
            $imp_compose = IMP_Compose::singleton($vars->imp_compose);
            foreach ($imp_compose->deleteAttachment($vars->atc_indices) as $val) {
                $GLOBALS['notification']->push(sprintf(_("Deleted attachment \"%s\"."), Horde_Mime::decode($val)), 'horde.success');
            }
        }

        return true;
    }

    /**
     * AJAX action: Generate data necessary to display preview message.
     *
     * @param Horde_Variables $vars  Variables used: NONE.
     *
     * @return mixed  False on failure, or an object with the following
     *                entries:
     * <pre>
     * 'linkTags' - (array) TODO
     * 'portal' - (string) The portal HTML data.
     * </pre>
     */
    public function ShowPortal($vars)
    {
        // Load the block list. Blocks are located in $dimp_block_list.
        // KEY: Block label; VALUE: Horde_Block object
        require IMP_BASE . '/config/portal.php';

        $blocks = $linkTags = array();
        $css_load = array('imp' => true);

        foreach ($dimp_block_list as $title => $block) {
            if ($block['ob'] instanceof Horde_Block) {
                $app = $block['ob']->getApp();
                // TODO: Fix CSS.
                $content = $block['ob']->getContent();
                $css_load[$app] = true;
                // Don't do substitutions on our own blocks.
                if ($app != 'imp') {
                    $content = preg_replace('/<a href="([^"]+)"/',
                                            '<a onclick="DimpBase.go(\'app:' . $app . '\', \'$1\');return false"',
                                            $content);
                    if (preg_match_all('/<link .*?rel="stylesheet".*?\/>/',
                                       $content, $links)) {
                        $content = str_replace($links[0], '', $content);
                        foreach ($links[0] as $link) {
                            if (preg_match('/href="(.*?)"/', $link, $href)) {
                                $linkOb = new stdClass;
                                $linkOb->href = $href[1];
                                if (preg_match('/media="(.*?)"/', $link, $media)) {
                                    $linkOb->media = $media[1];
                                }
                                $linkTags[] = $linkOb;
                            }
                        }
                    }
                }
                if (!empty($content)) {
                    $entry = array(
                        'app' => $app,
                        'content' => $content,
                        'title' => $title,
                        'class' => empty($block['class']) ? 'headerbox' : $block['class'],
                    );
                    if (!empty($block['domid'])) {
                        $entry['domid'] = $block['domid'];
                    }
                    if (!empty($block['tag'])) {
                        $entry[$block['tag']] = true;
                    }
                    $blocks[] = $entry;
                }
            }
        }

        $result = new stdClass;
        $result->portal = '';
        if (!empty($blocks)) {
            $t = $GLOBALS['injector']->createInstance('Horde_Template');
            $t->set('block', $blocks);
            $result->portal = $t->fetch(IMP_TEMPLATES . '/imp/portal.html');
        }
        $result->linkTags = $linkTags;

        return $result;
    }

    /**
     * AJAX action: Purge deleted messages.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _changed(), and _generateDeleteResult().
     *                               Additional variables used:
     * <pre>
     * 'uid' - (string) Indices of the messages to purge (IMAP sequence
     *         string).
     * 'view' - (string) The current full mailbox.
     * </pre>
     *
     * @return mixed  False on failure, or an object (see
     *                _generateDeleteResult() for format).
     */
    public function PurgeDeleted($vars)
    {
        $indices = $GLOBALS['imp_imap']->ob()->utils->fromSequenceString($vars->uid);
        $change = $this->_changed($vars, $indices);

        if (is_null($change)) {
            return false;
        }

        if (!$change) {
            $sort = IMP::getSort($vars->view);
            $change = ($sort['by'] == Horde_Imap_Client::SORT_THREAD);
        }

        $expunged = $GLOBALS['injector']->getInstance('IMP_Message')->expungeMailbox(array($vars->view => 1), array('list' => true));

        if (empty($expunged[$vars->view])) {
            return false;
        }

        $expunge_count = count($expunged[$vars->view]);
        $display_folder = IMP::displayFolder($vars->view);
        if ($expunge_count == 1) {
            $GLOBALS['notification']->push(sprintf(_("1 message was purged from \"%s\"."), $display_folder), 'horde.success');
        } else {
            $GLOBALS['notification']->push(sprintf(_("%s messages were purged from \"%s\"."), $expunge_count, $display_folder), 'horde.success');
        }
        $result = $this->_generateDeleteResult($vars, $expunged, $change);

        /* Need to manually set remove to true since we want to remove message
         * from the list no matter the current pref settings. */
        $result->deleted->remove = 1;

        return $result;
    }

    /**
     * AJAX action: Send a Message Disposition Notification (MDN).
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'uid' - (string) Indices of the messages to isend MDN for (IMAP sequence
     *         string).
     * 'view' - (string) The current full mailbox.
     * </pre>
     *
     * @return boolean  True on success, false on failure.
     */
    public function SendMDN($vars)
    {
        if (!$vars->view || !$vars->uid) {
            return false;
        }

        try {
            $fetch_ret = $GLOBALS['imp_imap']->ob()->fetch($vars->view, array(
                Horde_Imap_Client::FETCH_HEADERTEXT => array(array('parse' => true, 'peek' => false))
            ), array('ids' => array($vars->uid)));
        } catch (Horde_Imap_Client_Exception $e) {
            return false;
        }

        $imp_ui = new IMP_Ui_Message();
        $imp_ui->MDNCheck($vars->view, $vars->uid, reset($fetch_ret[$vars->uid]['headertext']), true);

        return true;
    }

    /**
     * AJAX action: Process a user-supplied PGP symmetric passphrase.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'dialog_input' - (string) Input from the dialog screen.
     * 'symmetricid' - (string) The symmetric ID to process.
     * </pre>
     *
     * @return object  An object with the following entries:
     * <pre>
     * 'error' - (string) An error message.
     * 'success' - (integer) 1 on success, 0 on failure.
     * </pre>
     */
    public function PGPSymmetric($vars)
    {
        $result = new stdClass;
        $result->success = 0;

        try {
            $imp_pgp = Horde_Crypt::singleton(array('IMP', 'Pgp'));
            Horde::requireSecureConnection();
            if ($vars->dialog_input) {
                if ($imp_pgp->storePassphrase('symmetric', $vars->dialog_input, $vars->symmetricid)) {
                    $result->success = 1;
                } else {
                    $result->error = _("Invalid passphrase entered.");
                }
            } else {
                $result->error = _("No passphrase entered.");
            }
        } catch (Horde_Exception $e) {
            $result->error = $e->getMessage();
        }

        return $result;
    }

    /**
     * AJAX action: Process a user-supplied passphrase for the PGP personal
     * key.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'dialog_input' - (string) Input from the dialog screen.
     * </pre>
     *
     * @return object  An object with the following entries:
     * <pre>
     * 'error' - (string) An error message.
     * 'success' - (integer) 1 on success, 0 on failure.
     * </pre>
     */
    public function PGPPersonal($vars)
    {
        $result = new stdClass;
        $result->success = false;

        try {
            $imp_pgp = Horde_Crypt::singleton(array('IMP', 'Pgp'));
            Horde::requireSecureConnection();
            if ($vars->dialog_input) {
                if ($imp_pgp->storePassphrase('personal', $vars->dialog_input)) {
                    $result->success = 1;
                } else {
                    $result->error = _("Invalid passphrase entered.");
                }
            } else {
                $result->error = _("No passphrase entered.");
            }
        } catch (Horde_Exception $e) {
            $result->error = $e->getMessage();
        }

        return $result;
    }

    /**
     * AJAX action: Process a user-supplied passphrase for the S/MIME
     * personal key.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'dialog_input' - (string) Input from the dialog screen.
     * </pre>
     *
     * @return object  An object with the following entries:
     * <pre>
     * 'error' - (string) An error message.
     * 'success' - (integer) 1 on success, 0 on failure.
     * </pre>
     */
    public function SMIMEPersonal($vars)
    {
        $result = new stdClass;
        $result->success = false;

        try {
            $imp_smime = Horde_Crypt::singleton(array('IMP', 'Smime'));
            Horde::requireSecureConnection();
            if ($vars->dialog_input) {
                if ($imp_smime->storePassphrase($vars->dialog_input)) {
                    $result->success = 1;
                } else {
                    $result->error = _("Invalid passphrase entered.");
                }
            } else {
                $result->error = _("No passphrase entered.");
            }
        } catch (Horde_Exception $e) {
            $result->error = $e->getMessage();
        }

        return $result;
    }

    /**
     * AJAX action: Add an attachment to a compose message.
     *
     * @param Horde_Variables $vars  Variables used:
     * <pre>
     * 'composeCache' - (string) The IMP_Compose cache identifier.
     * </pre>
     *
     * @return object  An object with the following entries:
     * <pre>
     * 'error' - (string) An error message.
     * 'success' - (integer) 1 on success, 0 on failure.
     * </pre>
     */
    public function AddAttachment($vars)
    {
        $imp_compose = IMP_Compose::singleton($vars->composeCache);

        $result = new stdClass;
        $result->action = 'AddAttachment';
        $result->success = 0;

        if ($_SESSION['imp']['file_upload'] &&
            $imp_compose->addFilesFromUpload('file_')) {
            $info = IMP_Dimp::getAttachmentInfo($imp_compose);
            $result->success = 1;
            $result->info = end($info);
            $result->imp_compose = $imp_compose->getCacheId();
        }

        return $result;
    }

    /**
     * AJAX action: Auto save a draft message.
     *
     * @param Horde_Variables $vars  See self::_dimpComposeSetup().
     *
     * @return object  See self::_dimpDraftAction().
     */
    public function AutoSaveDraft($vars)
    {
        return $this->_dimpDraftAction($vars);
    }

    /**
     * AJAX action: Save a draft message.
     *
     * @param Horde_Variables $vars  See self::_dimpComposeSetup().
     *
     * @return object  See self::_dimpDraftAction().
     */
    public function SaveDraft($vars)
    {
        return $this->_dimpDraftAction($vars);
    }

    /**
     * AJAX action: Send a message.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _dimpComposeSetup(). Additional
     *                               variables used:
     * <pre>
     * 'html' - (integer) In HTML compose mode?
     * 'message' - (string) The message text.
     * 'priority' - TODO
     * 'request_read_receipt' - TODO
     * 'save_attachments_select' - TODO
     * 'save_sent_mail' - TODO
     * 'save_sent_mail_folder' - (string) TODO
     * </pre>
     *
     * @return object  An object with the following entries:
     * <pre>
     * 'action' - (string) The AJAX action string
     * 'draft_delete' - (integer) TODO
     * 'log' - (array) TODO
     * 'mailbox' - (array) TODO
     * 'reply_folder' - (string) TODO
     * 'reply_type' - (string) TODO
     * 'success' - (integer) 1 on success, 0 on failure.
     * 'uid' - (integer) TODO
     * </pre>
     */
    public function SendMessage($vars)
    {
        list($result, $imp_compose, $headers, $identity) = $this->_dimpComposeSetup($vars);
        if (!IMP::canCompose()) {
            $result->success = 0;
        }
        if (!$result->success) {
            return $result;
        }

        $headers['replyto'] = $identity->getValue('replyto_addr');

        $result->uid = $imp_compose->getMetadata('uid');

        if ($reply_type = $imp_compose->getMetadata('reply_type')) {
            $result->reply_folder = $imp_compose->getMetadata('mailbox');
            $result->reply_type = $reply_type;
        }

        /* Use IMP_Tree to determine whether the sent mail folder was
         * created. */
        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imptree->eltDiffStart();

        $options = array(
            'priority' => $vars->priority,
            'readreceipt' => $vars->request_read_receipt,
            'save_attachments' => $vars->save_attachments_select,
            'save_sent' => (($GLOBALS['prefs']->isLocked('save_sent_mail'))
                            ? $identity->getValue('save_sent_mail')
                            : (bool)$vars->save_sent_mail),
            'sent_folder' => (($GLOBALS['prefs']->isLocked('save_sent_mail'))
                              ? $identity->getValue('sent_mail_folder')
                              : (isset($vars->save_sent_mail_folder) ? $vars->save_sent_mail_folder : $identity->getValue('sent_mail_folder')))
        );

        try {
            // TODO: Use 'sending_charset'
            $sent = $imp_compose->buildAndSendMessage($vars->message, $headers, Horde_Nls::getEmailCharset(), $vars->html, $options);
        } catch (IMP_Compose_Exception $e) {
            $result->success = 0;
            $GLOBALS['notification']->push($e, 'horde.error');
            return $result;
        }

        /* Remove any auto-saved drafts. */
        if ($GLOBALS['prefs']->getValue('auto_save_drafts') ||
            $GLOBALS['prefs']->getValue('auto_delete_drafts')) {
            $result->draft_delete = 1;
        }

        if ($sent && $GLOBALS['prefs']->getValue('compose_confirm')) {
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

        $imp_compose->destroy();

        $result->mailbox = $this->_getMailboxResponse($imptree);

        return $result;
    }

    /**
     * Setup environment for dimp compose actions.
     *
     * <pre>
     * 'composeCache' - (string) The IMP_Compose cache identifier.
     * </pre>
     * from, identity, composeCache
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
    protected function _dimpComposeSetup($vars)
    {
        $result = new stdClass;
        $result->action = $this->_action;
        $result->success = 1;

        /* Set up identity. */
        $identity = Horde_Prefs_Identity::singleton(array('imp', 'imp'));
        if (isset($vars->identity) &&
            !$GLOBALS['prefs']->isLocked('default_identity')) {
            $identity->setDefault($vars->identity);
        }

        /* Set up the From address based on the identity. */
        $headers = array();
        try {
            $headers['from'] = $identity->getFromLine(null, $vars->from);
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $result->success = 1;
            return array($result);
        }

        $imp_ui = new IMP_Ui_Compose();
        $headers['to'] = $imp_ui->getAddressList($vars->to);
        if ($GLOBALS['prefs']->getValue('compose_cc')) {
            $headers['cc'] = $imp_ui->getAddressList($vars->cc);
        }
        if ($GLOBALS['prefs']->getValue('compose_bcc')) {
            $headers['bcc'] = $imp_ui->getAddressList($vars->bcc);
        }
        $headers['subject'] = $vars->subject;

        $imp_compose = IMP_Compose::singleton($vars->composeCache);

        return array($result, $imp_compose, $headers, $identity);
    }

    /**
     * Save a draft composed message.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _dimpComposeSetup(). Additional
     *                               variables used:
     * <pre>
     * 'html' - (integer) In HTML compose mode?
     * 'message' - (string) The message text.
     * </pre>
     *
     * @return object  An object with the following entries:
     * <pre>
     * 'action' - (string) The AJAX action string
     * 'success' - (integer) 1 on success, 0 on failure.
     * </pre>
     */
    protected function _dimpDraftAction($vars)
    {
        list($result, $imp_compose, $headers, $identity) = $this->_dimpComposeSetup($vars);
        if (!$result->success) {
            return $result;
        }

        try {
            $res = $imp_compose->saveDraft($headers, $vars->message, Horde_Nls::getCharset(), $vars->html);
            if ($this->_action == 'AutoSaveDraft') {
                $GLOBALS['notification']->push(_("Draft automatically saved."), 'horde.message');
            } else {
                $GLOBALS['notification']->push($res);
                if ($GLOBALS['prefs']->getValue('close_draft')) {
                    $imp_compose->destroy();
                }
            }
        } catch (IMP_Compose_Exception $e) {
            $result->success = 0;
            $GLOBALS['notification']->push($e, 'horde.error');
        }

        return $result;
    }

    /**
     * Check the UID validity of the mailbox.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _viewPortData().
     *
     * @return mixed  The JSON result, possibly with ViewPort information
     *                added if UID validity has changed.
     */
    protected function _checkUidvalidity($vars, $result = false)
    {
        try {
            $GLOBALS['imp_imap']->checkUidvalidity($vars->view);
        } catch (Horde_Exception $e) {
            if (!is_object($result)) {
                $result = new stdClass;
            }
            $result->ViewPort = $this->_viewPortData($vars, true);
        }

        return $result;
    }

    /**
     * Generates the delete data needed for DimpBase.js.
     *
     * @param Horde_Variables $vars  See the list of variables needed for
     *                               _viewPortData().
     * @param array $indices         The list of indices that were deleted.
     * @param boolean $changed       If true, add ViewPort information.
     * @param boolean $nothread      If true, don't do thread sort check.
     *
     * @return object  An object with the following entries:
     * <pre>
     * 'deleted' - (object) TODO:
     *   folder
     *   remove
     *   uids
     * 'ViewPort' - (object) See _viewPortData().
     * 'poll' - (array) Mailbox names as the keys, number of unseen messages
     *          as the values.
     * </pre>
     */
    protected function _generateDeleteResult($vars, $indices, $change,
                                             $nothread = false)
    {
        $imp_mailbox = IMP_Mailbox::singleton($vars->view);

        $del = new stdClass;
        $del->folder = $vars->view;
        $del->uids = $GLOBALS['imp_imap']->ob()->utils->toSequenceString($indices, array('mailbox' => true));
        $del->remove = intval($GLOBALS['prefs']->getValue('hide_deleted') ||
                              $GLOBALS['prefs']->getValue('use_trash'));

        $result = new stdClass;
        $result->deleted = $del;

        /* Check if we need to update thread information. */
        if (!$change && !$nothread) {
            $sort = IMP::getSort($vars->view);
            $change = ($sort['by'] == Horde_Imap_Client::SORT_THREAD);
        }

        if ($change) {
            $result->ViewPort = $this->_viewPortData($vars, true);
        } else {
            $result->ViewPort = new stdClass;
            $result->ViewPort->updatecacheid = $imp_mailbox->getCacheID($vars->view);
            $result->ViewPort->view = $vars->view;
        }

        $poll = $this->_getPollInformation($vars->view);
        if (!empty($poll)) {
            $result->poll = $poll;
        }

        return $result;
    }

    /**
     * Determine if the cache information has changed.
     *
     * @param Horde_Variables $vars  The following variables:
     * <pre>
     * 'cacheid' - (string) The browser (ViewPort) cache identifier.
     * 'forceUpdate' - (integer) If 1, forces an update,
     * 'view' - (string) The current ViewPort view (mailbox).
     * </pre>
     * @param boolean $rw            Open mailbox as READ+WRITE?
     *
     * @return boolean  True if the server state differs from the browser
     *                  state.
     */
    protected function _changed($vars, $rw = null)
    {
        /* Only update search mailboxes on forced refreshes. */
        if ($GLOBALS['imp_search']->isSearchMbox($vars->view)) {
            return ($this->_action == 'ViewPort') || $vars->forceUpdate;
        }

        /* We know we are going to be dealing with this mailbox, so select it
         * on the IMAP server (saves some STATUS calls). */
        if (!is_null($rw)) {
            try {
                $GLOBALS['imp_imap']->ob()->openMailbox($vars->view, $rw ? Horde_Imap_Client::OPEN_READWRITE : Horde_Imap_Client::OPEN_AUTO);
            } catch (Horde_Imap_Client_Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                return null;
            }
        }

        return (IMP_Mailbox::singleton($vars->view)->getCacheID($vars->view) != $vars->cacheid);
    }

    /**
     * Generate the information necessary for a ViewPort request from/to the
     * browser.
     *
     * @param Horde_Variables $vars  The following variables:
     * <pre>
     * TODO
     * </pre>
     * @param boolean $change        True if cache information has changed.
     *
     * @return array  See IMP_Views_ListMessages::listMessages().
     */
    protected function _viewPortData($vars, $change)
    {
        $args = array(
            'applyfilter' => $vars->applyfilter,
            'cache' => $vars->cache,
            'cacheid' => $vars->cacheid,
            'change' => $change,
            'initial' => $vars->initial,
            'mbox' => $vars->view,
            'rangeslice' => $vars->rangeslice,
            'requestid' => $vars->requestid,
            'qsearch' => $vars->qsearch,
            'qsearchflag' => $vars->qsearchflag,
            'qsearchmbox' => $vars->qsearchmbox,
            'qsearchflagnot' => $vars->qsearchflagnot,
            'sortby' => $vars->sortby,
            'sortdir' => $vars->sortdir
        );

        if (!$vars->search || $args['initial']) {
            $args += array(
                'after' => intval($vars->after),
                'before' => intval($vars->before)
            );
        }

        if (!$vars->search) {
            list($slice_start, $slice_end) = explode(':', $vars->slice, 2);
            $args += array(
                'slice_start' => intval($slice_start),
                'slice_end' => intval($slice_end)
            );
        } else {
            $search = Horde_Serialize::unserialize($vars->search, Horde_Serialize::JSON);
            $args += array(
                'search_uid' => isset($search->imapuid) ? $search->imapuid : null,
                'search_unseen' => isset($search->unseen) ? $search->unseen : null
            );
        }

        $list_msg = new IMP_Views_ListMessages();
        return $list_msg->listMessages($args);
    }

    /**
     * Generate poll information for a single mailbox.
     *
     * @param string $mbox  The full mailbox name.
     *
     * @return array  Key is the mailbox name, value is the number of unseen
     *                messages.
     */
    protected function _getPollInformation($mbox)
    {
        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $elt = $imptree->get($mbox);
        if (!$imptree->isPolled($elt)) {
            return array();
        }

        try {
            $count = ($info = $GLOBALS['imp_imap']->ob()->status($mbox, Horde_Imap_Client::STATUS_UNSEEN))
                ? intval($info['unseen'])
                : 0;
        } catch (Horde_Imap_Client_Exception $e) {
            $count = 0;
        }

        return array($mbox => $count);
    }

    /**
     * Generate quota information.
     *
     * @return array  'p': Quota percentage; 'm': Quota message
     */
    protected function _getQuota()
    {
        if (isset($_SESSION['imp']['quota']) &&
            is_array($_SESSION['imp']['quota'])) {
            $quotadata = IMP::quotaData(false);
            if (!empty($quotadata)) {
                return array('p' => round($quotadata['percent']), 'm' => $quotadata['message']);
            }
        }

        return null;
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
                $result['a'][] = $this->_createMailboxElt(is_array($val) ? $val : $imptree->element($val));
            }
        }

        if (!empty($changes['c'])) {
            $result['c'] = array();
            foreach ($changes['c'] as $val) {
                // Skip the base element, since any change there won't ever be
                // updated on-screen.
                if ($val != IMP_Imap_Tree::BASE_ELT) {
                    $result['c'][] = $this->_createMailboxElt($imptree->element($val));
                }
            }
        }

        if (!empty($changes['d'])) {
            $result['d'] = array_reverse($changes['d']);
        }

        return $result;
    }

    /**
     * Create an object used by DimpCore to generate the folder tree.
     *
     * @param array $elt  The output from IMP_Tree::element().
     *
     * @return stdClass  The element object. Contains the following items:
     * <pre>
     * 'ch' (children) = Does the mailbox contain children? [boolean]
     *                   [DEFAULT: no]
     * 'cl' (class) = The CSS class. [string] [DEFAULT: 'base']
     * 'co' (container) = Is this mailbox a container element? [boolean]
     *                    [DEFAULT: no]
     * 'i' (icon) = A user defined icon to use. [string] [DEFAULT: none]
     * 'l' (label) = The mailbox display label. [string] [DEFAULT: 'm' val]
     * 'm' (mbox) = The mailbox value. [string]
     * 'n' (non-imap) = A non-IMAP element? [boolean] [DEFAULT: no]
     * 'pa' (parent) = The parent element. [string] [DEFAULT:
     *                 DIMP.conf.base_mbox]
     * 'po' (polled) = Is the element polled? [boolean] [DEFAULT: no]
     * 's' (special) = Is this a "special" element? [boolean] [DEFAULT: no]
     * 't' (title) = The title value. [string] [DEFAULT: 'm' val]
     * 'u' (unseen) = The number of unseen messages. [integer]
     * 'un' (unsubscribed) = Is this mailbox unsubscribed? [boolean]
     *                       [DEFAULT: no]
     * 'v' (virtual) = Virtual folder? 0 = not vfolder, 1 = system vfolder,
     *                 2 = user vfolder [integer] [DEFAULT: 0]
     * </pre>
     */
    protected function _createMailboxElt($elt)
    {
        $ob = new stdClass;

        if ($elt['children']) {
            $ob->ch = 1;
        }
        $ob->cl = $elt['class'];
        $ob->m = $elt['value'];
        if ($ob->m != $elt['name']) {
            $ob->l = $elt['name'];
        }
        if ($elt['parent'] != IMP_Imap_Tree::BASE_ELT) {
            $ob->pa = $elt['parent'];
        }
        if ($elt['polled']) {
            $ob->po = 1;
        }
        if ($elt['vfolder']) {
            $ob->v = $GLOBALS['imp_search']->isEditableVFolder($elt['value']) ? 2 : 1;
        }
        if (!$elt['sub']) {
            $ob->un = 1;
        }

        $tmp = IMP::getLabel($ob->m);
        if ($tmp != $ob->m) {
            $ob->t = $tmp;
        }

        if ($elt['container']) {
            $ob->cl = 'exp';
            $ob->co = 1;
            if ($elt['nonimap']) {
                $ob->n = 1;
            }
        } else {
            if ($elt['polled']) {
                $ob->u = intval($elt['unseen']);
            }

            if ($elt['special']) {
                $ob->s = 1;
            } elseif (!$elt['vfolder'] && $elt['children']) {
                $ob->cl = 'exp';
            }
        }

        if ($elt['user_icon']) {
            $ob->cl = 'customimg';
            $dir = empty($elt['icondir'])
                ? $GLOBALS['registry']->getImageDir()
                : $elt['icondir'];
            $ob->i = empty($dir)
                ? $elt['icon']
                : $dir . '/' . $elt['icon'];
        }

        return $ob;
    }

}
