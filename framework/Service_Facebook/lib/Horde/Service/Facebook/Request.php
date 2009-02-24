<?php
/**
 * Horde_Service_Facebook_Request:: encapsulate a request to the Facebook API.
 *
 */
class Horde_Service_Facebook_Request
{
    protected $_facebook;
    protected  $_last_call_id = 0;
    protected $_http;
    private $_method;
    private $_params;

    public function __construct($facebook, $method, $http_client, $params = array())
    {
        $this->_facebook = $facebook;
        $this->_http = $http_client;
        $this->_method = $method;
        $this->_params = $params;
    }

    /**
     * Run this request and return the data.
     * TODO: Still return by ref until the rest of the code is refactored to not
     * use the original post_request method call.
     *
     * @return unknown_type
     */
    public function &run()
    {
        $data = $this->_postRequest($this->_method, $this->_params);
        $result = json_decode($data, true);
        if (is_array($result) && isset($result['error_code'])) {
            throw new Horde_Service_Facebook_Exception($result['error_msg'], $result['error_code']);
        }
        return $result;
    }

    protected function _postRequest($method, $params)
    {
        $this->_finalizeParams($method, $params);
        // TODO: Figure out why passing the array to ->post doesn't work -
        //       we have to manually create the post string or we get an
        //       invalid signature error from FB
        $post_string = $this->_createPostString($params);
        $result = $this->_http->post(Horde_Service_Facebook::REST_SERVER_ADDR, $post_string);
        return $result->getBody();
    }

    /**
     *
     * @param $method
     * @param $params
     * @return unknown_type
     */
    protected function _finalizeParams($method, &$params)
    {
        $this->_addStandardParams($method, $params);
        // we need to do this before signing the params
        $this->_convertToCsv($params);
        $params['sig'] = Horde_Service_Facebook_Auth::generateSignature($params, $this->_facebook->secret);
    }

    protected function _addStandardParams($method, &$params)
    {
        // We only support JSON
        $params['format'] = 'json';
        $params['method'] = $method;
        $params['api_key'] = $this->_facebook->api_key;
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

    protected function _convertToCsv(&$params)
    {
        foreach ($params as $key => &$val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
        }
    }

    /**
     * TODO: Figure out why using http_build_query doesn't work here.
     *
     */
    protected function _createPostString($params)
    {
        $post_params = array();
        foreach ($params as $key => &$val) {
            $post_params[] = $key.'='.urlencode($val);
        }

        return implode('&', $post_params);
    }

}