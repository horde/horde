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

    public function verifyCredentials()
    {
        $url = $this->_endpoint . 'verify_credentials.' . $this->_format;
        return $this->_twitter->request->get($url);
    }

}
