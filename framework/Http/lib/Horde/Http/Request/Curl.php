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
    public function __construct($args = array())
    {
        if (!extension_loaded('curl')) {
            throw new Horde_Http_Exception('The curl extension is not installed. See http://php.net/curl.installation');
        }

        parent::__construct($args);
    }

    /**
     * Send this HTTP request
     *
     * @return Horde_Http_Response_Base
     */
    public function send()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->uri);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);

        $data = $this->data;
        if (is_array($data)) {
            // If we don't set POSTFIELDS to a string, and the first value
            // begins with @, it will be treated as a filename, and the proper
            // POST data isn't passed.
            $data = http_build_query($data);
        }
        if ($data) { curl_setopt($curl, CURLOPT_POSTFIELDS, $data); }

        $result = curl_exec($curl);
        if ($result === false) {
            throw new Horde_Http_Exception(curl_error($curl), curl_errno($curl));
        }
        $info = curl_getinfo($curl);
        return new Horde_Http_Response_Curl($this->uri, $result, $info);
    }
}
