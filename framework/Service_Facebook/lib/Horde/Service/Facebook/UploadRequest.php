<?php
/**
 * Upload Requests
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_UploadRequest extends Horde_Service_Facebook_Request
{
    protected $_filename;

    public function __construct($facebook, $method, $http_client, $file, $params = array())
    {
        parent::__construct($facebook, $method, $http_client, $params);
        $this->_filename = $file;
    }

    public function run()
    {
        // Ensure we ask for JSON
        $this->_params['format'] = 'json';
        $result = $this->_multipartHttpTransaction();
        return $result;
    }

    /**
     * TODO
     *
     * @param $method
     * @param $params
     * @param $file
     * @param $server_addr
     * @return unknown_type
     */
    private function _multipartHttpTransaction()
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
        $this->_finalizeParams();
        foreach ($this->_params as $key => &$val) {
            $content_lines[] = $delimiter;
            $content_lines[] = 'Content-Disposition: form-data; name="' . $key . '"';
            $content_lines[] = '';
            $content_lines[] = $val;
        }

        // now add the file data
        $content_lines[] = $delimiter;
        $content_lines[] = 'Content-Disposition: form-data; filename="' . $this->_filename . '"';
        $content_lines[] = 'Content-Type: application/octet-stream';
        $content_lines[] = '';
        $content_lines[] = file_get_contents($this->_filename);
        $content_lines[] = $close_delimiter;
        $content_lines[] = '';
        $content = implode("\r\n", $content_lines);
        $result = $this->_http->request('POST',
                                        Horde_Service_Facebook::REST_SERVER_ADDR,
                                        $content,
                                        array('Content-Type' => $content_type,
                                              'Content-Length' => strlen($content)));
        return $result->getBody();
    }

}