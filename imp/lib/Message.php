<?php
/**
 * The IMP_Message:: class contains all functions related to handling messages
 * within IMP. Actions such as moving, copying, and deleting messages are
 * handled in here so that code need not be repeated between mailbox, message,
 * and other pages.
 *
 * Copyright 2000-2001 Chris Hyde <chris@jeks.net>
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
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
    /**
     * Using POP to access mailboxes?
     *
     * @var boolean
     */
    protected $_usepop = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ($_SESSION['imp']['protocol'] == 'pop') {
            $this->_usepop = true;
        }
    }

    /**
     * Copies or moves a list of messages to a new mailbox.
     * Handles search and Trash mailboxes.
     * Also handles moves to the tasklist and/or notepad applications.
     *
     * @param string $targetMbox  The mailbox to move/copy messages to
     *                            (UTF7-IMAP).
     * @param string $action      Either 'copy' or 'move'.
     * @param mixed $indices      See IMP::parseIndicesList().
     * @param boolean $new        Whether the target mailbox has to be created.
     *
     * @return boolean  True if successful, false if not.
     */
    public function copy($targetMbox, $action, $indices, $new = false)
    {
        global $conf, $notification, $prefs;

        if (!($msgList = IMP::parseIndicesList($indices))) {
            return false;
        }

        /* If the target is a tasklist, handle the move/copy specially. */
        if ($conf['tasklist']['use_tasklist'] &&
            (strpos($targetMbox, '_tasklist_') === 0)) {
            $this->_createTasksOrNotes(str_replace('_tasklist_', '', $targetMbox), $action, $msgList, 'task');
            return true;
        }

        /* If the target is a notepad, handle the move/copy specially. */
        if ($conf['notepad']['use_notepad'] &&
            (strpos($targetMbox, '_notepad_') === 0)) {
            $this->_createTasksOrNotes(str_replace('_notepad_', '', $targetMbox), $action, $msgList, 'note');
            return true;
        }

        if ($new) {
            $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');
            if (!$imp_folder->exists($targetMbox) &&
                !$imp_folder->create($targetMbox, $prefs->getValue('subscribe'))) {
                return false;
            }
        }

        $imap_move = false;
        $return_value = true;

        switch ($action) {
        case 'move':
            $imap_move = true;
            $message = _("There was an error moving messages from \"%s\" to \"%s\". This is what the server said");
            break;

        case 'copy':
            $message = _("There was an error copying messages from \"%s\" to \"%s\". This is what the server said");
            break;
        }

        foreach ($msgList as $mbox => $msgIndices) {
            $error = null;

            if ($GLOBALS['imp_imap']->isReadOnly($targetMbox)) {
                $error = _("The target directory is read-only.");
            }

            if (!$error &&
                ($action == 'move') &&
                $GLOBALS['imp_imap']->isReadOnly($mbox)) {
                $error = _("The source directory is read-only.");
            }

            if (!$error) {
                try {
                    $GLOBALS['imp_imap']->checkUidvalidity($mbox);
                } catch (IMP_Exception $e) {
                    $error = $e->getMessage();
                }
            }

            /* Attempt to copy/move messages to new mailbox. */
            if (!$error) {
                try {
                    $GLOBALS['imp_imap']->ob()->copy($mbox, $targetMbox, array('ids' => $msgIndices, 'move' => $imap_move));

                    $imp_mailbox = $GLOBALS['injector']->getInstance('IMP_Mailbox')->getOb($mbox);
                    if (($action == 'move') && $imp_mailbox->isBuilt()) {
                        $imp_mailbox->removeMsgs(array($mbox => $msgIndices));
                    }
                } catch (Horde_Imap_Client_Exception $e) {
                    $error = $e->getMessage();
                }
            }

            if ($error) {
                $notification->push(sprintf($message, IMP::displayFolder($mbox), IMP::displayFolder($targetMbox)) . ': ' . $error, 'horde.error');
                $return_value = false;
                continue;
            }
        }

        return $return_value;
    }

    /**
     * Deletes a list of messages taking into account whether or not a
     * Trash folder is being used.
     * Handles search and Trash mailboxes.
     *
     * @param mixed $indices  See IMP::parseIndicesList().
     * @param array $options  Additional options:
     * <pre>
     * 'keeplog' - (boolean) Should any history information of the message be
     *             kept?
     * 'nuke' - (boolean) Override user preferences and nuke (i.e. permanently
     *          delete) the messages instead?
     * </pre>
     *
     * @return integer|boolean  The number of messages deleted if successful,
     *                          false if not.
     */
    public function delete($indices, $options = array())
    {
        global $conf, $notification, $prefs;

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
        $maillog_update = (empty($options['keeplog']) && !empty($conf['maillog']['use_maillog']));

        /* Check for Trash folder. */
        $use_trash_folder = !$this->_usepop && empty($options['nuke']) && !$use_vtrash && $use_trash;
        if ($use_trash_folder) {
            $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');

            if (!$imp_folder->exists($trash) &&
                !$imp_folder->create($trash, $prefs->getValue('subscribe'))) {
                return false;
            }
        }

        foreach ($msgList as $mbox => $msgIndices) {
            $error = null;

            if ($GLOBALS['imp_imap']->isReadOnly($mbox)) {
                $error = _("This folder is read-only.");
            }

            if (!$error) {
                try {
                    $GLOBALS['imp_imap']->checkUidvalidity($mbox);
                } catch (IMP_Exception $e) {
                    $error = $e->getMessage();
                }
            }

            if ($error) {
                $notification->push(sprintf(_("There was an error deleting messages from the folder \"%s\"."), IMP::displayFolder($mbox)) . ' ' . $error, 'horde.error');
                $return_value = false;
                continue;
            }

            $indices_array = array($mbox => $msgIndices);
            $return_value += count($msgIndices);

            /* Trash is only valid for IMAP mailboxes. */
            if ($use_trash_folder && ($mbox != $trash)) {
                try {
                    $GLOBALS['imp_imap']->ob()->copy($mbox, $trash, array('ids' => $msgIndices, 'move' => true));

                    $imp_mailbox = $GLOBALS['injector']->getInstance('IMP_Mailbox')->getOb($mbox);
                    if ($imp_mailbox->isBuilt()) {
                        $imp_mailbox->removeMsgs(array($mbox => $msgIndices));
                    }
                } catch (Horde_Imap_Client_Exception $e) {
                    // @todo Check for overquota error.
                    return false;
                }
            } else {
                /* Get the list of Message-IDs for the deleted messages if using
                 * maillogging. */
                $fetch = null;
                if ($maillog_update) {
                    try {
                        $fetch = $GLOBALS['imp_imap']->ob()->fetch($mbox, array(Horde_Imap_Client::FETCH_ENVELOPE => true), array('ids' => $msgIndices));
                    } catch (Horde_Imap_Client_Exception $e) {}
                }

                /* Delete the messages. */
                $expunge_now = false;
                $del_flags = array('\\deleted');

                if ($this->_usepop ||
                    !empty($options['nuke']) ||
                    ($use_trash && ($mbox == $trash)) ||
                    ($use_vtrash && ($GLOBALS['imp_search']->isVTrashFolder()))) {
                    /* Purge messages immediately. */
                    $expunge_now = true;
                } else {
                    /* If we are using virtual trash, we must mark the message
                     * as seen or else it will appear as an 'unseen' message for
                     * purposes of new message counts. */
                    if ($use_vtrash) {
                        $del_flags[] = '\\seen';
                    }
                }

                try {
                    $GLOBALS['imp_imap']->ob()->store($mbox, array('add' => array('\\deleted'), 'ids' => $msgIndices));
                    if ($expunge_now) {
                        $this->expungeMailbox($indices_array);
                    }
                } catch (Horde_Imap_Client_Exception $e) {}

                /* Get the list of Message-IDs deleted, and remove the
                 * information from the mail log. */
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
     * Handles search mailboxes.
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
     * Handles search and Trash mailboxes.
     *
     * @param string $list    The list in which the task or note will be
     *                        created.
     * @param string $action  Either 'copy' or 'move'.
     * @param mixed $msgList  See IMP::parseIndicesList().
     * @param string $type    The object type to create ('note' or 'task').
     */
    protected function _createTasksOrNotes($list, $action, $msgList, $type)
    {
        global $registry, $notification, $prefs;

        foreach ($msgList as $folder => $msgIndices) {
            foreach ($msgIndices as $index) {
                /* Fetch the message contents. */
                $imp_contents = $GLOBALS['injector']->getInstance('IMP_Contents')->getOb($folder, $index);

                /* Fetch the message headers. */
                $imp_headers = $imp_contents->getHeaderOb();
                $subject = $imp_headers->getValue('subject');

                /* Extract the message body. */
                $mime_message = $imp_contents->getMIMEMessage();
                $body_id = $imp_contents->findBody();
                $body_part = $mime_message->getPart($body_id);
                $body = $body_part->getContents();

                /* Re-flow the message for prettier formatting. */
                $flowed = new Horde_Text_Flowed($mime_message->replaceEOL($body, "\n"));
                if ($mime_message->getContentTypeParameter('delsp') == 'yes') {
                    $flowed->setDelSp(true);
                }
                $body = $flowed->toFlowed(false);

                /* Convert to current charset */
                /* TODO: When Horde_iCalendar supports setting of charsets
                 * we need to set it there instead of relying on the fact
                 * that both Nag and IMP use the same charset. */
                $body = Horde_String::convertCharset($body, $body_part->getCharset(), Horde_Nls::getCharset());

                /* Create a new iCalendar. */
                $vCal = new Horde_iCalendar();
                $vCal->setAttribute('PRODID', '-//The Horde Project//IMP ' . $GLOBALS['registry']->getVersion() . '//EN');
                $vCal->setAttribute('METHOD', 'PUBLISH');

                switch ($type) {
                case 'task':
                    /* Create a new vTodo object using this message's
                     * contents. */
                    $vTodo = Horde_iCalendar::newComponent('vtodo', $vCal);
                    $vTodo->setAttribute('SUMMARY', $subject);
                    $vTodo->setAttribute('DESCRIPTION', $body);
                    $vTodo->setAttribute('PRIORITY', '3');

                    /* Get the list of editable tasklists. */
                    try {
                        $lists = $registry->call('tasks/listTasklists', array(false, Horde_Perms::EDIT));
                    } catch (Horde_Exception $e) {
                        $lists = null;
                        $notification->push($e);
                    }

                    /* Attempt to add the new vTodo item to the requested
                     * tasklist. */
                    try {
                        $res = $registry->call('tasks/import', array($vTodo, 'text/calendar', $list));
                    } catch (Horde_Exception $e) {
                        $res = null;
                        $notification->push($e);
                    }
                    break;

                case 'note':
                    /* Create a new vNote object using this message's
                     * contents. */
                    $vNote = Horde_iCalendar::newComponent('vnote', $vCal);
                    $vNote->setAttribute('BODY', $subject . "\n". $body);

                    /* Get the list of editable notepads. */
                    try {
                        $lists = $registry->call('notes/listNotepads', array(false, Horde_Perms::EDIT));
                    } catch (Horde_Exception $e) {
                        $lists = null;
                        $notification->push($e);
                    }

                    /* Attempt to add the new vNote item to the requested
                     * notepad. */
                    try {
                        $res = $registry->call('notes/import', array($vNote, 'text/x-vnote', $list));
                    } catch (Horde_Exception $e) {
                        $res = null;
                        $notification->push($e);
                    }
                    break;
                }

                if (!is_null($res)) {
                    if (!$res) {
                        switch ($type) {
                        case 'task':
                            $notification->push(_("An unknown error occured while creating the new task."), 'horde.error');
                            break;

                        case 'note':
                            $notification->push(_("An unknown error occured while creating the new note."), 'horde.error');
                            break;
                        }
                    } elseif (!is_null($lists)) {
                        $name = '"' . htmlspecialchars($subject) . '"';

                        /* Attempt to convert the object name into a
                         * hyperlink. */
                        try {
                            switch ($type) {
                            case 'task':
                                $link = $registry->link('tasks/show', array('uid' => $res));
                                break;

                            case 'note':
                                $link = $registry->hasMethod('notes/show')
                                    ? $registry->link('notes/show', array('uid' => $res))
                                    : false;
                                break;
                            }

                            if ($link) {
                                $name = sprintf('<a href="%s">%s</a>', Horde::url($link), $name);
                            }

                            $notification->push(sprintf(_("%s was successfully added to \"%s\"."), $name, htmlspecialchars($lists[$list]->get('name'))), 'horde.success', array('content.raw'));
                        } catch (Horde_Exception $e) {}
                    }
                }
            }
        }

        /* Delete the original messages if this is a "move" operation. */
        if ($action == 'move') {
            $this->delete($indices);
        }
    }

    /**
     * Strips one or all MIME parts out of a message.
     * Handles search mailboxes.
     *
     * @param mixed $indices  See IMP::parseIndicesList().
     * @param string $partid  The MIME ID of the part to strip. All parts are
     *                        stripped if null.
     *
     * @throws IMP_Exception
     */
    public function stripPart($indices, $partid = null)
    {
        /* Return error if no index was provided. */
        if (!($msgList = IMP::parseIndicesList($indices))) {
            throw new IMP_Exception(_("An error occured while attempting to strip the attachment."));
        }

        /* If more than one UID provided, return error. */
        reset($msgList);
        list($mbox, $uid) = each($msgList);
        if (each($msgList) || (count($uid) > 1)) {
            throw new IMP_Exception(_("An error occured while attempting to strip the attachment."));
        }
        $uid = reset($uid);

        if ($GLOBALS['imp_imap']->isReadOnly($mbox)) {
            throw new IMP_Exception(_("Cannot strip the MIME part as the mailbox is read-only."));
        }

        $uidvalidity = $GLOBALS['imp_imap']->checkUidvalidity($mbox);

        $contents = $GLOBALS['injector']->getInstance('IMP_Contents')->getOb($mbox, $uid);
        $imap_ob = $GLOBALS['imp_imap']->ob();
        $message = $contents->getMIMEMessage();
        $boundary = trim($message->getContentTypeParameter('boundary'), '"');

        $url_array = array(
            'mailbox' => $mbox,
            'uid' => $uid ,
            'uidvalidity' => $uidvalidity
        );

        /* Always add the header to output. */
        $parts = array(
            array(
                't' => 'url',
                'v' => $imap_ob->utils->createUrl(array_merge($url_array, array('section' => 'HEADER')))
            )
        );

        for ($id = 1; ; ++$id) {
            $part = $message->getPart($id);
            if (!$part) {
                break;
            }

            $parts[] = array(
                't' => 'text',
                'v' => "\r\n--" . $boundary . "\r\n"
            );

            if (($id != 1) && is_null($partid) || ($id == $partid)) {
                $newPart = new Horde_Mime_Part();
                $newPart->setType('text/plain');

                /* Need to make sure all text is in the correct charset. */
                $part_name = $part->getName(true);
                $newPart->setCharset(Horde_Nls::getCharset());
                $newPart->setContents(sprintf(_("[Attachment stripped: Original attachment type: %s, name: %s]"), $part->getType(), $part_name ? $part_name : _("unnamed")));

                $parts[] = array(
                    't' => 'text',
                    'v' => $newPart->toString(array(
                        'canonical' => true,
                        'headers' => true,
                        'stream' => true
                    ))
                );
            } else {
                $parts[] = array(
                    't' => 'url',
                    'v' => $imap_ob->utils->createUrl(array_merge($url_array, array('section' => $id . '.MIME')))
                );
                $parts[] = array(
                    't' => 'url',
                    'v' => $imap_ob->utils->createUrl(array_merge($url_array, array('section' => $id)))
                );
            }
        }

        $parts[] = array(
            't' => 'text',
            'v' => "\r\n--" . $boundary . "--\r\n"
        );

        /* Get the headers for the message. */
        try {
            $res = $imap_ob->fetch($mbox, array(
                Horde_Imap_Client::FETCH_DATE => true,
                Horde_Imap_Client::FETCH_FLAGS => true
            ), array('ids' => array($uid)));
            $res = reset($res);

            /* If in Virtual Inbox, we need to reset flag to unseen so that it
             * appears again in the mailbox list. */
            if ($GLOBALS['imp_search']->isVINBOXFolder($mbox) &&
                ($pos = array_search('\\seen', $res['flags']))) {
                unset($res['flags'][$pos]);
            }

            $new_uid = $imap_ob->append($mbox, array(
                array(
                    'data' => $parts,
                    'flags' => $res['flags'],
                    'internaldate' => $res['date']
                )
            ));
            $new_uid = reset($new_uid);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new IMP_Exception(_("An error occured while attempting to strip the attachment."));
        }

        $this->delete($indices, array('nuke' => true, 'keeplog' => true));

        $GLOBALS['injector']->getInstance('IMP_Mailbox')->getOb($mbox)->setIndex($new_uid . IMP::IDX_SEP . $mbox);

        /* We need to replace the old index in the query string with the
         * new index. */
        $_SERVER['QUERY_STRING'] = str_replace($uid, $new_uid, $_SERVER['QUERY_STRING']);
    }

    /**
     * Sets or clears a given flag for a list of messages.
     * Handles search mailboxes.
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
        if (!($msgList = IMP::parseIndicesList($indices))) {
            return false;
        }

        $action_array = $action
            ? array('add' => $flags)
            : array('remove' => $flags);

        foreach ($msgList as $mbox => $msgIndices) {
            $error = null;

            if ($GLOBALS['imp_imap']->isReadOnly($mbox)) {
                $error = _("This folder is read-only.");
            }

            if (!$error) {
                try {
                    $GLOBALS['imp_imap']->checkUidvalidity($mbox);
                } catch (IMP_Exception $e) {
                    $error = $e->getMessage();
                }
            }

            if (!$error) {
                /* Flag/unflag the messages now. */
                try {
                    $GLOBALS['imp_imap']->ob()->store($mbox, array_merge($action_array, array('ids' => $msgIndices)));
                } catch (Horde_Imap_Client_Exception $e) {
                    $error = $e->getMessage();
                }
            }

            if ($error) {
                $GLOBALS['notification']->push(sprintf(_("There was an error flagging messages in the folder \"%s\". This folder is read-only."), IMP::displayFolder($mbox)), 'horde.error');
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
        if (empty($mboxes) || !is_array($mboxes)) {
            return false;
        }

        $action_array = $action
            ? array('add' => $flags)
            : array('remove' => $flags);

        foreach ($mboxes as $val) {
            try {
                $GLOBALS['imp_imap']->ob()->store($val, $action_array);
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
     * @param array $options    Additional options:
     * <pre>
     * 'list' - (boolean) Return a list of messages expunged.
     *          DEFAULT: false
     * </pre>
     *
     * @return array  If 'list' option is true, an array of mailbox names as
     *                keys and UIDs as values that were expunged.
     */
    public function expungeMailbox($mbox_list, $options = array())
    {
        $msg_list = !empty($options['list']);

        if (empty($mbox_list)) {
            return $msg_list ? array() : null;
        }

        $process_list = $update_list = array();

        foreach (array_keys($mbox_list) as $key) {
            if (!$GLOBALS['imp_imap']->isReadOnly($key)) {
                if ($GLOBALS['imp_search']->isSearchMbox($key)) {
                    foreach ($GLOBALS['imp_search']->getSearchFolders($key) as $skey) {
                        $process_list[$skey] = $mbox_list[$key];
                    }
                } else {
                    $process_list[$key] = $mbox_list[$key];
                }
            }
        }

        foreach ($process_list as $key => $val) {
            /* If expunging a particular UID list, need to check
             * UIDVALIDITY. */
            if (is_array($val)) {
                try {
                    $GLOBALS['imp_imap']->checkUidvalidity($key);
                } catch (IMP_Exception $e) {
                    continue;
                }
            }

            try {
                $update_list[$key] = $GLOBALS['imp_imap']->ob()->expunge($key, array('ids' => is_array($val) ? $val : array(), 'list' => $msg_list));

                $imp_mailbox = $GLOBALS['injector']->getInstance('IMP_Mailbox')->getOb($key);
                if ($imp_mailbox->isBuilt()) {
                    $imp_mailbox->removeMsgs(is_array($val) ? array($key => $val) : true);
                }
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        return $msg_list ? $update_list : null;
    }

    /**
     * Empties an entire mailbox.
     *
     * @param array $mbox_list  The list of mailboxes to empty.
     */
    public function emptyMailbox($mbox_list)
    {
        global $imp_search, $notification, $prefs;

        $trash_folder = ($prefs->getValue('use_trash'))
            ? IMP::folderPref($prefs->getValue('trash_folder'), true)
            : null;

        foreach ($mbox_list as $mbox) {
            $display_mbox = IMP::displayFolder($mbox);

            if ($GLOBALS['imp_imap']->isReadOnly($mbox)) {
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
                $status = $GLOBALS['imp_imap']->ob()->status($mbox, Horde_Imap_Client::STATUS_MESSAGES);
                if (empty($status['messages'])) {
                    $notification->push(sprintf(_("The mailbox %s is already empty."), $display_mbox), 'horde.message');
                    continue;
                }

                if (empty($trash_folder) || ($trash_folder == $mbox)) {
                    $this->flagAllInMailbox(array('\\deleted'), array($mbox), true);
                    $this->expungeMailbox(array($mbox => 1));
                } else {
                    $ret = $GLOBALS['imp_imap']->ob()->search($mbox);
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
            $res = $GLOBALS['imp_imap']->ob()->fetch($mbox, array(Horde_Imap_Client::FETCH_SIZE => true), array('sequence' => true));

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
