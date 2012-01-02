<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */
class Horde_Http_Request_Mock extends Horde_Http_Request_Base
{
    /**
     * Mock responses to return.
     *
     * @var array
     */
    protected $_responses = array();

    /**
     * Send this HTTP request
     *
     * @return Horde_Http_Response_Mock|NULL A response object or NULL in case
     *                                       no responses has been set.
     */
    public function send()
    {
        if (empty($this->_responses)) {
            return;
        } elseif (count($this->_responses) > 1) {
            return array_shift($this->_responses);
        } else {
            return $this->_responses[0];
        }
    }

    /**
     * Set the HTTP response(s) to be returned by this adapter. This overwrites
     * any responses set before.
     *
     * @param Horde_Http_Response_Base $response
     */
    public function setResponse(Horde_Http_Response_Base $response)
    {
        $this->_responses = array($response);
    }

    /**
     * Set the HTTP response(s) to be returned by this adapter as an array of strings.
     *
     * @since Horde_Http 1.1.0
     *
     * @param array $responses The responses to be added to the stack.
     *
     * @return NULL
     */
    public function addResponses($responses)
    {
        foreach ($responses as $response) {
            if (is_string($response)) {
                $this->addResponse($response);
            }
            if (is_array($response)) {
                $this->addResponse(
                    isset($response['body']) ? $response['body'] : '',
                    isset($response['code']) ? $response['code'] : 200,
                    isset($response['uri']) ? $response['uri'] : '',
                    isset($response['headers']) ? $response['headers'] : array()
                );
            }
        }
    }

    /**
     * Adds a response to the stack of responses.
     *
     * @since Horde_Http 1.1.0
     *
     * @param string|resourse $body    The response body content.
     * @param string          $code    The response code.
     * @param string          $uri     The request uri.
     * @param array           $headers Response headers. This can be one string
     *                                 representing the whole header or an array
     *                                 of strings with one string per header
     *                                 line.
     *
     * @return Horde_Http_Response_Mock The response.
     */
    public function addResponse(
        $body, $code = 200, $uri = '', $headers = array()
    )
    {
        if (is_string($body)) {
            $stream = new Horde_Support_StringStream($body);
            $response = new Horde_Http_Response_Mock(
                $uri, $stream->fopen(), $headers
            );
        } else {
            $response = new Horde_Http_Response_Mock($uri, $body, $headers);
        }
        $response->code = $code;
        $this->_responses[] = $response;
    }

}
