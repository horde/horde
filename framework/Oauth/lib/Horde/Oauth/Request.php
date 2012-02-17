<?php
/**
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Oauth
 */

/**
 * OAuth request class
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Oauth
 */
class Horde_Oauth_Request
{
    const VERSION = '1.0';

    protected $_params = array();
    protected $_url;
    protected $_method;

    function __construct($url, $params = array(), $method = 'POST')
    {
        if (!isset($params['oauth_version'])) {
            $params['oauth_version'] = self::VERSION;
        }
        if (!isset($params['oauth_nonce'])) {
            $params['oauth_nonce'] = self::_generateNonce();
        }
        if (!isset($params['oauth_timestamp'])) {
            $params['oauth_timestamp'] = time();
        }

        $this->_params = $params;
        $this->_url = $url;
        $this->_method = $method;
    }

    /**
     * Sign this request in accordance with OAuth
     *
     * @param $signatureMethod
     * @param $consumer
     * @param $token
     * @return unknown_type
     */
    public function sign($signatureMethod, $consumer, $token = null)
    {
        if (empty($this->_params['oauth_consumer_key'])) {
            $this->_params['oauth_consumer_key'] = $consumer->key;
        }

        if (empty($this->_params['oauth_token']) && !empty($token)) {
            $this->_params['oauth_token'] = $token->key;
        }

        $this->_params['oauth_signature_method'] = $signatureMethod->getName();
        $this->_params['oauth_signature'] = $signatureMethod->sign($this, $consumer, $token);

        return $this->_getNormalizedUrl() . '?' . $this->buildHttpQuery();
    }

    /**
     * Returns the signable string of this request
     *
     * The base string is defined as the method, the url and the parameters
     * (normalized), each urlencoded and concatenated with &.
     */
    public function getSignatureBaseString()
    {
        $parts = array(
            $this->_getNormalizedHttpMethod(),
            $this->_getNormalizedUrl(),
            $this->_getSignableParameters()
        );

        return implode('&', array_map(array('Horde_Oauth_Utils', 'urlencodeRfc3986'), $parts));
    }

    /**
     * Get a query string suitable for use in a URL or as POST data.
     */
    public function buildHttpQuery()
    {
        $parts = array();
        foreach ($this->_params as $k => $v) {
            $parts[] = Horde_Oauth_Utils::urlencodeRfc3986($k) . '=' . Horde_Oauth_Utils::urlencodeRfc3986($v);
        }
        return implode('&', $parts);
    }

    /**
     */
    public function buildAuthorizationHeader($realm = '')
    {
        $header = '';
        foreach ($this->_params as $k => $v) {
            if (strpos($k, 'oauth_') !== false) {
                $header .= Horde_Oauth_Utils::urlencodeRfc3986($k) . '="' . Horde_Oauth_Utils::urlencodeRfc3986($v) . '",';
            }
        }
        if (!empty($realm)) {
            $header .= 'realm="' . Horde_Oauth_Utils::urlencodeRfc3986($realm) . '"';
        }
        return 'OAuth ' . $header;
    }

    /**
     * Generate a nonce.
     */
    protected static function _generateNonce()
    {
        $mt = microtime();
        $rand = mt_rand();

        return hash('md5', microtime() . mt_rand());
    }

    /**
     * Returns the normalized parameters of the request
     *
     * This will be all parameters except oauth_signature, sorted first by key,
     * and if there are duplicate keys, then by value.
     *
     * The returned string will be all the key=value pairs concatenated by &.
     *
     * @return string
     */
    protected function _getSignableParameters()
    {
        // Grab all parameters
        $params = $this->_params;

        // Remove oauth_signature if present
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        // Urlencode both keys and values
        $keys = array_map(array('Horde_Oauth_Utils', 'urlencodeRfc3986'), array_keys($params));
        $values = array_map(array('Horde_Oauth_Utils', 'urlencodeRfc3986'), array_values($params));
        $params = array_combine($keys, $values);

        // Sort by keys (natsort)
        uksort($params, 'strnatcmp');

        // Generate key=value pairs
        $pairs = array();
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                // If the value is an array, it's because there are multiple values
                // with the same key. Sort them, then add all the pairs.
                natsort($value);
                foreach ($value as $v2) {
                    $pairs[] = $key . '=' . $v2;
                }
            } else {
                $pairs[] = $key . '=' . $value;
            }
        }

        // Return the pairs, concatenated with &
        return implode('&', $pairs);
    }

    /**
     * Uppercases the HTTP method
     */
    protected function _getNormalizedHttpMethod()
    {
        return strtoupper($this->_method);
    }

    /**
     * Parse the url and rebuilds it to be scheme://host/path
     */
    protected function _getNormalizedUrl()
    {
        $parts = parse_url($this->_url);

        $port = !empty($parts['port']) ? $parts['port'] : '80';
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $path = !empty($parts['path']) ? $parts['path'] : '';

        if (($scheme == 'https' && $port != '443') ||
            ($scheme == 'http' && $port != '80')) {
            $host = "$host:$port";
        }

        return "$scheme://$host$path";
    }
}
