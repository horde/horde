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
 * HTTP response object for the pecl_http backend.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Http
 */
class Horde_Http_Response_Peclhttp extends Horde_Http_Response_Base
{
    /**
     * HttpMessage object.
     *
     * @var HttpMessage
     */
    protected $_message;

    /**
     * Constructor.
     *
     * @param string $uri
     * @param HttpMessage $message
     */
    public function __construct($uri, HttpMessage $message)
    {
        try {
            $parent = $message->getParentMessage();
            $location = $parent->getHeader('Location');
            $this->uri = $location;
        } catch (HttpRuntimeException $e) {
            $this->uri = $uri;
        }

        $this->httpVersion = $message->getHttpVersion();
        $this->code = $message->getResponseCode();
        $this->_message = $message;
        $this->_headers = new Horde_Support_CaseInsensitiveArray(
            $message->getHeaders()
        );
        $this->headers = array_change_key_case($this->_headers->getArrayCopy());
    }

    public function getBody()
    {
        return $this->_message->getBody();
    }
}
