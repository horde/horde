<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Http
 */

/**
 * Base class for HTTP request objects.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Http
 * @property  string|Horde_Url $uri Default URI if not specified for
  *                                 individual requests.
 * @property  array $headers Hash with additional request headers.
 * @property  string $method Default request method if not specified for
 *                           individual requests.
 * @property  array|string $data POST data fields or POST/PUT data body.
 * @property  string $username Authentication user name.
 * @property  string $password Authentication password.
 * @property  string $authenticationScheme Authentication method, one of the
 *                                         Horde_Http::AUTH_* constants.
 * @property  string $proxyServer Host name of a proxy server.
 * @property  integer $proxyPort Port number of a proxy server.
 * @property  integer $proxyType Proxy server type, one of the
 *                               Horde_Http::PROXY_* constants.
 * @property  string $proxyUsername Proxy authentication user name.
 * @property  string $proxyPassword Proxy authentication password.
 * @property  string $proxyAuthenticationScheme Proxy authentication method,
 *                                              one of the Horde_Http::AUTH_*
 *                                              constants.
 * @property  integer $redirects Maximum number of redirects to follow.
 * @property  integer $timeout Timeout in seconds.
 * @property  string $userAgent User-Agent: request header contents.
 * @property  boolean $verifyPeer Verify SSL peer certificates?
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
            'redirects' => 5,
            'timeout' => 5,
            'userAgent' => str_replace(' @' . 'version@', '', 'Horde_Http @version@'),
            'verifyPeer' => true,
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
