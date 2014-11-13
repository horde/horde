<?php
/**
 * Copyright 2000-2001 Chris Hyde <chris@jeks.net>
 * Copyright 2000-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2001 Chris Hyde
 * @copyright 2000-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class contains all functions related to handling messages within IMP.
 * Actions such as moving, copying, and deleting messages are handled in here
 * so that code need not be repeated between mailbox, message, and other
 * pages.
 *
 * @author    Chris Hyde <chris@jeks.net>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2000-2001 Chris Hyde
 * @copyright 2000-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Message
{
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
        return $this->flag(array(
            'remove' => array(Horde_Imap_Client::FLAG_DELETED)
        ), $indices);
    }

    /**
     * Strips one or all MIME parts out of a message.
     * Handles search mailboxes.
     *
     * @param IMP_Indices $indices  An indices object.
     * @param string $partid        The MIME ID of the part to strip. All
     *                              parts are stripped if null.
     *
     * @return IMP_Indices  Returns the new indices object.
     * @throws IMP_Exception
     */
    public function stripPart(IMP_Indices $indices, $partid = null,
                              array $opts = array())
    {
        global $injector;

        list($mbox, $uid) = $indices->getSingle();
        if (!$uid) {
            return;
        }

        if ($mbox->readonly) {
            throw new IMP_Exception(_("Cannot strip the MIME part as the mailbox is read-only."));
        }

        $uidvalidity = $mbox->uidvalid;

        $contents = $injector->getInstance('IMP_Factory_Contents')->create($indices);
        $message = $contents->getMIMEMessage();
        $boundary = trim($message->getContentTypeParameter('boundary'), '"');

        $url = new Horde_Imap_Client_Url();
        $url->mailbox = $mbox;
        $url->uid = $uid;
        $url->uidvalidity = $uidvalidity;

        $imp_imap = $mbox->imp_imap;

        /* Always add the header to output. */
        $url->section = 'HEADER';
        $parts = array(
            array(
                't' => 'url',
                'v' => strval($url)
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
                $newPart->setDisposition('attachment');

                $parts[] = array(
                    't' => 'text',
                    'v' => $newPart->toString(array(
                        'canonical' => true,
                        'headers' => true,
                        'stream' => true
                    ))
                );
            } else {
                $url->section = $id . '.MIME';
                $parts[] = array(
                    't' => 'url',
                    'v' => strval($url)
                );

                $url->section = $id;
                $parts[] = array(
                    't' => 'url',
                    'v' => strval($url)
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

        $indices->delete(array(
            'keeplog' => true,
            'nuke' => true
        ));

        $indices_ob = $mbox->getIndicesOb($new_uid);

        /* We need to replace the old UID(s) in the URL params. */
        $vars = $injector->getInstance('Horde_Variables');
        if (isset($vars->buid)) {
            list(,$vars->buid) = $mbox->toBuids($indices_ob)->getSingle();
        }
        if (isset($vars->uid)) {
            $vars->uid = $new_uid;
        }

        return $indices_ob;
    }

    /**
     * Sets or clears a given flag for a list of messages.
     * Handles search mailboxes.
     * This function works with IMAP only, not POP3.
     *
     * @param array $action         A list of IMAP flag(s). Keys are 'add'
     *                              and/or 'remove'.
     * @param IMP_Indices $indices  An indices object.
     * @param array $opts           Additional options:
     *   - silent: (boolean) Don't output notification messages.
     *   - unchangedsince: (array) The unchangedsince value to pass to the
     *                     IMAP store command. Keys are mailbox names, values
     *                     are the unchangedsince values to use for that
     *                     mailbox.
     *
     * @return boolean  True if successful, false if not.
     */
    public function flag(array $action, IMP_Indices $indices,
                         array $opts = array())
    {
        global $injector, $notification;

        if (!count($indices)) {
            return false;
        }

        $opts = array_merge(array(
            'unchangedsince' => array()
        ), $opts);

        $ajax_queue = $injector->getInstance('IMP_Ajax_Queue');
        $ret = true;

        foreach ($indices as $ob) {
            try {
                if ($ob->mbox->readonly) {
                    throw new IMP_Exception(_("This mailbox is read-only."));
                }

                $ob->mbox->uidvalid;

                $unchangedsince = isset($opts['unchangedsince'][strval($ob->mbox)])
                    ? $opts['unchangedsince'][strval($ob->mbox)]
                    : null;

                /* Flag/unflag the messages now. */
                $imp_imap = $ob->mbox->imp_imap;
                $res = $imp_imap->store($ob->mbox, array_merge($action, array_filter(array(
                    'ids' => $imp_imap->getIdsOb($ob->uids),
                    'unchangedsince' => $unchangedsince
                ))));

                $flag_change = $ob->mbox->getIndicesOb($ob->uids);

                if ($unchangedsince && count($res)) {
                    foreach ($res as $val) {
                        unset($flag_change[$val]);
                    }
                    if (empty($opts['silent'])) {
                        $notification->push(sprintf(_("Flags were not changed for at least one message in the mailbox \"%s\" because the flags were altered by another connection to the mailbox prior to this request. You may redo the flag action if desired; this warning is precautionary to ensure you don't overwrite flag changes."), $ob->mbox->display), 'horde.warning');
                        $ret = false;
                    }
                }

                foreach ($action as $key => $val) {
                    $ajax_queue->flag($val, ($key == 'add'), $flag_change);
                    if ($indices instanceof IMP_Indices_Mailbox) {
                        $ajax_queue->flag($val, ($key == 'add'), $indices->mailbox->toBuids($flag_change));
                    }
                }
            } catch (Exception $e) {
                if (empty($opts['silent'])) {
                    $notification->push(sprintf(_("There was an error flagging messages in the mailbox \"%s\": %s."), $ob->mbox->display, $e->getMessage()), 'horde.error');
                }
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

        $ajax_queue->poll($mboxes);

        foreach (IMP_Mailbox::get($mboxes) as $val) {
            try {
                /* Grab list of UIDs before flagging, to make sure we
                 * determine the exact subset that has been flagged. */
                $mailbox_list = $val->list_ob->getIndicesOb();
                $val->imp_imap->store($val, $action_array);
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

        $process_list = $update_list = array();

        foreach ($mbox_list as $key => $val) {
            $key = IMP_Mailbox::get($key);

            if ($key->access_expunge) {
                $ids = $key->imp_imap->getIdsOb(is_array($val) ? $val : Horde_Imap_Client_Ids::ALL);

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
                $update_list[strval($val[0])] = $val[0]->imp_imap->expunge($val[0], array(
                    'ids' => $val[1],
                    'list' => $msg_list
                ));
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

        $trash = ($prefs->getValue('use_trash'))
            ? IMP_Mailbox::getPref(IMP_Mailbox::MBOX_TRASH)
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
             * delete. */
            try {
                $imp_imap = $mbox->imp_imap;
                $status = $imp_imap->status($mbox, Horde_Imap_Client::STATUS_MESSAGES);
                if (empty($status['messages'])) {
                    $notification->push(sprintf(_("The mailbox %s is already empty."), $mbox->display), 'horde.message');
                    continue;
                }

                if (!$trash || ($trash == $mbox)) {
                    $imp_imap->store($mbox, array(
                        'add' => array(Horde_Imap_Client::FLAG_DELETED)
                    ));
                    $this->expungeMailbox(array(strval($mbox) => 1));
                } else {
                    $ret = $imp_imap->search($mbox);
                    $mbox->getIndicesOb($ret['match'])->delete();
                }

                $notification->push(sprintf(_("Emptied all messages from %s."), $mbox->display), 'horde.success');
            } catch (IMP_Imap_Exception $e) {}
        }
    }

}
