<?php
/**
 * Horde_Service_Twitter_Request_* classes wrap sending requests to Twitter's
 * REST API using various authentication mechanisms.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Service_Twitter
 */
abstract class Horde_Service_Twitter_Request
{
    /**
     *
     * @var Horde_Service_Twitter
     */
    protected $_twitter;

    /**
     * 
     * @var Horde_Controller_Request_Http
     */
    protected $_request;

    public function __construct(Horde_Controller_Request_Http $request)
    {
        $this->_request = $request;
    }

    public function setTwitter(Horde_Service_Twitter $twitter)
    {
        $this->_twitter = $twitter;
    }

    abstract public function get($url, $params = array());
    abstract public function post($url, $params = array());

}
