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
     * Retrieves all of the notes of the current notepad from the backend.
     *
     * @throws Mnemo_Exception
     */
    public function retrieve()
    {
        $this->_memos = array();

        try {
            $note_list = $this->_getData()->getObjects();
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }
        if (empty($note_list)) {
            return;
        }

        foreach ($note_list as $note) {
            $this->_memos[$note['uid']] = $this->_buildNote($note);
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
    public function get($noteId, $passphrase = null)
    {
        try {
            if ($this->_getData()->objectIdExists($noteId)) {
                $note = $this->_getData()->getObject($noteId);
                return $this->_buildNote($note, $passphrase);
            } else {
                throw new Horde_Exception_NotFound();
            }
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }
    }

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
    public function getByUID($uid, $passphrase = null)
    {
        //@todo: search across notepads
        return $this->get($uid, $passphrase);
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
    protected function _add($noteId, $desc, $body, $category)
    {
        $object = array(
            'uid' => $noteId,
            'summary' => $desc,
            'body' => $body,
            'categories' => array($category),
        );
        try {
            $this->_getData()->create($object);
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }
        return $noteId;
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
    protected function _modify($noteId, $desc, $body, $category)
    {
        try {
            $this->_getData()->modify(
                array(
                    'uid' => $noteId,
                    'summary' => $desc,
                    'body' => $body,
                    'categories' => array($category),
                )
            );
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }
        return $noteId;
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
    protected function _move($noteId, $newNotepad)
    {
        try {
            $this->_getData()->move(
                $noteId,
                $GLOBALS['mnemo_shares']->getShare($newNotepad)->get('folder')
            );
            $this->_getDataForNotepad($newNotepad)->synchronize();
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }
        return $noteId;
    }

    /**
     * Deletes a note permanently.
     *
     * @param array $note  The note to delete.
     *
     * @return string  The note's UID.
     * @throws Mnemo_Exception
     */
    protected function _delete($noteId)
    {
        $note = $this->get($noteId);
        try {
            $this->_getData()->delete($noteId);
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }
        return $noteId;
    }

    /**
     * Deletes all notes from the current notepad.
     *
     * @return array  An array of uids that have been removed.
     * @throws Mnemo_Exception
     */
    protected function _deleteAll()
    {
        try {
            $ids = $this->_getData()->getObjectIds();
            $this->_getData()->deleteAll();
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }

        return $ids;
    }

    /**
     * Return the Kolab data handler for the current notepad.
     *
     * @return Horde_Kolab_Storage_Data The data handler.
     */
    protected function _getData()
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
     * @return Horde_Kolab_Storage_Data The data handler.
     */
    protected function _getDataForNotepad($notepad)
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
     * Build a note based on data array
     *
     * @param array  $note     The data for the note
     * @param string $passphrase A passphrase for decrypting a note
     *
     * @return array  The converted data array representing the note
     */
    protected function _buildNote($note, $passphrase = null)
    {
        $note['memolist_id'] = $this->_notepad;
        $note['memo_id'] = $note['uid'];

        $note['category'] = empty($note['categories']) ? '' : $note['categories'][0];
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

    /**
     * Generates a local note ID.
     *
     * @return string  A new note ID.
     */
    protected function _generateId()
    {
        return $this->_getData()->generateUid();
    }
}
