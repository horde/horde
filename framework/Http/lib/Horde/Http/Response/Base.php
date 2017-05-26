<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Http
 */

/**
 * Base class for HTTP response objects.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Http
 */
abstract class Horde_Http_Response_Base
{
    /**
     * Fetched URI.
     *
     * @var string
     */
    public $uri;

    /**
     * HTTP protocol version that was used.
     *
     * @var float
     */
    public $httpVersion;

    /**
     * HTTP response code.
     *
     * @var integer
     */
    public $code;

    /**
     * Response headers.
     *
     * @var array
     */
    public $headers;

    /**
     * Case-insensitive list of headers.
     *
     * @var Horde_Support_CaseInsensitiveArray
     */
    protected $_headers;

    /**
     * Parses an array of response headers, mindful of line continuations, etc.
     *
     * @param array $headers
     *
     * @return array
     */
    protected function _parseHeaders($headers)
    {
        if (!is_array($headers)) {
            $headers = preg_split("/\r?\n/", $headers);
        }

        $this->_headers = new Horde_Support_CaseInsensitiveArray();

        $lastHeader = null;
        foreach ($headers as $headerLine) {
            // stream_get_meta returns all headers generated while processing
            // a request, including ones for redirects before an eventually
            // successful request. We just want the last one, so whenever we
            // hit a new HTTP header, throw out anything parsed previously and
            // start over.
            if (preg_match('/^HTTP\/(\d.\d) (\d{3})/', $headerLine, $httpMatches)) {
                $this->httpVersion = $httpMatches[1];
                $this->code = (int)$httpMatches[2];
                $this->_headers = new Horde_Support_CaseInsensitiveArray();
                $lastHeader = null;
            }

            $headerLine = trim($headerLine, "\r\n");
            if ($headerLine == '') {
                break;
            }
            if (preg_match('|^([\w-]+):\s+(.+)|', $headerLine, $m)) {
                $headerName = $m[1];
                $headerValue = $m[2];

                if ($tmp = $this->_headers[$headerName]) {
                    if (!is_array($tmp)) {
                        $tmp = array($tmp);
                    }
                    $tmp[] = $headerValue;
                    $headerValue = $tmp;
                }

                $this->_headers[$headerName] = $headerValue;
                $lastHeader = $headerName;
            } elseif (preg_match("|^\s+(.+)$|", $headerLine, $m) &&
                      !is_null($lastHeader)) {
                if (is_array($this->_headers[$lastHeader])) {
                    $tmp = $this->_headers[$lastHeader];
                    end($tmp);
                    $tmp[key($tmp)] .= $m[1];
                    $this->_headers[$lastHeader] = $tmp;
                } else {
                    $this->_headers[$lastHeader] .= $m[1];
                }
            }
        }

        $this->headers = array_change_key_case($this->_headers->getArrayCopy());
    }

    /**
     * Returns the body of the HTTP response.
     *
     * @throws Horde_Http_Exception
     * @return string HTTP response body.
     */
    abstract public function getBody();

    /**
     * Returns a stream pointing to the response body that can be used with all
     * standard PHP stream functions.
     */
    public function getStream()
    {
        $string_body = $this->getBody();
        $body = new Horde_Support_StringStream($string_body);
        return $body->fopen();
    }

    /**
     * Returns the value of a single response header.
     *
     * @param string $header  Header name to get ('Content-Type',
     *                        'Content-Length', etc.).
     *
     * @return string  HTTP header value.
     */
    public function getHeader($header)
    {
        return $this->_headers[$header];
    }
}
