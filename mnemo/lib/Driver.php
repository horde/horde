<?php
/**
 * Mnemo_Driver:: defines an API for implementing storage backends for Mnemo.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Jon Parise <jon@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Mnemo
 */
abstract class Mnemo_Driver
{
    /**
     * Array holding the current memo list.  Each array entry is a hash
     * describing a memo.  The array is indexed numerically by memo ID.
     *
     * @var array
     */
    protected $_memos = array();

    /**
     * String containing the current notepad name.
     *
     * @var string
     */
    protected $_notepad = '';

    /**
     * Crypting processor.
     *
     * @var Horde_Crypt_pgp
     */
    protected $_pgp;

    /**
     * Retrieves all of the notes of the current notepad from the backend.
     *
     * @thows Mnemo_Exception
     */
    abstract public function retrieve();

    /**
     * Lists memos based on the given criteria. All memos will be
     * returned by default.
     *
     * @return array    Returns a list of the requested memos.
     */
    public function listMemos()
    {
        return $this->_memos;
    }

    /**
     * Update the description (short summary) of a memo.
     *
     * @param integer $memo_id  The memo to update.
     */
    public function getMemoDescription($body)
    {
        if (!strstr($body, "\n") && Horde_String::length($body) <= 64) {
            return trim($body);
        }

        $lines = explode("\n", $body);
        if (!is_array($lines)) {
            return trim(Horde_String::substr($body, 0, 64));
        }

        // Move to a line with more than spaces.
        $i = 0;
        while (isset($lines[$i]) && !preg_match('|[^\s]|', $lines[$i])) {
            $i++;
        }
        if (Horde_String::length($lines[$i]) <= 64) {
            return trim($lines[$i]);
        } else {
            return trim(Horde_String::substr($lines[$i], 0, 64));
        }
    }

    /**
     * Retrieves one note from the backend.
     *
     * @param string $noteId      The ID of the note to retrieve.
     * @param string $passphrase  A passphrase with which this note was
     *                            supposed to be encrypted.
     *
     * @return array  The array of note attributes.
     * @throws Mnemo_Exception
     * @throws Horde_Exception_NotFound
     */
    abstract public function get($noteId, $passphrase = null);

    /**
     * Retrieves one note from the backend by UID.
     *
     * @param string $uid         The UID of the note to retrieve.
     * @param string $passphrase  A passphrase with which this note was
     *                            supposed to be encrypted.
     *
     * @return array  The array of note attributes.
     * @throws Mnemo_Exception
     * @throws Horde_Exception_NotFound
     */
    abstract public function getByUID($uid, $passphrase = null);

    /**
     * Adds a note to the backend storage.
     *
     * @param string $desc        The first line of the note.
     * @param string $body        The whole note body.
     * @param string $category    The category of the note.
     * @param string $passphrase  The passphrase to encrypt the note with.
     *
     * @return string  The ID of the new note.
     * @throws Mnemo_Exception
     */
    public function add($desc, $body, $category = '', $passphrase = null)
    {
        $noteId = $this->_generateId();

        if ($passphrase) {
            $body = $this->_encrypt($body, $passphrase);
            Mnemo::storePassphrase($noteId, $passphrase);
        }

        $uid = $this->_add($noteId, $desc, $body, $category);

        // Log the creation of this item in the history log.
        try {
            $GLOBALS['injector']->getInstance('Horde_History')
                ->log('mnemo:' . $this->_notepad . ':' . $uid,
                      array('action' => 'add'), true);
        } catch (Horde_Exception $e) {
        }

        return $noteId;
    }

    /**
     * Adds a note to the backend storage.
     *
     * @param string $noteId    The ID of the new note.
     * @param string $desc      The first line of the note.
     * @param string $body      The whole note body.
     * @param string $category  The category of the note.
     *
     * @return string  The unique ID of the new note.
     * @throws Mnemo_Exception
     */
    abstract protected function _add($noteId, $desc, $body, $category);

    /**
     * Modifies an existing note.
     *
     * @param string $noteId      The note to modify.
     * @param string $desc        The first line of the note.
     * @param string $body        The whole note body.
     * @param string $category    The category of the note.
     * @param string $passphrase  The passphrase to encrypt the note with.
     *
     * @throws Mnemo_Exception
     */
    public function modify($noteId, $desc, $body, $category = null,
                           $passphrase = null)
    {
        if ($passphrase) {
            $body = $this->_encrypt($body, $passphrase);
            Mnemo::storePassphrase($noteId, $passphrase);
        }

        $uid = $this->_modify($noteId, $desc, $body, $category);

        // Log the modification of this item in the history log.
        if ($uid) {
            try {
                $GLOBALS['injector']->getInstance('Horde_History')
                    ->log('mnemo:' . $this->_notepad . ':' . $uid,
                          array('action' => 'modify'), true);
            } catch (Horde_Exception $e) {
            }
        }
    }

    /**
     * Modifies an existing note.
     *
     * @param string $noteId    The note to modify.
     * @param string $desc      The first line of the note.
     * @param string $body      The whole note body.
     * @param string $category  The category of the note.
     *
     * @return string  The note's UID.
     * @throws Mnemo_Exception
     */
    abstract protected function _modify($noteId, $desc, $body, $category);

    /**
     * Moves a note to a new notepad.
     *
     * @param string $noteId      The note to move.
     * @param string $newNotepad  The new notepad.
     *
     * @throws Mnemo_Exception
     */
    public function move($noteId, $newNotepad)
    {
        $uid = $this->_move($noteId, $newNotepad);

        // Log the moving of this item in the history log.
        if ($uid) {
            try {
                $history = $GLOBALS['injector']->getInstance('Horde_History');
                $history->log('mnemo:' . $this->_notepad . ':' . $uid,
                              array('action' => 'delete'), true);
                $history->log('mnemo:' . $newNotepad . ':' . $uid,
                              array('action' => 'add'), true);
            } catch (Horde_Exception $e) {
            }
        }
    }

    /**
     * Moves a note to a new notepad.
     *
     * @param string $noteId      The note to move.
     * @param string $newNotepad  The new notepad.
     *
     * @return string  The note's UID.
     * @throws Mnemo_Exception
     */
    abstract protected function _move($noteId, $newNotepad);

    /**
     * Deletes a note permanently.
     *
     * @param string $noteId  The note to delete.
     *
     * @throws Mnemo_Exception
     */
    public function delete($noteId)
    {
        $uid = $this->_delete($noteId);

        // Log the deletion of this item in the history log.
        if ($uid) {
            try {
                $GLOBALS['injector']->getInstance('Horde_History')
                    ->log('mnemo:' . $this->_notepad . ':' . $uid,
                          array('action' => 'delete'), true);
            } catch (Horde_Exception $e) {
            }
        }
    }

    /**
     * Deletes a note permanently.
     *
     * @param array $note  The note to delete.
     *
     * @return string  The note's UID.
     * @throws Mnemo_Exception
     */
    abstract protected function _delete($noteId);

    /**
     * Deletes all notes from the current notepad.
     *
     * @throws Mnemo_Exception
     */
    public function deleteAll()
    {
        $ids = $this->_deleteAll();

        // Update History.
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        try {
            foreach ($ids as $id) {
                $history->log(
                    'mnemo:' . $this->_notepad . ':' . $id,
                    array('action' => 'delete'),
                    true);
            }
        } catch (Horde_Exception $e) {
        }
    }

    /**
     * Deletes all notes from the current notepad.
     *
     * @return array  An array of uids that have been removed.
     * @throws Mnemo_Exception
     */
    abstract protected function _deleteAll();

    /**
     * Loads the PGP encryption driver.
     *
     * @TODO: Inject *into* driver from the factory binder
     */
    protected function _loadPGP()
    {
        if (empty($GLOBALS['conf']['gnupg']['path'])) {
            throw new Mnemo_Exception(_("Encryption support has not been configured, please contact your administrator."));
        }

        $this->_pgp = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Crypt')->create('pgp', array(
            'program' => $GLOBALS['conf']['gnupg']['path']
        ));
    }

    /**
     * Encrypts a note.
     *
     * @param string $note        The note text.
     * @param string $passphrase  The passphrase to encrypt the note with.
     *
     * @return string  The encrypted text.
     */
    protected function _encrypt($note, $passphrase)
    {
        $this->_loadPGP();
        return $this->_pgp->encrypt($note, array('type' => 'message', 'symmetric' => true, 'passphrase' => $passphrase));
    }

    /**
     * Decrypts a note.
     *
     * @param string $note        The encrypted note text.
     * @param string $passphrase  The passphrase to decrypt the note with.
     *
     * @return string  The decrypted text.
     * @throws Mnemo_Exception
     */
    protected function _decrypt($note, $passphrase)
    {
        $this->_loadPGP();

        try {
            return $this->_pgp->decrypt($note, array('type' => 'message', 'passphrase' => $passphrase));
        } catch (Horde_Crypt_Exception $e) {
            throw new Mnemo_Exception($e->getMessage(), Mnemo::ERR_DECRYPT);
        }
    }

    /**
     * Returns whether note encryption is supported.
     *
     * Checks if PGP support could be loaded, if it supports symmetric
     * encryption, and if we have a secure connection.
     *
     * @return boolean  Whether encryption is suppoted.
     */
    public function encryptionSupported()
    {
        try {
            $this->_loadPGP();
        } catch (Mnemo_Exception $e) {
        }
        return (is_callable(array($this->_pgp, 'encryptedSymmetrically')) &&
                Horde::isConnectionSecure());
    }

    /**
     * Export this memo in iCalendar format.
     *
     * @param array  memo      The memo (hash array) to export
     * @param Horde_Icalendar  A Horde_Icalendar object that acts as container.
     *
     * @return Horde_Icalendar_Vnote  object for this event.
     */
    public function toiCalendar($memo, $calendar)
    {
        global $prefs;

        $vnote = Horde_Icalendar::newComponent('vnote', $calendar);

        $vnote->setAttribute('UID', $memo['uid']);
        $vnote->setAttribute('BODY', $memo['body']);
        $vnote->setAttribute('SUMMARY', $this->getMemoDescription($memo['body']));

        if (!empty($memo['category'])) {
            $vnote->setAttribute('CATEGORIES', $memo['category']);
        }

        /* Get the note's history. */
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $log = $history->getHistory('mnemo:' . $memo['memolist_id'] . ':' . $memo['uid']);
        if ($log) {
            foreach ($log->getData() as $entry) {
                switch ($entry['action']) {
                case 'add':
                    $created = $entry['ts'];
                    break;

                case 'modify':
                    $modified = $entry['ts'];
                    break;
                }
            }
        }

        if (!empty($created)) {
            $vnote->setAttribute('DCREATED', $created);
        }
        if (!empty($modified)) {
            $vnote->setAttribute('LAST-MODIFIED', $modified);
        }

        return $vnote;
    }

    /**
     * Create a memo (hash array) from a Horde_Icalendar_Vnote object.
     *
     * @param Horde_Icalendar_Vnote $vnote  The iCalendar data to update from.
     *
     * @return array  Memo (hash array) created from the vNote.
     */
    public function fromiCalendar(Horde_Icalendar_Vnote $vNote)
    {
        $memo = array();

        try {
            $body = $vNote->getAttribute('BODY');
        } catch (Horde_Icalendar_Exception $e) {
        }
        if (!is_array($body)) {
            $memo['body'] = $body;
        } else {
            $memo['body'] = '';
        }

        $memo['desc'] = $this->getMemoDescription($memo['body']);

        try {
            $cat = $vNote->getAttribute('CATEGORIES');
        } catch (Horde_Icalendar_Exception $e) {
        }
        if (!is_array($cat)) {
            $memo['category'] = $cat;
        }

        return $memo;
    }

    /**
     * Generates a local note ID.
     *
     * @return string  A new note ID.
     */
    abstract protected function _generateId();
}
