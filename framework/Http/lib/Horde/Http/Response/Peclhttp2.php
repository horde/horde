<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
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
            $info = $response->getTransferInfo();
        } catch (\http\Exception $e) {
            throw new Horde_Http_Exception($e);
        }
        try {
            $this->uri = $info->effective_url;
        } catch (\http\Exception\RuntimeException $e) {
            $this->uri = $uri;
        }

        $this->httpVersion = $response->getHttpVersion();
        $this->code = $info->response_code;
        $this->_response = $response;
        $this->_headers = new Horde_Support_CaseInsensitiveArray(
            $response->getHeaders()
        );
        $this->headers = array_change_key_case($this->_headers->getArrayCopy());
    }

    public function getBody()
    {
        return $this->_response->getBody()->toString();
    }
}
