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
class Horde_Http_Request_Peclhttp extends Horde_Http_Request_Base
{
    /**
     * Map of HTTP authentication schemes from Horde_Http constants to HTTP_AUTH constants.
     * @var array
     */
    protected $_httpAuthSchemes = array(
        Horde_Http::AUTH_ANY => HTTP_AUTH_ANY,
        Horde_Http::AUTH_BASIC => HTTP_AUTH_BASIC,
        Horde_Http::AUTH_DIGEST => HTTP_AUTH_DIGEST,
        Horde_Http::AUTH_GSSNEGOTIATE => HTTP_AUTH_GSSNEG,
        Horde_Http::AUTH_NTLM => HTTP_AUTH_NTLM,
    );

    /**
     * Constructor
     *
     * @throws Horde_Http_Exception
     */
    public function __construct($args = array())
    {
        if (!class_exists('HttpRequest', false)) {
            throw new Horde_Http_Exception('The pecl_http extension is not installed. See http://php.net/http.install');
        }

        parent::__construct($args);
    }

    /**
     * Send this HTTP request
     *
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function send()
    {
        if (!defined('HTTP_METH_' . $this->method)) {
            throw new Horde_Http_Exception('Method ' . $this->method . ' not supported.');
        }

        $httpRequest = new HttpRequest($this->uri, constant('HTTP_METH_' . $this->method));

        $data = $this->data;
        if (is_array($data)) {
            $httpRequest->setPostFields($data);
        } else {
            $httpRequest->setRawPostData($data);
        }

        $httpOptions = array('timeout' => $this->timeout);

        // Proxy settings
        if ($this->proxyServer) {
            $httpOptions['proxyhost'] = $this->proxyServer;
            if ($this->proxyPort) {
                $httpOptions['proxyport'] = $this->proxyPort;
            }
            if ($this->proxyUsername && $this->proxyPassword) {
                $httpOptions['proxyauth'] = $this->proxyUsername . ':' . $this->proxyPassword;
                $httpOptions['proxyauthtype'] = $this->_httpAuthScheme($this->proxyAuthenticationScheme);
            }
        }

        // Authentication settings
        if ($this->username) {
            $httpOptions['httpauth'] = $this->username . ':' . $this->password;
            $httpOptions['httpauthtype'] = $this->_httpAuthScheme($this->authenticationScheme);
        }

        // Headers
        $httpOptions['headers'] = $this->headers;

        // Set options
        $httpRequest->setOptions($httpOptions);

        try {
            $httpResponse = $httpRequest->send();
        } catch (HttpException $e) {
            throw new Horde_Http_Exception($e->getMessage(), $e->getCode(), $e);
        }

        return new Horde_Http_Response_Peclhttp($this->uri, $httpResponse);
    }

    /**
     * Translate a Horde_Http::AUTH_* constant to HTTP_AUTH_*
     *
     * @param const
     * @throws Horde_Http_Exception
     * @return const
     */
    protected function _httpAuthScheme($httpAuthScheme)
    {
        if (!isset($this->_httpAuthSchemes[$httpAuthScheme])) {
            throw new Horde_Http_Exception('Unsupported authentication scheme (' . $httpAuthScheme . ')');
        }
        return $this->_httpAuthSchemes[$httpAuthScheme];
    }
}
