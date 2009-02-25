<?php
/**
 * Base class for all Horde_Service_Framework_* classes
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
abstract class Horde_Service_Facebook_Base
{
    protected $_facebook;
    protected $_request;
    protected $_sessionKey;

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
    }


}