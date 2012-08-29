<?php
/**
 * Horde_Service_Facebook_Request_Graph:: encapsulate a request to the
 * Graph API.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Request_Graph extends Horde_Service_Facebook_Request_Base
{
    /**
     * API Endpoint to call.
     *
     * @var string
     */
    protected $_endpoint;

    /**
     * The HTTP method to use for this request.
     *
     * @var
     */
    protected $_request = 'GET';

    /**
     * Raw POST data.
     *
     * @var string
     */
    protected $_raw;

    /**
     * Const'r
     *
     * @param Horde_Service_Facebook $facebook  The facebook client.
     * @param string $method                    The API method to call.
     * @param array $params                     Any parameters to send.
     * @param array $options                    Additional options:
     *  - request (string) The HTTP method to use.
     *            DEFAULT: GET
     */
    public function __construct(
        $facebook, $method = '', array $params = array(), array $options = array())
    {
        parent::__construct($facebook, $method, $params);

        $this->_endpoint = new Horde_Url($facebook->getFacebookUrl('graph') . '/' . $this->_method);
        if (!empty($options['request'])) {
            $this->_request = $options['request'];
        }
        $this->_params['access_token'] = $this->_facebook->auth->getSessionKey();
    }

    /**
     * Perform a multipart/form-data upload.
     *
     * @param array $options  An options array:
     *   - params: (array) Form parameters to pass
     *   - file: (string) Local path to the file
     */
    public function upload(array $options = array())
    {
        // the format of this message is specified in RFC1867/RFC1341.
        // we add twenty pseudo-random digits to the end of the boundary string.
        $boundary = '--------------------------FbMuLtIpArT' .
                    sprintf("%010d", mt_rand()) .
                    sprintf("%010d", mt_rand());
        $content_type = 'multipart/form-data; boundary=' . $boundary;

        // within the message, we prepend two extra hyphens.
        $delimiter = '--' . $boundary;
        $close_delimiter = $delimiter . '--';

        $content_lines = array();
        $params = array_merge($options['params'], $this->_params);
        foreach ($params as $key => $val) {
            $content_lines[] = $delimiter;
            $content_lines[] = 'Content-Disposition: form-data; name="' . $key . '"';
            $content_lines[] = '';
            $content_lines[] = $val;
        }

        // now add the file data
        $content_lines[] = $delimiter;
        $content_lines[] = 'Content-Disposition: form-data; filename="' . basename($options['file']) . '"';
        $content_lines[] = 'Content-Type: application/octet-stream';
        $content_lines[] = '';
        $content_lines[] = file_get_contents($options['file']);
        $content_lines[] = $close_delimiter;
        $content_lines[] = '';
        $content = implode("\r\n", $content_lines);

        try {
            $result = $this->_http->request('POST',
                $this->_endpoint->toString(true),
                $content,
                array(
                    'Content-Type' => $content_type ,
                    'Content-Length' => strlen($content)
                )
            );
        } catch (Horde_Http_Client_Exception $e) {
            throw new Horde_Service_Facebook_Exception(sprintf(Horde_Service_Facebook_Translation::t("Upload failed: %s"), $e->getMessage()));
        }

        if ($result->code != '200') {
            throw new Horde_Service_Facebook_Exception($result->getBody());
        }

        return json_decode($result->getBody());
    }

    /**
     * Run this request and return the data.
     *
     * @return mixed Either raw JSON, or an array of decoded values.
     * @throws Horde_Service_Facebook_Exception
     */
    public function run()
    {
        if ($this->_request != 'POST') {
            $this->_endpoint->add($this->_params);
            $params = array();
        } else {
            $params = $this->_params;
        }

        try {
            $result = $this->_http->request($this->_request, $this->_endpoint->toString(true), $params);
        } catch (Horde_Http_Client_Exception $e) {
            $this->_facebook->logger->err($e->getMessage());
            throw new Horde_Service_Facebook_Exception($e);
        }

        if ($result->code != '200') {
            throw new Horde_Service_Facebook_Exception($result->getBody());
        }

        return json_decode($result->getBody());
    }

}