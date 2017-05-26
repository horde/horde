<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Http
 */

/**
 * HTTP response object for the pecl_http 2.0 backend.
 *
 * @author    Michael Cramer <michael@bigmichi1.de>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Http
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
