<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 */
/**
 * Mnemo Base Class.
 *
 * @author  Jon Parise <jon@horde.org>
 * @package Mnemo
 */
class Mnemo
{
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
     * Sort by moddate
     */
    const SORT_MOD_DATE = 3;

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
     *                           self::SORT_CATEGORY, self::SORT_NOTEPAD,
     *                           self::SORT_MOD_DATE)
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
            self::SORT_MOD_DATE => 'ByModDate'
        );

        foreach ($display_notepads as $notepad) {
            $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create($notepad);
            try {
                $storage->retrieve();
            } catch (Mnemo_Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
            }
            $newmemos = $storage->listMemos();
            $memos = array_merge($memos, $newmemos);
        }

        // Sort the array if we have a sort function defined
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
    public static function countMemos()
    {
        static $count;
        if (isset($count)) {
            return $count;
        }

        $notepads = self::listNotepads(true, Horde_Perms::ALL);
        $count = 0;
        foreach (array_keys($notepads) as $notepad) {
            $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create($notepad);
            $storage->retrieve();
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
    public static function getMemo($notepad, $noteId, $passphrase = null)
    {
        $storage = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')->create($notepad);
        return $storage->get($noteId, $passphrase);
    }

    /**
     * Get preview text for a note (the first 20 lines or so).
     *
     * @param array $note The note array
     *
     * @return string A few lines of the note for previews or tooltips.
     */
    public static function getNotePreview($note)
    {
        $body = $note['body'];
        if ($body instanceof Mnemo_Exception) {
            $body = $body->getMessage();
        }
        $lines = explode("\n", wordwrap($body));
        return implode("\n", array_splice($lines, 0, 20));
    }

    /**
     * Lists all notepads a user has access to.
     *
     * This method takes the $conf['share']['hidden'] setting into account. If
     * this setting is enabled, even if requesting permissions different than
     * SHOW, it will only return calendars that the user owns or has SHOW
     * permissions for. For checking individual calendar's permissions, use
     * hasPermission() instead.
     *
     * @param boolean $owneronly   Only return memo lists that this user owns?
     *                             Defaults to false.
     * @param integer $permission  The permission to filter notepads by.
     *
     * @return array  The memo lists.
     */
    public static function listNotepads($owneronly = false,
                                        $permission = Horde_Perms::SHOW)
    {
        if ($owneronly && !$GLOBALS['registry']->getAuth()) {
            return array();
        }
        if ($owneronly || empty($GLOBALS['conf']['share']['hidden'])) {
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
        } else {
            try {
                $notepads = $GLOBALS['mnemo_shares']->listShares(
                    $GLOBALS['registry']->getAuth(),
                    array('perm' => $permission,
                          'attributes' => $GLOBALS['registry']->getAuth(),
                          'sort_by' => 'name'));
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e);
                return array();
            }
            $display_notepads = @unserialize($GLOBALS['prefs']->getValue('display_notepads'));
            if (is_array($display_notepads)) {
                foreach ($display_notepads as $id) {
                    try {
                        $notepad = $GLOBALS['mnemo_shares']->getShare($id);
                        if ($notepad->hasPermission($GLOBALS['registry']->getAuth(), $permission)) {
                            $notepads[$id] = $notepad;
                        }
                    } catch (Horde_Exception_NotFound $e) {
                    } catch (Horde_Share_Exception $e) {
                        Horde::logMessage($e);
                        return array();
                    }
                }
            }
        }

        return $notepads;
    }

    /**
     * Returns the default notepad for the current user at the specified
     * permissions level.
     *
     * @return mixed  The notepad identifier, or false if none found
     */
    public static function getDefaultNotepad($permission = Horde_Perms::SHOW)
    {
        global $prefs;

        $default_notepad = $prefs->getValue('default_notepad');
        $notepads = self::listNotepads(false, $permission);

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
     * @return string  The real name
     */
    public static function getUserName($uid)
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
    protected static function _sortByDesc($a, $b)
    {
        return strcoll($a['desc'], $b['desc']);
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
    protected static function _rsortByDesc($a, $b)
    {
        return strcoll($b['desc'], $a['desc']);
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
    protected static function _sortByCategory($a, $b)
    {
        return strcoll($a['category'] ? $a['category'] : _("Unfiled"),
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
    protected static function _rsortByCategory($a, $b)
    {
        return strcoll($b['category'] ? $b['category'] : _("Unfiled"),
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
    protected static function _sortByNotepad($a, $b)
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

        return strcoll($aowner, $bowner);
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
    protected static function _rsortByNotepad($a, $b)
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

        return strcoll($bowner, $aowner);
    }

    /**
     * Comparison function for sorting notes by modification date.
     *
     * @param array $a  Note one.
     * @param array $b  Note two.
     *
     * @return integer  1 if note one is greater, -1 if note two is greater;
     *                  0 if they are equal.
     */
    protected static function _sortByModDate($a, $b)
    {
        // Get notes` history
        $history = $GLOBALS['injector']->getInstance('Horde_History');

        $guidA = 'mnemo:' . $a['memolist_id'] . ':' . $a['uid'];
        $guidB = 'mnemo:' . $b['memolist_id'] . ':' . $b['uid'];

        // Gets the timestamp of the most recent modification to the note
        $modDateA = $history->getActionTimestamp($guidA, 'modify');
        $modDateB = $history->getActionTimestamp($guidB, 'modify');

        // If the note hasn't been modified, get the creation timestamp
        if ($modDateA == 0) {
            $modDateA = $history->getActionTimestamp($guidA, 'add');
        }
        if ($modDateB == 0) {
            $modDateB = $history->getActionTimestamp($guidB, 'add');
        }
        if ($modDateA == $modDateB) {
            return 0;
        }

        return ($modDateA > $modDateB) ? 1 : -1;
    }

     /**
     * Comparison function for reverse sorting notes by modification date.
     *
     * @param array $a  Note one.
     * @param array $b  Note two.
     *
     * @return integer  -1 if note one is greater, 1 if note two is greater,
     *                  0 if they are equal.
     */
    protected static function _rsortByModDate($a, $b)
    {
        // Get note's history
        $history = $GLOBALS['injector']->getInstance('Horde_History');

        $guidA = 'mnemo:' . $a['memolist_id'] . ':' . $a['uid'];
        $guidB = 'mnemo:' . $b['memolist_id'] . ':' . $b['uid'];

        // Gets the timestamp of the most recent modification to the note
        $modDateA = $history->getActionTimestamp($guidA, 'modify');
        $modDateB = $history->getActionTimestamp($guidB, 'modify');

        // If the note hasn't been modified, get the creation timestamp
        if ($modDateA == 0) {
            $modDateA = $history->getActionTimestamp($guidA, 'add');
        }
        if ($modDateB == 0) {
            $modDateB = $history->getActionTimestamp($guidB, 'add');
        }

        if ($modDateA == $modDateB) {
            return 0;
        }

        return ($modDateA < $modDateB) ? 1 : -1;
    }

    /**
     * Returns the specified permission for the current user.
     *
     * @param string $permission  A permission, currently only 'max_notes'.
     *
     * @return mixed  The value of the specified permission.
     */
    public static function hasPermission($permission)
    {
        global $perms;

        if (!$perms->exists('mnemo:' . $permission)) {
            return true;
        }

        $allowed = $perms->getPermissions('mnemo:' . $permission, $GLOBALS['registry']->getAuth());
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
    public static function getPassphrase($id)
    {
        if (!$id) {
            return;
        }
        if ($passphrase = $GLOBALS['session']->get('mnemo', 'passphrase/' . $id)) {
            $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
            return $secret->read($secret->getKey(), $passphrase);
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
    public static function storePassphrase($id, $passphrase)
    {
        $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
        $GLOBALS['session']->set('mnemo', 'passphrase/' . $id, $secret->write($secret->getKey(), $passphrase));
    }

    /**
     * Initial app setup code.
     *
     * Defines the following $GLOBALS (@TODO these should use the injector)
     *  mnemo_shares
     *  display_notepads
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

        $_all = self::listNotepads();
        $GLOBALS['display_notepads'] = array();
        foreach ($_temp as $id) {
            if (isset($_all[$id])) {
                $GLOBALS['display_notepads'][] = $id;
            }
        }

        // All notepads for guests.
        if (!count($GLOBALS['display_notepads']) &&
            !$GLOBALS['registry']->getAuth()) {
            $GLOBALS['display_notepads'] = array_keys($_all);
        }

        /* If the user doesn't own a notepad, create one. */
        $notepads = $GLOBALS['injector']->getInstance('Mnemo_Factory_Notepads')
            ->create();
        if (($new_default = $notepads->ensureDefaultShare()) !== null) {
            $GLOBALS['display_notepads'][] = $new_default;
        }

        $GLOBALS['prefs']->setValue('display_notepads', serialize($GLOBALS['display_notepads']));
    }

    /**
     */
    static public function getCssStyle($category, $stickies = false)
    {
        $cManager = new Horde_Prefs_CategoryManager();
        $colors = $cManager->colors();
        if (!isset($colors[$category])) {
            return '';
        }
        $fgColors = $cManager->fgColors();

        if (!$stickies) {
            return 'color:' . (isset($fgColors[$category]) ? $fgColors[$category] : $fgColors['_default_']) . ';' .
                'background:' . $colors[$category] . ';';
        }

        $hex = str_replace('#', '', $colors[$category]);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return "background: rgba($r, $g, $b, 0.5)";
    }

    public static function menu()
    {
        $sidebar = Horde::menu(array('menu_ob' => true))->render();
        $perms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
        if (Mnemo::getDefaultNotepad(Horde_Perms::EDIT) &&
            ($perms->hasAppPermission('max_notes') === true ||
             $perms->hasAppPermission('max_notes') > Mnemo::countMemos())) {
            $sidebar->addNewButton(
                _("_New Note"),
                Horde::url('memo.php')->add('actionID', 'add_memo'));
        }
        return $GLOBALS['injector']->getInstance('Horde_View_Topbar')->render()
            . $sidebar;
    }
}
