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
class Horde_Controller_Request_Http implements Horde_Controller_Request
{
    /**
     * Request path
     * @var string
     */
    protected $_path;

    /**
     * All the headers
     * @var array
     */
    protected $_headers = null;

    public function setPath($path)
    {
        $this->_path = $path;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getMethod()
    {
        $serverVars = $this->getServerVars();
        return $serverVars['REQUEST_METHOD'];
    }

    public function getGetVars()
    {
        return $_GET;
    }

    public function getFileVars()
    {
        return $_FILES;
    }

    public function getServerVars()
    {
        return $_SERVER;
    }

    public function getPostVars()
    {
        return $_POST;
    }

    public function getCookieVars()
    {
        return $_COOKIE;
    }

    public function getRequestVars()
    {
        return $_REQUEST;
    }

    public function getSessionId()
    {
        //TODO: how do we get session ID?
        //should probably be passing it in the constructor, or via setSession
        //we should definitely lazy-load sessions though cause we don't always care about it
        //perhaps a preFilter to start the session if the controller requests it, and then call setSession on the request
        //object
        return 0;
    }

    /**
     * Gets the value of header.
     *
     * Returns the value of the specified request header.
     *
     * @param    string  $name   the name of the header
     * @return   string          the value of the specified header
     */
    public function getHeader($name)
    {
        if ($this->_headers == null) {
            $this->_headers = $this->_getAllHeaders();
        }
        $name = Horde_String::lower($name);
        if (isset($this->_headers[$name])) {
            return $this->_headers[$name];
        }
        return null;
    }

    /**
     * Gets all the header names.
     *
     * Returns an array of all the header names this request
     * contains.
     *
     * @return   array   all the available headers as strings
     */
    public function getHeaderNames()
    {
        if ($this->_headers == null) {
            $this->_headers = $this->_getAllHeaders();
        }
        return array_keys($this->_headers);
    }

    /**
     * Gets all the headers.
     *
     * Returns an associative array of all the header names and values of this
     * request.
     *
     * @return   array   containing all the headers
     */
    public function getHeaders()
    {
        if ($this->_headers == null) {
            $this->_headers = $this->_getAllHeaders();
        }
        return $this->_headers;
    }

    /**
     * Returns all HTTP_* headers.
     *
     * Returns all the HTTP_* headers. Works both if PHP is an apache
     * module and if it's running as a CGI.
     *
     * @return   array    the headers' names and values
     */
    private function _getAllHeaders()
    {
        if (function_exists('getallheaders')) {
            return array_change_key_case(getallheaders(), CASE_LOWER);
        }

        $result = array();
        $server = $this->getServerVars();
        reset($server);
        foreach ($server as $key => $value) {
            $header_name = substr($key, 0, 5);
            if ($header_name == 'HTTP_') {
                $hdr = str_replace('_', '-', Horde_String::lower(substr($key, 5)));
                $result[$hdr] = $value;
            }
        }

        return $result;
    }
}
