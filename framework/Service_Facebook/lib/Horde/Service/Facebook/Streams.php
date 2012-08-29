<?php
/**
 * Open streams API
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Streams extends Horde_Service_Facebook_Base
{

    /**
     * Get a specific post.
     *
     * @param string  The post UID.
     *
     * @return object  The post object.
     */
    public function getPost($uid)
    {
        return $this->_facebook->callGraphApi($uid);
    }

    /**
     * Get a user's wall stream
     *
     * @param string $uid  The user id.
     *
     * @return mixed Method call results.
     */
    public function getWall($uid = '')
    {
        if (empty($uid) && !$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.get requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        if (empty($uid)) {
            $uid = 'me';
        }

        return $this->_facebook->callGraphApi($uid . '/feed');
    }

    /**
     * Return the logged in user's news stream.
     *
     * @param string $filter  A named stream filter to apply.
     * @param array $options  Additional options:
     *   - limit: (integer)  The maximum number of posts to return.
     *   - offset: (integer)  The post to start returning from.
     *   - until: (timestamp) Do not return posts after this timestamp.
     *   - since: (timestamp) Do not return posts before this timestamp.
     *
     * @return object
     */
    public function getStream($filter = null, array $options = array())
    {
        if (!$session_key = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.get requires a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }
        if (!empty($filter)) {
            $options['filter'] = $filter;
        }

        return $this->_facebook->callGraphApi('me/home', $options);
    }

    /**
     * Get a user's stream filter.
     *
     * @param integer $uid  The user id of whose filters we are requesting.
     *
     * @return array of filter data.
     */
    public function getFilters($uid)
    {
        if (empty($uid) || !$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.getFilters requires a uid and a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        $fql = 'SELECT filter_key, name FROM stream_filter WHERE uid="'
                . $uid . '"';

        return $this->_facebook->fql->run($fql);
    }

    /**
     * Post a message to a user's stream.
     *
     * @param string $uid      The user id of the user whose stream we are
     *                         posting the message to.
     * @param string $message  The message body to post.
     * @param array $options   Additional post options:
     *   - link: (string)  A link to attach to this post.
     *   - picture (string) A URL to a thumbnail image to use for this post if
     *             link is set.
     *   - name: (string)  A name for the post if link is set.
     *   - caption: (string) The caption, if link is set.
     *   - description: (string) A description, if link is specified.
     *   - actions: (array) An array of actions containing name and link (?).
     *   - place: (string) Facebook page id of the location associated with post.
     *   - tags: (string) Comma delimted list of Facebook ids of people tagged
     *           in this post. Requires place tags to be passed also.
     *   - privacy: (string) Privacy settings (if posting to the current user's
     *              stream only). This is a JSON encoded object that defines
     *              the privacy settings.
     *              See https://developers.facebook.com/docs/reference/api/user/#posts
     *   -object_attachment: (string)  The Facebook id for an existing picture
     *                       in the user's photo albums to use as the thumbnail
     *                       image. User must be the owner of the photo.
     *
     * @return string  The UID of the new post.
     */
    public function post($uid, $message, array $options = array())
    {
        $options['message'] = $message;
        $results = $this->_facebook->callGraphApi(
            $uid . '/feed',
            $options,
            array('request' => 'POST'));

        return $results->id;
    }

    /**
     * Remove a post from a user's stream
     *
     * @param string $postId  The post id
     *
     * @return boolean
     */
    public function delete($postid)
    {
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.remove requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        return $this->_facebook->callGraphApi(
            $postid,
            array(),
            array('request' => 'DELETE'));
    }

    /**
     * Add a comment to a user's post.
     *
     * @param string $postId  The post id the comment belongs to
     * @param string $comment  The body of the comment (text only, no HTML).
     *
     * @return string The comment id of the posted comment.
     */
    public function addComment($postId, $comment)
    {
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.addComment requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        return $this->_facebook->callGraphApi(
            $postId . '/comments',
            array('message' => $comment),
            array('request' => 'POST'));
    }

    /**
     * Remove a comment from a post.
     *
     * @param string $commentId  The comment id to remove.
     *
     * @return boolean
     */
    public function removeComment($commentId)
    {
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.removeComment requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        return $this->_facebook->callGraphApi(
            $commentId,
            array(),
            array('request' => 'DELETE'));
    }

    /**
     * Add a "like" to a post.
     *
     * @param string $postId
     *
     * @return boolean
     */
    public function addLike($postId)
    {
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.addLike requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        return $this->_facebook->callGraphApi(
            $postId . '/likes',
            array(),
            array('request' => 'POST'));
    }

    /**
     * Remove a "like" from a stream post.
     *
     * @param string $postId  The post id to remove a like from.
     *
     * @return boolean
     */
    public function removeLike($postId)
    {
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.removeLike requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        return $this->_facebook->callGraphApi(
            $postId . '/likes',
            array(),
            array('request' => 'DELETE'));
    }

}