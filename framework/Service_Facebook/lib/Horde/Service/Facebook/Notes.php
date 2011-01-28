<?php
/**
 * Notes methods.
 *
 * Note that these api calls are marked as BETA in the facebook docs.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_Notes extends Horde_Service_Facebook_Base
{
    /**
     * Creates a note with the specified title and content.
     *
     * @param string  $title   Title of the note.
     * @param string  $content Content of the note.
     * @param integer $uid     The user for whom you are creating a note;
     *                         defaults to current session user
     *
     * @return integer         The ID of the note that was just created.
     */
    public function &create($title, $content, $uid = null)
    {
        // Session key is *required*
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }
        return $this->_facebook->callMethod('notes.create',
            array('uid' => $uid,
                  'title' => $title,
                  'content' => $content,
                  'session_key' => $skey));
    }

    /**
     * Deletes the specified note.
     *
     * @param integer  $note_id  ID of the note you wish to delete
     * @param integer  $uid      Owner of the note you wish to delete;
     *                           defaults to current session user
     *
     * @return boolean
     */
    public function &delete($note_id, $uid = null)
    {
        // Session key is *required*
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }
        return $this->_facebook->callMethod('notes.delete',
            array('uid' => $uid,
                  'note_id' => $note_id,
                  'session_key' => $skey));
    }

    /**
     * Edits a note, replacing its title and contents with the title
     * and contents specified.
     *
     * @param integer $note_id  ID of the note you wish to edit
     * @param string  $title    Replacement title for the note
     * @param string  $content  Replacement content for the note
     *
     * @return boolean
     */
    public function &edit($note_id, $title, $content)
    {
        // Session key is *required*
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callMethod('notes.edit',
            array('note_id' => $note_id,
                  'title' => $title,
                  'content' => $content,
                  'session_key' => $skey));
    }

    /**
     * Retrieves all notes by a user. If note_ids are specified,
     * retrieves only those specific notes by that user.
     *
     * @param integer $uid       User whose notes you wish to retrieve
     * @param array   $note_ids  (Optional) List of specific note
     *                           IDs by this user to retrieve
     *
     * @return array A list of all of the given user's notes, or an empty list
     *               if the viewer lacks permissions or if there are no visible
     *               notes.
     */
    public function &get($uid, $note_ids = null)
    {
        // Session key is *required*
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callMethod('notes.get',
            array('session_key' => $skey,
                  'uid' => $uid,
                  'note_ids' => json_encode($note_ids)));
    }

}