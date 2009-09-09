<?php
/**
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */
class Horde_Http_Request_Curl extends Horde_Http_Request_Base
{
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new Horde_Http_Exception('The curl extension is not installed. See http://php.net/curl.installation');
        }
    }

    /**
     * Send this HTTP request
     *
     * @return Horde_Http_Response_Base
     */
    public function send()
    {
        $body = tmpfile();
        $headers = tmpfile();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->uri);
        curl_setopt($curl, CURLOPT_FILE, $body);
        curl_setopt($curl, CURLOPT_WRITEHEADER, $headers);

        // If we don't set POSTFIELDS to a string, and the first value begins
        // with @, it will be treated as a filename, and the proper POST data
        // isn't passed.
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($this->data));

        $result = curl_exec($curl);

        rewind($body);
        rewind($headers);

        return new Horde_Http_Response_Curl($this->uri, $body, stream_get_contents($headers));
    }
}
