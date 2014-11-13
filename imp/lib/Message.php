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

}
