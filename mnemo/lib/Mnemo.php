<?php
/**
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Mnemo
 */

/**
 * Mnemo Base Class.
 *
 * @author  Jon Parise <jon@horde.org>
 * @package Mnemo
 */
class Mnemo {
    /**
     * Sort by memo description.
     */
    const SORT_DESC = 0;

    /**
     * Sort by memo category.
     */
    const SORT_CATEGORY = 1;

    /**
     * Sort by notepad.
     */
    const SORT_NOTEPAD = 2;

    /**
     * Sort in ascending order.
     */
    const SORT_ASCEND = 0;

    /**
     * Sort in descending order.
     */
    const SORT_DESCEND = 1;

    /**
     * No passphrase provided.
     */
    const ERR_NO_PASSPHRASE = 100;

    /**
     * Decrypting failed
     */
    const ERR_DECRYPT = 101;

    /**
     * Retrieves the current user's note list from storage. This function will
     * also sort the resulting list, if requested.
     *
     * @param constant $sortby   The field by which to sort. (self::SORT_DESC,
     *                           self::SORT_CATEGORY, self::SORT_NOTEPAD)
     * @param constant $sortdir  The direction by which to sort.
     *                           (self::SORT_ASC, self::SORT_DESC)
     *
     * @return array  A list of the requested notes.
     *
     * @see Mnemo_Driver::listMemos()
     */
    public static function listMemos($sortby = self::SORT_DESC,
                       $sortdir = self::SORT_ASCEND)
    {
        global $conf, $display_notepads;
        $memos = array();

        /* Sort the memo list. */
        $sort_functions = array(
            self::SORT_DESC => 'ByDesc',
            self::SORT_CATEGORY => 'ByCategory',
            self::SORT_NOTEPAD => 'ByNotepad',
        );

        foreach ($display_notepads as $notepad) {
            /* Create a Mnemo storage instance. */
            $storage = Mnemo_Driver::singleton($notepad);
            $storage->retrieve();

            /* Retrieve the memo list from storage. */
            $newmemos = $storage->listMemos();
            $memos = array_merge($memos, $newmemos);
        }

        /* Sort the array if we have a sort function defined for this
         * field. */
        if (isset($sort_functions[$sortby])) {
            $prefix = ($sortdir == self::SORT_DESCEND) ? '_rsort' : '_sort';
            uasort($memos, array('Mnemo', $prefix . $sort_functions[$sortby]));
        }

        return $memos;
    }

    /**
     * Returns the number of notes in notepads that the current user owns.
     *
     * @return integer  The number of notes that the user owns.
     */
    function countMemos()
    {
        static $count;
        if (isset($count)) {
            return $count;
        }

        $notepads = Mnemo::listNotepads(true, Horde_Perms::ALL);

        $count = 0;
        foreach (array_keys($notepads) as $notepad) {
            /* Create a Mnemo storage instance. */
            $storage = Mnemo_Driver::singleton($notepad);
            $storage->retrieve();

            /* Retrieve the memo list from storage. */
            $count += count($storage->listMemos());
        }

        return $count;
    }

    /**
     * Retrieves a specific note from storage.
     *
     * @param string $notepad     The notepad to retrieve the note from.
     * @param string $noteId      The Id of the note to retrieve.
     * @param string $passphrase  A passphrase with which this note was
     *                            supposed to be encrypted.
     *
     * @return array  The note.
     */
    function getMemo($notepad, $noteId, $passphrase = null)
    {
        $storage = Mnemo_Driver::singleton($notepad);
        return $storage->get($noteId, $passphrase);
    }

    /**
     * Get preview text for a note (the first 20 lines or so).
     *
     * @param array $note The note array
     *
     * @return string A few lines of the note for previews or
     * tooltips.
     */
    function getNotePreview($note)
    {
        if (is_a($note['body'], 'PEAR_Error')) {
            return $note['body']->getMessage();
        }
        $lines = explode("\n", wordwrap($note['body']));
        return implode("\n", array_splice($lines, 0, 20));
    }

    /**
     * Lists all notepads a user has access to.
     *
     * @param boolean $owneronly   Only return memo lists that this user owns?
     *                             Defaults to false.
     * @param integer $permission  The permission to filter notepads by.
     *
     * @return array  The memo lists.
     */
    public static function listNotepads($owneronly = false, $permission = Horde_Perms::SHOW)
    {
        if ($owneronly && !$GLOBALS['registry']->getAuth()) {
            return array();
        }
        try {
            $notepads = $GLOBALS['mnemo_shares']->listShares(
                $GLOBALS['registry']->getAuth(),
                array('perm' => $permission,
                      'attributes' => $owneronly ? $GLOBALS['registry']->getAuth() : null,
                      'sort_by' => 'name'));
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($e->getMessage(), 'ERR');
            return array();
        }

        return $notepads;
    }

    /**
     * Returns the default notepad for the current user at the specified
     * permissions level.
     */
    public static function getDefaultNotepad($permission = Horde_Perms::SHOW)
    {
        global $prefs;

        $default_notepad = $prefs->getValue('default_notepad');
        $notepads = Mnemo::listNotepads(false, $permission);

        if (isset($notepads[$default_notepad])) {
            return $default_notepad;
        } elseif ($prefs->isLocked('default_notepad')) {
            return $GLOBALS['registry']->getAuth();
        } elseif (count($notepads)) {
	    reset($notepads);
            return key($notepads);
        }

        return false;
    }

    /**
     * Returns the real name, if available, of a user.
     *
     * @since Mnemo 2.2
     */
    function getUserName($uid)
    {
        static $names = array();

        if (!isset($names[$uid])) {
            $ident = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($uid);
            $ident->setDefault($ident->getDefault());
            $names[$uid] = $ident->getValue('fullname');
            if (empty($names[$uid])) {
                $names[$uid] = $uid;
            }
        }

        return $names[$uid];
    }

    /**
     * Comparison function for sorting notes by description.
     *
     * @param array $a  Note one.
     * @param array $b  Note two.
     *
     * @return integer  1 if memo one is greater, -1 if memo two is greater; 0
     *                  if they are equal.
     */
    function _sortByDesc($a, $b)
    {
        return strcasecmp($a['desc'], $b['desc']);
    }

    /**
     * Comparison function for reverse sorting notes by description.
     *
     * @param array $a  Note one.
     * @param array $b  Note two.
     *
     * @return integer  -1 if note one is greater, 1 if note two is greater; 0
     *                  if they are equal.
     */
    function _rsortByDesc($a, $b)
    {
        return strcasecmp($b['desc'], $a['desc']);
    }

    /**
     * Comparison function for sorting notes by category.
     *
     * @param array $a  Note one.
     * @param array $b  Note two.
     *
     * @return integer  1 if note one is greater, -1 if note two is greater; 0
     *                  if they are equal.
     */
    function _sortByCategory($a, $b)
    {
        return strcasecmp($a['category'] ? $a['category'] : _("Unfiled"),
                          $b['category'] ? $b['category'] : _("Unfiled"));
    }

    /**
     * Comparison function for reverse sorting notes by category.
     *
     * @param array $a  Note one.
     * @param array $b  Note two.
     *
     * @return integer  -1 if note one is greater, 1 if note two is greater; 0
     *                  if they are equal.
     */
    function _rsortByCategory($a, $b)
    {
        return strcasecmp($b['category'] ? $b['category'] : _("Unfiled"),
                          $a['category'] ? $a['category'] : _("Unfiled"));
    }

    /**
     * Comparison function for sorting notes by notepad name.
     *
     * @param array $a  Note one.
     * @param array $b  Note two.
     *
     * @return integer  1 if note one is greater, -1 if note two is greater;
     *                  0 if they are equal.
     */
    function _sortByNotepad($a, $b)
    {
        $aowner = $a['memolist_id'];
        $bowner = $b['memolist_id'];

        $ashare = $GLOBALS['mnemo_shares']->getShare($aowner);
        $bshare = $GLOBALS['mnemo_shares']->getShare($bowner);

        if ($aowner != $ashare->get('owner')) {
            $aowner = $ashare->get('name');
        }
        if ($bowner != $bshare->get('owner')) {
            $bowner = $bshare->get('name');
        }

        return strcasecmp($aowner, $bowner);
    }

    /**
     * Comparison function for reverse sorting notes by notepad name.
     *
     * @param array $a  Note one.
     * @param array $b  Note two.
     *
     * @return integer  -1 if note one is greater, 1 if note two is greater;
     *                  0 if they are equal.
     */
    function _rsortByNotepad($a, $b)
    {
        $aowner = $a['memolist_id'];
        $bowner = $b['memolist_id'];

        $ashare = $GLOBALS['mnemo_shares']->getShare($aowner);
        $bshare = $GLOBALS['mnemo_shares']->getShare($bowner);

        if ($aowner != $ashare->get('owner')) {
            $aowner = $ashare->get('name');
        }
        if ($bowner != $bshare->get('owner')) {
            $bowner = $bshare->get('name');
        }

        return strcasecmp($bowner, $aowner);
    }

    /**
     * Returns the specified permission for the current user.
     *
     * @since Mnemo 2.1
     *
     * @param string $permission  A permission, currently only 'max_notes'.
     *
     * @return mixed  The value of the specified permission.
     */
    function hasPermission($permission)
    {
        global $perms;

        if (!$perms->exists('mnemo:' . $permission)) {
            return true;
        }

        $allowed = $perms->getPermissions('mnemo:' . $permission);
        if (is_array($allowed)) {
            switch ($permission) {
            case 'max_notes':
                $allowed = max($allowed);
                break;
            }
        }

        return $allowed;
    }

    /**
     * Returns a note's passphrase for symmetric encryption from the session
     * cache.
     *
     * @param string $id  A note id.
     *
     * @return string  The passphrase, if set.
     */
    function getPassphrase($id)
    {
        if (isset($_SESSION['mnemo'][$id]['passphrase'])) {
            return Horde_Secret::read(Horde_Secret::getKey('mnemo'), $_SESSION['mnemo'][$id]['passphrase']);
        }
    }

    /**
     * Stores a note's passphrase for symmetric encryption in the session
     * cache.
     *
     * @param string $id          A note id.
     * @param string $passphrase  The note's passphrase.
     *
     * @return boolean  True
     */
    function storePassphrase($id, $passphrase)
    {
        $_SESSION['mnemo'][$id]['passphrase'] = Horde_Secret::write(Horde_Secret::getKey('mnemo'), $passphrase);
    }

    /**
     * Initial app setup code.
     */
    public static function initialize()
    {
        $GLOBALS['mnemo_shares'] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();

        // Update the preference for which notepads to display. If the
        // user doesn't have any selected notepads for view then fall
        // back to some available notepad.
        $GLOBALS['display_notepads'] = unserialize($GLOBALS['prefs']->getValue('display_notepads'));
        if (($notepadId = Horde_Util::getFormData('display_notepad')) !== null) {
            if (is_array($notepadId)) {
                $GLOBALS['display_notepads'] = $notepadId;
            } else {
                if (in_array($notepadId, $GLOBALS['display_notepads'])) {
                    $key = array_search($notepadId, $GLOBALS['display_notepads']);
                    unset($GLOBALS['display_notepads'][$key]);
                } else {
                    $GLOBALS['display_notepads'][] = $notepadId;
                }
            }
        }

        // Make sure all notepads exist now, to save on checking later.
        $_temp = ($GLOBALS['display_notepads']) ? $GLOBALS['display_notepads'] : array();

        $_all = Mnemo::listNotepads();
        $GLOBALS['display_notepads'] = array();
        foreach ($_temp as $id) {
            if (isset($_all[$id])) {
                $GLOBALS['display_notepads'][] = $id;
            }
        }

        /* All tasklists for guests. */
        if (!count($GLOBALS['display_notepads']) &&
            !$GLOBALS['registry']->getAuth()) {
            $GLOBALS['display_tasklists'] = array_keys($_all);
        }

        /* If the user doesn't own a notepad, create one. */
        if (!empty($GLOBALS['conf']['share']['auto_create']) &&
            $GLOBALS['registry']->getAuth() &&
            !count(Mnemo::listNotepads(true))) {
            $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create();
            $share = $GLOBALS['mnemo_shares']->newShare($GLOBALS['registry']->getAuth(), strval(new Horde_Support_Randomid()));
            $share->set('name', sprintf(_("Notepad of %s"), $identity->getName()));
            $GLOBALS['mnemo_shares']->addShare($share);
            $GLOBALS['display_notepads'][] = $share->getName();
        }

        $GLOBALS['prefs']->setValue('display_notepads', serialize($GLOBALS['display_notepads']));
    }

}
