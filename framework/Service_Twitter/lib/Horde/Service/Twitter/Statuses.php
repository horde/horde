<?php
/**
 * Horde_Service_Twitter_Statuses class for updating user statuses.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Horde_Service_Twitter
 */
class Horde_Service_Twitter_Statuses
{

    private $_endpoint = 'http://twitter.com/statuses/';
    private $_format = 'json';

    public function __construct($twitter)
    {
        $this->_twitter = $twitter;
    }

    /**
     * Obtain the requested status
     *
     * @return unknown_type
     */
    public function show($id)
    {
        $url = $this->_endpoint . 'show.' . $this->_format;
        return $this->_twitter->request->post($url, array('id' => $id));
    }

    /**
     * Destroy the specified status update, obviously only if the current user
     * is the author of the update.
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses%C2%A0destroy
     *
     * @param string $id  The status id
     *
     * @return string
     */
    public function destroy($id)
    {
        $url = $this->_endpoint . 'destroy.' . $this->_format;
        return $this->_twitter->request->post($url, array('id' => $id));
    }

    /**
     * Update the current user's status.
     *
     * @param string $status  The new status text.
     * @param string $replyTo If specified, *and* the text of the status contains
     *                        a mention of the author of the replied to status
     *                        (i.e. `@username`) this update will be "in reply to"
     *                        the specifed status message id.
     *
     * @return string
     */
    public function update($status, $replyTo = '')
    {
        $url = $this->_endpoint . 'update.' . $this->_format;
        $params = array('status' => $status);
        if (!empty($replyTo)) {
            $params['in_reply_to_status_id'] = $replyTo;
        }

        return $this->_twitter->request->post($url, $params);
    }

    public function friendsTimeline($params = array())
    {
        $url = $this->_endpoint . 'friends_timeline.' . $this->_format;
        return $this->_twitter->request->get($url);
    }

}
