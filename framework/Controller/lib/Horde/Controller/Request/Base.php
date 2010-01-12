<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 * @subpackage Request
 *
 * http://pythonpaste.org/webob/
 * http://usrportage.de/archives/875-Dojo-and-the-Zend-Framework.html
 * http://framework.zend.com/manual/en/zend.filter.input.html
 * http://www.lornajane.net/posts/2009/Adding-PUT-variables-to-Request-Object-in-Zend-Framework
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 * @subpackage Request
 */
abstract class Horde_Controller_Request_Base
{
    /**
     * Request timestamp
     *
     * @var integer
     */
    protected $_timestamp;

    /**
     * The SAPI
     *
     * @var string
     */
    protected $_sapi;

    /**
     * Unique id per request.
     * @var string
     */
    protected $_requestId;

    protected $_server;
    protected $_env;

    protected $_body;

    /**
     */
    public function __construct($options = array())
    {
        $this->_initRequestId();

        $this->_server  = isset($options['server'])  ? $options['server']  : $_SERVER;
        $this->_env     = isset($options['env'])     ? $options['env']     : $_ENV;

        if (isset($_SERVER['REQUEST_TIME'])) {
            $this->_timestamp = $_SERVER['REQUEST_TIME'];
        } else {
            $this->_timestamp = time();
        }

        $this->_sapi = php_sapi_name();
    }

    /**
     * Get request timestamp
     *
     * @return integer
     */
    public function getTimestamp()
    {
        return $this->_timestamp;
    }

    /**
     * Get server variable with the specified $name
     *
     * @param   string  $name
     * @return  string
     */
    public function getServer($name)
    {
        if ($name == 'SCRIPT_NAME' && strncmp($this->_sapi, 'cgi', 3) === 0) {
            $name = 'SCRIPT_URL';
        }
        return isset($this->_server[$name]) ? $this->_server[$name] : null;
    }

    /**
     * Get environment variable with the specified $name
     *
     * @param   string  $name
     * @return  string
     */
    public function getEnv($name)
    {
        return isset($this->_env[$name]) ? $this->_env[$name] : null;
    }

    /**
     * Get a combination of all parameters. We have to do
     * some wacky loops to make sure that nested values in one
     * param list don't overwrite other nested values
     *
     * @return  array
     */
    public function getParameters()
    {
        $allParams = array();
        $paramArrays = array($this->_pathParams, $this->_formattedRequestParams);

        foreach ($paramArrays as $params) {
            foreach ((array)$params as $key => $value) {
                if (!is_array($value) || !isset($allParams[$key])) {
                    $allParams[$key] = $value;
                } else {
                    $allParams[$key] = array_merge($allParams[$key], $value);
                }
            }
        }
        return $allParams;
    }

    /**
     * Get entire list of parameters set by {@link Horde_Controller_Route_Path} for
     * the current request
     *
     * @return  array
     */
    public function getPathParams()
    {
        return $this->_pathParams;
    }

    /**
     * When the {@link Horde_Controller_Dispatcher} determines the
     * correct {@link Horde_Controller_Route_Path} to match the url, it uses the
     * Routing object data to set appropriate variables so that they can be passed
     * to the Controller object.
     *
     * @param   array   $params
     */
    public function setPathParams($params)
    {
        $this->_pathParams = !empty($params) ? $params : array();
    }

    /**
     * Get the unique ID generated for this request
     * @see     _initRequestId()
     * @return  string
     */
    public function getRequestId()
    {
        return $this->_requestId;
    }

    /**
     * The request body
     *
     * @TODO Allow overriding php://input, and make the default interface to
     * return an SplFileObject, or a (doesn't currently exist) Horde_File
     * object, instead of the raw data.
     *
     * @return  string
     */
    public function getBody()
    {
        if (!isset($this->_body)) {
            $this->_body = file_get_contents('php://input');
        }
        return $this->_body;
    }

    /**
     * Return the request content length
     *
     * @return  int
     */
    public function getContentLength()
    {
        return strlen($this->getBody());
    }

    /**
     * Turn this request into a URL-encoded query string.
     */
    public function __toString()
    {
        return http_build_query($this);
    }

    abstract public function getPath();

    /**
     * Uniquely identify each request from others. This aids in threading
     * related log requests during troubleshooting on a busy server
     */
    private function _initRequestId()
    {
        $this->_requestId = (string)new Horde_Support_Uuid;
    }
}
