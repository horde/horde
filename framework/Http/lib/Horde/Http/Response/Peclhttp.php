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
        $this->uri = $uri;
        $this->httpVersion = $message->getHttpVersion();
        $this->code = $message->getResponseCode();
        $this->_message = $message;
        foreach ($message->getHeaders() as $k => $v) {
            $this->headers[strtolower($k)] = $v;
        }
    }

    public function getBody()
    {
        return $this->_message->getBody();
    }
}
