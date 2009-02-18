<?php
/**
 * Horde_Service_Facebook_Request:: encapsulate a request to the Facebook API.
 *
 */
class Horde_Service_Facebook_Request
{
    private $_facebook;
    private  $_last_call_id = 0;
    private $_http;
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
        $data = $this->_post_request($this->_method, $this->_params);
        $result = json_decode($data, true);
        if (is_array($result) && isset($result['error_code'])) {
            throw new Horde_Service_Facebook_Exception($result['error_msg'], $result['error_code']);
        }
        return $result;
    }

    protected function _post_request($method, $params)
    {
        $this->_finalize_params($method, $params);
        // TODO: Figure out why passing the array to ->post doesn't work -
        //       we have to manually create the post string or we get an
        //       invalid signature error from FB
        $post_string = $this->_create_post_string($params);
        $result = $this->_http->post(Horde_Service_Facebook::REST_SERVER_ADDR, $post_string);
        return $result->getBody();
    }

    /**
     *
     * @param $method
     * @param $params
     * @return unknown_type
     */
    private function _finalize_params($method, &$params)
    {
        $this->_add_standard_params($method, $params);
        // we need to do this before signing the params
        $this->convert_array_values_to_csv($params);
        $params['sig'] = Horde_Service_Facebook::generate_sig($params, $this->_facebook->secret);
    }

    private function _add_standard_params($method, &$params)
    {
        // We only support JSON
        $params['format'] = 'json';
        $params['method'] = $method;
        $params['session_key'] = $this->_facebook->session_key;
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

    private function convert_array_values_to_csv(&$params)
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
    private function _create_post_string($params)
    {
        $post_params = array();
        foreach ($params as $key => &$val) {
            $post_params[] = $key.'='.urlencode($val);
        }

        return implode('&', $post_params);
    }

}