<?php
/**
 * Horde_Service_Facebook class abstracts communication with Facebook's
 * rest interface.
 *
 * This code was originally a Hordified version of Facebook's official PHP
 * client. However, since that client was very buggy and incomplete, very little
 * of the original code or design is left. I left the original copyright notice
 * intact below.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */

/**
 * Facebook Platform PHP5 client
 *
 * Copyright 2004-2009 Facebook. All Rights Reserved.
 *
 * Copyright (c) 2007 Facebook, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * For help with this library, contact developers-help@facebook.com
 */
class Horde_Service_Facebook
{
    /**
     * Use only ssl resource flag
     *
     * @var boolean
     */
    public $useSslResources = false;

    /**
     * The API Secret Key
     *
     * @var string
     */
    protected $_secret;

    /**
     * Holds an optional logger object
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     *
     * @var Horde_Http_Client
     */
    protected $_http;


    /**
     * Cache for the various objects we lazy load in __get()
     *
     * @var hash of Horde_Service_Facebook_* objects
     */
     protected $_objCache = array();


    const API_VALIDATION_ERROR = 1;
    const REST_SERVER_ADDR = 'https://api.facebook.com/method/';
    const GRAPH_SERVER_ADDR = 'https://graph.facebook.com';

    /**
     * Const'r
     *
     * @param string $appId    Application ID.
     * @param string $secret   Developer API secret.
     * @param array $context   Array of context information containing:
     *  <pre>
     *      http_client - required
     *      logger
     *      use_ssl
     * </pre>
     */
    public function __construct($appId, $secret, $context)
    {
        // We require a http client object.
        if (empty($context['http_client'])) {
            throw new InvalidArgumentException('A http client object is required');
        } else {
            $this->_http = $context['http_client'];
        }

        // Optional Horde_Log_Logger
        if (!empty($context['logger'])) {
            $this->_logger = $context['logger'];
        } else {
            $this->_logger = new Horde_Support_Stub();
        }

        $this->_logger->debug('Initializing Horde_Service_Facebook');

        $this->_appId = $appId;
        $this->secret = $secret;

        if (!empty($context['use_ssl'])) {
            $this->useSslResources = true;
        }
    }

    /**
     * Lazy load the facebook classes.
     *
     * @param string $value  The lowercase representation of the subclass.
     *
     * @return mixed
     * @throws Horde_Service_Facebook_Exception
     */
    public function __get($value)
    {
        // First, see if it's an allowed protected value.
        switch ($value) {
        case 'appId':
            return $this->_appId;
        case 'secret':
            return $this->_secret;
        case 'http':
            return $this->_http;
        case 'logger':
            return $this->_logger;
        }

        // If not, assume it's a method/action class...
        $class = 'Horde_Service_Facebook_' . ucfirst($value);
        if (!class_exists($class)) {
            throw new Horde_Service_Facebook_Exception(sprintf("%s class not found", $class));
        }

        if (empty($this->_objCache[$class])) {
            $this->_objCache[$class] = new $class($this);
        }

        return $this->_objCache[$class];
    }

    /**
     * Helper function to get the appropriate facebook url
     *
     * @param string $subdomain  The subdomain to use (www).
     *
     * @return string
     */
    public static function getFacebookUrl($subdomain = 'www')
    {
        return 'https://' . $subdomain . '.facebook.com';
    }

    /**
     * Calls the specified normal REST API method.
     *
     * @param string $method  Name of the Facebook method to invoke
     * @param array $params   A map of param names => param values
     *
     * @return mixed  Result of method call
     */
    public function callMethod($method, array $params = array())
    {
        $this->_logger->debug(sprintf('Calling method %s with parameters %s', $method, print_r($params, true)));
        $request = new Horde_Service_Facebook_Request_Rest($this, $method, $params);
        return $request->run();
    }

    /**
     * Call the Facebook Graph API.
     *
     * @param string $method  The endpoint (method) to call.
     * @param array $params   An array of parameters to pass along with the call.
     * @param array $options  Additional request options:
     *   - request: (string) 'POST', 'GET', 'DELETE' etc..
     *
     * @return mixed  The results of the API call.
     */
    public function callGraphApi(
        $method = '', array $params = array(), array $options = array())
    {
        $request = new Horde_Service_Facebook_Request_Graph(
            $this,
            $method,
            $params,
            $options);

        return $request->run();
    }

}