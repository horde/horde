<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
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
    /**
     * Map of HTTP authentication schemes from Horde_Http constants to
     * HTTP_AUTH constants.
     *
     * @var array
     */
    protected $_httpAuthSchemes = array(
        Horde_Http::AUTH_ANY => CURLAUTH_ANY,
        Horde_Http::AUTH_BASIC => CURLAUTH_BASIC,
        Horde_Http::AUTH_DIGEST => CURLAUTH_DIGEST,
        Horde_Http::AUTH_GSSNEGOTIATE => CURLAUTH_GSSNEGOTIATE,
        Horde_Http::AUTH_NTLM => CURLAUTH_NTLM,
    );

    /**
     * Constructor
     *
     * @throws Horde_Http_Exception
     */
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
     * @throws Horde_Http_Exception
     * @return Horde_Http_Response_Base
     */
    public function send()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->uri);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

        $data = $this->data;
        if (is_array($data)) {
            // If we don't set POSTFIELDS to a string, and the first value
            // begins with @, it will be treated as a filename, and the proper
            // POST data isn't passed.
            $data = http_build_query($data);
        }
        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        // Proxy settings
        if ($this->proxyServer) {
            curl_setopt($curl, CURLOPT_PROXY, $this->proxyServer);
            if ($this->proxyPort) {
                curl_setopt($curl, CURLOPT_PROXYPORT, $this->proxyPort);
            }
            if ($this->proxyUsername && $this->proxyPassword) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->proxyUsername . ':' . $this->proxyPassword);
                curl_setopt($curl, CURLOPT_PROXYAUTH, $this->_httpAuthScheme($this->proxyAuthenticationScheme));
            }
        }

        // Authentication settings
        if ($this->username) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->username . ':' . $this->password);
            curl_setopt($curl, CURLOPT_HTTPAUTH, $this->_httpAuthScheme($this->authenticationScheme));
        }

        // Concatenate the headers
        $hdr = array();
        foreach ($this->headers as $header => $value) {
            $hdr[] = $header . ': ' . $value;
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $hdr);

        $result = curl_exec($curl);
        if ($result === false) {
            throw new Horde_Http_Exception(curl_error($curl), curl_errno($curl));
        }
        $info = curl_getinfo($curl);
        return new Horde_Http_Response_Curl($this->uri, $result, $info);
    }

    /**
     * Translate a Horde_Http::AUTH_* constant to CURLAUTH_*
     *
     * @param const
     * @throws Horde_Http_Exception
     * @return const
     */
    protected function _httpAuthScheme($httpAuthScheme)
    {
        if (!isset($this->_httpAuthSchemes[$httpAuthScheme])) {
            throw new Horde_Http_Exception('Unsupported authentication scheme (' . $httpAuthScheme . ')');
        }
        return $this->_httpAuthSchemes[$httpAuthScheme];
    }
}
