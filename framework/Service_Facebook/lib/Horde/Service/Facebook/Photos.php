<?php
/**
 * Photos methods for Horde_Service_Facebook
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_Photos extends Horde_Service_Facebook_Base
{
    /**
     * Adds a tag with the given information to a photo. See the wiki for details:
     *
     *  http://wiki.developers.facebook.com/index.php/Photos.addTag
     *
     * @param integer $pid      The ID of the photo to be tagged
     * @param integer $tag_uid  The ID of the user being tagged. You must specify
     *                          either the $tag_uid or the $tag_text parameter
     *                          (unless $tags is specified).
     * @param string $tag_text  Some text identifying the person being tagged.
     *                          You must specify either the $tag_uid or $tag_text
     *                          parameter (unless $tags is specified).
     * @param float $x          The horizontal position of the tag, as a
     *                          percentage from 0 to 100, from the left of the
     *                          photo.
     * @param float $y          The vertical position of the tag, as a percentage
     *                          from 0 to 100, from the top of the photo.
     * @param array $tags       (Optional) An array of maps, where each map
     *                          can contain the tag_uid, tag_text, x, and y
     *                          parameters defined above.  If specified, the
     *                          individual arguments are ignored.
     * @param integer $owner_uid    (Optional)  The user ID of the user whose photo
     *                              you are tagging. If this parameter is not
     *                              specified, then it defaults to the session user.
     *
     * @return boolean
     */
    public function &addTag($pid, $tag_uid, $tag_text, $x, $y, $tags, $uid = 0)
    {
        // Requires either a owner_uid or a session_key
        if (empty($uid) && !$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('photos.addTag requires either a uid or a session_key',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        $params = array('pid' => $pid,
                  'tag_uid' => $tag_uid,
                  'tag_text' => $tag_text,
                  'x' => $x,
                  'y' => $y,
                  'tags' => (is_array($tags)) ? json_encode($tags) : null);
        if (!empty($owner_uid)) {
            $params['owner_uid'] = $uid;
        }
        if ($skey = $this->_facebook->auth->getSessionKey()) {
            $params['session_key'] = $skey;
        }

        $results = $this->_facebook->callMethod('facebook.photos.addTag', $params);

        return $results;

    }

    /**
     * Creates and returns a new album owned by the specified user or the current
     * session user.
     *
     * @param string $name         The name of the album.
     * @param string $description  (Optional) A description of the album.
     * @param string $location     (Optional) A description of the location.
     * @param string $visible      (Optional) A privacy setting for the album.
     *                             One of 'friends', 'friends-of-friends',
     *                             'networks', or 'everyone'.  Default 'everyone'.
     * @param integer $uid         (Optional) User id for creating the album; if
     *                             not specified, the session user is used.
     *
     * @return array  An album object
     */
    public function &createAlbum($name, $description = '', $location = '', $visible = '', $uid = 0)
    {
        // Requires either a owner_uid or a session_key
        if (empty($owner_uid) && !$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('photos.addTag requires either a owner_uid or a session_key',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        $params = array('name' => $name,
                  'description' => $description,
                  'location' => $location,
                  'visible' => $visible);

        // Yes, this method uses 'uid' while some of the others use
        // 'owner_uid' - don't ask me...
        if (!empty($uid)) {
            $params['uid'] = $uid;
        }

        if ($skey = $this->_facebook->auth->getSessionKey()) {
            $params['session_key'] = $skey;
        }

        $results = $this->_facebook->callMethod('facebook.photos.createAlbum', $params);

        return $results;
    }

    /**
     * Returns photos according to the filters specified.
     *
     * Note that at least one of these parameters needs to be specified, or an
     * error is returned.
     *
     * @param integer $subj_id  (Optional) Filter by uid of user tagged in the photos.
     * @param integer $aid      (Optional) Filter by an album, as returned by
     *                          photos_getAlbums.
     * @param string $pids      (Optional) Restrict to a comma-separated list of pids
     *
     * @return array  An array of photo objects.
     */
    public function &get($subj_id = null, $aid = null, $pids = null)
    {
        // Requires a session_key
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('photos.addTag requires a session_key',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        $params = array('session_key' => $skey);
        if ($subj_id) {
            $params['subj_id'] = $subj_id;
        }

        if ($aid) {
            $params['aid'] = $aid;
        }

        if ($pids) {
            $params['pids'] = $pids;
        }

        $results = $this->_facebook->callMethod('facebook.photos.get', $params);

        return $results;
    }

    /**
     * Returns the albums created by the given user.
     *
     * Note that at least one of the (uid, aids) parameters must be specified.
     *
     * @param integer $uid  (Optional) The uid of the user whose albums you want.
     *                      A null will return the albums of the session user.
     * @param string $aids  (Optional) A comma-separated list of aids to restricti
     *                      the query.
     *
     * @return array of album objects.
     */
    public function &getAlbums($uid = null, $aids = null)
    {
        // Requires a session_key
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('photos.addTag requires a session_key',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        $results = $this->_facebook->callMethod('facebook.photos.getAlbums',
                                                 array('uid' => $uid,
                                                       'aids' => $aids,
                                                       'session_key' => $skey));
       return $results;
    }

    /**
     * Returns the tags on all photos specified.
     *
     * @param string $pids  A list of pids to query
     *
     * @return array  An array of photo tag objects, which include pid,
     *                subject uid, and two floating-point numbers (xcoord, ycoord)
     *                for tag pixel location.
     */
    public function &getTags($pids)
    {
        // Requires a session_key
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('photos.addTag requires a session_key',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        $results = $this->_facebook->callMethod('facebook.photos.getTags', array('pids' => $pids, 'session_key' => $skey));

        return $results;
    }

    /**
     * Uploads a photo.
     *
     * @param string  $file     The location of the photo on the local filesystem.
     * @param integer $aid      (Optional) The album into which to upload the
     *                          photo.
     * @param string  $caption  (Optional) A caption for the photo.
     * @param integer $uid      (Optional) The user ID of the user whose photo you
     *                          are uploading
     *
     * @return array  An array of user objects
     */
    public function upload($file, $aid = null, $caption = null, $uid = null)
    {
        // Requires either a owner_uid or a session_key
        if (empty($uid) && !$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('photos.addTag requires either a uid or a session_key',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        $params = array('aid' => $aid, 'caption' => $caption);
        if (!empty($uid)) {
            $params['uid'] = $uid;
        }

        if (!empty($skey)) {
            $params['session_key'] = $skey;
        }

        $results = $this->_facebook->callUploadMethod('facebook.photos.upload', $params, $file);

        return $results;
    }

}