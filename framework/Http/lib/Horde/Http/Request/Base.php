<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */
abstract class Horde_Http_Request_Base
{
    /**
     * Request headers
     * @var array
     */
    protected $_headers = array();

    /**
     * @var array
     */
    protected $_options = array();

    /**
     * Constructor
     */
    public function __construct($options = array())
    {
        $this->setOptions($options);
    }

    public function setOptions($options = array())
    {
        $this->_options = array_merge($this->getDefaultOptions(), $options);
    }

    public function getDefaultOptions()
    {
        return array(
            'uri' => null,
            'method' => 'GET',
            'data' => null,
            'username' => '',
            'password' => '',
            'authenticationScheme' => Horde_Http::AUTH_ANY,
            'proxyServer' => null,
            'proxyPort' => null,
            'proxyType' => Horde_Http::PROXY_HTTP,
            'proxyUsername' => null,
            'proxyPassword' => null,
            'proxyAuthenticationScheme' => Horde_Http::AUTH_BASIC,
            'timeout' => 5,
            'redirects' => 5,
        );
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
        switch ($name) {
        case 'headers':
            return $this->_headers;
        }

        return isset($this->_options[$name]) ? $this->_options[$name] : null;
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

        $this->_options[$name] = $value;
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
