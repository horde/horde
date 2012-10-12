<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * Resources:
 * http://wezfurlong.org/blog/2006/nov/http-post-from-php-without-curl
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */
class Horde_Http_Request_Fopen extends Horde_Http_Request_Base
{
    /**
     * Catched errors from the error handler.
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Constructor
     *
     * @throws Horde_Http_Exception
     */
    public function __construct($args = array())
    {
        if (!ini_get('allow_url_fopen')) {
            throw new Horde_Http_Exception('allow_url_fopen must be enabled');
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
        $method = $this->method;
        $uri = $this->uri;
        $headers = $this->headers;
        $data = $this->data;
        if (is_array($data)) {
            $data = http_build_query($data, '', '&');
        }

        $opts = array('http' => array());

        // Proxy settings
        if ($this->proxyServer) {
            $opts['http']['proxy'] = 'tcp://' . $this->proxyServer;
            if ($this->proxyPort) {
                $opts['http']['proxy'] .= ':' . $this->proxyPort;
            }
            $opts['http']['request_fulluri'] = true;
            if ($this->proxyUsername && $this->proxyPassword) {
                // @TODO check $this->proxyAuthenticationScheme
                $headers['Proxy-Authorization'] = 'Basic ' . base64_encode($this->proxyUsername . ':' . $this->proxyPassword);
            }
            if ($this->proxyType != Horde_Http::PROXY_HTTP) {
                throw new Horde_Http_Exception(sprintf('Proxy type %s not supported by this request type!', $this->proxyType));
            }
        }

        // Authentication settings
        if ($this->username) {
            switch ($this->authenticationScheme) {
            case Horde_Http::AUTH_BASIC:
            case Horde_Http::AUTH_ANY:
                $headers['Authorization'] = 'Basic ' . base64_encode($this->username . ':' . $this->password);
                break;

            default:
                throw new Horde_Http_Exception('Unsupported authentication scheme (' . $this->authenticationScheme . ')');
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
        $opts['http']['max_redirects'] = $this->redirects;
        $opts['http']['ignore_errors'] = true;
        $opts['ssl']['verify_peer'] = $this->verifyPeer;

        $context = stream_context_create($opts);
        set_error_handler(array($this, '_errorHandler'), E_WARNING | E_NOTICE);
        $stream = fopen($uri, 'rb', false, $context);
        restore_error_handler();
        if (!$stream) {
            if (preg_match('/HTTP\/(\d+\.\d+) (\d{3}) (.*)$/', $this->_errors[0]['message'], $matches)) {
                // Create a Response for the HTTP error code
                return new Horde_Http_Response_Fopen($uri, null, $matches[0]);
            } else {
                throw new Horde_Http_Exception('Problem with ' . $uri . ': ' . implode('. ', array_reverse($this->_errors)));
            }
        }

        $meta = stream_get_meta_data($stream);
        $headers = isset($meta['wrapper_data']) ? $meta['wrapper_data'] : array();

        return new Horde_Http_Response_Fopen($uri, $stream, $headers);
    }

    /**
     * PHP error handler.
     *
     * @param integer $errno     See set_error_handler().
     * @param string $errstr     See set_error_handler().
     * @param string $errfile    See set_error_handler().
     * @param integer $errline   See set_error_handler().
     * @param array $errcontext  See set_error_handler().
     */
    protected function _errorHandler($errno, $errstr, $errfile, $errline,
                                     $errcontext)
    {
        array_unshift($this->_errors, preg_replace('/^(.*?) \[<a href[^\]]*\](.*)/', '$1$2', $errstr));
    }
}
