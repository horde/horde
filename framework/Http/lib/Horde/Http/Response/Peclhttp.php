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
