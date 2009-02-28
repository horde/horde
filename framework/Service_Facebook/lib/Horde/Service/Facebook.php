<?php
/**
 * Horde_Service_Facebook class abstracts communication with Facebook's
 * rest interface.
 *
 * This code was originally a Hordified version of Facebook's official PHP
 * client. However, very little of the original code or design is left. I left
 * the original copyright notice intact below.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
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
 *   notice, this list of conditions and the following disclaimer.
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
     * The application's API Key
     *
     * @var stirng
     */
    public $api_key;

    /**
     * The API Secret Key
     *
     * @var string
     */
    public $secret;

    /**
     * Use only ssl resource flag
     *
     * @var boolean
     */
    public $useSslResources = false;

    /**
     * Holds the batch object when building a batch request.
     *
     * @var Horde_Service_Facebook_Batch
     */
    public $batchRequest;

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
     *
     * @var Horde_Controller_Request_Http
     */
    protected $_request;

    /**
     *
     * @var array
     */
    protected $_context;

    /**
     * Return format
     *
     * @var Horde_Service_Facebook::DATA_FORMAT_* constant
     */
    public $dataFormat = self::DATA_FORMAT_ARRAY;

    /**
     * Data format used internally if DATA_FORMAT_OBJECT is specified.
     * ('json' or 'xml'). Needed to overcome some current bugs in Facebook's
     * JSON implementation.
     */
    protected $_internalFormat = self::DATA_FORMAT_JSON;

    /**
     * Cache for the various objects we lazy load in __get()
     *
     * @var hash of Horde_Service_Facebook_* objects
     */
    static protected $_objCache = array();


    const API_VALIDATION_ERROR = 1;
    const REST_SERVER_ADDR = 'http://api.facebook.com/restserver.php';

    /**
     * Data format returned to client code.
     */
    const DATA_FORMAT_JSON = 'json';
    const DATA_FORMAT_XML = 'xml';
    const DATA_FORMAT_ARRAY = 'array';

    /**
     * Const'r
     *
     * @param string $api_key  Developer API key.
     * @param string $secret   Developer API secret.
     * @param array $context   Array of context information containing:
     *  <pre>
     *      http_client - required
     *      http_response - required
     *      logger
     *      no_resolve - set to true to prevent attempting to obtain a session
     *                   from an auth_token. Useful if client code wants to
     *                   handle this.
     * </pre>
     * @param session_key
     */
    public function __construct($api_key, $secret, $context)
    {
        // We require a http client object.
        if (empty($context['http_client'])) {
            throw new InvalidArgumentException('A http client object is required');
        } else {
            $this->_http = $context['http_client'];
        }

        // Required Horde_Controller_Request object, but we can also get it
        // if we have a Horde_Controller object.
        if (empty($context['http_request']) && empty($context['controller'])) {
            throw new InvalidArgumentException('A http request object is required');
        } elseif (!empty($context['http_request'])) {
            $this->_request = $context['http_request'];
        } else {
            $this->_request = $context['controller']->request;
        }

        // Optional Horde_Log_Logger
        if (!empty($context['logger'])) {
            $this->_logger = $context['logger'];
        }

        $this->_logDebug('Initializing Horde_Service_Facebook');

        $this->api_key = $api_key;
        $this->secret = $secret;

        if (!empty($context['use_ssl'])) {
            $this->useSslResources = true;
        }

        // Save the rest
        $this->_context = $context;
    }

    /**
     * Initialize the object - check to see if we have a valid FB
     * session, verify the signature etc...
     */
    public function validateSession()
    {
        return $this->auth->validateSession(empty($this->_context['no_resolve']));
    }

    /**
     * Lazy load the facebook classes.
     *
     * @param string $value  The lowercase representation of the subclass.
     *
     * @throws Horde_Service_Facebook_Exception
     * @return Horde_Service_Facebook_* object.
     */
    public function __get($value)
    {
        // First, see if it's an allowed protected value.
        switch ($value) {
        case 'internalFormat':
            return $this->_internalFormat;
        }

        // If not, assume it's a method/action class...
        $class = 'Horde_Service_Facebook_' . ucfirst($value);
        if (!empty(self::$_objCache[$class])) {
            return self::$_objCache[$class];
        }

        if (!class_exists($class)) {
            throw new Horde_Service_Facebook_Exception(sprintf("%s class not found", $class));
        }

        self::$_objCache[$class] = new $class($this, $this->_request);
        return self::$_objCache[$class];
    }

    /**
     * Return the current request's url
     *
     * @return string
     */
    protected function _current_url()
    {
        return sprintf("%s/%s", $this->_request->getHost(), $this->_request->getUri());
    }

    /**
     * Helper function to get the appropriate facebook url
     *
     * @return string
     */
    public static function get_facebook_url($subdomain = 'www')
    {
        return 'http://' . $subdomain . '.facebook.com';
    }

    /**
     *  Return a valid FB login URL with necessary GET parameters appended.
     *
     *  @return string
     */
    public function get_login_url($next)
    {
        return self::get_facebook_url() . '/login.php?v=1.0&api_key='
            . $this->api_key . ($next ? '&next=' . urlencode($next)  : '');
    }

    /**
     * Start a batch operation.
     */
    public function batchBegin()
    {
        if ($this->batchRequest !== null) {
            $code = Horde_Service_Facebook_ErrorCodes::API_EC_BATCH_ALREADY_STARTED;
            $description = Horde_Service_Facebook_ErrorCodes::$api_error_descriptions[$code];
            throw new Horde_Service_Facebook_Exception($description, $code);
        }

        $this->batchRequest = new Horde_Service_Facebook_BatchRequest($this, $this->_http);
    }

    /**
     * End current batch operation
     */
    public function batchEnd()
    {
        if ($this->batchRequest === null) {
            $code = Horde_Service_Facebook_ErrorCodes::API_EC_BATCH_NOT_STARTED;
            $description = Horde_Service_Facebook_ErrorCodes::$api_error_descriptions[$code];
            throw new Horde_Service_Facebook_Exception($description, $code);
        }

        $this->batchRequest->run();
        $this->batchRequest = null;
    }

    /**
     * Setter for the internal data format. Returns the previously used
     * format to make it easier for methods that need a certain format to
     * reset the old format when done.
     *
     * @param Horde_Service_Facebook::DATA_FORMAT_* constant $format
     *
     * @return Horde_Service_Facebook::DATA_FORMAT_* constant
     */
    public function setInternalFormat($format)
    {
        $old = $this->_internalFormat;
        $this->_internalFormat = $format;

        return $old;
    }

    /**
     * Calls the specified normal POST method with the specified parameters.
     *
     * @param string $method  Name of the Facebook method to invoke
     * @param array $params   A map of param names => param values
     *
     * @return mixed  Result of method call; this returns a reference to support
     *                'delayed returns' when in a batch context.
     *     See: http://wiki.developers.facebook.com/index.php/Using_batching_API
     */
    public function &call_method($method, $params = array())
    {
        if ($this->batchRequest === null) {
            $request = new Horde_Service_Facebook_Request($this, $method, $this->_http, $params);
            $results = &$request->run();
        } else {
            $results = &$this->batchRequest->add($method, $params);
        }

        return $results;
    }

    /**
     * Calls the specified file-upload POST method with the specified parameters
     *
     * @param string $method Name of the Facebook method to invoke
     * @param array  $params A map of param names => param values
     * @param string $file   A path to the file to upload (required)
     *
     * @return array A dictionary representing the response.
     */
    public function call_upload_method($method, $params, $file, $server_addr = null)
    {
        if ($this->batch_queue === null) {
            if (!file_exists($file)) {
                $code = Horde_Service_Facebook_ErrorCodes::API_EC_PARAM;
                $description = Horde_Service_Facebook_ErrorCodes::$api_error_descriptions[$code];
                throw new Horde_Service_Facebook_Exception($description, $code);
            }
        }

        $json = $this->post_upload_request($method, $params, $file, $server_addr);
        $result = json_decode($json, true);
        if (is_array($result) && isset($result['error_code'])) {
            throw new Horde_Service_Facebook_Exception($result['error_msg'], $result['error_code']);
        } else {
            $code = Horde_Service_Facebook_ErrorCodes::API_EC_BATCH_METHOD_NOT_ALLOWED_IN_BATCH_MODE;
            $description = Horde_Service_Facebook_ErrorCodes::$api_error_descriptions[$code];
            throw new Horde_Service_Facebook_Exception($description, $code);
        }

        return $result;
    }

    private function post_upload_request($method, $params, $file, $server_addr = null)
    {
        // Ensure we ask for JSON
        $params['format'] = 'json';
        $server_addr = $server_addr ? $server_addr : self::REST_SERVER_ADDR;
        $this->finalize_params($method, $params);
        $result = $this->run_multipart_http_transaction($method, $params, $file, $server_addr);
        return $result;
    }

    private function run_http_post_transaction($content_type, $content, $server_addr)
    {
        $user_agent = 'Facebook API PHP5 Client 1.1 (non-curl) ' . phpversion();
        $content_length = strlen($content);
        $context =
            array('http' => array('method' => 'POST',
                                  'user_agent' => $user_agent,
                                  'header' => 'Content-Type: ' . $content_type . "\r\n" . 'Content-Length: ' . $content_length,
                                  'content' => $content));
        $context_id = stream_context_create($context);
        $sock = fopen($server_addr, 'r', false, $context_id);
        $result = '';
        if ($sock) {
          while (!feof($sock)) {
            $result .= fgets($sock, 4096);
          }
          fclose($sock);
        }
        return $result;
    }

    /**
     * TODO: This will probably be replaced
     * @param $method
     * @param $params
     * @param $file
     * @param $server_addr
     * @return unknown_type
     */
    private function run_multipart_http_transaction($method, $params, $file, $server_addr)
    {
        // the format of this message is specified in RFC1867/RFC1341.
        // we add twenty pseudo-random digits to the end of the boundary string.
        $boundary = '--------------------------FbMuLtIpArT' .
                    sprintf("%010d", mt_rand()) .
                    sprintf("%010d", mt_rand());
        $content_type = 'multipart/form-data; boundary=' . $boundary;
        // within the message, we prepend two extra hyphens.
        $delimiter = '--' . $boundary;
        $close_delimiter = $delimiter . '--';
        $content_lines = array();
        foreach ($params as $key => &$val) {
            $content_lines[] = $delimiter;
            $content_lines[] = 'Content-Disposition: form-data; name="' . $key . '"';
            $content_lines[] = '';
            $content_lines[] = $val;
        }
        // now add the file data
        $content_lines[] = $delimiter;
        $content_lines[] = 'Content-Disposition: form-data; filename="' . $file . '"';
        $content_lines[] = 'Content-Type: application/octet-stream';
        $content_lines[] = '';
        $content_lines[] = file_get_contents($file);
        $content_lines[] = $close_delimiter;
        $content_lines[] = '';
        $content = implode("\r\n", $content_lines);
        return $this->run_http_post_transaction($content_type, $content, $server_addr);
    }

    protected function _logDebug($message)
    {
        if (!empty($this->_logger)) {
            $this->_logger->debug($message);
        }
    }

    protected function _logErr($message)
    {
        if (!empty($this->_logger)) {
            $this->_logger->err($message);
        }
    }

}