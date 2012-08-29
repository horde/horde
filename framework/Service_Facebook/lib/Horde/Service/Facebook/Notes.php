<?php
/**
 * Notes methods.
 *
 * Note that these api calls are marked as BETA in the facebook docs.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
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
    public function create($title, $content, $uid = 'me')
    {
        // Session key is *required*
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'session_key is required',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callGraphApi(
            $uid . '/notes',
            array('message' => $content, 'subject' => $title),
            array('request' => 'POST'));
    }

    /**
     * Deletes the specified note.
     *
     * @param integer  $note_id  ID of the note you wish to delete
     *
     * @return boolean
     */
    public function delete($note_id)
    {
        // Session key is *required*
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'session_key is required',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callGraphApi(
            $note_id,
            array(),
            array('request' => 'DELETE'));
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
    public function get($uid = 'me', $note_ids = null)
    {
        // Session key is *required*
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'session_key is required',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        if (empty($note_ids)) {
            return $this->_facebook->callGraphApi($uid . '/notes');
        }

        return $this->_facebook->callMethod('', array('ids' => $note_ids));
    }

}