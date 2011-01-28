<?php
/**
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */
class Horde_Http_Client
{
    /**
     * The current HTTP request
     * @var Horde_Http_Request_Base
     */
    protected $_request;

    /**
     * The previous HTTP request
     * @var Horde_Http_Request_Base
     */
    protected $_lastRequest;

    /**
     * The most recent HTTP response
     * @var Horde_Http_Response_Base
     */
    protected $_lastResponse;

    /**
     * Use POST instead of PUT and DELETE, sending X-HTTP-Method-Override with
     * the intended method name instead.
     *
     * @var boolean
     */
    protected $_httpMethodOverride = false;

    /**
     * Horde_Http_Client constructor.
     *
     * @param array $args Any Http_Client settings to initialize in the
     *                    constructor. Available settings are:
     *                    - client.httpMethodOverride
     *                    - request
     *                    - request.uri
     *                    - request.headers
     *                    - request.method
     *                    - request.data
     *                    - request.username
     *                    - request.password
     *                    - request.authenticationScheme
     *                    - request.proxyServer
     *                    - request.proxyPort
     *                    - request.proxyUsername
     *                    - request.proxyPassword
     *                    - request.proxyAuthenticationScheme
     *                    - request.timeout
     */
    public function __construct($args = array())
    {
        // Set or create request object
        if (isset($args['request'])) {
            $this->_request = $args['request'];
            unset($args['request']);
        } else {
            $requestFactory = new Horde_Http_Request_Factory();
            $this->_request = $requestFactory->create();
        }

        foreach ($args as $key => $val) {
            list($object, $objectkey) = explode('.', $key, 2);
            if ($object == 'request') {
                $this->$object->$objectkey = $val;
            } elseif ($object == 'client') {
                $objectKey = '_' . $objectKey;
                $this->$objectKey = $val;
            }
        }
    }

    /**
     * Send a GET request
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function get($uri = null, $headers = array())
    {
        return $this->request('GET', $uri, null, $headers);
    }

    /**
     * Send a POST request
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function post($uri = null, $data = null, $headers = array())
    {
        return $this->request('POST', $uri, $data, $headers);
    }

    /**
     * Send a PUT request
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function put($uri = null, $data = null, $headers = array())
    {
        if ($this->_httpMethodOverride) {
            $headers = array_merge(array('X-HTTP-Method-Override' => 'PUT'), $headers);
            return $this->post($uri, $data, $headers);
        }

        return $this->request('PUT', $uri, $data, $headers);
    }

    /**
     * Send a DELETE request
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function delete($uri = null, $headers = array())
    {
        if ($this->_httpMethodOverride) {
            $headers = array_merge(array('X-HTTP-Method-Override' => 'DELETE'), $headers);
            return $this->post($uri, null, $headers);
        }

        return $this->request('DELETE', $uri, null, $headers);
    }

    /**
     * Send a HEAD request
     * @TODO
     *
     * @throws Horde_Http_Exception
     * @return  ? Probably just the status
     */
    public function head($uri = null, $headers = array())
    {
        return $this->request('HEAD', $uri, null, $headers);
    }

    /**
     * Send an HTTP request
     *
     * @param string $method  HTTP request method (GET, PUT, etc.)
     * @param string $uri     URI to request, if different from $this->uri
     * @param mixed $data     Request data. Can be an array of form data that
     *                        will be encoded automatically, or a raw string.
     * @param array $headers  Any headers specific to this request. They will
     *                        be combined with $this->_headers, and override
     *                        headers of the same name for this request only.
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function request($method, $uri = null, $data = null, $headers = array())
    {
        if ($method !== null) {
            $this->request->method = $method;
        }
        if ($uri !== null) {
            $this->request->uri = $uri;
        }
        if ($data !== null) {
            $this->request->data = $data;
        }
        if (count($headers)) {
            $this->request->setHeaders($headers);
        }

        $this->_lastRequest = $this->_request;
        $this->_lastResponse = $this->_request->send();
        return $this->_lastResponse;
    }

    /**
     * Get a client parameter
     *
     * @param string $name  The parameter to get.
     * @return mixed        Parameter value.
     */
    public function __get($name)
    {
        return isset($this->{'_' . $name}) ? $this->{'_' . $name} : null;
    }

    /**
     * Set a client parameter
     *
     * @param string $name   The parameter to set.
     * @param mixed  $value  Parameter value.
     */
    public function __set($name, $value)
    {
        $this->{'_' . $name} = $value;
    }
}
