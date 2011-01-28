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
class Horde_Http_Request_Mock extends Horde_Http_Request_Base
{
    /**
     * Mock response to return
     * @var Horde_Http_Request_Mock
     */
    protected $_response;

    /**
     * Send this HTTP request
     *
     * @return Horde_Http_Response_Mock
     *
     * @TODO make lastRequest work somehow - not sure if this is still an issue.
     */
    public function send()
    {
        return $this->_response;
    }

    /**
     * Set the HTTP response(s) to be returned by this adapter
     *
     * @param Horde_Http_Response_Base $response
     */
    public function setResponse(Horde_Http_Response_Base $response)
    {
        $this->_response = $response;
    }
}
