<?php
/**
 * The IMP_Message:: class contains all functions related to handling messages
 * within IMP. Actions such as moving, copying, and deleting messages are
 * handled in here so that code need not be repeated between mailbox, message,
 * and other pages.
 *
 * Copyright 2000-2001 Chris Hyde <chris@jeks.net>
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chris Hyde <chris@jeks.net>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Message
{
    /**
     * Copies or moves a list of messages to a new mailbox.
     * Handles search and Trash mailboxes.
     * Also handles moves to the tasklist and/or notepad applications.
     *
     * @param string $targetMbox    The mailbox to move/copy messages to
     *                              (UTF-8).
     * @param string $action        Either 'copy' or 'move'.
     * @param IMP_Indices $indices  An indices object.
     * @param array $opts           Additional options:
     *   - create: (boolean) Should the target mailbox be created?
     *             DEFAULT: false
     *   - mailboxob: (IMP_Mailbox_List) Update this mailbox object.
     *                DEFAULT: No update.
     *
     * @return boolean  True if successful, false if not.
     */
    public function copy($targetMbox, $action, IMP_Indices $indices,
                         array $opts = array())
    {
        global $conf, $notification;

        if (!count($indices)) {
            return false;
        }

        $targetMbox = IMP_Mailbox::get($targetMbox);

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

        if (!empty($opts['create']) && !$targetMbox->create()) {
            return false;
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

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        foreach ($indices as $ob) {
            try {
                if ($targetMbox->readonly) {
                    throw new IMP_Exception(_("The target directory is read-only."));
                }

                if (($action == 'move') && $ob->mbox->readonly) {
                    throw new IMP_Exception(_("The source directory is read-only."));
                }

                $ob->mbox->uidvalid;

                /* Attempt to copy/move messages to new mailbox. */
                $imp_imap->copy($ob->mbox, $targetMbox, array(
                    'ids' => $imp_imap->getIdsOb($ob->uids),
                    'move' => $imap_move
                ));

                if (($action == 'move') &&
                    !empty($opts['mailboxob']) &&
                    $opts['mailboxob']->isBuilt()) {
                    $opts['mailboxob']->removeMsgs($ob->mbox->getIndicesOb($ob->uids));
                }
            } catch (Exception $e) {
                $error_msg = sprintf($message, $ob->mbox->display, $targetMbox->display) . ': ' . $e->getMessage();
                if ($e instanceof IMP_Imap_Exception) {
                    $e->notify($error_msg);
                } else {
                    $notification->push($error_msg, 'horde.error');
                }
                $return_value = false;
            }
        }

        return $return_value;
    }

    /**
     * Deletes a list of messages.
     * Handles search and Trash mailboxes.
     *
     * @param IMP_Indices $indices  An indices object.
     * @param array $opts           Additional options:
     *   - keeplog: (boolean) Should any history information of the message be
     *              kept?
     *   - mailboxob: (IMP_Mailbox_List) Update this mailbox object.
     *                DEFAULT: No update.
     *   - nuke: (boolean) Override user preferences and nuke (i.e.
     *           permanently delete) the messages instead?
     *
     * @return integer|boolean  The number of messages deleted if successful,
     *                          false if not.
     */
    public function delete(IMP_Indices $indices, array $opts = array())
    {
        global $conf, $injector, $notification, $prefs;

        if (!count($indices)) {
            return false;
        }

        $trash = IMP_Mailbox::getPref('trash_folder');
        $use_trash = $prefs->getValue('use_trash');
        if ($use_trash && !$trash) {
            $notification->push(_("Cannot move messages to Trash - no Trash mailbox set in preferences."), 'horde.error');
            return false;
        }

        $maillog_update = (empty($opts['keeplog']) && !empty($conf['maillog']['use_maillog']));
        $return_value = 0;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        /* Check for Trash mailbox. */
        $no_expunge = $use_trash_mbox = $use_vtrash = false;
        if ($use_trash &&
            empty($opts['nuke']) &&
            $imp_imap->access(IMP_Imap::ACCESS_TRASH)) {
            $use_vtrash = $trash->vtrash;
            $use_trash_mbox = !$use_vtrash;
        }

        /* Check whether we are marking messages as seen.
         * If using virtual trash, we must mark the message as seen or else it
         * will appear as an 'unseen' message for purposes of new message
         * counts. */
        $mark_seen = empty($opts['nuke']) &&
                     ($use_vtrash || $prefs->getValue('delete_mark_seen'));

        if ($use_trash_mbox && !$trash->create()) {
            /* If trash mailbox could not be created, just mark message as
             * deleted. */
            $no_expunge = true;
            $return_value = $use_trash_mbox = false;
        }

        foreach ($indices as $ob) {
            try {
                if (!$ob->mbox->access_deletemsgs) {
                    throw new IMP_Exception(_("This mailbox is read-only."));
                }

                $ob->mbox->uidvalid;
            } catch (IMP_Exception $e) {
                $notification->push(sprintf(_("There was an error deleting messages from the mailbox \"%s\"."), $ob->mbox->display) . ' ' . $e->getMessage(), 'horde.error');
                $return_value = false;
                continue;
            }

            $imp_indices = $ob->mbox->getIndicesOb($ob->uids);
            if ($return_value !== false) {
                $return_value += count($ob->uids);
            }

            $ids_ob = $imp_imap->getIdsOb($ob->uids);

            /* Trash is only valid for IMAP mailboxes. */
            if ($use_trash_mbox && ($ob->mbox != $trash)) {
                if ($ob->mbox->access_expunge) {
                    try {
                        if ($mark_seen) {
                            $imp_imap->store($ob->mbox, array(
                                'add' => array(
                                    Horde_Imap_Client::FLAG_SEEN
                                ),
                                'ids' => $ids_ob
                            ));
                        }

                        $imp_imap->copy($ob->mbox, $trash, array(
                            'ids' => $ids_ob,
                            'move' => true
                        ));

                        if (!empty($opts['mailboxob']) &&
                            $opts['mailboxob']->isBuilt()) {
                            $opts['mailboxob']->removeMsgs($imp_indices);
                        }
                    } catch (IMP_Imap_Exception $e) {
                        if ($e->getCode() == $e::OVERQUOTA) {
                            $notification->push(_("You are over your quota, so your messages will be permanently deleted instead of moved to the Trash mailbox."), 'horde.warning');
                            $opts['nuke'] = true;
                            return $this->delete(new IMP_Indices($ob->mbox, $ob->uids), $opts);
                        }

                        return false;
                    }
                }
            } else {
                /* Get the list of Message-IDs for the deleted messages if
                 * using mail logging. */
                $fetch = null;
                if ($maillog_update) {
                    $query = new Horde_Imap_Client_Fetch_Query();
                    $query->envelope();

                    try {
                        $fetch = $imp_imap->fetch($ob->mbox, $query, array(
                            'ids' => $ids_ob
                        ));
                    } catch (IMP_Imap_Exception $e) {}
                }

                /* Delete the messages. */
                $expunge_now = false;
                $del_flags = array(Horde_Imap_Client::FLAG_DELETED);

                if (!$use_vtrash &&
                    (!$imp_imap->access(IMP_Imap::ACCESS_TRASH) ||
                     !empty($opts['nuke']) ||
                     ($use_trash && ($ob->mbox == $trash)))) {
                    /* Purge messages immediately. */
                    $expunge_now = !$no_expunge;
                } elseif ($mark_seen) {
                    $del_flags[] = Horde_Imap_Client::FLAG_SEEN;
                }

                try {
                    $imp_imap->store($ob->mbox, array(
                        'add' => $del_flags,
                        'ids' => $ids_ob
                    ));
                    if ($expunge_now) {
                        $this->expungeMailbox(
                            $imp_indices->indices(),
                            array(
                                'mailboxob' => empty($opts['mailboxob']) ? null : $opts['mailboxob']
                            )
                        );
                    } elseif (!empty($opts['mailboxob']) &&
                              $opts['mailboxob']->isBuilt() &&
                              $ob->mbox->hideDeletedMsgs()) {
                        $opts['mailboxob']->removeMsgs($imp_indices);
                    }
                } catch (IMP_Imap_Exception $e) {}

                /* Get the list of Message-IDs deleted, and remove the
                 * information from the mail log. */
                if (!is_null($fetch)) {
                    $msg_ids = array();
                    foreach ($fetch as $v) {
                        if ($msg_id = $v->getEnvelope()->message_id) {
                            $msg_ids[] = $msg_id;
                        }
                    }

                    $injector->getInstance('IMP_Maillog')->deleteLog($msg_ids);
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
    public function undelete(IMP_Indices $indices)
    {
        return $this->flag(array(Horde_Imap_Client::FLAG_DELETED), $indices, false);
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
    protected function _createTasksOrNotes($list, $action,
                                           IMP_Indices $indices, $type)
    {
        global $registry, $notification;

        foreach ($indices as $ob) {
            foreach ($ob->uids as $uid) {
                /* Fetch the message contents. */
                $imp_contents = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($ob->mbox->getIndicesOb($uid));

                /* Fetch the message headers. */
                $imp_headers = $imp_contents->getHeader();
                $subject = $imp_headers->getValue('subject');

                /* Re-flow the message for prettier formatting. */
                $body_part = $imp_contents->getMIMEPart($imp_contents->findBody());
                $flowed = new Horde_Text_Flowed($body_part->getContents());
                if ($body_part->getContentTypeParameter('delsp') == 'yes') {
                    $flowed->setDelSp(true);
                }
                $body = $flowed->toFlowed(false);

                /* Convert to current charset */
                /* TODO: When Horde_Icalendar supports setting of charsets
                 * we need to set it there instead of relying on the fact
                 * that both Nag and IMP use the same charset. */
                $body = Horde_String::convertCharset($body, $body_part->getCharset(), 'UTF-8');

                /* Create a new iCalendar. */
                $vCal = new Horde_Icalendar();
                $vCal->setAttribute('PRODID', '-//The Horde Project//IMP ' . $GLOBALS['registry']->getVersion() . '//EN');
                $vCal->setAttribute('METHOD', 'PUBLISH');

                switch ($type) {
                case 'task':
                    /* Create a new vTodo object using this message's
                     * contents. */
                    $vTodo = Horde_Icalendar::newComponent('vtodo', $vCal);
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
                    $vNote = Horde_Icalendar::newComponent('vnote', $vCal);
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
     * @param IMP_Indices $indices  An indices object.
     * @param string $partid        The MIME ID of the part to strip. All
     *                              parts are stripped if null.
     * @param array $opts           Additional options:
     *   - mailboxob: (IMP_Mailbox_List) Update this mailbox object.
     *                DEFAULT: No update.
     *
     * @return IMP_Indices  Returns the new indices object.
     * @throws IMP_Exception
     */
    public function stripPart(IMP_Indices $indices, $partid = null,
                              array $opts = array())
    {
        list($mbox, $uid) = $indices->getSingle();
        if (!$uid) {
            return;
        }

        if ($mbox->readonly) {
            throw new IMP_Exception(_("Cannot strip the MIME part as the mailbox is read-only."));
        }

        $uidvalidity = $mbox->uidvalid;

        $contents = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($indices);
        $message = $contents->getMIMEMessage();
        $boundary = trim($message->getContentTypeParameter('boundary'), '"');

        $url_array = array(
            'mailbox' => $mbox,
            'uid' => $uid ,
            'uidvalidity' => $uidvalidity
        );

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

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
                $newPart->setCharset('UTF-8');
                $newPart->setContents(sprintf(_("[Attachment stripped: Original attachment type: %s, name: %s]"), $part->getType(), $contents->getPartName($part)));

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
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->imapDate();
        $query->flags();

        try {
            $res = $imp_imap->fetch($mbox, $query, array(
                'ids' => $imp_imap->getIdsOb($uid)
            ))->first();
            if (is_null($res)) {
                throw new IMP_Imap_Exception();
            }
            $flags = $res->getFlags();

            /* If in Virtual Inbox, we need to reset flag to unseen so that it
             * appears again in the mailbox list. */
            if ($mbox->vinbox) {
                $flags = array_values(array_diff($flags, array(Horde_Imap_Client::FLAG_SEEN)));
            }

            $new_uid = $imp_imap->append($mbox, array(
                array(
                    'data' => $parts,
                    'flags' => $flags,
                    'internaldate' => $res->getImapDate()
                )
            ))->ids;
            $new_uid = reset($new_uid);
        } catch (IMP_Imap_Exception $e) {
            throw new IMP_Exception(_("An error occured while attempting to strip the attachment."));
        }

        $this->delete($indices, array(
            'keeplog' => true,
            'mailboxob' => empty($opts['mailboxob']) ? null : $opts['mailboxob'],
            'nuke' => true
        ));

        $indices_ob = $mbox->getIndicesOb($new_uid);

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
    public function flag($flags, IMP_Indices $indices, $action = true)
    {
        if (!count($indices)) {
            return false;
        }

        $action_array = $action
            ? array('add' => $flags)
            : array('remove' => $flags);
        $ajax_queue = $GLOBALS['injector']->getInstance('IMP_Ajax_Queue');
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        $ret = true;

        foreach ($indices as $ob) {
            try {
                if ($ob->mbox->readonly) {
                    throw new IMP_Exception(_("This mailbox is read-only."));
                }

                $ob->mbox->uidvalid;

                /* Flag/unflag the messages now. */
                $imp_imap->store($ob->mbox, array_merge($action_array, array(
                    'ids' => $imp_imap->getIdsOb($ob->uids)
                )));

                $ajax_queue->flag(reset($action_array), $action, $ob->mbox->getIndicesOb($ob->uids));
            } catch (Exception $e) {
                $GLOBALS['notification']->push(sprintf(_("There was an error flagging messages in the mailbox \"%s\": %s."), $ob->mbox->display, $e->getMessage()), 'horde.error');
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     * Adds or removes flag(s) for all messages in a list of mailboxes.
     * This function works with IMAP only, not POP3.
     *
     * @param array $flags     The IMAP flag(s) to add or remove.
     * @param array $mboxes    The list of mailboxes to flag.
     * @param boolean $action  If true, add the flag(s), otherwise, remove the
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
        $ajax_queue = $GLOBALS['injector']->getInstance('IMP_Ajax_Queue');
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        $ajax_queue->poll($mboxes);

        foreach (IMP_Mailbox::get($mboxes) as $val) {
            try {
                /* Grab list of UIDs before flagging, to make sure we
                 * determine the exact subset that has been flagged. */
                $mailbox_list = $val->getListOb()->getIndicesOb();
                $imp_imap->store($val, $action_array);
                $ajax_queue->flag(reset($action_array), $action, $mailbox_list);
            } catch (IMP_Imap_Exception $e) {
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
     * @param array $opts       Additional options:
     *   - list: (boolean) Return a list of messages expunged.
     *           DEFAULT: false
     *   - mailboxob: (IMP_Mailbox_List) Update this mailbox object.
     *                DEFAULT: No update.
     *
     * @return IMP_Indices  If 'list' option is true, an indices object
     *                      containing the messages that have been expunged.
     */
    public function expungeMailbox($mbox_list, array $opts = array())
    {
        $msg_list = !empty($opts['list']);

        if (empty($mbox_list)) {
            return $msg_list ? new IMP_Indices() : null;
        }

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        $process_list = $update_list = array();

        foreach ($mbox_list as $key => $val) {
            $key = IMP_Mailbox::get($key);

            if ($key->access_expunge) {
                $ids = $imp_imap->getIdsOb(is_array($val) ? $val : Horde_Imap_Client_Ids::ALL);

                if ($key->search) {
                    foreach ($key->getSearchOb()->mboxes as $skey) {
                        $process_list[] = array($skey, $ids);
                    }
                } else {
                    $process_list[] = array($key, $ids);
                }
            }
        }

        // [0] = IMP_Mailbox object, [1] = Horde_Imap_Client_Ids object
        foreach ($process_list as $val) {
            /* If expunging a particular UID list, need to check
             * UIDVALIDITY. */
            if (!$val[1]->all) {
                try {
                    $val[0]->uidvalid;
                } catch (IMP_Exception $e) {
                    continue;
                }
            }

            try {
                $update_list[strval($val[0])] = $imp_imap->expunge($val[0], array(
                    'ids' => $val[1],
                    'list' => $msg_list
                ));

                if (!empty($opts['mailboxob']) &&
                    $opts['mailboxob']->isBuilt()) {
                    $opts['mailboxob']->removeMsgs($val[1]->all ? true : $val[0]->getIndicesOb($val[1]));
                }
            } catch (IMP_Imap_Exception $e) {}
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

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        $trash = ($prefs->getValue('use_trash'))
            ? IMP_Mailbox::getPref('trash_folder')
            : null;

        foreach (IMP_Mailbox::get($mbox_list) as $mbox) {
            if (!$mbox->access_empty) {
                $notification->push(sprintf(_("Could not delete messages from %s. This mailbox is read-only."), $mbox->display), 'horde.error');
                continue;
            }

            if ($mbox->vtrash) {
                $this->expungeMailbox(array_flip($mbox->getSearchOb()->mboxes));
                $notification->push(_("Emptied all messages from Virtual Trash Folder."), 'horde.success');
                continue;
            }

            /* Make sure there is at least 1 message before attempting to
               delete. */
            try {
                $status = $imp_imap->status($mbox, Horde_Imap_Client::STATUS_MESSAGES);
                if (empty($status['messages'])) {
                    $notification->push(sprintf(_("The mailbox %s is already empty."), $mbox->display), 'horde.message');
                    continue;
                }

                if (!$trash || ($trash == $mbox)) {
                    $this->flagAllInMailbox(array(Horde_Imap_Client::FLAG_DELETED), array($mbox), true);
                    $this->expungeMailbox(array(strval($mbox) => 1));
                } else {
                    $ret = $imp_imap->search($mbox);
                    $this->delete($mbox->getIndicesOb($ret['match']));
                }

                $notification->push(sprintf(_("Emptied all messages from %s."), $mbox->display), 'horde.success');
            } catch (IMP_Imap_Exception $e) {}
        }
    }

    /**
     * Obtains the size of a mailbox.
     *
     * @param IMP_Mailbox $mbox   The mailbox to obtain the size of.
     * @param boolean $formatted  Whether to return a human readable value.
     *
     * @return mixed  Either the size of the mailbox (in bytes) or a formatted
     *                string with this information.
     */
    public function sizeMailbox(IMP_Mailbox $mbox, $formatted = true)
    {
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->size();

        try {
            $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
            $res = $imp_imap->fetch($mbox, $query, array(
                'ids' => $imp_imap->getIdsOb(Horde_Imap_Client_Ids::ALL, true)
            ));

            $size = 0;
            foreach ($res as $v) {
                $size += $v->getSize();
            }
            return ($formatted)
                ? sprintf(_("%.2fMB"), $size / (1024 * 1024))
                : $size;
        } catch (IMP_Imap_Exception $e) {
            return 0;
        }
    }

}
