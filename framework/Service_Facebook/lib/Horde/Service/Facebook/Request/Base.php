<?php
/**
 * Horde_Service_Facebook_Request:: encapsulate a request to the Facebook API.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
abstract class Horde_Service_Facebook_Request_Base
{
    /**
     *
     * @var Horde_Service_Facebook
     */
    protected $_facebook;

    /**
     *
     * @var Horde_Http_Client
     */
    protected $_http;

    /**
     * The current method being processed.
     *
     * @var string
     */
    protected $_method;

    /**
     * The method parameters for the current method call.
     *
     * @var array
     */
    protected $_params;

    /**
     * Const'r
     *
     * @param Horde_Service_Facebook $facebook
     * @param string                 $method
     * @param array                  $params
     *
     */
    public function __construct($facebook, $method, array $params = array())
    {
        $this->_facebook = $facebook;
        $this->_http = $facebook->http;
        $this->_method = $method;
        $this->_params = $params;
    }

    /**
     * Run this request and return the data.
     *
     * @return array  The results of the request.
     *
     * @throws Horde_Service_Facebook_Exception
     */
    abstract public function run();

    /**
     * Perform a multipart/form-data upload.
     *
     * @param array $options  An options array:
     *   - params: (array) Form parameters to pass
     *   - file: (string) Local path to the file
     */
    abstract public function upload(array $options = array());
}