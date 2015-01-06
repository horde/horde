<?php
/**
 * Mnemo external API interface.
 *
 * This file defines Mnemo's external API interface.  Other applications can
 * interact with Mnemo through this API.
 *
 * Copyright 2001-2015 Horde LLC (http://www.horde.org/)
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
     * Links.
     *
     * @var array
     */
    protected $_links = array(
        'show' => '%application%/view.php?memo=|memo|&memolist=|memolist|&uid=|uid|'
    );

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
     * @param array|string $notepads  The notepad(s) to list notes from.
     *
     * @return array  An array of UIDs for all notes the user can access.
     * @throws Mnemo_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    public function listUids($notepads = null)
    {
        global $conf;

        if (!isset($conf['storage']['driver'])) {
            throw new RuntimeException('Not configured');
        }

        // Make sure we have a valid notepad.
        if (empty($notepads)) {
            $notepads = Mnemo::getSyncNotepads();
        } else {
            if (!is_array($notepads)) {
                $notepads = array($notepads);
            }
            foreach ($notepads as $notepad) {
                if (!Mnemo::hasPermission($notepad, Horde_Perms::READ)) {
                    throw new Horde_Exception_PermissionDenied();
                }
            }
        }

        // Set notepad for listMemos.
        $GLOBALS['display_notepads'] = $notepads;

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
     * @param string|array $notepads     The sources to check. @since 4.2.0
     *
     * @return array  An hash with 'add', 'modify' and 'delete' arrays.
     */
    public function getChanges($start, $end, $isModSeq = false, $notepads = null)
    {
        return array('add' => $this->listBy('add', $start, $notepads, $end, $isModSeq),
                     'modify' => $this->listBy('modify', $start, $notepads, $end, $isModSeq),
                     'delete' => $this->listBy('delete', $start, $notepads, $end, $isModSeq));
    }

    /**
     * Return all changes occuring between the specified modification
     * sequences.
     *
     * @param integer $start          The starting modseq.
     * @param integer $end            The ending modseq.
     * @param string|array $notepads  The sources to check. @since 4.2.0
     *
     * @return array  The changes @see getChanges()
     * @since 4.1.1
     */
    public function getChangesByModSeq($start, $end, $notepads = null)
    {
        return $this->getChanges($start, $end, true, $notepads);
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
            $notepads = Mnemo::getSyncNotepads();
            $results = array();
            foreach ($notepads as $notepad) {
                $results = array_merge($results, $this->listBy($action, $timestamp, $notepad, $end, $isModSeq));
            }
            return $results;
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
     * @param string $id  The notepad id to get the MODSEQ for. If null, the
     *                    highest MODSEQ across all notepads is returned.
     *                    @since 4.2.0
     *
     * @return integer  The modseq.
     * @since 4.1.1
     */
    public function getHighestModSeq($id = null)
    {
        $parent = 'mnemo';
        if (!empty($id)) {
            $parent .= ':' . $id;
        }
        return $GLOBALS['injector']->getInstance('Horde_History')->getHighestModSeq($parent);
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
                                !empty($note['tags']) ? $note['tags'] : '');
                            $ids[] = $noteId;
                        }
                    }
                    return $ids;
                }
            }

            $note = $storage->fromiCalendar($content);
            $noteId = $storage->add(
                $note['desc'], $note['body'],
                !empty($note['tags']) ? $note['tags'] : '');
            break;

        case 'activesync':
            // We only support plaintext
            if ($content->body->type == Horde_ActiveSync::BODYPREF_TYPE_HTML) {
                $body = Horde_Text_Filter::filter($content->body->data, 'Html2text');
            } else {
                $body = $content->body->data;
            }
            $noteId = $storage->add($content->subject, $body, $content->categories);
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
     * @return mixed  The requested data
     * @throws Mnemo_Exception
     * @throws Horde_Exception_PermissionDenied
     */
    public function export($uid, $contentType, array $options = array())
    {
        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create();
        $memo = $storage->getByUID($uid);
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
            $iCal->setAttribute('PRODID', '-//The Horde Project//Mnemo ' . $GLOBALS['registry']->getVersion() . '//EN');
            $iCal->setAttribute('METHOD', 'PUBLISH');

            // Create a new vNote.
            $vNote = $storage->toiCalendar($memo, $iCal);
            return $vNote->exportvCalendar();

        case 'activesync':
            return $storage->toASNote($memo, $options);
        }

        throw new Mnemo_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
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
            return;
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
                             !empty($note['tags']) ? $note['tags'] : '');
            break;

        case 'activesync':
            $storage->modify($memo['memo_id'], $content->subject, $content->body->data, $content->categories);
            break;

        default:
            throw new Mnemo_Exception(sprintf(_("Unsupported Content-Type: %s"),$contentType));
        }
    }

    /**
     * Returns a list of available sources.
     *
     * @param boolean $writeable  If true, limits to writeable sources.
     * @param boolean $sync_only  Only include synchable notepads.
     *
     * @return array  An array of the available sources. Keys are source IDs,
     *                values are source titles.
     * @since 4.2.0
     */
    public function sources($writeable = false, $sync_only = false)
    {
        $out = array();

        foreach (Mnemo::listNotepads(false, $writeable ? Horde_Perms::EDIT : Horde_Perms::READ) as $key => $val) {
            $out[$key] = $val->get('name');
        }

        if ($sync_only) {
            $syncable = Mnemo::getSyncNotepads();
            $out = array_intersect_key($out, array_flip($syncable));
        }

        return $out;
    }

    /**
     * Retrieve the UID for the current user's default notepad.
     *
     * @return string  UID.
     * @since 4.2.0
     */
    public function getDefaultShare()
    {
        return Mnemo::getDefaultNotepad(Horde_Perms::EDIT);
    }

    /**
     * Create a new notepad.
     *
     * @param string $name    The notepad display name.
     * @param array  $params  Any additional parameters needed.
     *     - synchronize:   (boolean) If true, add notepad to the list of
     *                                notepads to syncronize.
     *                      DEFAULT: false (do not add to the list).
     *                      @since 4.2.1
     *
     * @return string  The new notepad's id.
     * @since 4.2.0
     */
    public function addNotepad($name, array $params = array())
    {
        if ($GLOBALS['prefs']->isLocked('default_notepad')) {
            throw new Horde_Exception_PermissionDenied();
        }

        $notepad = $GLOBALS['mnemo_shares']->newShare(
            $GLOBALS['registry']->getAuth(),
            strval(new Horde_Support_Uuid()),
            $name);

        $name = $notepad->getName();
        if (!empty($params['synchronize'])) {
            $sync = @unserialize($prefs->getValue('sync_notepads'));
            $sync[] = $name;
            $prefs->setValue('sync_notepads', serialize($sync));
        }

        return $name;
    }

    /**
     * Delete notepad.
     *
     * @param string $id  The notepad id.
     * @since 4.2.0
     */
    public function deleteNotepad($id)
    {
        // Delete the notepad.
        $storage = $GLOBALS['injector']
            ->getInstance('Mnemo_Factory_Driver')
            ->create($id);
        $storage->deleteAll();
        $share = $GLOBALS['mnemo_shares']->getShare($id);
        $GLOBALS['mnemo_shares']->removeShare($share);
    }

    /**
     * Update a notepad's title and/or description.
     *
     * @param string $id   The notepad id
     * @param array $info  The data to change:
     *   - name:  The display name.
     *   - desc:  The description.
     *
     * @since 4.2.0
     */
    public function updateNotepad($id, array $info)
    {
        $notepad = $GLOBALS['mnemo_shares']->getShare($id);
        if (!empty($info['name'])) {
            $notepad->set('name', $info['name']);
        }
        if (!empty($info['desc'])) {
            $notepad->set('desc', $info['desc']);
        }

        $notepad->save();
    }

    /**
     * Retrieve the list of used tag_names, tag_ids and the total number
     * of resources that are linked to that tag.
     *
     * @param array $tags  An optional array of tag_ids. If omitted, all tags
     *                     will be included.
     *
     * @return array  An array containing tag_name, and total
     */
    public function listTagInfo($tags = null, $user = null)
    {
        return $GLOBALS['injector']->getInstance('Mnemo_Tagger')
            ->getTagInfo($tags, 500, null, $user);
    }

    /**
     * SearchTags API:
     * Returns an application-agnostic array (useful for when doing a tag search
     * across multiple applications)
     *
     * The 'raw' results array can be returned instead by setting $raw = true.
     *
     * @param array $names           An array of tag_names to search for.
     * @param integer $max           The maximum number of resources to return.
     * @param integer $from          The number of the resource to start with.
     * @param string $resource_type  The resource type [bookmark, '']
     * @param string $user           Restrict results to resources owned by $user.
     * @param boolean $raw           Return the raw data?
     *
     * @return array An array of results:
     * <pre>
     *  'title'    - The title for this resource.
     *  'desc'     - A terse description of this resource.
     *  'view_url' - The URL to view this resource.
     *  'app'      - The Horde application this resource belongs to.
     *  'icon'     - URL to an image.
     * </pre>
     */
    public function searchTags($names, $max = 10, $from = 0,
                               $resource_type = '', $user = null, $raw = false)
    {
        // TODO: $max, $from, $resource_type not honored
        global $injector, $registry;

        $results = $injector
            ->getInstance('Mnemo_Tagger')
            ->search(
                $names,
                array('user' => $user));

        // Check for error or if we requested the raw data array.
        if ($raw) {
            return $results;
        }

        $return = array();
        $redirectUrl = Horde::url('memo.php');
        foreach ($results as $memo_id) {
            try {
                $memo = $injector->getInstance('Mnemo_Factory_Driver')
                    ->create(null)
                    ->getByUID($memo_id);
                $return[] = array(
                    'title' => $memo['desc'],
                    'desc' => '',
                    'view_url' => $redirectUrl->copy()->add(array('memo' => $memo['memo_id'], 'memolist' => $memo['memolist_id'])),
                    'app' => 'mnemo'
                );
            } catch (Exception $e) {
            }
        }

        return $return;
    }

}
