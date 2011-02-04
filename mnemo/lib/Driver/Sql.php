<?php
/**
 * Mnemo storage implementation for Horde's Horde_Db database abstraction
 * layer.
 *
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Mnemo
 */
class Mnemo_Driver_Sql extends Mnemo_Driver
{
    /**
     * The database connection object.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Share table name
     *
     * @var string
     */
    protected $_table;

    /**
     * Construct a new SQL storage object.
     *
     * @param string $notepad  The name of the notepad to load/save notes from.
     * @param array $params    The connection parameters
     *
     * @throws InvalidArguementException
     */
    public function __construct($notepad, $params = array())
    {
        if (empty($params['db']) || empty($params['table'])) {
            throw new InvalidArgumentException('Missing required connection parameter(s).');
        }
        $this->_notepad = $notepad;
        $this->_db = $params['db'];
        $this->_table = $params['table'];
    }

    /**
     * Retrieve one note from the database.
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
        $query = 'SELECT * FROM ' . $this->_table .
                 ' WHERE memo_owner = ? AND memo_id = ?';
        $values = array($this->_notepad, $noteId);
        try {
            $row = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Mnemo_Exception($e->getMessage());
        }

        if (!count($row)) {
            throw new Horde_Exception_NotFound(_("Not Found"));
        }

        return $this->_buildNote($row, $passphrase);
    }

    /**
     * Retrieve one note from the database by UID.
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
        $query = 'SELECT * FROM ' . $this->_table . ' WHERE memo_uid = ?';
        $values = array($uid);
        try {
            $row = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Mnemo_Exception($e->getMessage());
        }

        if (!count($row)) {
            throw new Horde_Exception_NotFound('Not found');
        }
        $this->_notepad = $row['memo_owner'];

        return $this->_buildNote($row, $passphrase);
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
        $noteId = strval(new Horde_Support_Randomid());

        if ($passphrase) {
            $body = $this->_encrypt($body, $passphrase);
            Mnemo::storePassphrase($noteId, $passphrase);
        }

        if (is_null($uid)) {
            $uid = strval(new Horde_Support_Uuid());
        }

        $query = 'INSERT INTO ' . $this->_table .
                 ' (memo_owner, memo_id, memo_desc, memo_body, memo_category, memo_uid)' .
                 ' VALUES (?, ?, ?, ?, ?, ?)';
        $values = array($this->_notepad,
                        $noteId,
                        Horde_String::convertCharset($desc, 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($body, 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($category, 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($uid, 'UTF-8', $this->_params['charset']));

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Mnemo_Exception($e->getMessage());
        }

        // Log the creation of this item in the history log.
        // @TODO: Inject the history driver
        $history = $GLOBALS['injector']->getInstance('Horde_History');
        $history->log('mnemo:' . $this->_notepad . ':' . $uid, array('action' => 'add'), true);

        return $noteId;
    }

    /**
     * Modify an existing note.
     *
     * @param string $noteId      The note to modify.
     * @param string $desc        The description (long) of the note.
     * @param string $body        The description (long) of the note.
     * @param string $category    The category of the note.
     * @param string $passphrase  The passphrase to encrypt the note with.
     *
     * @throws Mnemo_Exception
     */
    public function modify($noteId, $desc, $body, $category = null, $passphrase = null)
    {
        if ($passphrase) {
            $body = $this->_encrypt($body, $passphrase);
            Mnemo::storePassphrase($noteId, $passphrase);
        }

        $query  = 'UPDATE ' . $this->_table . ' SET memo_desc = ?, memo_body = ?';
        $values = array(Horde_String::convertCharset($desc, 'UTF-8', $this->_params['charset']),
                        Horde_String::convertCharset($body, 'UTF-8', $this->_params['charset']));

        // Don't change the category if it isn't provided.
        // @TODO: Category -> Tags
        if (!is_null($category)) {
            $query .= ', memo_category = ?';
            $values[] = Horde_String::convertCharset($category, 'UTF-8', $this->_params['charset']);
        }
        $query .= ' WHERE memo_owner = ? AND memo_id = ?';
        array_push($values, $this->_notepad, $noteId);

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Mnemo_Exception($e->getMessage());
        }
        // Log the modification of this item in the history log.
        $note = $this->get($noteId);
        if (!empty($note['uid'])) {
            $history = $GLOBALS['injector']->getInstance('Horde_History');
            $history->log('mnemo:' . $this->_notepad . ':' . $note['uid'], array('action' => 'modify'), true);
        }
    }

    /**
     * Move a note to a new notepad.
     *
     * @param string $noteId      The note to move.
     * @param string $newNotepad  The new notepad.
     *
     * @throws Mnemo_Exception
     */
    public function move($noteId, $newNotepad)
    {
        // Get the note's details for use later.
        $note = $this->get($noteId);

        $query = 'UPDATE ' . $this->_table .
                 ' SET memo_owner = ?' .
                 ' WHERE memo_owner = ? AND memo_id = ?';
        $values = array($newNotepad, $this->_notepad, $noteId);
        try {
            $result = $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Mnemo_Exception($e->getMessage());
        }

        // Log the moving of this item in the history log.
        if (!empty($note['uid'])) {
            $history = $GLOBALS['injector']->getInstance('Horde_History');
            $history->log('mnemo:' . $this->_notepad . ':' . $note['uid'], array('action' => 'delete'), true);
            $history->log('mnemo:' . $newNotepad . ':' . $note['uid'], array('action' => 'add'), true);
        }
    }

    /**
     * Delete a note permanently
     *
     * @param string $noteId  The note to delete.
     *
     * @throws Mnemo_Exception
     */
    public function delete($noteId)
    {
        // Get the note's details for use later.
        $note = $this->get($noteId);

        $query = 'DELETE FROM ' . $this->_table .
                 ' WHERE memo_owner = ? AND memo_id = ?';
        $values = array($this->_notepad, $noteId);

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Mnemo_Exception($e->getMessage());
        }

        // Log the deletion of this item in the history log.
        if (!empty($note['uid'])) {
            $history = $GLOBALS['injector']->getInstance('Horde_History');
            $history->log('mnemo:' . $this->_notepad . ':' . $note['uid'], array('action' => 'delete'), true);
        }
    }

    /**
     * Remove ALL notes belonging to the curernt user.
     *
     * @throws Mnemo_Exception
     */
    public function deleteAll()
    {
        $query = sprintf('DELETE FROM %s WHERE memo_owner = ?',
			 $this->_table);
        $values = array($this->_notepad);

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Mnemo_Exception($e->getMessage());
        }
    }

    /**
     * Retrieves all of the notes from $this->_notepad from the
     * database.
     *
     * @throws Mnemo_Exception
     */
    public function retrieve()
    {
        $query = sprintf('SELECT * FROM %s WHERE memo_owner = ?', $this->_table);
        $values = array($this->_notepad);

        try {
            $rows = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Mnemo_Exception($e->getMessage());
        }

        // Store the retrieved values in a fresh $memos list.
        $this->_memos = array();
        foreach ($rows as $row) {
            $this->_memos[$row['memo_id']] = $this->_buildNote($row);
        }
    }

    /**
     *
     * @param array $row           Hash of the note data, db keys.
     * @param string  $passphrase  The encryption passphrase.
     *
     * @return array a Task hash.
     * @throws Mnemo_Exception
     */
    protected function _buildNote($row, $passphrase = null)
    {
        // Make sure notes always have a UID.
        if (empty($row['memo_uid'])) {
            $row['memo_uid'] = strval(new Horde_Support_Guid());

            $query = 'UPDATE ' . $this->_table .
                ' SET memo_uid = ?' .
                ' WHERE memo_owner = ? AND memo_id = ?';
            $values = array($row['memo_uid'], $row['memo_owner'], $row['memo_id']);
            try {
                $this->_db->update($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Mnemo_Exception($e->getMessage());
            }
        }

        // Decrypt note if requested.
        $encrypted = false;
        $body = Horde_String::convertCharset($row['memo_body'], $this->_params['charset'], 'UTF-8');
        if (strpos($body, '-----BEGIN PGP MESSAGE-----') === 0) {
            $encrypted = true;
            if (empty($passphrase)) {
                $passphrase = Mnemo::getPassphrase($row['memo_id']);
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

        // Create a new task based on $row's values.
        return array('memolist_id' => $row['memo_owner'],
                     'memo_id' => $row['memo_id'],
                     'uid' => Horde_String::convertCharset($row['memo_uid'], $this->_params['charset'], 'UTF-8'),
                     'desc' => Horde_String::convertCharset($row['memo_desc'], $this->_params['charset'], 'UTF-8'),
                     'body' => $body,
                     'category' => Horde_String::convertCharset($row['memo_category'], $this->_params['charset'], 'UTF-8'),
                     'encrypted' => $encrypted);
    }

}
