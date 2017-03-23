<?php
/**
 * Copyright 2008-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   James Pepin <james@bluestatedigital.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Controller
 */

/**
 *
 *
 * @author    James Pepin <james@bluestatedigital.com>
 * @category  Horde
 * @copyright 2008-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Controller
 */
class Horde_Controller_Response
{
    protected $_headers = array();
    protected $_body;
    protected $_requestConfiguration;

    public function __construct()
    {
    }

    public function setHeaders(array $headers)
    {
        $this->_headers = array_merge($this->_headers, $headers);
    }

    public function setHeader($name, $value)
    {
        $this->_headers[$name] = $value;
    }

    public function setContentType($contentType, $charset = 'UTF-8')
    {
        $this->setHeader('Content-Type', "$contentType; charset=$charset");
    }

    public function setBody($body)
    {
        $this->_body = $body;
    }

    public function getHeaders()
    {
        return $this->_headers;
    }

    public function getBody()
    {
        return $this->_body;
    }

    public function internalRedirect()
    {
        return $this->_requestConfiguration != null;
    }

    public function setRedirectUrl($url)
    {
        $this->_headers['Location'] = $url;
    }

    public function getRedirectConfiguration()
    {
        return $this->_requestConfiguration;
    }

    public function setRedirectConfiguration(Horde_Controller_RequestConfiguration $config)
    {
        $this->_requestConfiguration = $config;
    }
}
