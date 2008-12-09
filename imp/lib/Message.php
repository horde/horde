<?php
/**
 * The IMP_Message:: class contains all functions related to handling messages
 * within IMP. Actions such as moving, copying, and deleting messages are
 * handled in here so that code need not be repeated between mailbox, message,
 * and other pages.
 *
 * Copyright 2000-2001 Chris Hyde <chris@jeks.net>
 * Copyright 2000-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chris Hyde <chris@jeks.net>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Message
{
    /* Constants used in copy(). */
    const MOVE = 1;
    const COPY = 2;

    /**
     * Using POP to access mailboxes?
     *
     * @var boolean
     */
    protected $_usepop = false;

    /**
     * Returns a reference to the global IMP_Message object, only creating it
     * if it doesn't already exist. This ensures that only one IMP_Message
     * instance is instantiated for any given session.
     *
     * This method must be invoked as:<code>
     *   $imp_message = &IMP_Message::singleton();
     * </code>
     *
     * @return IMP_Message  The IMP_Message instance.
     */
    static final public function &singleton()
    {
        static $message;

        if (!isset($message)) {
            $message = new IMP_Message();
        }

        return $message;
    }

    /**
     * Constructor.
     */
    function __construct()
    {
        if ($_SESSION['imp']['protocol'] == 'pop') {
            $this->_usepop = true;
        }
    }

    /**
     * Copies or moves a list of messages to a new mailbox.
     * Handles use of the IMP::SEARCH_MBOX mailbox and the Trash mailbox.
     *
     * @param string $targetMbox  The mailbox to move/copy messages to
     *                            (UTF7-IMAP).
     * @param integer $action     Either IMP_Message::MOVE or
     *                            IMP_Message::COPY.
     * @param mixed $indices      See IMP::parseIndicesList().
     * @param boolean $new        Whether the target mailbox has to be created.
     *
     * @return boolean  True if successful, false if not.
     */
    public function copy($targetMbox, $action, $indices, $new = false)
    {
        global $conf, $imp_imap, $notification, $prefs;

        if (($action == self::MOVE) && $imp_imap->isReadOnly($targetMbox)) {
            return false;
        }

        if ($conf['tasklist']['use_tasklist'] &&
            (strpos($targetMbox, '_tasklist_') === 0)) {
            /* If the target is a tasklist, handle the move/copy specially. */
            $tasklist = str_replace('_tasklist_', '', $targetMbox);
            return $this->createTasksOrNotes($tasklist, $action, $indices, 'task');
        }
        if ($conf['notepad']['use_notepad'] &&
            (strpos($targetMbox, '_notepad_') === 0)) {
            /* If the target is a notepad, handle the move/copy specially. */
            $notepad = str_replace('_notepad_', '', $targetMbox);
            return $this->createTasksOrNotes($notepad, $action, $indices, 'note');
        }

        if (!($msgList = IMP::parseIndicesList($indices))) {
            return false;
        }

        if ($new) {
            $imp_folder = &IMP_Folder::singleton();
            if (!$imp_folder->exists($targetMbox) &&
                !$imp_folder->create($targetMbox, $prefs->getValue('subscribe'))) {
                return false;
            }
        }

        $imap_move = false;
        $return_value = true;

        switch ($action) {
        case self::MOVE:
            $imap_move = true;
            $message = _("There was an error moving messages from \"%s\" to \"%s\". This is what the server said");
            break;

        case self::COPY:
            $message = _("There was an error copying messages from \"%s\" to \"%s\". This is what the server said");
            break;
        }

        foreach ($msgList as $mbox => $msgIndices) {
            if (($action == self::MOVE) && $imp_imap->isReadOnly($mbox)) {
                $notification->push(sprintf($message, IMP::displayFolder($mbox), IMP::displayFolder($targetMbox)) . ': ' . _("The target directory is read-only."), 'horde.error');
                $return_value = false;
                continue;
            }

            /* Attempt to copy/move messages to new mailbox. */
            try {
                $imp_imap->ob->copy($mbox, $targetMbox, array('ids' => $msgIndices, 'move' => $imap_move));

                $imp_mailbox = &IMP_Mailbox::singleton($mbox);
                if ($imp_mailbox->isBuilt()) {
                    $imp_mailbox->removeMsgs(array($mbox => $msgIndices));
                }
            } catch (Horde_Imap_Client_Exception $e) {
                $notification->push(sprintf($message, IMP::displayFolder($mbox), IMP::displayFolder($targetMbox)) . ': ' . imap_last_error(), 'horde.error');
                $return_value = false;
            }
        }

        return $return_value;
    }

    /**
     * Deletes a list of messages taking into account whether or not a
     * Trash folder is being used.
     * Handles use of the IMP::SEARCH_MBOX mailbox and the Trash folder.
     *
     * @param mixed $indices    See IMP::parseIndicesList().
     * @param boolean $nuke     Override user preferences and nuke (i.e.
     *                          permanently delete) the messages instead?
     * @param boolean $keeplog  Should any history information of the
     *                          message be kept?
     *
     * @return integer|boolean  The number of messages deleted if successful,
     *                          false if not.
     */
    public function delete($indices, $nuke = false, $keeplog = false)
    {
        global $conf, $imp_imap, $notification, $prefs;

        if (!($msgList = IMP::parseIndicesList($indices))) {
            return false;
        }

        $trash = IMP::folderPref($prefs->getValue('trash_folder'), true);
        $use_trash = $prefs->getValue('use_trash');
        $use_vtrash = $prefs->getValue('use_vtrash');
        if ($use_trash && !$use_vtrash && empty($trash)) {
            $notification->push(_("Cannot move messages to Trash - no Trash mailbox set in preferences."), 'horde.error');
            return false;
        }

        $return_value = 0;
        $maillog_update = (!$keeplog && !empty($conf['maillog']['use_maillog']));

        /* Check for Trash folder. */
        $use_trash_folder = !$this->_usepop && !$nuke && !$use_vtrash && $use_trash;
        if ($use_trash_folder) {
            include_once IMP_BASE . '/lib/Folder.php';
            $imp_folder = &IMP_Folder::singleton();

            if (!$imp_folder->exists($trash) &&
                !$imp_folder->create($trash, $prefs->getValue('subscribe'))) {
                return false;
            }
        }

        foreach ($msgList as $mbox => $msgIndices) {
            if ($imp_imap->isReadOnly($mbox)) {
                $notification->push(sprintf(_("There was an error deleting messages from the folder \"%s\". This folder is read-only."), IMP::displayFolder($mbox)), 'horde.error');
                $return_value = false;
                continue;
            }

            $indices_array = array($mbox => $msgIndices);
            $return_value += count($msgIndices);

            /* Trash is only valid for IMAP mailboxes. */
            if ($use_trash_folder && ($mbox != $trash)) {
                try {
                    $imp_imap->ob->copy($mbox, $trash, array('ids' => $msgIndices, 'move' => true));

                    $imp_mailbox = &IMP_Mailbox::singleton($mbox);
                    if ($imp_mailbox->isBuilt()) {
                        $imp_mailbox->removeMsgs(array($mbox => $msgIndices));
                    }
                } catch (Horde_Imap_Client_Exception $e) {
                    // @todo Check for overquota error.
                    return false;
                }
            } else {
                /* Get the list of Message-IDs for the deleted messages if
                 * using maillogging. */
                $fetch = null;
                if ($maillog_update) {
                    try {
                        $fetch = $imp_imap->ob->fetch($mbox, array(Horde_Imap_Client::FETCH_ENVELOPE => true), array('ids' => $msgIndices));
                    } catch (Horde_Imap_Client_Exception $e) {}
                }

                /* Delete the messages. */
                $expunge_now = false;
                $del_flags = array('\\deleted');

                if ($this->_usepop ||
                    $nuke ||
                    ($use_trash && ($mbox == $trash)) ||
                    ($use_vtrash && ($GLOBALS['imp_search']->isVTrashFolder()))) {
                    /* Purge messages immediately. */
                    $expunge_now = true;
                } else {
                    /* If we are using virtual trash, we must mark the message
                     * as seen or else it will appear as an 'unseen' message
                     * for purposes of new message counts. */
                    if ($use_vtrash) {
                        $del_flags[] = '\\seen';
                    }
                }

                try {
                    $imp_imap->ob->store($mbox, array('add' => array('\\deleted'), 'ids' => $msgIndices));
                    if ($expunge_now) {
                        $this->expungeMailbox($indices_array);
                    }
                } catch (Horde_Imap_Client_Exception $e) {}

                /* Get the list of Message-IDs deleted, and remove
                 * the information from the mail log. */
                if (!is_null($fetch)) {
                    $msg_ids = array();
                    reset($fetch);
                    while (list(,$v) = each($fetch)) {
                        if (!empty($v['envelope']['message-id'])) {
                            $msg_ids[] = $v['envelope']['message-id'];
                        }
                    }

                    IMP_Maillog::deleteLog($msg_ids);
                }
            }
        }

        return $return_value;
    }

    /**
     * Undeletes a list of messages.
     * Handles the IMP::SEARCH_MBOX mailbox.
     * This function works with IMAP only, not POP3.
     *
     * @param mixed $indices  See IMP::parseIndicesList().
     *
     * @return boolean  True if successful, false if not.
     */
    public function undelete($indices)
    {
        return $this->flag(array('deleted'), $indices, false);
    }

    /**
     * Copies or moves a list of messages to a tasklist or notepad.
     * Handles use of the IMP::SEARCH_MBOX mailbox and the Trash folder.
     *
     * @param string $list      The list in which the task or note will be
     *                          created.
     * @param integer $action   Either IMP_Message::MOVE or IMP_Message::COPY.
     * @param mixed $indices    See IMP::parseIndicesList().
     * @param string $type      The object type to create, defaults to task.
     *
     * @return boolean  True if successful, false if not.
     */
    public function createTasksOrNotes($list, $action, $indices,
                                       $type = 'task')
    {
        global $registry, $notification, $prefs;

        if (!($msgList = IMP::parseIndicesList($indices))) {
            return false;
        }

        require_once 'Text/Flowed.php';

        foreach ($msgList as $folder => $msgIndices) {
            foreach ($msgIndices as $index) {
                /* Fetch the message contents. */
                $imp_contents = &IMP_Contents::singleton($index . IMP::IDX_SEP . $folder);
                $imp_contents->buildMessage();

                /* Fetch the message headers. */
                $imp_headers = &$imp_contents->getHeaderOb();
                $subject = $imp_headers->getValue('subject');

                /* Extract the message body. */
                $imp_compose = &IMP_Compose::singleton();
                $mime_message = $imp_contents->getMIMEMessage();
                $body_id = $imp_compose->getBodyId($imp_contents);
                $body = $imp_compose->findBody($imp_contents);

                /* Re-flow the message for prettier formatting. */
                $flowed = new Text_Flowed($mime_message->replaceEOL($body, "\n"));
                if ($mime_message->getContentTypeParameter('delsp') == 'yes') {
                    $flowed->setDelSp(true);
                }
                $body = $flowed->toFlowed(false);

                /* Convert to current charset */
                /* TODO: When Horde_iCalendar supports setting of charsets
                 * we need to set it there instead of relying on the fact
                 * that both Nag and IMP use the same charset. */
                $body_part = $mime_message->getPart($body_id);
                $body = String::convertCharset($body, $body_part->getCharset(), NLS::getCharset());

                /* Create a new iCalendar. */
                $vCal = new Horde_iCalendar();
                $vCal->setAttribute('PRODID', '-//The Horde Project//IMP ' . IMP_VERSION . '//EN');
                $vCal->setAttribute('METHOD', 'PUBLISH');

                switch ($type) {
                case 'task':
                    /* Create a new vTodo object using this message's
                     * contents. */
                    $vTodo = &Horde_iCalendar::newComponent('vtodo', $vCal);
                    $vTodo->setAttribute('SUMMARY', $subject);
                    $vTodo->setAttribute('DESCRIPTION', $body);
                    $vTodo->setAttribute('PRIORITY', '3');

                    /* Get the list of editable tasklists. */
                    $lists = $registry->call('tasks/listTasklists',
                                             array(false, PERMS_EDIT));

                    /* Attempt to add the new vTodo item to the requested
                     * tasklist. */
                    $res = $registry->call('tasks/import',
                                           array($vTodo, 'text/calendar', $list));
                    break;

                case 'note':
                    /* Create a new vNote object using this message's
                     * contents. */
                    $vNote = &Horde_iCalendar::newComponent('vnote', $vCal);
                    $vNote->setAttribute('BODY', $subject . "\n". $body);

                    /* Get the list of editable notepads. */
                    $lists = $registry->call('notes/listNotepads',
                                             array(false, PERMS_EDIT));

                    /* Attempt to add the new vNote item to the requested
                     * notepad. */
                    $res = $registry->call('notes/import',
                                           array($vNote, 'text/x-vnote', $list));
                    break;
                }

                if (is_a($res, 'PEAR_Error')) {
                    $notification->push($res, $res->getCode());
                } elseif (!$res) {
                    switch ($type) {
                    case 'task': $notification->push(_("An unknown error occured while creating the new task."), 'horde.error'); break;
                    case 'note': $notification->push(_("An unknown error occured while creating the new note."), 'horde.error'); break;
                    }
                } else {
                    $name = '"' . htmlspecialchars($subject) . '"';

                    /* Attempt to convert the object name into a hyperlink. */
                    switch ($type) {
                    case 'task':
                        $link = $registry->link('tasks/show',
                                                array('uid' => $res));
                        break;
                    case 'note':
                        if ($registry->hasMethod('notes/show')) {
                            $link = $registry->link('notes/show',
                                                    array('uid' => $res));
                        } else {
                            $link = false;
                        }
                        break;
                    }
                    if ($link && !is_a($link, 'PEAR_Error')) {
                        $name = sprintf('<a href="%s">%s</a>',
                                        Horde::url($link),
                                        $name);
                    }

                    $notification->push(sprintf(_("%s was successfully added to \"%s\"."), $name, htmlspecialchars($lists[$list]->get('name'))), 'horde.success', array('content.raw'));
                }
            }
        }

        /* Delete the original messages if this is a "move" operation. */
        if ($action == self::MOVE) {
            $this->delete($indices);
        }

        return true;
    }

    /**
     * Strips one or all MIME parts out of a message.
     * Handles the IMP::SEARCH_MBOX mailbox.
     *
     * @param IMP_Mailbox $imp_mailbox  The IMP_Mailbox object with the
     *                                  current index set to the message to be
     *                                  processed.
     * @param string $partid            The MIME ID of the part to strip. All
     *                                  parts are stripped if null.
     *
     * @return mixed  Returns true on success, or PEAR_Error on error.
     */
    public function stripPart(&$imp_mailbox, $partid = null)
    {
        global $imp_imap;

        /* Return error if no index was provided. */
        if (!($msgList = IMP::parseIndicesList($imp_mailbox))) {
            return PEAR::raiseError('No index provided to IMP_Message::stripPart().');
        }

        /* If more than one index provided, return error. */
        reset($msgList);
        list($folder, $index) = each($msgList);
        if (each($msgList) || (count($index) > 1)) {
            return PEAR::raiseError('More than 1 index provided to IMP_Message::stripPart().');
        }
        $index = implode('', $index);

        if ($imp_imap->isReadOnly($folder)) {
            return PEAR::raiseError(_("Cannot strip the MIME part as the folder is read-only"));
        }

        /* Get a local copy of the message. */
        $contents = &IMP_Contents::singleton($index . IMP::IDX_SEP . $folder);
        $contents->rebuildMessage();
        $message = $contents->getMIMEMessage();

        /* Loop through all to-be-stripped mime parts. */
        if (is_null($partid)) {
            // TODO
            $partids = array_slice(array_keys($message->contentTypeMap()), 1);
        } else {
            /* Sanity checking: make sure part does not live under a
             * message/rfc822 part. */
            if ($contents->isParent($partid, 'message/rfc822')) {
                return PEAR::raiseError(_("Cannot strip a MIME part contained within a message/rfc822 part."));
            }
            $partids = array($partid);
        }

        foreach ($partids as $partid) {
            $oldPart = $message->getPart($partid);
            if (!is_a($oldPart, 'Horde_Mime_Part')) {
                continue;
            }
            $newPart = new Horde_Mime_Part();
            $newPart->setType('text/plain');
            $newPart->setDisposition('attachment');

            /* We need to make sure all text is in the correct charset. */
            $newPart->setCharset(NLS::getCharset());
            $newPart->setContents(sprintf(_("[Attachment stripped: Original attachment type: %s, name: %s]"), $oldPart->getType(), $oldPart->getName(true)), '8bit');
            $message->alterPart($partid, $newPart);
        }

        /* Get the headers for the message. */
        try {
            $res = $GLOBALS['imp_imap']->ob->fetch($folder, array(
                Horde_Imap_Client::FETCH_HEADERTEXT => array(array('peek' => true)),
                Horde_Imap_Client::FETCH_ENVELOPE => true,
                Horde_Imap_Client::FETCH_FLAGS => true
            ), array('ids' => array($index)));
            $res = reset($res);

            /* If in Virtual Inbox, we need to reset flag to unseen so that it
             * appears again in the mailbox list. */
            if ($GLOBALS['imp_search']->isVINBOXFolder($imp_mailbox->getMailboxName()) &&
                ($pos = array_search('\\seen', $res['flags']))) {
                unset($res['flags'][$pos]);
            }

            $uid = $GLOBALS['imp_imap']->ob->append($folder, array(array('data' => $res['headertext'][0] . $contents->toString($message, true), 'flags' => $res['flags'], 'messageid' => $res['envelope']['message-id'])));

        } catch (Horde_Imap_Client_Exception $e) {
            return PEAR::raiseError(_("An error occured while attempting to strip the attachment."));
        }

        $this->delete($imp_mailbox, true, true);
        $imp_mailbox->setIndex(reset($uid));

        /* We need to replace the old index in the query string with the
         * new index. */
        $_SERVER['QUERY_STRING'] = preg_replace('/' . $index . '/', $ids[0], $_SERVER['QUERY_STRING']);

        return true;
    }

    /**
     * Sets or clears a given flag for a list of messages.
     * Handles use of the IMP::SEARCH_MBOX mailbox.
     * This function works with IMAP only, not POP3.
     *
     * @param array $flags     The IMAP flag(s) to set or clear.
     * @param mixed $indices   See IMP::parseIndicesList().
     * @param boolean $action  If true, set the flag(s), otherwise clear the
     *                         flag(s).
     *
     * @return boolean  True if successful, false if not.
     */
    public function flag($flags, $indices, $action = true)
    {
        global $imp_imap, $notification;

        if (!($msgList = IMP::parseIndicesList($indices))) {
            return false;
        }

        if ($action) {
            $action_array = array('add' => $flags);
        } else {
            $action_array = array('remove' => $flags);
        }

        foreach ($msgList as $mbox => $msgIndices) {
            if ($imp_imap->isReadOnly($mbox)) {
                $notification->push(sprintf(_("There was an error flagging messages in the folder \"%s\". This folder is read-only."), IMP::displayFolder($mbox)), 'horde.error');
                continue;
            }

            /* Flag/unflag the messages now. */
            try {
                $imp_imap->ob->store($mbox, array_merge($action_array, array('ids' => $msgIndices)));
            } catch (Horde_Imap_Client_Exception $e) {
                $notification->push(sprintf(_("There was an error flagging messages in the folder \"%s\". This is what the server said"), IMP::displayFolder($mbox)) . ': ' . imap_last_error(), 'horde.error');
                return false;
            }
        }

        return true;
    }

    /**
     * Sets or clears a given flag(s) for all messages in a list of mailboxes.
     * This function works with IMAP only, not POP3.
     *
     * @param array $flags     The IMAP flag(s) to set or clear.
     * @param array $mboxes    The list of mailboxes to flag.
     * @param boolean $action  If true, set the flag(s), otherwise, clear the
     *                         flag(s).
     *
     * @return boolean  True if successful, false if not.
     */
    public function flagAllInMailbox($flags, $mboxes, $action = true)
    {
        global $imp_imap;

        if (empty($mboxes) || !is_array($mboxes)) {
            return false;
        }

        if ($action) {
            $action_array = array('add' => $flags);
        } else {
            $action_array = array('remove' => $flags);
        }

        foreach ($mboxes as $val) {
            try {
                $imp_imap->ob->store($val, $action_array);
            } catch (Horde_Imap_Client_Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Expunges all deleted messages from the list of mailboxes.
     *
     * @param array $mbox_list  The list of mailboxes to empty as keys; an
     *                          optional array of indices to delete as values.
     *                          If the value is not an array, all messages
     *                          flagged as deleted in the mailbox will be
     *                          deleted.
     *
     * @return array  An array of mailbox names as keys and UIDs as values
     *                that were expunged.
     */
    public function expungeMailbox($mbox_list)
    {
        global $imp_imap, $imp_search;

        if (empty($mbox_list)) {
            return array();
        }

        $process_list = $update_list = array();

        foreach (array_keys($mbox_list) as $key) {
            if (!$imp_imap->isReadOnly($key)) {
                if ($imp_search->isSearchMbox($key)) {
                    foreach ($imp_search->getSearchFolders($key) as $skey) {
                        $process_list[$skey] = $mbox_list[$key];
                    }
                } else {
                    $process_list[$key] = $mbox_list[$key];
                }
            }
        }

        foreach ($process_list as $key => $val) {
            try {
                $imp_imap->ob->expunge($key, array('ids' => is_array($val) ? $val : array()));

                $imp_mailbox = &IMP_Mailbox::singleton($mbox);
                if ($imp_mailbox->isBuilt()) {
                    $imp_mailbox->removeMsgs(is_array($val) ? array($key => $val) : true);
                }
                $update_list[$key] = $val;
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return $update_list;
    }

    /**
     * Empties an entire mailbox.
     *
     * @param array $mbox_list  The list of mailboxes to empty.
     */
    public function emptyMailbox($mbox_list)
    {
        global $imp_search, $notification, $prefs;

        $trash_folder = ($prefs->getValue('use_trash')) ? IMP::folderPref($prefs->getValue('trash_folder'), true) : null;

        foreach ($mbox_list as $mbox) {
            $display_mbox = IMP::displayFolder($mbox);

            if ($imp_imap->isReadOnly($mbox)) {
                $notification->push(sprintf(_("Could not delete messages from %s. This mailbox is read-only."), $display_mbox), 'horde.error');
                continue;
            }

            if ($imp_search->isVTrashFolder($mbox)) {
                $this->expungeMailbox(array_flip($imp_search->getSearchFolders($mbox)));
                $notification->push(_("Emptied all messages from Virtual Trash Folder."), 'horde.success');
                continue;
            }

            /* Make sure there is at least 1 message before attempting to
               delete. */
            try {
                $status = $imp_imap->ob->status($mbox, Horde_Imap_Client::STATUS_MESSAGES);
                if (empty($status['messages'])) {
                    $notification->push(sprintf(_("The mailbox %s is already empty."), $display_mbox), 'horde.message');
                    continue;
                }

                if (empty($trash_folder) || ($trash_folder == $mbox)) {
                    $this->flagAllInMailbox(array('\\deleted'), $mbox, true);
                    $this->expungeMailbox(array($mbox => 1));
                } else {
                    $ret = $imp_imap->ob->search($mbox);
                    $indices = array($mbox => $ret['match']);
                    $this->delete($indices);
                }

                $notification->push(sprintf(_("Emptied all messages from %s."), $display_mbox), 'horde.success');
            } catch (Horde_Imap_Client_Exception $e) {}
        }
    }

    /**
     * Obtains the size of a mailbox.
     *
     * @param string $mbox_list   The mailbox to obtain the size of.
     * @param boolean $formatted  Whether to return a human readable value.
     *
     * @return mixed  Either the size of the mailbox (in bytes) or a formatted
     *                string with this information.
     */
    public function sizeMailbox($mbox, $formatted = true)
    {
        try {
            $res = $GLOBALS['imp_imap']->ob->fetch($mbox, array(Horde_Imap_Client::FETCH_SIZE => true), array('sequence' => true));

            $size = 0;
            reset($res);
            while (list(,$v) = each($res)) {
                $size += $v['size'];
            }
            return ($formatted)
                ? sprintf(_("%.2fMB"), $size / (1024 * 1024))
                : $size;
        } catch (Horde_Imap_Client_Exception $e) {
            return 0;
        }
    }

}
