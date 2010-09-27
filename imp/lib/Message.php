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
 * @author   Chris Hyde <chris@jeks.net>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
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
     * @param string $targetMbox    The mailbox to move/copy messages to
     *                              (UTF7-IMAP).
     * @param string $action        Either 'copy' or 'move'.
     * @param IMP_Indices $indices  An indices object.
     * @param array $opts           Additional options:
     * <pre>
     * 'create' - (boolean) Should the target mailbox be created?
     *            DEFAULT: false
     * 'mailboxob' - (IMP_Mailbox_List) Update this mailbox object.
     *               DEFAULT: No update.
     * </pre>
     *
     * @return boolean  True if successful, false if not.
     */
    public function copy($targetMbox, $action, $indices, array $opts = array())
    {
        global $conf, $notification, $prefs;

        if (!count($indices)) {
            return false;
        }

        /* If the target is a tasklist, handle the move/copy specially. */
        if ($conf['tasklist']['use_tasklist'] &&
            (strpos($targetMbox, IMP::TASKLIST_EDIT) === 0)) {
            $this->_createTasksOrNotes(str_replace(IMP::TASKLIST_EDIT, '', $targetMbox), $action, $indices, 'task');
            return true;
        }

        /* If the target is a notepad, handle the move/copy specially. */
        if ($conf['notepad']['use_notepad'] &&
            (strpos($targetMbox, IMP::NOTEPAD_EDIT) === 0)) {
            $this->_createTasksOrNotes(str_replace(IMP::NOTEPAD_EDIT, '', $targetMbox), $action, $indices, 'note');
            return true;
        }

        if (!empty($opts['create'])) {
            $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');
            if (!$imp_folder->exists($targetMbox) &&
                !$imp_folder->create($targetMbox, $prefs->getValue('subscribe'))) {
                return false;
            }
        }

        /* Determine if report on move to Spam mailbox is active. */
        $spam_report =
            $prefs->getValue('move_spam_report') &&
            ($targetMbox == IMP::folderPref($prefs->getValue('spam_folder'), true));

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

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();

        foreach ($indices->indices() as $mbox => $msgIndices) {
            $error = null;

            if ($imp_imap->isReadOnly($targetMbox)) {
                $error = _("The target directory is read-only.");
            }

            if (!$error &&
                ($action == 'move') &&
                $imp_imap->isReadOnly($mbox)) {
                $error = _("The source directory is read-only.");
            }

            if (!$error) {
                try {
                    $imp_imap->checkUidvalidity($mbox);
                } catch (IMP_Exception $e) {
                    $error = $e->getMessage();
                }
            }

            /* Attempt to copy/move messages to new mailbox. */
            if (!$error) {
                try {
                    $imp_imap->copy($mbox, $targetMbox, array('ids' => $msgIndices, 'move' => $imap_move));

                    if (($action == 'move') &&
                        !empty($opts['mailboxob']) &&
                        $opts['mailboxob']->isBuilt()) {
                        $opts['mailboxob']->removeMsgs(new IMP_Indices($mbox, $msgIndices));
                    }

                    if ($spam_report) {
                        IMP_Spam::reportSpam(new IMP_Indices($mbox, $msgIndices), 'spam', array('noaction' => true));
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
     * @param IMP_Indices $indices  An indices object.
     * @param array $options        Additional options:
     * <pre>
     * 'keeplog' - (boolean) Should any history information of the message be
     *             kept?
     * 'mailboxob' - (IMP_Mailbox_List) Update this mailbox object.
     *               DEFAULT: No update.
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

        if (!count($indices)) {
            return false;
        }

        $trash = IMP::folderPref($prefs->getValue('trash_folder'), true);
        $use_trash = $prefs->getValue('use_trash');
        if ($use_trash && empty($trash)) {
            $notification->push(_("Cannot move messages to Trash - no Trash mailbox set in preferences."), 'horde.error');
            return false;
        }

        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
        $maillog_update = (empty($options['keeplog']) && !empty($conf['maillog']['use_maillog']));
        $return_value = 0;

        /* Check for Trash folder. */
        $use_trash_folder = $use_vtrash = false;
        if (!$this->_usepop && empty($options['nuke']) && $use_trash) {
            $use_vtrash = $imp_search->isVTrash($trash);
            $use_trash_folder = !$use_vtrash;
        }

        if ($use_trash_folder) {
            $imp_folder = $GLOBALS['injector']->getInstance('IMP_Folder');

            if (!$imp_folder->exists($trash) &&
                !$imp_folder->create($trash, $prefs->getValue('subscribe'))) {
                return false;
            }
        }

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();

        foreach ($indices->indices() as $mbox => $msgIndices) {
            $error = null;

            if ($imp_imap->isReadOnly($mbox)) {
                $error = _("This folder is read-only.");
            }

            if (!$error) {
                try {
                    $imp_imap->checkUidvalidity($mbox);
                } catch (IMP_Exception $e) {
                    $error = $e->getMessage();
                }
            }

            if ($error) {
                $notification->push(sprintf(_("There was an error deleting messages from the folder \"%s\"."), IMP::displayFolder($mbox)) . ' ' . $error, 'horde.error');
                $return_value = false;
                continue;
            }

            $imp_indices = new IMP_Indices($mbox, $msgIndices);
            $return_value += count($msgIndices);

            /* Trash is only valid for IMAP mailboxes. */
            if ($use_trash_folder && ($mbox != $trash)) {
                try {
                    $imp_imap->copy($mbox, $trash, array('ids' => $msgIndices, 'move' => true));

                    if (!empty($options['mailboxob']) &&
                        $options['mailboxob']->isBuilt()) {
                        $options['mailboxob']->removeMsgs($imp_indices);
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
                        $fetch = $imp_imap->fetch($mbox, array(Horde_Imap_Client::FETCH_ENVELOPE => true), array('ids' => $msgIndices));
                    } catch (Horde_Imap_Client_Exception $e) {}
                }

                /* Delete the messages. */
                $expunge_now = false;
                $del_flags = array('\\deleted');

                if ($this->_usepop ||
                    !empty($options['nuke']) ||
                    ($use_trash && ($mbox == $trash)) ||
                    ($imp_search->isVTrash($mbox))) {
                    /* Purge messages immediately. */
                    $expunge_now = true;
                } elseif ($use_vtrash) {
                    /* If we are using virtual trash, we must mark the message
                     * as seen or else it will appear as an 'unseen' message
                     * for purposes of new message counts. */
                    $del_flags[] = '\\seen';
                }

                try {
                    $imp_imap->store($mbox, array('add' => $del_flags, 'ids' => $msgIndices));
                    if ($expunge_now) {
                        $this->expungeMailbox(
                            $imp_indices->indices(),
                            array(
                                'mailboxob' => empty($opts['mailboxob']) ? null : $opts['mailboxob']
                            )
                        );
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
     * @param IMP_Indices $indices  An indices object.
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
     * @param string $list          The list in which the task or note will be
     *                              created.
     * @param string $action        Either 'copy' or 'move'.
     * @param IMP_Indices $indices  An indices object.
     * @param string $type          The object type to create ('note' or
     *                              'task').
     */
    protected function _createTasksOrNotes($list, $action, $indices, $type)
    {
        global $registry, $notification, $prefs;

        foreach ($indices as $folder => $index) {
            /* Fetch the message contents. */
            $imp_contents = $GLOBALS['injector']->getInstance('IMP_Contents')->getOb(new IMP_Indices($folder, $index));

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
            $body = Horde_String::convertCharset($body, $body_part->getCharset(), $GLOBALS['registry']->getCharset());

            /* Create a new iCalendar. */
            $vCal = new Horde_iCalendar();
            $vCal->setAttribute('PRODID', '-//The Horde Project//IMP ' . $GLOBALS['registry']->getVersion() . '//EN');
            $vCal->setAttribute('METHOD', 'PUBLISH');

            switch ($type) {
            case 'task':
                /* Create a new vTodo object using this message's contents. */
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
                /* Create a new vNote object using this message's contents. */
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

                    /* Attempt to convert the object name into a hyperlink. */
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

        /* Delete the original messages if this is a "move" operation. */
        if ($action == 'move') {
            $this->delete($indices);
        }
    }

    /**
     * Strips one or all MIME parts out of a message.
     * Handles search mailboxes.
     *
     * @param IMP_Indices $indices  An indices object.
     * @param string $partid        The MIME ID of the part to strip. All
     *                              parts are stripped if null.
     * @param array $opts           Additional options:
     * <pre>
     * 'mailboxob' - (IMP_Mailbox_List) Update this mailbox object.
     *               DEFAULT: No update.
     * </pre>
     *
     * @return IMP_Indices  Returns the new indices object.
     * @throws IMP_Exception
     */
    public function stripPart($indices, $partid = null, array $opts = array())
    {
        list($mbox, $uid) = $indices->getSingle();
        if (!$uid) {
            return;
        }

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();

        if ($imp_imap->isReadOnly($mbox)) {
            throw new IMP_Exception(_("Cannot strip the MIME part as the mailbox is read-only."));
        }

        $uidvalidity = $imp_imap->checkUidvalidity($mbox);

        $contents = $GLOBALS['injector']->getInstance('IMP_Contents')->getOb($indices);
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
                'v' => $imp_imap->getUtils()->createUrl(array_merge($url_array, array('section' => 'HEADER')))
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
                $newPart->setCharset($GLOBALS['registry']->getCharset());
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
                    'v' => $imp_imap->getUtils()->createUrl(array_merge($url_array, array('section' => $id . '.MIME')))
                );
                $parts[] = array(
                    't' => 'url',
                    'v' => $imp_imap->getUtils()->createUrl(array_merge($url_array, array('section' => $id)))
                );
            }
        }

        $parts[] = array(
            't' => 'text',
            'v' => "\r\n--" . $boundary . "--\r\n"
        );

        /* Get the headers for the message. */
        try {
            $res = $imp_imap->fetch($mbox, array(
                Horde_Imap_Client::FETCH_DATE => true,
                Horde_Imap_Client::FETCH_FLAGS => true
            ), array('ids' => array($uid)));
            $res = reset($res);

            /* If in Virtual Inbox, we need to reset flag to unseen so that it
             * appears again in the mailbox list. */
            if ($GLOBALS['injector']->getInstance('IMP_Search')->isVinbox($mbox) &&
                ($pos = array_search('\\seen', $res['flags']))) {
                unset($res['flags'][$pos]);
            }

            $new_uid = $imp_imap->append($mbox, array(
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

        $this->delete($indices, array(
            'keeplog' => true,
            'mailboxob' => empty($opts['mailboxob']) ? null : $opts['mailboxob'],
            'nuke' => true
        ));

        $indices_ob = new IMP_Indices($mbox, $new_uid);

        if (!empty($opts['mailboxob'])) {
            $opts['mailboxob']->setIndex($indices_ob);
        }

        /* We need to replace the old index in the query string with the
         * new index. */
        $_SERVER['QUERY_STRING'] = str_replace($uid, $new_uid, $_SERVER['QUERY_STRING']);

        return $indices_ob;
    }

    /**
     * Sets or clears a given flag for a list of messages.
     * Handles search mailboxes.
     * This function works with IMAP only, not POP3.
     *
     * @param array $flags          The IMAP flag(s) to set or clear.
     * @param IMP_Indices $indices  An indices object.
     * @param boolean $action       If true, set the flag(s), otherwise clear
     *                              the flag(s).
     *
     * @return boolean  True if successful, false if not.
     */
    public function flag($flags, $indices, $action = true)
    {
        if (!count($indices)) {
            return false;
        }

        $action_array = $action
            ? array('add' => $flags)
            : array('remove' => $flags);
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();

        foreach ($indices->indices() as $mbox => $msgIndices) {
            $error = null;

            if ($imp_imap->isReadOnly($mbox)) {
                $error = _("This folder is read-only.");
            }

            if (!$error) {
                try {
                    $imp_imap->checkUidvalidity($mbox);
                } catch (IMP_Exception $e) {
                    $error = $e->getMessage();
                }
            }

            if (!$error) {
                /* Flag/unflag the messages now. */
                try {
                    $imp_imap->store($mbox, array_merge($action_array, array('ids' => $msgIndices)));
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
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();

        foreach ($mboxes as $val) {
            try {
                $imp_imap->store($val, $action_array);
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
     * 'mailboxob' - (IMP_Mailbox_List) Update this mailbox object.
     *               DEFAULT: No update.
     * </pre>
     *
     * @return IMP_Indices  If 'list' option is true, an indices object
     *                      containing the messages that have been expunged.
     */
    public function expungeMailbox($mbox_list, array $options = array())
    {
        $msg_list = !empty($options['list']);

        if (empty($mbox_list)) {
            return $msg_list ? new IMP_Indices() : null;
        }

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();
        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
        $process_list = $update_list = array();

        foreach (array_keys($mbox_list) as $key) {
            if (!$imp_imap->isReadOnly($key)) {
                if ($imp_search->isSearchMbox($key)) {
                    foreach ($imp_search[$key]->mboxes as $skey) {
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
                    $imp_imap->checkUidvalidity($key);
                } catch (IMP_Exception $e) {
                    continue;
                }
            }

            try {
                $update_list[$key] = $imp_imap->expunge($key, array('ids' => is_array($val) ? $val : array(), 'list' => $msg_list));

                if (!empty($opts['mailboxob']) &&
                    $opts['mailboxob']->isBuilt()) {
                    $opts['mailboxob']->removeMsgs(is_array($val) ? new IMP_Indices($key, $val) : true);
                }
            } catch (Horde_Imap_Client_Exception $e) {}
        }

        if ($msg_list) {
            return new IMP_Indices($update_list);
        }
    }

    /**
     * Empties an entire mailbox.
     *
     * @param array $mbox_list  The list of mailboxes to empty.
     */
    public function emptyMailbox($mbox_list)
    {
        global $notification, $prefs;

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb();
        $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
        $trash_folder = ($prefs->getValue('use_trash'))
            ? IMP::folderPref($prefs->getValue('trash_folder'), true)
            : null;

        foreach ($mbox_list as $mbox) {
            $display_mbox = IMP::displayFolder($mbox);

            if ($imp_imap->isReadOnly($mbox)) {
                $notification->push(sprintf(_("Could not delete messages from %s. This mailbox is read-only."), $display_mbox), 'horde.error');
                continue;
            }

            if ($imp_search->isVTrash($mbox)) {
                $this->expungeMailbox(array_flip($imp_search[$mbox]->mboxes));
                $notification->push(_("Emptied all messages from Virtual Trash Folder."), 'horde.success');
                continue;
            }

            /* Make sure there is at least 1 message before attempting to
               delete. */
            try {
                $status = $imp_imap->status($mbox, Horde_Imap_Client::STATUS_MESSAGES);
                if (empty($status['messages'])) {
                    $notification->push(sprintf(_("The mailbox %s is already empty."), $display_mbox), 'horde.message');
                    continue;
                }

                if (empty($trash_folder) || ($trash_folder == $mbox)) {
                    $this->flagAllInMailbox(array('\\deleted'), array($mbox), true);
                    $this->expungeMailbox(array($mbox => 1));
                } else {
                    $ret = $imp_imap->search($mbox);
                    $this->delete(new IMP_Indices($mbox, $ret['match']));
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
            $res = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->fetch($mbox, array(Horde_Imap_Client::FETCH_SIZE => true), array('sequence' => true));

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
