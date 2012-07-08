<?php
/**
 * Horde_Service_Facebook_Request:: encapsulate a request to the Facebook API.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Request_Rest extends Horde_Service_Facebook_Request_Base
{
    /**
     * Run this request and return the data.
     *
     * @return array  The results of the request.
     *
     * @throws Horde_Service_Facebook_Exception
     */
    public function run()
    {
        $data = $this->_postRequest($this->_method, $this->_params);

        if (defined('JSON_BIGINT_AS_STRING')) {
            $result = json_decode($data, true, constant('JSON_BIGINT_AS_STRING'));
        } else {
            if (is_numeric($data)) {
                $result = $data;
            } else {
                $result = json_decode($data, true);
            }
        }

        if (is_array($result) && isset($result['error_code'])) {
            throw new Horde_Service_Facebook_Exception($result['error_msg'], $result['error_code']);
        }

        return $result;
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
        throw new Horde_Service_Facebook_Exception('Deprecated');
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
        $this->_finalizeParams($params);
        try {
            $url = new Horde_Url(Horde_Service_Facebook::REST_SERVER_ADDR . $method);
            $result = $this->_http->request('POST', $url->toString(), $this->_createPostString($params));
        } catch (Exception $e) {
            $this->_facebook->logger->err($e->getMessage());
            throw new Horde_Service_Facebook_Exception(Horde_Service_Facebook_Translation::t("Facebook service is unavailable. Please try again later."));
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
    protected function _finalizeParams(&$params)
    {
        // Run through the params and see if any of them are arrays. If so,
        // json encode them, as per the new Facebook API guidlines.
        // http://www.facebook.com/developers/message.php#msg_351
        foreach ($params as &$param) {
            if (is_array($param)) {
                $param = json_encode($param);
            }
        }

        $this->_addStandardParams($params);
    }

    /**
     * Adds standard facebook api parameters to $params
     *
     * @param array  $params  Method parameters
     *
     * @return void
     */
    protected function _addStandardParams(&$params)
    {
        $params['access_token'] = $this->_facebook->auth->getSessionKey();
        $params['format'] = 'json';
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

}