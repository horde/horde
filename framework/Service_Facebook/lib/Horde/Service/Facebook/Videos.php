<?php
/**
 * Videos methods
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Videos extends Horde_Service_Facebook_Base
{
    /**
     * Uploads a video.
     *
     * @param array $params  The parameter array.
     *  - file: (string)  A local path to the file to upload.
     *         DEFAULT: none REQUIRED
     *  - caption: (string)  The photo caption.
     *             DEFAULT: None.
     *  - uid: (string) The Facebook UID of where to post the video to. Normally
     *         a user id.
     *         DEFAULT: None (Will upload on behalf of the current user).
     * @return array  An array of user objects
     */
    public function upload(array $params = array())
    {
        // Requires either a owner_uid or a session_key
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'photos.addTag requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        if (empty($params['file'])) {
            throw new InvalidArgumentException('Missing required file parameter.');
        }

        // Build the data to send.
        $data = array(
            'message' => empty($params['caption']) ? '' : $params['caption']
        );

        $uid = empty($params['uid']) ? 'me/videos' : $params['uid'] . '/videos';
        $request = new Horde_Service_Facebook_Request_Graph($this->_facebook, $uid);

        return $request->upload(array('params' => $data, 'file' => $params['file']));
    }

}