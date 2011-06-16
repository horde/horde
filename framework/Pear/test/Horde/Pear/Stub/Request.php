<?php

class Horde_Pear_Stub_Request extends Horde_Http_Request_Base
{
    /**
     * Mock responses to return
     * @var array
     */
    protected $_responses = array();

    /**
     * Send this HTTP request
     *
     * @return Horde_Http_Response_Mock
     */
    public function send()
    {
        if (empty($this->_responses)) {
            throw new Exception('No more reponses left!');
        }
        return array_shift($this->_responses);
    }

    /**
     * Set the HTTP response(s) to be returned by this adapter as an array of strings.
     *
     * @param array $responses
     */
    public function setResponses($responses)
    {
        foreach ($responses as $response) {
            $body = new Horde_Support_StringStream($response['body']);
            $r = new Horde_Http_Response_Mock(
                isset($response['uri']) ? $response['uri'] : '',
                $body->fopen(),
                isset($response['headers']) ? $response['headers'] : array()
            );
            if (isset($response['code'])) {
                $r->code = $response['code'];
            }
            $this->_responses[] = $r;
        }
    }
}
