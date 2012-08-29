<?php
/**
 * Links methods
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Links extends Horde_Service_Facebook_Base
{
    /**
     * Retrieves links posted by the given user.
     *
     * @param integer    $uid  The user whose links you wish to retrieve
     * @param array  $options  An options array:
     *   - limit: (integer)  The maximum number of posts to return.
     *   - offset: (integer)  The post to start returning from.
     *   - ids: (array) Only return these specfic links.
     *
     * @return array  An array of links.
     */
    public function get($uid = null, array $options = array())
    {
        // Require a session
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'session_key is required',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }

        if (empty($uid)) {
            $uid = 'me';
        }
        if (!empty($options['ids'])) {
            $options['ids'] = implode(',', $options['ids']);
            return $this->_facebook->callGraphApi(
                '',
                $options);
        }

        return $this->_facebook->callGraphApi(
            $uid . '/links',
            $options);
    }

    /**
     * Posts a link on Facebook.
     *
     * @param string  $link   URL/link you wish to post
     * @param integer $uid    User ID that is posting this link
     * @param array $options  Additional post options:
     *   - message: (string) A message to attach to the link.
     *   - picture (string) A URL to a thumbnail image to use for this post if
     *             link is set.
     *   - name: (string)  A name for the post if link is set.
     *   - caption: (string) The caption, if link is set.
     *   - description: (string) A description, if link is specified.
     *
     * @return boolean
     */
    public function post($link, $uid = null, array $options = array())
    {
        if (!$this->_facebook->auth->getSessionKey()) {
            throw new Horde_Service_Facebook_Exception(
                'session_key is required',
                Horde_Service_Facebook_ErrorCodes::API_EC_SESSION_REQUIRED);
        }
        if (empty($uid)) {
            $uid = 'me';
        }
        $options['link'] = $link;

        return $this->_facebook->callGraphApi(
            $uid . '/links',
            $options,
            array('request' => 'POST'));
    }

}