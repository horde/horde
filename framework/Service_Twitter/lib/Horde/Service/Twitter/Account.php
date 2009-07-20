<?php
/**
 * Horde_Service_Twitter_Account class for calling account methods
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Horde_Service_Twitter
 */
class Horde_Service_Twitter_Account
{
    protected $_endpoint = 'http://twitter.com/account/';
    protected $_format = 'json';

    public function __construct($twitter)
    {
        $this->_twitter = $twitter;
    }

    /**
     * Used to verify current credentials, and obtain some basic profile
     * information about the current user.
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-account%C2%A0verify_credentials
     *
     * @return string  JSON reprentation of profile.
     */
    public function verifyCredentials()
    {
        $url = $this->_endpoint . 'verify_credentials.' . $this->_format;
        return $this->_twitter->request->get($url);
    }

    /**
     * Obtain the current user's (if authenticated) or IP address' (if not
     * authenticated) remaining number of requests left for the hour.
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-account%C2%A0rate_limit_status
     *
     * @return string  JSON representation of result object.
     */
    public function rateLimitStatus()
    {
        $url = $this->_endpoint . 'rate_limit_status.' . $this->_format;
        return $this->_twitter->request->get($url);
    }

    /**
     * Ends the current session.
     *
     * @TODO: Verify that this invalidates any OAuth keys/secrets
     *
     */
    public function endSession()
    {
        $url = $this->_endpoint . 'end_session.' . $this->_format;
        return $this->_twitter->request->post($url);
    }

    /**
     * Update/reset where twitter sends automatic updates to
     * (im/sms etc...)
     *
     * @param $device
     * @return unknown_type
     */
    public function updateDeliveryDevice($device = '')
    {
    }

    /**
     * Update user's profile data.
     *
     * http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-account%C2%A0update_profile
     *
     * @param array $profile  Profile data see API docs for key-values
     *
     * @return string  JSON representation of user's updated profile data
     */
    public function updateProfile($profile)
    {
    }

}
