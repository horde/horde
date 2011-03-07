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

        $this->headers = array();

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
                $this->headers = array();
                $lastHeader = null;
            }

            $headerLine = trim($headerLine, "\r\n");
            if ($headerLine == '') {
                break;
            }
            if (preg_match('|^([\w-]+):\s+(.+)|', $headerLine, $m)) {
                unset($lastHeader);
                $headerName = strtolower($m[1]);
                $headerValue = $m[2];

                if (isset($this->headers[$headerName])) {
                    if (!is_array($this->headers[$headerName])) {
                        $this->headers[$headerName] = array($this->headers[$headerName]);
                    }

                    $this->headers[$headerName][] = $headerValue;
                } else {
                    $this->headers[$headerName] = $headerValue;
                }
                $lastHeader = $headerName;
            } elseif (preg_match("|^\s+(.+)$|", $headerLine, $m) && !is_null($lastHeader)) {
                if (is_array($this->headers[$lastHeader])) {
                    end($this->headers[$lastHeader]);
                    $this->headers[$lastHeader][key($this->headers[$lastHeader])] .= $m[1];
                } else {
                    $this->headers[$lastHeader] .= $m[1];
                }
            }
        }
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
     *                        'Content-Length', etc.). This is case sensitive.
     *
     * @return string HTTP header value.
     */
    public function getHeader($header)
    {
        return isset($this->headers[$header]) ? $this->headers[$header] : null;
    }

}
