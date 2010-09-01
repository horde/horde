<?php
/**
 * Mnemo external API interface.
 *
 * This file defines Mnemo's external API interface.  Other applications can
 * interact with Mnemo through this API.
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @category Horde
 * @package  Mnemo
 */

class Mnemo_Api extends Horde_Registry_Api
{
    /**
     * Removes user data.
     *
     * @param string $user  Name of user to remove data for.
     *
     * @throws Mnemo_Exception
     */
    public function removeUserData($user)
    {
        /* Get the share object for later deletion */
        try {
            $share = $GLOBALS['mnemo_shares']->getShare($user);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        $GLOBALS['display_notepads'] = array($user);
        $memos = Mnemo::listMemos();
        if ($memos instanceof PEAR_Error) {
            Horde::logMessage($mnemos, 'ERR');
            throw new Mnemo_Exception(sprintf(_("There was an error removing notes for %s. Details have been logged."), $user));
        } else {
            $uids = array();
            foreach ($memos as $memo) {
                $uids[] = $memo['uid'];
            }

            /* ... and delete them. */
            foreach ($uids as $uid) {
                _mnemo_delete($uid);
            }
        }

        /* Remove the share itself */
        if (!empty($share)) {
            try {
                $GLOBALS['mnemo_shares']->removeShare($share);
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e, 'ERR');
                throw new Mnemo_Exception(sprintf(_("There was an error removing notes for %s. Details have been logged."), $user));
            }
        }

        /* Get a list of all shares this user has perms to and remove the
         * perms. */
        try {
            $shares = $GLOBALS['mnemo_shares']->listShares($user);
            foreach ($shares as $share) {
                $share->removeUser($user);
            }
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Mnemo_Exception(sprintf(_("There was an error removing notes for %s. Details have been logged."), $user));
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
     */
    public function listNotes($notepad = null)
    {
        global $conf;

        if (!isset($conf['storage']['driver'])) {
            return PEAR::raiseError('Not configured');
        }

        /* Make sure we have a valid notepad. */
        if (empty($notepad)) {
            $notepad = Mnemo::getDefaultNotepad();
        }

        if (!array_key_exists($notepad,
                              Mnemo::listNotepads(false, Horde_Perms::READ))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        /* Set notepad for listMemos. */
        $GLOBALS['display_notepads'] = array($notepad);

        $memos = Mnemo::listMemos();
        if (is_a($memos, 'PEAR_Error')) {
            return $memos;
        }

        $uids = array();
        foreach ($memos as $memo) {
            $uids[] = $memo['uid'];
        }

        return $uids;
    }

    /**
     * Returns an array of UIDs for notes that have had $action happen since
     * $timestamp.
     *
     * @param string  $action     The action to check for - add, modify, or delete.
     * @param integer $timestamp  The time to start the search.
     * @param string  $notepad    The notepad to search in.
     * @param integer $end        The optional ending timestamp.
     *
     * @return array  An array of UIDs matching the action and time criteria.
     */
    public function listBy($action, $timestamp, $notepad = null, $end = null)
    {
        /* Make sure we have a valid notepad. */
        if (empty($notepad)) {
            $notepad = Mnemo::getDefaultNotepad();
        }

        if (!array_key_exists($notepad,
                              Mnemo::listNotepads(false, Horde_Perms::READ))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        $filter = array(array('op' => '=', 'field' => 'action', 'value' => $action));
        if (!empty($end)) {
            $filter[] = array('op' => '<', 'field' => 'ts', 'value' => $end);
        }
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $histories = $history->getByTimestamp('>', $timestamp, $filter, 'mnemo:' . $notepad);
        if (is_a($histories, 'PEAR_Error')) {
            return $histories;
        }

        // Strip leading mnemo:username:.
        return preg_replace('/^([^:]*:){2}/', '', array_keys($histories));
    }

    /**
     * Returns the timestamp of an operation for a given uid an action.
     *
     * @param string $uid     The uid to look for.
     * @param string $action  The action to check for - add, modify, or delete.
     * @param string $notepad The notepad to search in.
     *
     * @return integer  The timestamp for this action.
     */
    public function getActionTimestamp($uid, $action, $notepad = null)
    {
        /* Make sure we have a valid notepad. */
        if (empty($notepad)) {
            $notepad = Mnemo::getDefaultNotepad();
        }

        if (!array_key_exists($notepad,
                              Mnemo::listNotepads(false, Horde_Perms::READ))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        $history = $GLOBALS['injector']->getInstance('Horde_History');
        return $history->getActionTimestamp('mnemo:' . $notepad . ':' . $uid, $action);
    }

    /**
     * Import a memo represented in the specified contentType.
     *
     * @param string $content      The content of the memo.
     * @param string $contentType  What format is the data in? Currently supports:
     *                             text/plain
     *                             text/x-vnote
     * @param string $notepad      (optional) The notepad to save the memo on.
     *
     * @return string  The new UID, or false on failure.
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
            return PEAR::raiseError(_("Permission Denied"));
        }

        /* Create a Mnemo_Driver instance. */
        $storage = Mnemo_Driver::singleton($notepad);

        switch ($contentType) {
        case 'text/plain':
            $noteId = $storage->add($storage->getMemoDescription($content), $content);
            break;

        case 'text/x-vnote':
            if (!is_a($content, 'Horde_iCalendar_vnote')) {
                require_once 'Horde/iCalendar.php';
                $iCal = new Horde_iCalendar();
                if (!$iCal->parsevCalendar($content)) {
                    return PEAR::raiseError(_("There was an error importing the iCalendar data."));
                }

                $components = $iCal->getComponents();
                switch (count($components)) {
                case 0:
                    return PEAR::raiseError(_("No iCalendar data was found."));

                case 1:
                    $content = $components[0];
                    break;

                default:
                    $ids = array();
                    foreach ($components as $content) {
                        if (is_a($content, 'Horde_iCalendar_vnote')) {
                            $note = $storage->fromiCalendar($content);
                            $noteId = $storage->add($note['desc'],
                                                    $note['body'],
                                                    !empty($note['category']) ? $note['category'] : '');
                            if (is_a($noteId, 'PEAR_Error')) {
                                return $noteId;
                            }
                            $ids[] = $noteId;
                        }
                    }
                    return $ids;
                }
            }

            $note = $storage->fromiCalendar($content);
            $noteId = $storage->add($note['desc'],
                                    $note['body'], !empty($note['category']) ? $note['category'] : '');
            break;

        default:
            return PEAR::raiseError(sprintf(_("Unsupported Content-Type: %s"),$contentType));
        }

        if (is_a($noteId, 'PEAR_Error')) {
            return $noteId;
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
     *                             </pre>
     *
     * @return string  The requested data or PEAR_Error.
     */
    public function export($uid, $contentType)
    {
        $storage = Mnemo_Driver::singleton();
        $memo = $storage->getByUID($uid);
        if (is_a($memo, 'PEAR_Error')) {
            return $memo;
        }

        if (!array_key_exists($memo['memolist_id'], Mnemo::listNotepads(false, Horde_Perms::READ))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        if (is_a($memo['body'], 'PEAR_Error')) {
            if ($memo['body']->getCode() == Mnemo::ERR_NO_PASSPHRASE ||
                $memo['body']->getCode() == Mnemo::ERR_DECRYPT) {
                $memo['body'] = _("This note has been encrypted.");
            } else {
                return $memo['body'];
            }
        }

        switch ($contentType) {
        case 'text/plain':
            return $memo['body'];

        case 'text/x-vnote':
            require_once dirname(__FILE__) . '/version.php';
            require_once 'Horde/iCalendar.php';

            // Create the new iCalendar container.
            $iCal = new Horde_iCalendar('1.1');
            $iCal->setAttribute('VERSION', '1.1');
            $iCal->setAttribute('PRODID', '-//The Horde Project//Mnemo ' . Mnemo::VERSION . '//EN');
            $iCal->setAttribute('METHOD', 'PUBLISH');

            // Create a new vNote.
            $vNote = $storage->toiCalendar($memo, $iCal);
            return $vNote->exportvCalendar();
        }

        return PEAR::raiseError(sprintf(_("Unsupported Content-Type: %s"),$contentType));
    }

    /**
     * Delete a memo identified by UID.
     *
     * @param string | array $uid  Identify the note to delete, either a
     *                             single UID or an array.
     *
     * @return boolean  Success or failure.
     */
    public function delete($uid)
    {
        // Handle an arrray of UIDs for convenience of deleting multiple
        // notes at once.
        if (is_array($uid)) {
            foreach ($uid as $u) {
                $result = _mnemo_delete($u);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
            }

            return true;
        }


        $storage = Mnemo_Driver::singleton();
        $memo = $storage->getByUID($uid);
        if (is_a($memo, 'PEAR_Error')) {
            return $memo;
        }

        if (!$GLOBALS['registry']->isAdmin() &&
            !array_key_exists($memo['memolist_id'],
                              Mnemo::listNotepads(false, Horde_Perms::DELETE))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        return $storage->delete($memo['memo_id']);
    }

    /**
     * Replace the memo identified by UID with the content represented in
     * the specified contentType.
     *
     * @param string $uid         Idenfity the memo to replace.
     * @param string $content      The content of the memo.
     * @param string $contentType  What format is the data in? Currently supports:
     *                             text/plain
     *                             text/x-vnote
     *
     * @return boolean  Success or failure.
     */
    public function replace($uid, $content, $contentType)
    {
        $storage = Mnemo_Driver::singleton();
        $memo = $storage->getByUID($uid);
        if (is_a($memo, 'PEAR_Error')) {
            return $memo;
        }

        if (!array_key_exists($memo['memolist_id'], Mnemo::listNotepads(false, Horde_Perms::EDIT))) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        switch ($contentType) {
        case 'text/plain':
            return $storage->modify($memo['memo_id'], $storage->getMemoDescription($content), $content, null);

        case 'text/x-vnote':
            if (!is_a($content, 'Horde_iCalendar_vnote')) {
                require_once 'Horde/iCalendar.php';
                $iCal = new Horde_iCalendar();
                if (!$iCal->parsevCalendar($content)) {
                    return PEAR::raiseError(_("There was an error importing the iCalendar data."));
                }

                $components = $iCal->getComponents();
                switch (count($components)) {
                case 0:
                    return PEAR::raiseError(_("No iCalendar data was found."));

                case 1:
                    $content = $components[0];
                    break;

                default:
                    return PEAR::raiseError(_("Multiple iCalendar components found; only one vNote is supported."));
                }
            }
            $note = $storage->fromiCalendar($content);

            return $storage->modify($memo['memo_id'], $note['desc'],
                                    $note['body'],!empty($note['category']) ? $note['category'] : '');

        default:
            return PEAR::raiseError(sprintf(_("Unsupported Content-Type: %s"),$contentType));
        }
    }
}
