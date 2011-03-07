<?php
/**
 * Videos methods
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
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
     * @param  string $file        The location of the video on the local filesystem.
     * @param  string $title       (Optional) A title for the video. Titles over 65 characters in length will be truncated.
     * @param  string $description (Optional) A description for the video.
     *
     * @return array  An array with the video's ID, title, description, and a link to view it on Facebook.
     */
    public function upload($file, $title = null, $description = null)
    {
        // Session key is *required*
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->call_upload_method('facebook.video.upload',
            array('title' => $title,
                  'description' => $description,
                  'session_key' => $skey),
            $file,
            Horde_Service_Facebook::getFacebookUrl('api-video') . '/restserver.php');
    }

    /**
     * Returns an array with the video limitations imposed on the current session's
     * associated user. Maximum length is measured in seconds; maximum size is
     * measured in bytes.
     *
     * @return array  Array with "length" and "size" keys
     */
    public function &getUploadLimits()
    {
        // Session key is *required*
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callMethod('facebook.video.getUploadLimits',
            array('session_key' => $skey));
    }

}