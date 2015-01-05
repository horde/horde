<?php
/**
 * Horde Mnemo driver for the Kolab_Storage backend.
 *
 * Copyright 2004-2015 Horde LLC (http://www.horde.org/)
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
            $this->_memos[Horde_Url::uriB64Encode($note['uid'])] = $this->_buildNote($note);
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
            $uid = Horde_Url::uriB64Decode($noteId);
            if ($this->_getData()->objectIdExists($uid)) {
                $note = $this->_getData()->getObject($uid);
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
        // HACK: Use default notepad if no notepad is set.
        //       ActiveSync does not work without this
        //       as we don't search across all notepads yet.
        if (empty($this->_notepad)) {
            $this->_notepad = Mnemo::getDefaultNotepad();
        }

        return $this->get(Horde_Url::uriB64Encode($uid), $passphrase);
    }

    /**
     * Adds a note to the backend storage.
     *
     * @param string $noteId  The ID of the new note.
     * @param string $desc    The first line of the note.
     * @param string $body    The whole note body.
     * @param string $tags    The tags of the note.
     *
     * @return string  The unique ID of the new note.
     * @throws Mnemo_Exception
     */
    protected function _add($noteId, $desc, $body, $tags)
    {
        $object = $this->_buildObject($noteId, $desc, $body, $tags);

        try {
            $this->_getData()->create($object);
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }

        return $object['uid'];
    }

    /**
     * Modifies an existing note.
     *
     * @param string $noteId  The note to modify.
     * @param string $desc    The first line of the note.
     * @param string $body    The whole note body.
     * @param string $tags    The tags of the note.
     *
     * @return string  The note's UID.
     * @throws Mnemo_Exception
     */
    protected function _modify($noteId, $desc, $body, $tags)
    {

        $object = $this->_buildObject($noteId, $desc, $body, $tags);

        try {
            $this->_getData()->modify($object);
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }

        return $object['uid'];
    }

    /**
     * Converts a note hash to a Kolab hash.
     *
     * @param string $noteId  The note to modify.
     * @param string $desc    The first line of the note.
     * @param string $body    The whole note body.
     * @param string $tags    The tags of the note.
     *
     * @return object  The Kolab hash.
     */
    protected function _buildObject($noteId, $desc, $body, $tags)
    {
        $uid = Horde_Url::uriB64Decode($noteId);
        $object = array(
            'uid' => $uid,
            'summary' => $desc,
            'body' => $body,
        );
        $object['categories'] = $GLOBALS['injector']
            ->getInstance('Mnemo_Tagger')
            ->split($tags);
        usort($object['categories'], 'strcoll');

        return $object;
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
        $uid = Horde_Url::uriB64Decode($noteId);
        try {
            $this->_getData()->move(
                $uid,
                $GLOBALS['mnemo_shares']->getShare($newNotepad)->get('folder')
            );
            $this->_getDataForNotepad($newNotepad)->synchronize();
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }
        return $uid;
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
        $uid = Horde_Url::uriB64Decode($noteId);
        try {
            $this->_getData()->delete($uid);
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }
        return $uid;
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
            $uids = $this->_getData()->getObjectIds();
            $this->_getData()->deleteAll();
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Mnemo_Exception($e);
        }

        return $uids;
    }

    /**
     * Return the Kolab data handler for the current notepad.
     *
     * @return Horde_Kolab_Storage_Data The data handler.
     */
    protected function _getData()
    {
        if (empty($this->_notepad)) {
            throw new LogicException(
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
                    _("Failed retrieving Kolab data for notepad %s: %s"),
                    $notepad,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Build a note based on data array
     *
     * @param array $note         The data for the note
     * @param string $passphrase  A passphrase for decrypting a note
     *
     * @return array  The converted data array representing the note
     */
    protected function _buildNote($note, $passphrase = null)
    {
        $encrypted = false;
        $body = $note['body'];
        $id = Horde_Url::uriB64Encode($note['uid']);

        if (strpos($body, '-----BEGIN PGP MESSAGE-----') === 0) {
            $encrypted = true;
            if (empty($passphrase)) {
                $passphrase = Mnemo::getPassphrase($id);
            }
            if (empty($passphrase)) {
                $body = new Mnemo_Exception(_("This note has been encrypted."), Mnemo::ERR_NO_PASSPHRASE);
            } else {
                try {
                    $body = $this->_decrypt($body, $passphrase)->message;
                } catch (Mnemo_Exception $e) {
                    $body = $e;
                }
                Mnemo::storePassphrase($id, $passphrase);
            }
        }

        $tagger = $GLOBALS['injector']->getInstance('Mnemo_Tagger');
        $tags = $tagger->getTags($note['uid'], 'note');
        if (!empty($note['categories'])) {
            usort($tags, 'strcoll');
            if (array_diff($note['categories'], $tags)) {
                $tagger->replaceTags(
                    $note['uid'],
                    $note['categories'],
                    $GLOBALS['registry']->getAuth(),
                    'note'
                );
            }
            $tags = $note['categories'];
        }

        $result = array(
            'memolist_id' => $this->_notepad,
            'memo_id' => $id,
            'uid' => $note['uid'],
            'tags' => $tags,
            'desc' => $note['summary'],
            'encrypted' => $encrypted,
            'body' => $body,
        );

        if (!empty($note['creation-date'])) {
            $result['created'] = new Horde_Date($note['creation-date']);
        }
        if (!empty($note['last-modification-date'])) {
            $result['modified'] = new Horde_Date($note['last-modification-date']);
        }

        return $result;
    }

    /**
     * Generates a local note ID.
     *
     * @return string  A new note ID.
     */
    protected function _generateId()
    {
        return Horde_Url::uriB64Encode($this->_getData()->generateUid());
    }
}
