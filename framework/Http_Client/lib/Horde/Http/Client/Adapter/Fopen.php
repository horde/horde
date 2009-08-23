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
class Horde_Http_Client_Adapter_Fopen extends Horde_Http_Client_Adapter_Base
{
    public function __construct()
    {
        if (!ini_get('allow_url_fopen')) {
            throw new Horde_Http_Client_Exception('allow_url_fopen must be enabled');
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
        $method = $request->method;
        $uri = $request->uri;
        $headers = $request->headers;
        $data = $request->data;
        if (is_array($data)) {
            $data = http_build_query($data, '', '&');
        }

        $opts = array('http' => array());

        // Proxy settings - check first, so we can include the correct headers
        if ($this->proxyServer) {
            $opts['http']['proxy'] = 'tcp://' . $this->proxyServer;
            $opts['http']['request_fulluri'] = true;
            if ($this->proxyUser && $this->proxyPass) {
                $headers['Proxy-Authorization'] = 'Basic ' . base64_encode($this->proxyUser . ':' . $this->proxyPass);
            }
        }

        // Concatenate the headers
        $hdr = array();
        foreach ($headers as $header => $value) {
            $hdr[] = $header . ': ' . $value;
        }

        // Stream context config.
        $opts['http']['method'] = $method;
        $opts['http']['header'] = implode("\n", $hdr);
        $opts['http']['content'] = $data;
        $opts['http']['timeout'] = $this->timeout;

        $context = stream_context_create($opts);
        $stream = @fopen($uri, 'rb', false, $context);
        if (!$stream) {
            $error = error_get_last();
            if (preg_match('/HTTP\/(\d+\.\d+) (\d{3}) (.*)$/', $error['message'], $matches)) {
                // Create a Response for the HTTP error code
                return new Horde_Http_Client_Response($uri, null, $matches[0]);
            } else {
                throw new Horde_Http_Client_Exception('Problem with ' . $uri . ': ', $error);
            }
        }

        $meta = stream_get_meta_data($stream);
        $headers = isset($meta['wrapper_data']) ? $meta['wrapper_data'] : array();

        return new Horde_Http_Client_Response($uri, $stream, $headers);
    }

}
