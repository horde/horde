<?php
/**
 * Mnemo external API interface.
 *
 * This file defines Mnemo's external API interface.  Other applications can
 * interact with Mnemo through this API.
 *
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
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
        // Get the share object for later deletion
        try {
            $share = $GLOBALS['mnemo_shares']->getShare($user);
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        $GLOBALS['display_notepads'] = array($user);
        $memos = Mnemo::listMemos();
        $uids = array();
        foreach ($memos as $memo) {
            $uids[] = $memo['uid'];
        }

        // ... and delete them.
        foreach ($uids as $uid) {
            $this->delete($uid);
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

        // Get a list of all shares this user has perms to and remove the perms.
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
            throw new Horde_Exception_PermissionDenied(_("Permission Denied"));
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

        if (!array_key_exists($notepad, Mnemo::listNotepads(false, Horde_Perms::READ))) {
           throw new Horde_Exception_PermissionDenied(_("Permission Denied"));
        }

        $filter = array(array('op' => '=', 'field' => 'action', 'value' => $action));
        if (!empty($end)) {
            $filter[] = array('op' => '<', 'field' => 'ts', 'value' => $end);
        }
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $histories = $history->getByTimestamp('>', $timestamp, $filter, 'mnemo:' . $notepad);

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
     * @throws Horde_Exception_PermissionDenied
     */
    public function getActionTimestamp($uid, $action, $notepad = null)
    {
        /* Make sure we have a valid notepad. */
        if (empty($notepad)) {
            $notepad = Mnemo::getDefaultNotepad();
        }

        if (!array_key_exists($notepad, Mnemo::listNotepads(false, Horde_Perms::READ))) {
            throw new Horde_Exception_PermissionDenied(_("Permission Denied"));
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
            throw new Horde_Exception_PermissionDenied(_("Permission Denied"));
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
                            $noteId = $storage->add($note['desc'],
                                                    $note['body'],
                                                    !empty($note['category']) ? $note['category'] : '');
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
     *                             </pre>
     *
     * @return string  The requested data
     * @throws Mnemo_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    public function export($uid, $contentType)
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
            throw new Horde_Exception_PermissionDenied(_("Permission Denied"));
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

            throw new Horde_Exception_PermissionDenied(_("Permission Denied"));
        }

        $storage->delete($memo['memo_id']);
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
     * @throws Mnemo_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    public function replace($uid, $content, $contentType)
    {
        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create();
        $memo = $storage->getByUID($uid);
        if (!array_key_exists($memo['memolist_id'], Mnemo::listNotepads(false, Horde_Perms::EDIT))) {
            throw new Horde_Exception_PermissionDenied(_("Permission Denied"));
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
        default:
            throw new Mnemo_Exception(sprintf(_("Unsupported Content-Type: %s"),$contentType));
        }
    }

}
