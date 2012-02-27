<?php
/**
 * Horde Mnemo driver for the Kolab_Storage backend.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @author  Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author  Stuart Binge <omicron@mighty.co.za>
 * @since   Mnemo 2.0
 * @package Mnemo
 */
class Mnemo_Driver_Kolab extends Mnemo_Driver
{
    /**
     * The Kolab_Storage backend.
     *
     * @var Horde_Kolab_Storage
     */
    private $_kolab;

    /**
     * The current notepad.
     *
     * @var Horde_Kolab_Storage_Data
     */
    private $_data;

    /**
     * Construct a new Kolab storage object.
     *
     * @param string $notepad  The name of the notepad to load/save notes from.
     * @param array $params    The connection parameters
     *
     * @throws InvalidArguementException
     */
    public function __construct($notepad, $params = array())
    {
        if (empty($params['storage'])) {
            throw new InvalidArgumentException('Missing required storage handler.');
        }
        $this->_notepad = $notepad;
        $this->_kolab = $params['storage'];
    }

    /**
     * Return the Kolab data handler for the current notepad.
     *
     * @return Horde_Kolab_Storage_Date The data handler.
     */
    private function _getData()
    {
        if (empty($this->_notepad)) {
            throw new Mnemo_Exception(
                'The notepad has been left undefined but is required!'
            );
        }
        if ($this->_data === null) {
            $this->_data = $this->_getDataForNotepad($this->_notepad);
        }
        return $this->_data;
    }

    /**
     * Return the Kolab data handler for the specified notepad.
     *
     * @param string $notepad The notepad name.
     *
     * @return Horde_Kolab_Storage_Date The data handler.
     */
    private function _getDataForNotepad($notepad)
    {
        try {
            return $this->_kolab->getData(
                $GLOBALS['mnemo_shares']->getShare($notepad)->get('folder'),
                'note'
            );
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception(
                sprintf(
                    'Failed retrieving Kolab data for notepad %s: %s',
                    $notepad,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }
    }

    /**
     * Retrieve one note from the store.
     *
     * @param string $noteId      The ID of the note to retrieve.
     * @param string $passphrase  A passphrase with which this note was
     *                            supposed to be encrypted.
     *
     * @return array  The array of note attributes.
     */
    function get($noteId, $passphrase = null)
    {
        if ($this->_getData()->objectIdExists($noteId)) {
            $note = $this->_getData()->getObject($noteId);
            return $this->_buildNote($note, $passphrase);
        } else {
            throw new Horde_Exception_NotFound(_("Not Found"));
        }
    }

    /**
     * Retrieve one note by UID.
     *
     * @param string $uid         The UID of the note to retrieve.
     * @param string $passphrase  A passphrase with which this note was
     *                            supposed to be encrypted.
     *
     * @return array  The array of note attributes.
     */
    function getByUID($uid, $passphrase = null)
    {
        //@todo: search across notepads
        return $this->get($uid, $passphrase);
    }

    /**
     * Add a note to the backend storage.
     *
     * @param string $desc        The first line of the note.
     * @param string $body        The whole note body.
     * @param string $category    The category of the note.
     * @param string $uid         A Unique Identifier for the note.
     * @param string $passphrase  The passphrase to encrypt the note with.
     *
     * @return string  The unique ID of the new note.
     * @throws Mnemo_Exception
     */
    public function add($desc, $body, $category = '', $uid = null, $passphrase = null)
    {
        if (is_null($uid)) {
            $uid = $this->_getData()->generateUid();
        }

        if ($passphrase) {
            $body = $this->_encrypt($body, $passphrase);
            Mnemo::storePassphrase($uid, $passphrase);
        }

        $object = array(
            'uid' => $uid,
            'summary' => $desc,
            'body' => $body,
            'categories' => $category,
        );
        $this->_getData()->create($object);

        // Log the creation of this item in the history log.
        // @TODO: Inject the history driver
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $history->log('mnemo:' . $this->_notepad . ':' . $uid, array('action' => 'add'), true);

        return $uid;
    }

    /**
     * Modify an existing note.
     *
     * @param integer $noteId   The note to modify.
     * @param string $desc      The description (long) of the note.
     * @param string $body      The description (long) of the note.
     * @param string $category  The category of the note.
     * @param string $passphrase  The passphrase to encrypt the note with.
     *
     * @return booelan
     */
    function modify($noteId, $desc, $body, $category = '', $passphrase = null)
    {
        if ($passphrase) {
            $body = $this->_encrypt($body, $passphrase);
            Mnemo::storePassphrase($uid, $passphrase);
        }

        $this->_getData()->modify(
            array(
                'uid' => $noteId,
                'summary' => $desc,
                'body' => $body,
                'categories' => $category,
            )
        );

        // Log the creation of this item in the history log.
        // @TODO: Inject the history driver
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $history->log('mnemo:' . $this->_notepad . ':' . $uid, array('action' => 'modify'), true);

        return $uid;
    }

    /**
     * Move a note to a new notepad.
     *
     * @param string $noteId      The note to move.
     * @param string $newNotepad  The new notepad.
     *
     * @return mixed  True on success, PEAR_Error on failure.
     */
    function move($noteId, $newNotepad)
    {
        return $this->_getData()->move(
            $noteId,
            $GLOBALS['mnemo_shares']->getShare($newNotepad)->get('folder')
        );
    }

    /**
     * Delete the specified note from the current notepad
     *
     * @param string $noteId The note to delete.
     *
     * @return NULL
     */
    function delete($noteId)
    {
        $note = $this->get($noteId);
        $this->_getData()->delete($noteId);
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $history->log('mnemo:' . $this->_notepad . ':' . $note['uid'], array('action' => 'delete'), true);
    }

    /**
     * Delete all notes from the current notepad
     *
     * @return array  An array of uids that have been removed.
     */
    protected function _deleteAll()
    {
        $this->_retrieve();
        $ids = array_keys($this->_memos);
        $this->_getData()->deleteAll();

        return $ids;
    }

    /**
     * Retrieves all of the notes from $this->_notepad from the database.
     *
     * @return NULL
     *
     * @throws Mnemo_Exception
     */
    function retrieve()
    {
        $this->_memos = array();

        $note_list = $this->_getData()->getObjects();
        if (empty($note_list)) {
            return;
        }

        foreach ($note_list as $note) {
            $this->_memos[$note['uid']] = $this->_buildNote($note);
        }
    }

    /**
     * Build a note based on data array
     *
     * @param array  $note     The data for the note
     * @param string $passphrase A passphrase for decrypting a note
     *
     * @return array  The converted data array representing the note
     */
    function _buildNote($note, $passphrase = null)
    {
        $note['memolist_id'] = $this->_notepad;
        $note['memo_id'] = $note['uid'];

        $note['category'] = $note['categories'];
        unset($note['categories']);

        $note['desc'] = $note['summary'];
        unset($note['summary']);

        $note['encrypted'] = false;
        $body = $note['body'];

        if (strpos($body, '-----BEGIN PGP MESSAGE-----') === 0) {
            $note['encrypted'] = true;
            if (empty($passphrase)) {
                $passphrase = Mnemo::getPassphrase($note['memo_id']);
            }
            if (empty($passphrase)) {
                $body = new Mnemo_Exception(_("This note has been encrypted."), Mnemo::ERR_NO_PASSPHRASE);
            } else {
                try {
                    $body = $this->_decrypt($body, $passphrase);
                    $body = $body->message;
                } catch (Mnemo_Exception $e) {
                    $body = $e;
                }
                Mnemo::storePassphrase($row['memo_id'], $passphrase);
            }
        }
        $note['body'] = $body;

        return $note;
    }
}
