<?php
/**
 * Horde_Service_Facebook_Request:: encapsulate a request to the Facebook API.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_Request
{
    protected $_facebook;
    protected  $_last_call_id = 0;
    protected $_http;
    protected $_method;
    protected $_params;

    /**
     * Const'r
     *
     * @param Horde_Service_Facebook $facebook
     * @param string                 $method
     * @param Horde_Http_Client      $http_client
     * @param array                  $params
     */
    public function __construct($facebook, $method, $http_client,
                                $params = array())
    {
        $this->_facebook = $facebook;
        $this->_http = $http_client;
        $this->_method = $method;
        $this->_params = $params;
    }

    /**
     * Run this request and return the data.
     *
     * @param string $dataFormat  Optionally specify the datatype to return.
     *
     * @return mixed Either raw XML, JSON, or an array of decoded values.
     * @throws Horde_Service_Facebook_Exception
     */
    public function &run()
    {
        $data = $this->_postRequest($this->_method, $this->_params);
        switch ($this->_facebook->dataFormat) {
        case Horde_Service_Facebook::DATA_FORMAT_JSON:
        case Horde_Service_Facebook::DATA_FORMAT_XML:
            // Return the raw data, calling code is resposible for decoding.
            return $data;

        case Horde_Service_Facebook::DATA_FORMAT_ARRAY:
            if ($this->_facebook->internalFormat == Horde_Service_Facebook::DATA_FORMAT_JSON) {
                $result = json_decode($data, true);
            } else {
                $result = $this->_xmlToResult($data);
            }
        }
        if (is_array($result) && isset($result['error_code'])) {
            throw new Horde_Service_Facebook_Exception($result['error_msg'], $result['error_code']);
        }
        return $result;
    }

    /**
     * Send a POST request
     *
     * @param string $method  The method to call.
     * @param array  $params  The method parameters.
     *
     * @return string The request results
     * @throws Horde_Service_Facebook_Exception
     */
    protected function _postRequest($method, &$params)
    {
        $this->_finalizeParams($method, $params);
        // TODO: Figure out why passing the array to ->post doesn't work -
        //       we have to manually create the post string or we get an
        //       invalid signature error from FB
        $post_string = $this->_createPostString($params);
        try {
            $result = $this->_http->post(Horde_Service_Facebook::REST_SERVER_ADDR, $post_string);
        } catch (Exception $e) {
            // Not much we can do about a client exception - rethrow it as
            // temporarily unavailable.
            throw new Horde_Service_Facebook_Exception(Horde_Service_Facebook_Translation::t("Service is unavailable. Please try again later."));
        }
        return $result->getBody();
    }

    /**
     * Finalize, sanity check, standardze and sign the method parameters, $params
     *
     * @param string $method  The method name
     * @param array  $params  Method parameters
     *
     * @return void
     */
    protected function _finalizeParams($method, &$params)
    {
        // Run through the params and see if any of them are arrays. If so,
        // json encode them, as per the new Facebook API guidlines.
        // http://www.facebook.com/developers/message.php#msg_351
        foreach ($params as &$param) {
            if (is_array($param)) {
                $param = json_encode($param);
            }
        }

        $this->_addStandardParams($method, $params);
        // we need to do this before signing the params
        $this->_convertToCsv($params);
        $params['sig'] = Horde_Service_Facebook_Auth::generateSignature($params, $this->_facebook->secret);
    }

    /**
     * Adds standard facebook api parameters to $params
     *
     * @param string $method  The method name
     * @param array  $params  Method parameters
     *
     * @return void
     */
    protected function _addStandardParams($method, &$params)
    {
        // Select the correct data format.
        if ($this->_facebook->dataFormat == Horde_Service_Facebook::DATA_FORMAT_ARRAY) {
            $params['format'] = $this->_facebook->internalFormat;
        } else {
            $params['format'] = $this->_facebook->dataFormat;
        }

        $params['method'] = $method;
        $params['api_key'] = $this->_facebook->apiKey;
        $params['call_id'] = microtime(true);
        if ($params['call_id'] <= $this->_last_call_id) {
            $params['call_id'] = $this->_last_call_id + 0.001;
        }
        $this->_last_call_id = $params['call_id'];
        if (!isset($params['v'])) {
            $params['v'] = '1.0';
        }
        if (!empty($this->_facebook->useSslResources)) {
            $params['return_ssl_resources'] = true;
        }
    }

    /**
     * Helper function to convert array to CSV string
     *
     * @param array $params
     * @return string
     */
    protected function _convertToCsv(&$params)
    {
        foreach ($params as $key => &$val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
        }
    }

    /**
     * Create a string suitable for sending as POST data.
     *
     * TODO: Figure out why using http_build_query doesn't work here.
     *
     * @param array $params  The parameters array
     *
     * @return string  The POST string
     */
    protected function _createPostString($params)
    {
        $post_params = array();
        foreach ($params as $key => &$val) {
            $post_params[] = $key.'='.urlencode($val);
        }

        return implode('&', $post_params);
    }

    /**
     *
     * @param string $xml
     *
     * @return array
     */
    private function _xmlToResult($xml)
    {
        $sxml = simplexml_load_string($xml);
        $result = self::_simplexmlToArray($sxml);

        return $result;
    }

    /**
     *
     * @param string $sxml
     *
     * @return array
     */
    private static function _simplexmlToArray($sxml)
    {
        $arr = array();
        if ($sxml) {
            foreach ($sxml as $k => $v) {
                if ($sxml['list']) {
                    $arr[] = self::_SimplexmlToArray($v);
                } else {
                    $arr[$k] = self::_SimplexmlToArray($v);
                }
            }
        }
        if (sizeof($arr) > 0) {
            return $arr;
        } else {
            return (string)$sxml;
        }
    }

}