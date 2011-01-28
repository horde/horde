<?php
/**
 * Upload Requests
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_UploadRequest extends Horde_Service_Facebook_Request
{
    /**
     * Filename to upload
     *
     * @var string
     */
    protected $_filename;

    /**
     * Const'r
     *
     * @param Horde_Service_Facebook $facebook
     * @param string                 $method
     * @param Horde_Http_Client      $http_client
     * @param string                 $file
     * @param array                  $params
     */
    public function __construct($facebook, $method, $http_client, $file,
                                $params = array())
    {
        parent::__construct($facebook, $method, $http_client, $params);
        $this->_filename = $file;
    }

    /**
     * Run the request
     *
     * @return mixed
     */
    public function run()
    {
        // Ensure we ask for JSON
        $this->_params['format'] = 'json';
        $result = $this->_multipartHttpTransaction();
        return $result;
    }

    /**
     * Execute a RFC1867/RFC1341 Multipart Http Transaction.
     *
     * @throws Horde_Service_Facebook_Exception
     *
     * @return string
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
        $this->_finalizeParams($this->_method, $this->_params);
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
        try {
            $result = $this->_http->request('POST',
                                            Horde_Service_Facebook::REST_SERVER_ADDR,
                                            $content,
                                            array('Content-Type' => $content_type,
                                                  'Content-Length' => strlen($content)));
        } catch (Exception $e) {
            throw new Horde_Service_Facebook_Exception(sprintf(Horde_Service_Facebook_Translation::t("Upload failed: %s"), $e->getMessage()));
        }

        return $result->getBody();
    }

}