<?php
/**
 * Base class for all Horde_Service_Framework_* classes
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
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
     * @param Horde_Service_Facebook         $facebook
     * @param Horde_Service_Facebook_Request $request
     * @param array                          $params
     */
    public function __construct($facebook, $request, $params = array())
    {
        $this->_facebook = $facebook;
        $this->_request = $request;
    }


}