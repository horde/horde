<?php
/**
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
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
     * Array of mock responses
     * @var array
     */
    protected $_responses = array();

    /**
     * Current mock response
     * @var integer
     */
    protected $_responseIndex = 0;

    /**
     * Send this HTTP request
     *
     * @return Horde_Http_Response_Base
     *
     * @TODO make lastRequest work somehow - not sure if this is still an issue.
     */
    public function send()
    {
        if ($this->_responseIndex >= count($this->_responses)) {
            $this->_responseIndex = 0;
        }
        return $this->_responses[$this->_responseIndex++];
    }

    /**
     * Set the HTTP response(s) to be returned by this adapter
     *
     * @param Horde_Http_Response_Base $response
     */
    public function setResponse($response)
    {
        $this->_responses = array($response);
        $this->_responseIndex = 0;
    }

    /**
     * Add another response to the response buffer.
     *
     * @param string $response
     */
    public function addResponse($response)
    {
        $this->_responses[] = $response;
    }

    /**
     * Sets the position of the response buffer.  Selects which
     * response will be returned on the next call to read().
     *
     * @param integer $index
     */
    public function setResponseIndex($index)
    {
        if ($index < 0 || $index >= count($this->_responses)) {
            throw new OutOfBoundsException;
        }
        $this->_responseIndex = $index;
    }

}
