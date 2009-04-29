<?php
/**
 * Open streams API
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_Streams extends Horde_Service_Facebook_Base
{
    /**
     * Get a user's stream
     * http://wiki.developers.facebook.com/index.php/Stream.get
     *
     */
    function &get($viewerId, $sourceIds = array(), $start = '', $end = '',
                 $limit = '', $filterKey = '')
    {
        return $this->_facebook->callMethod('Stream.get',
            array('viewer_id' => $viewerId,
                  'source_ids' => $sourceIds,
                  'start_time' => $start,
                  'end_time' => $end,
                  'filter_key' => $filterKey));
    }

    /**
     * Get a post's comments.
     *
     *
     */
    function &getComments($post_id)
    {

    }

    /**
     * Get a user's stream filter.
     *
     * http://wiki.developers.facebook.com/index.php/Stream.getFilters
     *
     * @param integer $uid  The user id of whose filters we are requesting.
     */
    function getFilters($uid)
    {

    }

    /**
     * Publish into a user's stream
     *
     * http://wiki.developers.facebook.com/index.php/Stream.publish
     *
     * @param string $message       The string of the message to post.
     * @param string $attachment    An array describing the item we are publishing
     *                              see the API docs.
     * @param string $action_links  Array of action links.
     * @param string $target_id     The id of user/page you are publishing to.
     */
    function publish($message = '', $attachment = '', $action_links = '', $target_id = '')
    {

    }

    /**
     * Remove a post from a user's stream
     *
     * @param string $post_id  The post id
     * @param string $uid      The user id
     *
     * @return unknown_type
     */
    function remove($post_id, $uid = '')
    {

    }

    /**
     *
     * @param string $post_id  The post id the comment belongs to
     * @param string $comment  The body of the comment.
     * @param string $uid
     *
     * @return unknown_type
     */
    function addComment($post_id, $comment, $uid = '')
    {
    }

    /**
     * Remove a comment from a post.
     *
     * @param string $comment_id  The comment id to remove.
     * @param string $uid         User id
     *
     * @return unknown_type
     */
    function removeComment($comment_id, $uid = '')
    {
    }

    /**
     * Add a "like" to a post.
     *
     * @param string $post_id
     * @param string $uid
     *
     * @return unknown_type
     */
    function addLike($post_id, $uid = '')
    {
    }

    /**
     * Remove a "like" from a stream post.
     *
     * @param string $post_id
     * @param string $uid
     *
     * @return unknown_type
     */
    function removeLike($post_id, $uid = '')
    {

    }

}