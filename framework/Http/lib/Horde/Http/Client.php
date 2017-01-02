<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */

/**
 * An HTTP client.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 *
 * @property       boolean      $httpMethodOverride
 *                              @see $_httpMethodOverride
 * @property       Horde_Http_Request_Base $request
 *                              A concrete request instance.
 * @property-write string|Horde_Url $request.uri
 *                              Default URI if not specified for individual
 *                              requests.
 * @property-write array        $request.headers
 *                              Hash with additional request headers.
 * @property-write string       $request.method
 *                              Default request method if not specified for
 *                              individual requests.
 * @property-write array|string $request.data
 *                              POST data fields or POST/PUT data body.
 * @property-write string       $request.username
 *                              Authentication user name.
 * @property-write string       $request.password
 *                              Authentication password.
 * @property-write string       $request.authenticationScheme
 *                              Authentication method, one of the
 *                              Horde_Http::AUTH_* constants.
 * @property-write string       $request.proxyServer
 *                              Host name of a proxy server.
 * @property-write integer      $request.proxyPort
 *                              Port number of a proxy server.
 * @property-write integer      $request.proxyType
 *                              Proxy server type, one of the
 *                              Horde_Http::PROXY_* constants.
 * @property-write string       $request.proxyUsername
 *                              Proxy authentication user name.
 * @property-write string       $request.proxyPassword
 *                              Proxy authentication password.
 * @property-write string       $request.proxyAuthenticationScheme
 *                              Proxy authentication method, one of the
 *                              Horde_Http::AUTH_* constants.
 * @property-write integer      $request.redirects
 *                              Maximum number of redirects to follow.
 * @property-write integer      $request.timeout
 *                              Timeout in seconds.
 * @property-write boolean      $request.verifyPeer
 *                              Verify SSL peer certificates?
 */
class Horde_Http_Client
{
    /**
     * The current HTTP request.
     *
     * @var Horde_Http_Request_Base
     */
    protected $_request;

    /**
     * The previous HTTP request.
     *
     * @var Horde_Http_Request_Base
     */
    protected $_lastRequest;

    /**
     * The most recent HTTP response.
     *
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
     *                    constructor. See the class properties for available
     *                    settings.
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
            $this->$key = $val;
        }
    }

    /**
     * Sends a GET request.
     *
     * @param string $uri     Request URI.
     * @param array $headers  Additional request headers.
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function get($uri = null, $headers = array())
    {
        return $this->request('GET', $uri, null, $headers);
    }

    /**
     * Sends a POST request.
     *
     * @param string $uri         Request URI.
     * @param array|string $data  Data fields or data body.
     * @param array $headers      Additional request headers.
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function post($uri = null, $data = null, $headers = array())
    {
        return $this->request('POST', $uri, $data, $headers);
    }

    /**
     * Sends a PUT request.
     *
     * @param string $uri     Request URI.
     * @param string $data    Data body.
     * @param array $headers  Additional request headers.
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function put($uri = null, $data = null, $headers = array())
    {
        if ($this->_httpMethodOverride) {
            $headers = array_merge(
                array('X-HTTP-Method-Override' => 'PUT'),
                $headers
            );
            return $this->post($uri, $data, $headers);
        }

        return $this->request('PUT', $uri, $data, $headers);
    }

    /**
     * Sends a DELETE request.
     *
     * @param string $uri     Request URI.
     * @param array $headers  Additional request headers.
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function delete($uri = null, $headers = array())
    {
        if ($this->_httpMethodOverride) {
            $headers = array_merge(
                array('X-HTTP-Method-Override' => 'DELETE'),
                $headers
            );
            return $this->post($uri, null, $headers);
        }

        return $this->request('DELETE', $uri, null, $headers);
    }

    /**
     * Sends a HEAD request.
     *
     * @param string $uri     Request URI.
     * @param array $headers  Additional request headers.
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function head($uri = null, $headers = array())
    {
        return $this->request('HEAD', $uri, null, $headers);
    }

    /**
     * Sends an HTTP request.
     *
     * @param string $method         HTTP request method (GET, PUT, etc.)
     * @param string|Horde_Url $uri  URI to request, if different from
     *                               $this->uri
     * @param string|array $data     Request data. Array of form data that will
     *                               be encoded automatically, or a raw string.
     * @param array $headers         Any headers specific to this request. They
     *                               will be combined with $this->_headers, and
     *                               override headers of the same name for this
     *                               request only.
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function request(
        $method, $uri = null, $data = null, $headers = array()
    )
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
     * Returns a client parameter.
     *
     * @param string $name  The parameter to return.
     *
     * @return mixed  The parameter value.
     */
    public function __get($name)
    {
        return isset($this->{'_' . $name}) ? $this->{'_' . $name} : null;
    }

    /**
     * Sets a client parameter.
     *
     * @param string $name  The parameter to set.
     * @param mixed $value  The parameter value.
     */
    public function __set($name, $value)
    {
        if ((strpos($name, '.') === false)) {
            if (isset($this->{'_' . $name})) {
                $this->{'_' . $name} = $value;
                return true;
            } else {
                throw new Horde_Http_Exception('unknown parameter: "' . $name . '"');
            }
        }

        list($object, $objectkey) = explode('.', $name, 2);
        if ($object == 'request') {
            $this->$object->$objectkey = $value;
            return true;
        } elseif ($object == 'client') {
            $objectKey = '_' . $objectKey;
            $this->$objectKey = $value;
            return true;
        }

        throw new Horde_Http_Exception('unknown parameter: "' . $name . '"');
    }
}
