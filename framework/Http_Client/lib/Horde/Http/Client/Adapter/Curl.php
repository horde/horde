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
class Horde_Http_Client_Adapter_Curl extends Horde_Http_Client_Adapter_Base
{
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new Horde_Http_Client_Exception('The curl extension is not installed. See http://php.net/curl.installation');
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
        $body = tmpfile();
        $headers = tmpfile();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $request->uri);
        curl_setopt($curl, CURLOPT_FILE, $body);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request->data);
        curl_setopt($curl, CURLOPT_WRITEHEADER, $headers);

        $result = curl_exec($curl);

        rewind($body);
        rewind($headers);

        return new Horde_Http_Client_Response($request->uri, $body, stream_get_contents($headers));
    }
}
