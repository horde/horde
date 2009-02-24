<?php
/**
 * Base class for all Horde_Service_Framework_* classes
 */
abstract class Horde_Service_Facebook_Base
{
    private $_facebook;
    private $_request;
    private $_sessionKey;

    /**
     *
     * @param $facebook
     * @param $request
     * @param $params
     * @return unknown_type
     */
    public function __construct($facebook, $request, $params = array())
    {
        $this->_facebook = $facebook;
        $this->_request = $request;
        $this->_sessionKey = $facebook->auth->getSessionKey();
    }


}