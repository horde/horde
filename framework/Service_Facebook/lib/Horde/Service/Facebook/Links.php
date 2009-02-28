<?php
/**
 * Links methods
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_Links extends Horde_Service_Facebook_Base
{
    /**
     * Retrieves links posted by the given user.
     *
     * @param int    $uid      The user whose links you wish to retrieve
     * @param int    $limit    The maximimum number of links to retrieve
     * @param array $link_ids (Optional) Array of specific link
     *                          IDs to retrieve by this user
     *
     * @return array  An array of links.
     */
    public function &get($uid, $limit, $link_ids = null)
    {
        // Require a session
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }
        return $this->_facebook->call_method('links.get',
            array('uid' => $uid,
                  'limit' => $limit,
                  'link_ids' => json_encode($link_ids),
                  'session_key' => $skey));
    }

    /**
     * Posts a link on Facebook.
     *
     * @param string $url     URL/link you wish to post
     * @param string $comment (Optional) A comment about this link
     * @param int    $uid     (Optional) User ID that is posting this link;
     *                        defaults to current session user
     *
     * @return bool
     */
    public function &post($url, $comment = '', $uid = null)
    {
        // Require a session
        if (!$skey = $this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception('session_key is required',
                                               Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }
        return $this->_facebook->call_method('links.post',
            array('uid' => $uid,
                  'url' => $url,
                  'comment' => $comment,
                  'session_key' => $skey));
    }

}