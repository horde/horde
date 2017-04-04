<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Pear
 */

/**
 * Unit test.
 *
 * @author    Gunnar Wrobel <wrobel@pardus.de>
 * @category  Horde
 * @copyright 2011-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Pear
 */

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
