<?php
/**
 * Mnemo external API interface.
 *
 * This file defines Mnemo's external API interface.  Other applications can
 * interact with Mnemo through this API.
 *
 * Copyright 2001-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Horde
 * @package  Mnemo
 */

class Mnemo_Api extends Horde_Registry_Api
{
    /**
     * Removes user data.
     *
     * @deprecated  Use Horde's removeUserData API call instead.
     *
     * @param string $user  Name of user to remove data for.
     *
     * @throws Mnemo_Exception
     */
    public function removeUserData($user)
    {
        try {
            $GLOBALS['registry']->removeUserData($user, 'mnemo');
        } catch (Horde_Exception $e) {
            throw new Mnemo_Exception($e);
        }
    }

    /**
     * @param boolean $owneronly   Only return notepads that this user owns?
     *                             Defaults to false.
     * @param integer $permission  The permission to filter notepads by.
     *
     * @return array  The notepads.
     */
    public function listNotepads($owneronly, $permission)
    {
        return Mnemo::listNotepads($owneronly, $permission);
    }

    /**
     * Returns an array of UIDs for all notes that the current user is authorized
     * to see.
     *
     * @param string $notepad  The notepad to list notes from.
     *
     * @return array  An array of UIDs for all notes the user can access.
     * @throws Mnemo_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    public function listUids($notepad = null)
    {
        global $conf;

        if (!isset($conf['storage']['driver'])) {
            throw new Mnemo_Exception('Not configured');
        }

        // Make sure we have a valid notepad.
        if (empty($notepad)) {
            $notepad = Mnemo::getDefaultNotepad();
        }

        if (!array_key_exists($notepad, Mnemo::listNotepads(false, Horde_Perms::READ))) {
            throw new Horde_Exception_PermissionDenied();
        }

        // Set notepad for listMemos.
        $GLOBALS['display_notepads'] = array($notepad);
        $memos = Mnemo::listMemos();
        $uids = array();
        foreach ($memos as $memo) {
            $uids[] = $memo['uid'];
        }

        return $uids;
    }

    /**
     * Method for obtaining all server changes between two timestamps. Basically
     * a wrapper around listBy(), but returns an array containing all adds,
     * edits and deletions.
     *
     * @param integer $start             The starting timestamp
     * @param integer $end               The ending timestamp.
     * @param boolean $isModSeq          If true, $timestamp and $end are
     *                                   modification sequences and not
     *                                   timestamps. @since 4.1.1
     *
     * @return array  An hash with 'add', 'modify' and 'delete' arrays.
     * @since 3.0.5
     */
    public function getChanges($start, $end, $isModSeq = false)
    {
        return array('add' => $this->listBy('add', $start, null, $end, $isModSeq),
                     'modify' => $this->listBy('modify', $start, null, $end, $isModSeq),
                     'delete' => $this->listBy('delete', $start, null, $end, $isModSeq));
    }

    /**
     * Return all changes occuring between the specified modification
     * sequences.
     *
     * @param integer $start  The starting modseq.
     * @param integer $end    The ending modseq.
     *
     * @return array  The changes @see getChanges()
     * @since 4.1.1
     */
    public function getChangesByModSeq($start, $end)
    {
        return $this->getChanges($start, $end, true);
    }

    /**
     * Returns an array of UIDs for notes that have had $action happen since
     * $timestamp.
     *
     * @param string  $action     The action to check for - add, modify, or delete.
     * @param integer $timestamp  The time to start the search.
     * @param string  $notepad    The notepad to search in.
     * @param integer $end        The optional ending timestamp.
     * @param boolean $isModSeq   If true, $timestamp and $end are modification
     *                            sequences and not timestamps. @since 4.1.1
     *
     * @return array  An array of UIDs matching the action and time criteria.
     */
    public function listBy($action, $timestamp, $notepad = null, $end = null, $isModSeq = false)
    {
        /* Make sure we have a valid notepad. */
        if (empty($notepad)) {
            $notepad = Mnemo::getDefaultNotepad();
        }

        if (!array_key_exists($notepad, Mnemo::listNotepads(false, Horde_Perms::READ))) {
           throw new Horde_Exception_PermissionDenied();
        }

        $filter = array(array('op' => '=', 'field' => 'action', 'value' => $action));
        if (!empty($end) && !$isModSeq) {
            $filter[] = array('op' => '<', 'field' => 'ts', 'value' => $end);
        }
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        if (!$isModSeq) {
            $histories = $history->getByTimestamp('>', $timestamp, $filter, 'mnemo:' . $notepad);
        } else {
            $histories = $history->getByModSeq($timestamp, $end, $filter, 'mnemo:' . $notepad);
        }

        // Strip leading mnemo:username:.
        return preg_replace('/^([^:]*:){2}/', '', array_keys($histories));
    }

    /**
     * Returns the timestamp of an operation for a given uid an action.
     *
     * @param string $uid      The uid to look for.
     * @param string $action   The action to check for - add, modify, or delete.
     * @param string $notepad  The notepad to search in.
     * @param boolean $modSeq  Request a modification sequence instead of a
     *                         timestamp. @since 4.1.1
     *
     * @return integer  The timestamp or modseq for this action.
     * @throws Horde_Exception_PermissionDenied
     */
    public function getActionTimestamp($uid, $action, $notepad = null, $modSeq = false)
    {
        /* Make sure we have a valid notepad. */
        if (empty($notepad)) {
            $notepad = Mnemo::getDefaultNotepad();
        }

        if (!array_key_exists($notepad, Mnemo::listNotepads(false, Horde_Perms::READ))) {
            throw new Horde_Exception_PermissionDenied();
        }

        $history = $GLOBALS['injector']->getInstance('Horde_History');
        if (!$modSeq) {
            return $history->getActionTimestamp('mnemo:' . $notepad . ':' . $uid, $action);
        } else {
            return $history->getActionModSeq('mnemo:' . $notepad . ':' . $uid, $action);
        }
    }

    /**
     * Return the largest modification sequence from the history backend.
     *
     * @return integer  The modseq.
     * @since 4.1.1
     */
    public function getHighestModSeq()
    {
        return $GLOBALS['injector']->getInstance('Horde_History')->getHighestModSeq('mnemo');
    }

    /**
     * Import a memo represented in the specified contentType.
     *
     * @param string $content      The content of the memo.
     * @param string $contentType  What format is the data in? Currently supports:
     *                             text/plain
     *                             text/x-vnote
     *                             activesync
     * @param string $notepad      (optional) The notepad to save the memo on.
     *
     * @return string  The new UID, or false on failure.
     * @throws Mnemo_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    public function import($content, $contentType, $notepad = null)
    {
        global $prefs;

        /* Make sure we have a valid notepad and permissions to edit
         * it. */
        if (empty($notepad)) {
            $notepad = Mnemo::getDefaultNotepad(Horde_Perms::EDIT);
        }

        if (!array_key_exists($notepad, Mnemo::listNotepads(false, Horde_Perms::EDIT))) {
            throw new Horde_Exception_PermissionDenied();
        }

        /* Create a Mnemo_Driver instance. */
        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create($notepad);

        switch ($contentType) {
        case 'text/plain':
            $noteId = $storage->add($storage->getMemoDescription($content), $content);
            break;

        case 'text/x-vnote':
            if (!($content instanceof Horde_Icalendar_Vnote)) {
                $iCal = new Horde_Icalendar();
                if (!$iCal->parsevCalendar($content)) {
                    throw new Mnemo_Exception(_("There was an error importing the iCalendar data."));
                }

                $components = $iCal->getComponents();
                switch (count($components)) {
                case 0:
                    throw new Mnemo_Exception(_("No iCalendar data was found."));

                case 1:
                    $content = $components[0];
                    break;

                default:
                    $ids = array();
                    foreach ($components as $content) {
                        if ($content instanceof Horde_Icalendar_Vnote) {
                            $note = $storage->fromiCalendar($content);
                            $noteId = $storage->add(
                                $note['desc'], $note['body'],
                                !empty($note['category']) ? $note['category'] : '');
                            $ids[] = $noteId;
                        }
                    }
                    return $ids;
                }
            }

            $note = $storage->fromiCalendar($content);
            $noteId = $storage->add(
                $note['desc'], $note['body'],
                !empty($note['category']) ? $note['category'] : '');
            break;

        case 'activesync':
            $category = is_array($content->categories) ? current($content->categories) : '';
            $noteId = $storage->add($content->subject, $content->body->data, $category);
            break;

        default:
            throw new Mnemo_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
        }
        $note = $storage->get($noteId);

        return $note['uid'];
    }

    /**
     * Export a memo, identified by UID, in the requested contentType.
     *
     * @param string $uid          Identify the memo to export.
     * @param string $contentType  What format should the data be in?
     *                             A string with one of:
     *                             <pre>
     *                               'text/plain'
     *                               'text/x-vnote'
     *                               'activesync'
     *                             </pre>
     * @param array $options       Any additional options to be passed to the
     *                             exporter.
     *
     * @return string  The requested data
     * @throws Mnemo_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    public function export($uid, $contentType, array $options = array())
    {
        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create();
        try {
            $memo = $storage->getByUID($uid);
        } catch (Mnemo_Exception $e) {
            if ($e->getCode() == Mnemo::ERR_NO_PASSPHRASE ||
                $e->getCode() == Mnemo::ERR_DECRYPT) {

                $memo['body'] = _("This note has been encrypted.");
            } else {
                throw $e;
            }
        }
        if (!array_key_exists($memo['memolist_id'], Mnemo::listNotepads(false, Horde_Perms::READ))) {
            throw new Horde_Exception_PermissionDenied();
        }

        switch ($contentType) {
        case 'text/plain':
            return $memo['body'];

        case 'text/x-vnote':
            // Create the new iCalendar container.
            $iCal = new Horde_Icalendar('1.1');
            $iCal->setAttribute('VERSION', '1.1');
            $iCal->setAttribute('PRODID', '-//The Horde Project//Mnemo ' . $registry->getVersion() . '//EN');
            $iCal->setAttribute('METHOD', 'PUBLISH');

            // Create a new vNote.
            $vNote = $storage->toiCalendar($memo, $iCal);
            return $vNote->exportvCalendar();

        case 'activesync':
            return $storage->toASNote($memo, $options);
        }

        throw new Mnemo_Exception(sprintf(_("Unsupported Content-Type: %s"),$contentType));
    }

    /**
     * Delete a memo identified by UID.
     *
     * @param string | array $uid  Identify the note to delete, either a
     *                             single UID or an array.
     * @throws Horde_Exception_PermissionDenied
     */
    public function delete($uid)
    {
        // Handle an arrray of UIDs for convenience of deleting multiple
        // notes at once.
        if (is_array($uid)) {
            foreach ($uid as $u) {
                $result = $this->delete($u);
            }
        }


        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create();
        $memo = $storage->getByUID($uid);
        if (!$GLOBALS['registry']->isAdmin() &&
            !array_key_exists($memo['memolist_id'], Mnemo::listNotepads(false, Horde_Perms::DELETE))) {

            throw new Horde_Exception_PermissionDenied();
        }

        $storage->delete($memo['memo_id']);
    }

    /**
     * Replace the memo identified by UID with the content represented in
     * the specified contentType.
     *
     * @param string $uid          Idenfity the memo to replace.
     * @param string $content      The content of the memo.
     * @param string $contentType  What format is the data in? Currently supports:
     *                             text/plain
     *                             text/x-vnote
     *                             activesync
     * @throws Mnemo_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    public function replace($uid, $content, $contentType)
    {
        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create();
        $memo = $storage->getByUID($uid);
        if (!array_key_exists($memo['memolist_id'], Mnemo::listNotepads(false, Horde_Perms::EDIT))) {
            throw new Horde_Exception_PermissionDenied();
        }

        switch ($contentType) {
        case 'text/plain':
            $storage->modify($memo['memo_id'], $storage->getMemoDescription($content), $content, null);
            break;
        case 'text/x-vnote':
            if (!($content instanceof Horde_Icalendar_Vnote)) {
                $iCal = new Horde_Icalendar();
                if (!$iCal->parsevCalendar($content)) {
                    throw new Mnemo_Exception(_("There was an error importing the iCalendar data."));
                }

                $components = $iCal->getComponents();
                switch (count($components)) {
                case 0:
                    throw new Mnemo_Exception(_("No iCalendar data was found."));

                case 1:
                    $content = $components[0];
                    break;

                default:
                    throw new Mnemo_Exception(_("Multiple iCalendar components found; only one vNote is supported."));
                }
            }
            $note = $storage->fromiCalendar($content);
            $storage->modify($memo['memo_id'],
                             $note['desc'],
                             $note['body'],
                             !empty($note['category']) ? $note['category'] : '');
            break;

        case 'activesync':
            $category = is_array($content->categories) ? current($content->categories) : '';
            $storage->modify($memo['memo_id'], $content->subject, $content->body->data, $category);
            break;

        default:
            throw new Mnemo_Exception(sprintf(_("Unsupported Content-Type: %s"),$contentType));
        }
    }

}
