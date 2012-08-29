<?php
/**
 * Friends methods for Horde_Service_Facebook
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Friends extends Horde_Service_Facebook_Base
{
    /**
     * Returns whether or not pairs of users are friends.
     * Note that the Facebook friend relationship is symmetric.
     *
     * @param string $uid        user id to check
     * @param string $friend_id  Id of user to check for friend status.
     *
     * @return boolean
     */
    public function areFriends($uid, $friend_id)
    {
        $results = $this->_facebook->callGraphApi($uid . '/friends/' . $friend_id);

        return !empty($results->data);
    }

    /**
     * Returns the friends of the current session user.
     *
     * @param string $uid   The uid to obtain friends for.
     * @param string $list  Return only friends in the specified list.
     *
     * @return array  An array of friend objects containing 'name' and 'id'
     *                properties.
     */
    public function get($uid, $list = null)
    {
        if (!empty($list)) {
            return $this->_facebook->callGraphApi($list);
        }

        return $this->_facebook->callGraphApi($uid . '/friends');
    }

    /**
     * Returns the set of friend lists for the current session user.
     *
     * @param string $uid  The uid to obtain friend lists for.
     *
     * @return array  An array of friend list objects
     */
    public function getLists($uid)
    {
        return $this->_facebook->callGraphApi($uid . '/friendlists');
    }

}