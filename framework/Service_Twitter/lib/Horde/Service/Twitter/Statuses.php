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

    public function __construct($twitter, $oauth)
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

    }

    public function update($status)
    {
        $url = 'http://twitter.com/statuses/update.json';
        return $this->_twitter->postRequest($url, array('status' => $status));
    }
}
