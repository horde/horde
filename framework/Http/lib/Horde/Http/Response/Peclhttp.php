<?php
/**
 * Copyright 2007-2014 Horde LLC (http://www.horde.org/)
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
        $this->headers = $this->_headers->getArrayCopy();
    }

    public function getBody()
    {
        return $this->_message->getBody();
    }
}
