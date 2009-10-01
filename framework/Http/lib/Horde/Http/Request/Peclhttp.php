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
class Horde_Http_Request_Peclhttp extends Horde_Http_Request_Base
{
    public static $methods = array(
        'GET' => HTTP_METH_GET,
        'HEAD' => HTTP_METH_HEAD,
        'POST' => HTTP_METH_POST,
        'PUT' => HTTP_METH_PUT,
        'DELETE' => HTTP_METH_DELETE,
    );

    public function __construct()
    {
        if (!class_exists('HttpRequest', false)) {
            throw new Horde_Http_Exception('The pecl_http extension is not installed. See http://php.net/http.install');
        }
    }

    /**
     * Send this HTTP request
     *
     * @return Horde_Http_Response_Base
     */
    public function send()
    {
        $httpRequest = new HttpRequest($this->uri, self::$methods[$this->method]);
        $httpRequest->setHeaders($this->headers);

        $data = $this->data;
        if (is_array($data)) {
            $httpRequest->setPostFields($data);
        } else {
            $httpRequest->setRawPostData($data);
        }

        $httpOptions = array();

        // Proxy

        // Set authentication data
        if ($this->username) {
            $httpOptions['httpauth'] = $this->username . ':' . $this->password;
            switch ($this->authenticationScheme) {
            case Horde_Http::AUTH_ANY:
                $httpOptions['httpauthtype'] = HTTP_AUTH_ANY;
                break;

            case Horde_Http::AUTH_BASIC:
                $httpOptions['httpauthtype'] = HTTP_AUTH_BASIC;
                break;

            case Horde_Http::AUTH_DIGEST:
                $httpOptions['httpauthtype'] = HTTP_AUTH_DIGEST;
                break;

            case Horde_Http::AUTH_GSSNEGOTIATE:
                $httpOptions['httpauthtype'] = HTTP_AUTH_GSSNEG;
                break;

            case Horde_Http::AUTH_NTLM:
                $httpOptions['httpauthtype'] = HTTP_AUTH_NTLM;
                break;

            default:
                throw new Horde_Http_Exception('Unsupported authentication scheme (' . $this->authenticationScheme . ')');
            }
        }

        // Set options
        $httpRequest->setOptions($httpOptions);

        try {
            $httpResponse = $httpRequest->send();
        } catch (HttpException $e) {
            throw new Horde_Http_Exception($e->getMessage(), $e->getCode(), $e);
        }

        return new Horde_Http_Response_Peclhttp($this->uri, $httpResponse);
    }
}
