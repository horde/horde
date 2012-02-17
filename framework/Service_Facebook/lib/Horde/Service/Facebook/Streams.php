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
     * Get a user's stream
     * http://wiki.developers.facebook.com/index.php/Stream.get
     *
     * Note: This requires the READ_STREAM extended permission to be added for
     *       the application.
     *
     * @param string $viewerId  The user id or page id of the page whose stream
     *                          to read.
     * @param array     $sourceIds
     * @param timestamp $start
     * @param timestamp $end
     * @param integer   $limit
     * @param string    $filterKey
     *
     * @return mixed Method call results.
     * @deprecated by the Graph API
     */
    function &get($viewerId = '', $sourceIds = array(), $start = '', $end = '',
                  $limit = '', $filterKey = '')
    {
        if (empty($viewerId) && !$session_key = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.publish requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }
        $params = array('viewer_id' => $viewerId,
                  'source_ids' => $sourceIds,
                  'start_time' => $start,
                  'end_time' => $end,
                  'filter_key' => $filterKey,
                  'limit' => $limit);

        return $this->_facebook->callMethod('Stream.get', $params);
    }

    /**
     * Get a post's comments. Note that the owner of the post that is being
     * retrieved must have the application authorized.
     *
     * @param string $postId  The post id of the post whose comments we are
     *                        retrieving.
     *
     * @return mixed
     */
    function &getComments($postId)
    {
        return $this->_facebook->callMethod(
            'Stream.getComments', array('post_id' => $postId));
    }

    /**
     * Get a user's stream filter.
     *
     * http://wiki.developers.facebook.com/index.php/Stream.getFilters
     *
     * @param integer $uid  The user id of whose filters we are requesting.
     *
     * @return Array of filter data.
     */
    function getFilters($uid = '')
    {
        if (empty($uid) && !$session_key = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.getFilters requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        if (!empty($uid)) {
            $params = array('uid' => $uid);
        } else {
            $params = array();
        }

        return $this->_facebook->callMethod('Streams.getFilters', $params);
    }

    /**
     * Publish into a user's stream
     *
     * http://wiki.developers.facebook.com/index.php/Stream.publish
     *
     * @param string $message       The string of the message to post.
     * @param array  $attachment    An array describing the item we are publishing
     *                              see the API docs.
     * @param array  $action_links  Array of action links.
     * @param string $target_id     The id of user/page you are publishing to.
     * @param string $uid           The id of user publishing the post.
     * @param array  $privacy       The privacy settings for the post.
     * - value: (string) The privacy value for the object, specify one of
     *     EVERYONE, CUSTOM, ALL_FRIENDS, NETWORKS_FRIENDS, FRIENDS_OF_FRIENDS,
     *     SELF.
     * - friends: (string) For CUSTOM settings, this indicates which users can
     *     see the object. Can be one of EVERYONE, NETWORKS_FRIENDS (when the
     *     object can be seen by networks and friends), FRIENDS_OF_FRIENDS,
     *     ALL_FRIENDS, SOME_FRIENDS, SELF, or NO_FRIENDS (when the object can
     *     be seen by a network only).
     * - networks: (string) For CUSTOM settings, specify a comma-separated list
     *     of network IDs that can see the object, or 1 for all of a user's
     *     networks.
     * - allow: (string) When friends is set to SOME_FRIENDS, specify a
     *     comma-separated list of user IDs and friend list IDs that ''can'' see
     *     the post.
     * - deny: (string) When friends is set to SOME_FRIENDS, specify a
     *     comma-separated list of user IDs and friend list IDs that ''cannot''
     *     see the post.
     *
     * @return mixed
     */
    function publish($message = '', $attachment = array(), $action_links = '',
                     $target_id = '', $uid = '', $privacy = array())
    {
        if (empty($uid) && !$session_key = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.publish requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        $params = array('message' => $message,
                        'action_links' => $action_links,
                        'target_id' => $target_id);
        if (!empty($uid)) {
            $params['uid'] = $uid;
        }
        if (!empty($attachment)) {
            $params['attachment'] = json_encode($attachment);
        }
        if (!empty($privacy)) {
            $privacy_object = new stdClass;
            if (isset($privacy['value'])) {
                $privacy_object->value = $privacy['value'];
            } else {
                $privacy_object->value = 'EVERYONE';
            }
            foreach (array('friends', 'networks', 'allow', 'deny') as $setting) {
                if (isset($privacy[$setting])) {
                    $privacy_object->{$setting} = $privacy[$setting];
                }
            }
            $params['privacy'] = json_encode($privacy_object);
        }
        return $this->_facebook->callMethod('Stream.publish', $params);
    }

    /**
     * Remove a post from a user's stream
     *
     * @param string $postId  The post id
     * @param string $uid      The user id
     *
     * @return mixed
     */
    function remove($postId, $uid = '')
    {
        if (empty($uid) && !$session_key = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.remove requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        $params = array('post_id' => $postId);
        if (!empty($uid)) {
            $params['uid'] = $uid;
        }

        return $this->_facebook->callMethod('Stream.remove', $params);
    }

    /**
     * Add a comment to a user's post.
     *
     * @param string $postId  The post id the comment belongs to
     * @param string $comment  The body of the comment (text only, no HTML).
     * @param string $uid      The user id of the user who is posting the
     *                         comment.
     *
     * @return string The comment id of the posted comment.
     */
    function addComment($postId, $comment, $uid = '')
    {
        if (empty($uid) && !$session_key = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.addComment requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        $params = array('post_id' => $postId,
                        'comment' => $comment);

        if (!empty($uid)) {
            $params['uid'] = $uid;
        }

        return $this->_facebook->callMethod('Stream.addComment', $params);
    }

    /**
     * Remove a comment from a post.
     *
     * @param string $commentId  The comment id to remove.
     * @param string $uid         User id
     *
     * @return boolean
     */
    function removeComment($commentId, $uid = '')
    {
        if (empty($uid) && !$session_key = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.removeComment requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        $params = array('comment_id' => $commentId);
        if (!empty($uid)) {
            $params['uid'] = $uid;
        }

        return $this->_facebook->callMethod('Stream.removeComment', $params);
    }

    /**
     * Add a "like" to a post.
     *
     * @param string $postId
     * @param string $uid
     *
     * @return mixed
     */
    function addLike($postId, $uid = '')
    {
        if (empty($uid) && !$session_key = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.addLike requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        $params = array('post_id' => $postId);
        if (!empty($uid)) {
            $params['uid'] = $uid;
        }

        return $this->_facebook->callMethod('Stream.addLike', $params);
    }

    /**
     * Remove a "like" from a stream post.
     *
     * @param string $postId  The post id to remove a like from.
     * @param string $uid     The user id who the like belongs to.
     *
     * @return boolean
     */
    function removeLike($postId, $uid = '')
    {
        if (empty($uid) && !$session_key = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'Streams.removeLike requires either a uid or a session_key',
                Horde_Service_Facebook_ErrorCodes::API_EC_PARAM_SESSION_KEY);
        }

        $params = array('post_id' => $postId);
        if (!empty($uid)) {
            $params['uid'] = $uid;
        }

        return $this->_facebook->callMethod('Stream.removeLike', $params);
    }

}