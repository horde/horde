<?php
/**
 * Copyright 2007-2015 Horde LLC (http://www.horde.org/)
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
class Horde_Http_Request_Peclhttp extends Horde_Http_Request_PeclhttpBase
{
    /**
     * Map of HTTP authentication schemes from Horde_Http constants to
     * HTTP_AUTH constants.
     *
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

        $httpRequest = new HttpRequest((string)$this->uri, constant('HTTP_METH_' . $this->method));

        $data = $this->data;
        if (is_array($data)) {
            $httpRequest->setPostFields($data);
        } else {
            if ($this->method == 'PUT') {
                $httpRequest->setPutData($data);
            } else {
                $httpRequest->setBody($data);
            }
        }

        $httpRequest->setOptions($this->_httpOptions());

        try {
            $httpResponse = $httpRequest->send();
        } catch (HttpException $e) {
            if (isset($e->innerException)){
                throw new Horde_Http_Exception($e->innerException);
            } else {
                throw new Horde_Http_Exception($e);
            }
        }

        return new Horde_Http_Response_Peclhttp((string)$this->uri, $httpResponse);
    }
}
