<?php
/**
 * Copyright 2007-2014 Horde LLC (http://www.horde.org/)
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
class Horde_Http_Response_Peclhttp2 extends Horde_Http_Response_Base
{
    /**
     * HttpMessage object.
     *
     * @var \http\Client\Response
     */
    protected $_response;

    /**
     * Constructor.
     *
     * @param string                $uri
     * @param \http\Client\Response $response
     */
    public function __construct($uri, \http\Client\Response $response)
    {
        try {
            $parent = $response->getParentMessage();
            $location = $parent->getHeader('Location');
            $this->uri = $location;
        } catch (HttpRuntimeException $e) {
            $this->uri = $uri;
        }

        $this->httpVersion = $response->getHttpVersion();
        $this->code = $response->getResponseCode();
        $this->_response = $response;
        foreach ($response->getHeaders() as $k => $v) {
            $this->headers[strtolower($k)] = $v;
        }
    }

    public function getBody()
    {
        return $this->_response->getBody()->toString();
    }
}
