<?php
/**
 * Photos methods for Horde_Service_Facebook
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Photos extends Horde_Service_Facebook_Base
{
    /**
     * Adds a tag with the given information to a photo. See the wiki for details:
     *
     *  http://wiki.developers.facebook.com/index.php/Photos.addTag
     *
     * @param string $pid  The ID of the photo to be tagged
     * @param array $options  An options array:
     *   - to: (string)    A UID of the user being tagged.
     *   - text: (string)  Text to name the user if UID is not known/available.
     *   - x: (float)  The horizontal position of the tag as a percentage from
     *                 the left of the photo.
     *   - y: (float)  The vertical position of the tag as a percentage from the
     *                 top of the photo.
     *
     *
     * @return boolean
     */
    public function addTag($pid, array $options = array())
    {
        // Requires either a owner_uid or a session_key
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'photos.addTag requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->callGraphApi(
            $pid,
            $options,
            array('request' => 'POST'));

        return $results;
    }

    /**
     * Creates and returns a new album owned by the specified user or the current
     * session user.
     *
     * @param string $name         The name of the album.
     * @param string $description  (Optional) A description of the album.
     * @param string $uid         (Optional) User id for creating the album; if
     *                             not specified, the session user is used.
     *
     * @return array  An album object
     */
    public function createAlbum($name, $description = '', $uid = 'me')
    {
        // Requires either a owner_uid or a session_key
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'photos.addTag requires either a owner_uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }
        return $this->_facebook->callGraphApi(
            $uid . '/albums',
            array('name' => $name, 'message' => $description),
            array('request' => 'POST'));
    }

    /**
     * Returns photos according to the filters specified.
     *
     * @param array $filter  An options array containing a maximum of ONE of
     *                       the following values:
     *  - tagged:  Filter by photos tagged with this user.
     *  - album:   Filter by photos in these albums.
     *  - photos:  Only return indicated photos.
     *
     * @param array $options  Additional options:
     *   - limit: (integer)  The maximum number of posts to return.
     *   - offset: (integer)  The post to start returning from.
     *
     * @return array  An array of photo objects.
     */
    public function get(array $filter = array(), array $options = array())
    {
        // Requires a session_key
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'photos.addTag requires a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }


        $params = array();
        if (!empty($filter['tagged'])) {
            $uid = $filter['tagged'] . '/photos';
        } elseif (!empty($filter['album'])) {
            $uid = $filter['album'];
        } elseif (!empty($filter['photos'])) {
            $uid = '';
            $params = array('ids' => $filter['photos']);
        } else {
            $uid = 'me/photos';
        }
        $params = array_merge($options, $params);

        return $this->_facebook->callGraphApi($uid, $params);
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
    public function getAlbums($uid = 'me', $aids = null)
    {
        // Requires a session_key
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'photos.addTag requires a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }
        if (empty($aids)) {
            $params = array();
            $uid = $uid . '/albums';
        } else {
            $params = array('ids' => $aids);
            $uid = '';
        }

        return $this->_facebook->callGraphApi($uid, $params);
    }

    /**
     * Return the tags for a photo.
     *
     * @param string $pid The photo id
     *
     * @return array  An array of photo tag objects, which include pid,
     *                subject uid, and two floating-point numbers (xcoord, ycoord)
     *                for tag pixel location.
     */
    public function getTags($pid)
    {
        // Requires a session_key
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'photos.addTag requires a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        return $this->_facebook->graphApi($pid . '/tags');
    }

    /**
     * Uploads a photo.
     *
     * @param array $params  The parameter array.
     *  - file: (string)  A local path to the file to upload.
     *         DEFAULT: None, but either 'file' or 'url' is required.
     *  - url: (string) A URL to an image to upload.
     *        DEFAULT: None, but either 'file' or 'url' is required.
     *  - aid: (string)  The album id.
     *         DEFAULT: None (Will upload to the application's album).
     *  - caption: (string)  The photo caption.
     *             DEFAULT: None.
     *  - place: (string)  A Facebook UID of the place the photo was taken near.
     *           DEFAULT: None.
     *  - uid: (string) The Facebook UID of the user we are uploading on behalf
     *                  of.
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

        // Defaults
        $params = array_merge(
            array(
                'caption' => '',
                'aid' => '',
                'place' => ''
            ),
            $params
        );

        // Build the data to send.
        $data = array(
            'message' => $params['caption'],
            'place' => $params['place']
        );

        // Uploading to the application gallery or other?
        if (!empty($params['aid'])) {
            $uid = $params['aid'] . '/photos';
        } else {
            $uid = empty($params['uid']) ? 'me/photos' : $params['uid'] . '/photos';
        }

        // Uploading image or providing URL?
        if (!empty($params['file'])) {
            $request = new Horde_Service_Facebook_Request_Graph($this->_facebook, $uid);
            return $request->upload(array('params' => $data, 'file' => $params['file']));
        } elseif (!empty($params['url'])) {
            $data['url'] = $params['url'];
        }

        return $this->_facebook->callGraphApi(
            $uid,
            $data,
            array('request' => 'POST')
        );
    }

}