<?php
/**
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http_Client
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http_Client
 */
class Horde_Http_Client_Adapter_Peclhttp extends Horde_Http_Client_Adapter_Base
{
    public static $methods = array(
        'GET' => HTTP_METH_GET,
        'HEAD' => HTTP_METH_HEAD,
        'POST' => HTTP_METH_POST,
        'PUT' => HTTP_METH_PUT,
        'DELETE' => HTTP_METH_DELETE,
    );

    public function __construct()
    {
        if (!class_exists('HttpRequest', false)) {
            throw new Horde_Http_Client_Exception('The pecl_http extension is not installed. See http://php.net/http.install');
        }
    }

    /**
     * Send an HTTP request
     *
     * @param Horde_Http_Client_Request  HTTP Request object
     *
     * @return Horde_Http_Client_Response
     */
    public function send($request)
    {
        $httpRequest = new HttpRequest($request->uri, self::$methods[$request->method]);
        $httpRequest->setHeaders($request->headers);

        $data = $request->data;
        if (is_array($data)) {
            $httpRequest->setPostFields($data);
        } else {
            $httpRequest->setRawPostData($data);
        }

        try {
            $httpResponse = $httpRequest->send();
        } catch (HttpException $e) {
            throw new Horde_Http_Client_Exception($e->getMessage(), $e->getCode(), $e);
        }

        /*@TODO build Horde_Http_Client_Response from $httpResponse */
        return $httpResponse;
    }
}
