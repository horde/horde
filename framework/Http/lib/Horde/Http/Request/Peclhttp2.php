<?php
/**
 * Copyright 2007-2015 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */

/**
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */
class Horde_Http_Request_Peclhttp2 extends Horde_Http_Request_PeclhttpBase
{
    /**
     * Map of HTTP authentication schemes from Horde_Http constants to HTTP_AUTH constants.
     * @var array
     */
    protected $_httpAuthSchemes = array(
        Horde_Http::AUTH_ANY => \http\Client\Curl\AUTH_ANY,
        Horde_Http::AUTH_BASIC => \http\Client\Curl\AUTH_BASIC,
        Horde_Http::AUTH_DIGEST => \http\Client\Curl\AUTH_DIGEST,
        Horde_Http::AUTH_GSSNEGOTIATE => \http\Client\Curl\AUTH_GSSNEG,
        Horde_Http::AUTH_NTLM => \http\Client\Curl\AUTH_NTLM,
    );

    /**
     * Map of proxy types from Horde_Http to implementation specific constants.
     * @var array
     */
    protected $_proxyTypes = array(
        Horde_Http::PROXY_SOCKS4 => \http\Client\Curl\PROXY_SOCKS4,
        Horde_Http::PROXY_SOCKS5 => \http\Client\Curl\PROXY_SOCKS5
    );

    /**
     * Constructor
     *
     * @throws Horde_Http_Exception
     */
    public function __construct($args = array())
    {
        if (!class_exists('\http\Client', false)) {
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
        // at this time only the curl driver is supported
        $client = new \http\Client('curl');

        $body = new \http\Message\Body();
        $data = $this->data;
        if (is_array($data)) {
            $body->addForm($data);
        } else {
            $body->append($data);
        }

        $httpRequest = new \http\Client\Request($this->method, $this->uri, $this->headers, $body);

        $client->setOptions($this->_httpOptions());

        $client->enqueue($httpRequest);

        try {
            $client->send();
            $httpResponse = $client->getResponse($httpRequest);
        } catch (\http\Exception $e) {
            throw new Horde_Http_Exception($e);
        }

        return new Horde_Http_Response_Peclhttp2($this->uri, $httpResponse);
    }
}