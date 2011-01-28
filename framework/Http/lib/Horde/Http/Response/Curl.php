<?php
/**
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Http_Response_Curl extends Horde_Http_Response_Base
{
    /**
     * Info on the request obtained from curl_getinfo().
     *
     * @var array
     */
    protected $_info = array();

    /**
     * Response body.
     *
     * @var string
     */
    protected $_body;

    /**
     * Constructor.
     *
     * @param string $uri
     * @param string $curlresult
     * @param array $curlinfo
     */
    public function __construct($uri, $curlresult, $curlinfo)
    {
        $this->uri = $uri;
        $this->_parseResult($curlresult);
        $this->_parseInfo($curlinfo);
    }

    /**
     * Returns the body of the HTTP response.
     *
     * @return string HTTP response body.
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * Parses the combined header/body result from cURL.
     *
     * @param string $curlresult
     */
    protected function _parseResult($curlresult)
    {
        /* Curl returns multiple headers, if the last action required multiple
         * requests, e.g. when doing Digest authentication. Only parse the
         * headers of the latest response. */
        preg_match_all('/(^|\r\n\r\n)(HTTP\/)/', $curlresult, $matches, PREG_OFFSET_CAPTURE);
        $startOfHeaders = $matches[2][count($matches[2]) - 1][1];
        $endOfHeaders = strpos($curlresult, "\r\n\r\n", $startOfHeaders);
        $headers = substr($curlresult, $startOfHeaders, $endOfHeaders - $startOfHeaders);
        $this->_parseHeaders($headers);
        $this->_body = substr($curlresult, $endOfHeaders + 4);
    }

    /**
     * Processes the results of curl_getinfo.
     *
     * @param array $curlinfo
     */
    protected function _parseInfo($curlinfo)
    {
        $this->uri = $curlinfo['url'];
        $this->code = $curlinfo['http_code'];
        $this->_info = $curlinfo;
    }
}
