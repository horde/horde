<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Object representing a list of message indices.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Indices implements ArrayAccess, Countable, Iterator
{
    /**
     * The indices list.
     *
     * @var array
     */
    protected $_indices = array();

    /**
     * Constructor.
     *
     * Parameters are the same as add().
     *
     * @see add()
     */
    public function __construct()
    {
        if (func_num_args()) {
            $args = func_get_args();
            call_user_func_array(array($this, 'add'), $args);
        }
    }

    /**
     * Add indices.
     *
     * Input format:
     * <pre>
     * 1 argument:
     * -----------
     * + Array
     *   Either:
     *     KEYS: Mailbox names
     *     VALUES: UIDs -or- Horde_Imap_Client_Ids object
     *  -or-
     *     VALUES: IMAP sequence strings
     * + IMP_Compose object
     * + IMP_Contents object
     * + IMP_Indices object
     * + String
     *   Format: IMAP sequence string
     *
     * 2 arguments:
     * ------------
     * 1st argument: Mailbox name -or- IMP_Mailbox object
     * 2nd argument: Either a single UID, array of UIDs, or a
     *               Horde_Imap_Client_Ids object.
     * </pre>
     */
    public function add()
    {
        $data = func_get_arg(0);
        $indices = array();

        switch (func_num_args()) {
        case 1:
            if (is_array($data)) {
                foreach ($data as $key => $val) {
                    if (is_array($val)) {
                        $indices[$key] = array_keys(array_flip($val));
                    } elseif ($val instanceof Horde_Imap_Client_Ids) {
                        $this->add($key, $val);
                    } else {
                        $this->add($val);
                    }
                }
            } elseif (is_string($data)) {
                $indices = $this->_fromSequenceString($data);
            } elseif ($data instanceof IMP_Compose) {
                $indices = $data->getMetadata('indices')->indices();
            } elseif ($data instanceof IMP_Contents) {
                $indices = array(
                    strval($data->getMailbox()) => array($data->getUid())
                );
            } elseif ($data instanceof IMP_Indices) {
                $indices = $data->indices();
            }
            break;

        case 2:
            $secondarg = func_get_arg(1);
            if (is_array($secondarg)) {
                $secondarg = array_keys(array_flip($secondarg));
            } elseif ($secondarg instanceof Horde_Imap_Client_Ids) {
                $secondarg = $secondarg->ids;
            } else {
                $secondarg = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getIdsOb($secondarg)->ids;
            }

            if (!empty($secondarg)) {
                $indices = array(
                    strval(func_get_arg(0)) => $secondarg
                );
            }
            break;
        }

        if (!empty($indices)) {
            if (empty($this->_indices)) {
                $this->_indices = $indices;
            } else {
                /* Can't use array_merge_recursive() here because keys may
                 * be numeric mailbox names (e.g. 123), and these keys are
                 * treated as numeric (not strings) when merging. */
                foreach (array_keys($indices) as $key) {
                    $this->_indices[$key] = isset($this->_indices[$key])
                        ? array_keys(array_flip(array_merge($this->_indices[$key], $indices[$key])))
                        : $indices[$key];
                }
            }
        }
    }

    /**
     * Returns mailbox/UID information for the first index.
     *
     * @return boolean $all  If true, returns all UIDs for the first index
     *                       in an array. If false, returns the first UID for
     *                       the first index as a string.
     *
     * @return array  A 2-element array with an IMP_Mailbox object and the
     *                UID(s).
     */
    public function getSingle($all = false)
    {
        $val = reset($this->_indices);
        return array(
            IMP_Mailbox::get(key($this->_indices)),
            $all ? $val : (is_array($val) ? reset($val) : null)
        );
    }

    /**
     * Return a copy of the indices array.
     *
     * @return array  The indices array (keys are mailbox names, values are
     *                arrays of UIDs).
     */
    public function indices()
    {
        return $this->_indices;
    }

    /**
     * Returns an array containing compressed UID values.
     *
     * @return array  Keys are base64 encoded mailbox names, values are
     *                sequence strings.
     */
    public function toArray()
    {
        $converted = array();
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        foreach ($this->_indices as $key => $val) {
            $converted[IMP_Mailbox::formTo($key)] = strval($imp_imap->getIdsOb($val));
        }

        return $converted;
    }

    /**
     * Parse an IMAP message sequence string into a list of indices.
     * Extends Horde_Imap_Client_Ids by allowing mailbox information to appear
     * in the string.
     *
     * @param string $str  The IMAP message sequence string.
     *
     * @return array  An array of indices.  If string contains mailbox info,
     *                return value will be an array of arrays, with keys as
     *                mailbox names and values as IDs. Otherwise, return the
     *                list of IDs.
     */
    protected function _fromSequenceString($str)
    {
        $str = trim($str);

        if (!strlen($str)) {
            return array();
        }

        if ($str[0] != '{') {
            return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->getIdsOb($str)->ids;
        }

        $i = strpos($str, '}');
        $count = intval(substr($str, 1, $i - 1));
        $mbox = substr($str, $i + 1, $count);
        $i += $count + 1;
        $end = strpos($str, '{', $i);

        if ($end === false) {
            $ids = array();
            $uidstr = substr($str, $i);
        } else {
            $ids = $this->_fromSequenceString(substr($str, $end));
            $uidstr = substr($str, $i, $end - $i);
        }

        $ids[$mbox] = $this->_fromSequenceString($uidstr);

        return $ids;
    }

    /**
     * Create an IMAP message sequence string from a list of indices.
     * Extends Horde_Imap_Client_Ids by allowing mailbox information to appear
     * in the string.
     *
     * @param array $in  An array of indices.
     *
     * @return string  The message sequence string.
     */
    protected function _toSequenceString($in)
    {
        $imap_ob = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        $str = '';

        foreach ($in as $mbox => $ids) {
            $str .= '{' . strlen($mbox) . '}' . $mbox . $imap_ob->getIdsOb($ids)->tostring_sort;
        }

        return $str;
    }

    /* Mail server modification methods. */

    /**
     * Copies or moves a list of messages to a new mailbox.
     * Also handles moves to the tasklist and/or notepad applications.
     *
     * @param string $targetMbox  The mailbox to move/copy messages to
     *                            (UTF-8).
     * @param string $action      Either 'copy' or 'move'.
     * @param array $opts         Additional options:
     * <pre>
     *   - create: (boolean) Should the target mailbox be created?
     *             DEFAULT: false
     * </pre>
     *
     * @return boolean  True if successful, false if not.
     */
    public function copy($targetMbox, $action, array $opts = array())
    {
        global $notification;

        if (!count($this)) {
            return false;
        }

        $targetMbox = IMP_Mailbox::get($targetMbox);

        /* If the target is a tasklist, handle the move/copy specially. */
        $tasks = new IMP_Indices_Copy_Tasklist();
        if ($tasks->match($targetMbox)) {
            return $tasks->copy($targetMbox, $this, $action == 'copy');
        }

        /* If the target is a notepad, handle the move/copy specially. */
        $note = new IMP_Indices_Copy_Notepad();
        if ($note->match($targetMbox)) {
            return $note->copy($targetMbox, $this, $action == 'copy');
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

        foreach ($this as $ob) {
            try {
                if ($targetMbox->readonly) {
                    throw new IMP_Exception(
                        _("The target directory is read-only.")
                    );
                }

                if (($action == 'move') && $ob->mbox->readonly) {
                    throw new IMP_Exception(
                        _("The source directory is read-only.")
                    );
                }

                /* Throws Exception on error. */
                $ob->mbox->uidvalid;

                /* Attempt to copy/move messages to new mailbox. */
                $imp_imap = $ob->mbox->imp_imap;
                $imp_imap->copy($ob->mbox, $targetMbox, array(
                    'ids' => $imp_imap->getIdsOb($ob->uids),
                    'move' => $imap_move
                ));
            } catch (Exception $e) {
                $error_msg = sprintf(
                    $message,
                    $ob->mbox->display,
                    $targetMbox->display
                ) . ': ' . $e->getMessage();

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
     *
     * @param array $opts  Additional options:
     * <pre>
     *   - keeplog: (boolean) Should any history information of the message be
     *              kept?
     *              DEFAULT: false
     *   - nuke: (boolean) Override user preferences and nuke (i.e.
     *           permanently delete) the messages instead?
     *           DEFAULT: false
     * </pre>
     *
     * @return integer|boolean  The number of messages deleted if successful,
     *                          false if not successful.
     */
    public function delete(array $opts = array())
    {
        global $injector, $notification, $prefs;

        if (!count($this)) {
            return false;
        }

        $trash = IMP_Mailbox::getPref(IMP_Mailbox::MBOX_TRASH);
        $use_trash = $prefs->getValue('use_trash');
        if ($use_trash && !$trash) {
            $notification->push(
                _("Cannot move messages to Trash - no Trash mailbox set in preferences."),
                'horde.error'
            );
            return false;
        }

        $ajax_queue = $injector->getInstance('IMP_Ajax_Queue');
        $maillog = empty($opts['keeplog'])
            ? $injector->getInstance('IMP_Maillog')
            : null;
        $return_value = 0;

        /* Check for Trash mailbox. */
        $no_expunge = $use_trash_mbox = $use_vtrash = false;
        if ($use_trash &&
            empty($opts['nuke']) &&
            $injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_TRASH)) {
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

        foreach ($this as $ob) {
            try {
                if (!$ob->mbox->access_deletemsgs) {
                    throw new IMP_Exception(_("This mailbox is read-only."));
                }

                $ob->mbox->uidvalid;
            } catch (IMP_Exception $e) {
                $notification->push(
                    sprintf(
                        _("There was an error deleting messages from the mailbox \"%s\"."),
                        $ob->mbox->display
                    ) . ' ' . $e->getMessage(),
                    'horde.error'
                );
                $return_value = false;
                continue;
            }

            if ($return_value !== false) {
                $return_value += count($ob->uids);
            }

            $imp_imap = $ob->mbox->imp_imap;
            $ids_ob = $imp_imap->getIdsOb($ob->uids);

            /* Trash is only valid for IMAP mailboxes. */
            if ($use_trash_mbox &&
                ($ob->mbox != $trash) &&
                /* TODO(?): Don't use Trash mailbox for remote accounts. */
                !$ob->mbox->remote_mbox) {
                if ($ob->mbox->access_expunge) {
                    try {
                        if ($mark_seen) {
                            $imp_imap->store($ob->mbox, array(
                                'add' => array(Horde_Imap_Client::FLAG_SEEN),
                                'ids' => $ids_ob
                            ));
                        }

                        $imp_imap->copy($ob->mbox, $trash, array(
                            'ids' => $ids_ob,
                            'move' => true
                        ));
                    } catch (IMP_Imap_Exception $e) {
                        if ($e->getCode() == $e::OVERQUOTA) {
                            $notification->push(
                                _("You are over your quota, so your messages will be permanently deleted instead of moved to the Trash mailbox."),
                                'horde.warning'
                            );

                            $idx = new IMP_Indices($ob->mbox, $ob->uids);
                            return $idx->delete(array(
                                'keeplog' => !empty($opts['keeplog']),
                                'nuke' => true
                            ));
                        }

                        return false;
                    }
                }
            } else {
                /* Delete message logs now. This may result in loss of message
                 * log data for messages that might not be deleted - i.e. if
                 * an error occurs. But 1) the user has already indicated they
                 * don't care about this data and 2) message IDs (used by some
                 * maillog backends) won't be available after deletion. */
                if ($maillog) {
                    $delete_ids = array();
                    foreach ($ids_ob as $val) {
                        $delete_ids[] = new IMP_Maillog_Message(
                            new IMP_Indices($ob->mbox, $val)
                        );
                    }
                    $maillog->deleteLog($delete_ids);
                }

                /* Delete the messages. */
                $expunge_now = false;
                $del_flags = array(Horde_Imap_Client::FLAG_DELETED);

                if (!$use_vtrash &&
                    (!$imp_imap->access(IMP_Imap::ACCESS_TRASH) ||
                     !empty($opts['nuke']) ||
                     ($use_trash &&
                      ($ob->mbox == $trash) || $ob->mbox->remote_mbox))) {
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
                        $ob->mbox->expunge($ids_ob);
                    } else {
                        $ajax_queue->flag(
                            $del_flags,
                            true,
                            new IMP_Indices($ob->mbox, $ids_ob)
                        );
                    }
                } catch (IMP_Imap_Exception $e) {}
            }
        }

        return $return_value;
    }

    /**
     * Strips one or all MIME parts out of a message.
     *
     * @param string $partid  The MIME ID of the part to strip. All parts are
     *                        stripped if null.
     *
     * @return IMP_Indices  Returns the new indices object.
     * @throws IMP_Exception
     */
    public function stripPart($partid = null)
    {
        global $injector;

        list($mbox, $uid) = $this->getSingle();
        if (!$uid) {
            return;
        }

        if ($mbox->readonly) {
            throw new IMP_Exception(
                _("Cannot strip the part as the mailbox is read-only.")
            );
        }

        $uidvalidity = $mbox->uidvalid;

        $contents = $injector->getInstance('IMP_Factory_Contents')->create($this);
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
            if (!($part = $message[$id])) {
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
                $newPart->setContents(sprintf(
                    _("[Part stripped: Original part type: %s, name: %s]"),
                    $part->getType(),
                    $contents->getPartName($part)
                ));
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
            throw new IMP_Exception(
                _("An error occured while attempting to strip the part.")
            );
        }

        $this->delete(array(
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
     *
     * @param array $add     A list of IMAP flag(s) to add.
     * @param array $remove  A list of IMAP flag(s) to remove.
     * @param array $opts    Additional options:
     * <pre>
     *   - silent: (boolean) Don't output notification messages.
     *             DEFAULT: false
     *   - unchangedsince: (array) The unchangedsince value to pass to the
     *                     IMAP store command. Keys are mailbox names, values
     *                     are the unchangedsince values to use for that
     *                     mailbox.
     *                     DEFAULT: array()
     * </pre>
     *
     * @return boolean  True if successful, false if not.
     */
    public function flag(array $add = array(), array $remove = array(),
                         array $opts = array())
    {
        global $injector, $notification;

        if (!count($this)) {
            return false;
        }

        $opts = array_merge(array(
            'unchangedsince' => array()
        ), $opts);

        $ajax_queue = $injector->getInstance('IMP_Ajax_Queue');
        $ret = true;

        foreach ($this as $ob) {
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
                $res = $imp_imap->store($ob->mbox, array_filter(array(
                    'add' => $add,
                    'ids' => $imp_imap->getIdsOb($ob->uids),
                    'remove' => $remove,
                    'unchangedsince' => $unchangedsince
                )));

                $flag_change = $ob->mbox->getIndicesOb($ob->uids);

                if ($unchangedsince && count($res)) {
                    foreach ($res as $val) {
                        unset($flag_change[$val]);
                    }
                    if (empty($opts['silent'])) {
                        $notification->push(
                            sprintf(
                                _("Flags were not changed for at least one message in the mailbox \"%s\" because the flags were altered by another connection to the mailbox prior to this request. You may redo the flag action if desired; this warning is precautionary to ensure you don't overwrite flag changes."),
                                $ob->mbox->display
                            ),
                            'horde.warning'
                        );
                        $ret = false;
                    }
                }

                foreach (array('add' => $add, 'remove' => $remove) as $key => $val) {
                    if (!empty($val)) {
                        $ajax_queue->flag($val, ($key == 'add'), $flag_change);
                        if ($this instanceof IMP_Indices_Mailbox) {
                            $ajax_queue->flag(
                                $val,
                                ($key == 'add'),
                                $this->mailbox->toBuids($flag_change)
                            );
                        }
                    }
                }
            } catch (Exception $e) {
                if (empty($opts['silent'])) {
                    $notification->push(
                        sprintf(
                            _("There was an error flagging messages in the mailbox \"%s\": %s."),
                            $ob->mbox->display, $e->getMessage()
                        ),
                        'horde.error'
                    );
                }
                $ret = false;
            }
        }

        return $ret;
    }

    /**
     * Check if we need to send a MDN, and send if needed.
     *
     * @param Horde_Mime_Headers $headers  The headers of the message.
     * @param boolean $confirmed           Has the MDN request been confirmed?
     *
     * @return boolean  True if the MDN request needs to be confirmed.
     */
    public function mdnCheck($headers, $confirmed = false)
    {
        global $conf, $injector, $prefs;

        if (!($pref_val = $prefs->getValue('send_mdn'))) {
            return false;
        }

        /* Check to see if an MDN has been requested. */
        $mdn = new Horde_Mime_Mdn($headers);
        if (!($return_addr = $mdn->getMdnReturnAddr())) {
            return false;
        }

        /* Check to see if MDN information can be stored. */
        $log_msg = new IMP_Maillog_Message($this);
        $log_ob = new IMP_Maillog_Log_Mdn();
        $maillog = $injector->getInstance('IMP_Maillog');

        if (!$maillog->storage->isAvailable($log_msg, $log_ob) ||
            count($maillog->getLog($log_msg, array('IMP_Maillog_Log_Mdn')))) {
            return false;
        }

        /* See if we need to query the user. */
        if (!$confirmed &&
            ((intval($pref_val) == 1) ||
             $mdn->userConfirmationNeeded())) {
            try {
                if ($injector->getInstance('Horde_Core_Hooks')->callHook('mdn_check', 'imp', array($headers))) {
                    return true;
                }
            } catch (Horde_Exception_HookNotSet $e) {
                return true;
            }
        }

        /* Send out the MDN now. */
        $success = false;
        try {
            $mdn->generate(
                false,
                $confirmed,
                'displayed',
                $conf['server']['name'],
                $injector->getInstance('IMP_Mail'),
                array(
                    'charset' => 'UTF-8',
                    'from_addr' => $injector->getInstance('Horde_Core_Factory_Identity')->create()->getDefaultFromAddress()
                )
            );

            $maillog->log($log_msg, $log_ob);

            $success = true;
        } catch (Exception $e) {}

        $injector->getInstance('IMP_Sentmail')->log(
            IMP_Sentmail::MDN,
            '',
            $return_addr,
            $success
        );

        return false;
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        return isset($this->_indices[$offset]);
    }

    /**
     */
    public function offsetGet($offset)
    {
        return isset($this->_indices[$offset])
            ? $this->_indices[$offset]
            : null;
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        unset($this->_indices[$offset]);
        $this->add($offset, $value);
    }

    /**
     */
    public function offsetUnset($offset)
    {
        unset($this->_indices[$offset]);
    }

    /* Countable methods. */

    /**
     * Index count.
     *
     * @return integer  The number of indices.
     */
    public function count()
    {
        $count = 0;

        foreach (array_keys($this->_indices) as $key) {
            $count += count($this->_indices[$key]);
        }

        return $count;
    }

    /* Magic methods. */

    /**
     * String representation of the object.
     *
     * @return string  String representation (IMAP sequence string).
     */
    public function __toString()
    {
        return $this->_toSequenceString($this->_indices);
    }

    /* Iterator methods. */

    public function current()
    {
        if (!$this->valid()) {
            return null;
        }

        $ret = new stdClass;
        $ret->mbox = IMP_Mailbox::get($this->key());
        $ret->uids = current($this->_indices);

        return $ret;
    }

    public function key()
    {
        return key($this->_indices);
    }

    public function next()
    {
        if ($this->valid()) {
            next($this->_indices);
        }
    }

    public function rewind()
    {
        reset($this->_indices);
    }

    public function valid()
    {
        return !is_null(key($this->_indices));
    }

}
