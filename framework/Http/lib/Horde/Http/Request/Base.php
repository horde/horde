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
abstract class Horde_Http_Request_Base
{
    /**
     * URI
     * @var string
     */
    protected $_uri;

    /**
     * Request method
     * @var string
     */
    protected $_method = 'GET';

    /**
     * Request headers
     * @var array
     */
    protected $_headers = array();

    /**
     * Request data. Can be an array of form data that will be encoded
     * automatically, or a raw string
     * @var mixed
     */
    protected $_data;

    /**
     * Authentication username
     * @var string
     */
    protected $_username = '';

    /**
     * Authentication password
     * @var string
     */
    protected $_password = '';

    /**
     * Authentication scheme
     * @var const Horde_Http::AUTH_*
     */
    protected $_authenticationScheme = Horde_Http::AUTH_ANY;

    /**
     * Proxy server
     * @var string
     */
    protected $_proxyServer = null;

    /**
     * Proxy port
     * @var string
     */
    protected $_proxyPort = null;

    /**
     * Proxy username
     * @var string
     */
    protected $_proxyUsername = null;

    /**
     * Proxy password
     * @var string
     */
    protected $_proxyPassword = null;

    /**
     * Proxy authentication schem
     * @var const Horde_Http::AUTH_*
     */
    protected $_proxyAuthenticationScheme = Horde_Http::AUTH_BASIC;

    /**
     * HTTP timeout
     * @var float
     */
    protected $_timeout = 5;

    /**
     * Constructor
     */
    public function __construct($args = array())
    {
        foreach ($args as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Send this HTTP request
     *
     * @return Horde_Http_Response_Base
     */
    abstract public function send();

    /**
     * Get an adapter parameter
     *
     * @param string $name  The parameter to get.
     * @return mixed        Parameter value.
     */
    public function __get($name)
    {
        return isset($this->{'_' . $name}) ? $this->{'_' . $name} : null;
    }

    /**
     * Set a request parameter
     *
     * @param string $name   The parameter to set.
     * @param mixed  $value  Parameter value.
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'headers':
            $this->setHeaders($value);
            break;
        }

        $this->{'_' . $name} = $value;
    }

    /**
     * Set one or more headers
     *
     * @param mixed $headers A hash of header + value pairs, or a single header name
     * @param string $value  A header value
     */
    public function setHeaders($headers, $value = null)
    {
        if (!is_array($headers)) {
            $headers = array($headers => $value);
        }

        foreach ($headers as $header => $value) {
            $this->_headers[$header] = $value;
        }
    }

    /**
     * Get the current value of $header
     *
     * @param string $header Header name to get
     * @return string $header's current value
     */
    public function getHeader($header)
    {
        return isset($this->_headers[$header]) ? $this->_headers[$header] : null;
    }
}
