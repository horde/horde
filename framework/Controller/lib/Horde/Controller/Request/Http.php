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
 */

/**
 * Represents an HTTP request to the server. This class handles all
 * headers/cookies/session data so that it all has one point of entry for being
 * written/retrieved.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 * @subpackage Request
 */
class Horde_Controller_Request_Http extends Horde_Controller_Request_Base
{
    /**
     * All the headers.
     * @var array
     */
    protected $_headers = null;

    /**
     * PHPSESSID
     * @var string
     */
    protected $_sessionId;

    // superglobal arrays
    protected $_get;
    protected $_post;
    protected $_files;
    protected $_request;

    // cookie/session info
    protected $_cookie;
    protected $_session;

    protected $_contentType;
    protected $_accepts;
    protected $_format;
    protected $_method;
    protected $_remoteIp;
    protected $_port;
    protected $_https;
    protected $_isAjax;

    protected $_domain;
    protected $_uri;
    protected $_pathParams;

    /*##########################################################################
    # Construct/Destruct
    ##########################################################################*/

    /**
     * Request is populated with all the superglobals from page request if
     * data is not passed in.
     *
     * @param   array   $options  Associative array with all superglobals
     */
    public function __construct($options = array())
    {
        try {
            $this->_initSessionData();

            // register default mime types
            Horde_Controller_Mime_Type::registerTypes();

            // superglobal data if not passed in thru constructor
            $this->_get     = isset($options['get'])     ? $options['get']     : $_GET;
            $this->_post    = isset($options['post'])    ? $options['post']    : $_POST;
            $this->_cookie  = isset($options['cookie'])  ? $options['cookie']  : $_COOKIE;
            $this->_request = isset($options['request']) ? $options['request'] : $_REQUEST;

            parent::__construct($options);

            $this->_pathParams = array();
            //$this->_formattedRequestParams = $this->_parseFormattedRequestParameters();

            // use FileUpload object to store files
            $this->_setFilesSuperglobals();

            // disable all superglobal data to force us to use correct way
            //@TODO
            //$_GET = $_POST = $_FILES = $_COOKIE = $_REQUEST = $_SERVER = array();

            $this->_domain   = $this->getServer('SERVER_NAME');
            $this->_uri      = trim($this->getServer('REQUEST_URI'), '/');
            $this->_method   = $this->getServer('REQUEST_METHOD');
            // @TODO look at HTTP_X_FORWARDED_FOR, handling multiple addresses: http://weblogs.asp.net/james_crowley/archive/2007/06/19/gotcha-http-x-forwarded-for-returns-multiple-ip-addresses.aspx
            $this->_remoteIp = $this->getServer('REMOTE_ADDR');
            $this->_port     = $this->getServer('SERVER_PORT');
            $this->_https    = $this->getServer('HTTPS') || $this->getServer('SSL_PROTOCOL') || $this->getServer('HTTP_X_CLUSTER_SSL');
            $this->_isAjax   = $this->getServer('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest';
        } catch (Exception $e) {
            $this->_malformed = true;
            $this->_exception = $e;
        }
    }


    /*##########################################################################
    # Public Methods
    ##########################################################################*/

    /**
     * Get the http request method:
     *  eg. GET, POST, PUT, DELETE
     *
     * @return  string
     */
    public function getMethod()
    {
        $methods = array('GET', 'HEAD', 'PUT', 'POST', 'DELETE', 'OPTIONS');

        if ($this->_method == 'POST') {
            $params = $this->getParameters();
            if (isset($params['_method'])) {
                $faked = strtoupper($params['_method']);
                if (in_array($faked, $methods)) return $faked;
            }
        }

        return $this->_method;
    }

    /**
     * Get list of all superglobals to pass into a different request
     *
     * @return  array
     */
    public function getGlobals()
    {
        return array('get'     => $this->_get,
                     'post'    => $this->_post,
                     'cookie'  => $this->_cookie,
                     'session' => $this->_session,
                     'files'   => $this->_files,
                     'request' => $this->_request,
                     'server'  => $this->_server,
                     'env'     => $this->_env);
    }

    /**
     * Get the domain for the current request
     * eg. https://www.maintainable.com/articles/show/123
     *     $domain is -> www.maintainable.com
     *
     * @return  string
     */
    public function getDomain()
    {
        return $this->_domain;
    }

    /**
     * Get the host for the current request
     * eg. http://www.maintainable.com:3000/articles/show/123
     *     $host is -> http://www.maintainablesoftware.com:3000
     *
     * @param   boolean $usePort
     * @return  string
     */
    public function getHost($usePort = false)
    {
        $scheme = 'http' . ($this->_https == 'on' ? 's' : null);
        $port   = $usePort && !empty($this->_port) && $this->_port != '80' ? ':' . $this->_port : null;
        return "{$scheme}://{$this->_domain}$port";
    }

    /**
     * @todo    add ssl support
     * @return  string
     */
    public function getProtocol()
    {
        // return $this->getServer('SERVER_PROTOCOL');
        return 'http://';
    }

    /**
     * Get the uri for the current request
     * eg. https://www.maintainable.com/articles/show/123?page=1
     *     $uri is -> articles/show/123?page=1
     *
     * @return  string
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * Get the path from the URI. (strip get params)
     * eg. https://www.maintainable.com/articles/show/123?page=1
     *     $path is -> articles/show/123
     *
     * @return  string
     */
    public function getPath()
    {
        $path = $this->_uri;
        if (strstr($path, '?')) {
            $path = trim(substr($path, 0, strpos($path, '?')), '/');
        }
        return $path;
    }

    public function getContentType()
    {
        if (!isset($this->_contentType)) {
            $type = $this->getServer('CONTENT_TYPE');
            // strip parameters from content-type like "; charset=UTF-8"
            if (is_string($type)) {
                if (preg_match('/^([^,\;]*)/', $type, $matches)) {
                    $type = $matches[1];
                }
            }

            $this->_contentType = Horde_Controller_Mime_Type::lookup($type);
        }
        return $this->_contentType;
    }

    /**
     * @return  array
     */
    public function getAccepts()
    {
        if (!isset($this->_accepts)) {
            $accept = $this->getServer('HTTP_ACCEPT');
            if (empty($accept)) {
                $types = array();
                $contentType = $this->getContentType();
                if ($contentType) { $types[] = $contentType; }
                $types[] = Horde_Controller_Mime_Type::lookupByExtension('all');
                $accepts = $types;
            } else {
                $accepts = Horde_Controller_Mime_Type::parse($accept);
            }
            $this->_accepts = $accepts;
        }
        return $this->_accepts;
    }


    /**
     * Returns the Mime type for the format used in the request. If there is no
     * format available, the first of the
     *
     * @return  string
     */
    public function getFormat()
    {
        if (!isset($this->_format)) {
            $params = $this->getParameters();
            if (isset($params['format'])) {
                $this->_format = Horde_Controller_Mime_Type::lookupByExtension($params['format']);
            } else {
                $this->_format = current($this->getAccepts());
            }
        }
        return $this->_format;
    }

    /**
     * Get the remote Ip address as a dotted decimal string.
     *
     * @return  string
     */
    public function getRemoteIp()
    {
        return $this->_remoteIp;
    }

    /**
     * Get cookie value from specified $name OR get All when $name isn't passed in
     *
     * @param   string  $name
     * @param   string  $default
     * @return  string
     */
    public function getCookie($name=null, $default=null)
    {
        if (isset($name)) {
            return isset($this->_cookie[$name]) ? $this->_cookie[$name] : $default;
        } else {
            return $this->_cookie;
        }
    }

    /**
     * Get session value from session data by $name or ALL when $name isn't passed in
     *
     * @param   string  $name
     * @param   string  $default
     * @return  mixed
     */
    public function getSession($name=null, $default=null)
    {
        if (isset($name)) {
            return isset($this->_session[$name]) ? $this->_session[$name] : $default;
        } else {
            return $this->_session;
        }
    }

    /**
     * Get entire list of $_COOKIE parameters
     *
     * @return  array
     */
    public function getCookieParams()
    {
        return $this->_cookie;
    }

    /**
     * Get entire list of $_SERVER parameters
     *
     * @return  array
     */
    public function getServerParams()
    {
        return $this->_server;
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
        $paramArrays = array($this->_pathParams, /*$this->_formattedRequestParams, */
                             $this->_get, $this->_post, $this->_files);

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
     * Get entire list of $_GET parameters
     * @return  array
     */
    public function getGetParams()
    {
        return $this->_get;
    }

    /**
     * Get entire list of $_POST parameters
     *
     * @return  array
     */
    public function getPostParams()
    {
        return $this->_post;
    }

    /**
     * Get entire list of $_FILES parameters
     *
     * @return  array
     */
    public function getFilesParams()
    {
        return $this->_files;
    }

    /**
     * Get the session ID of this request (PHPSESSID)
     * @see    _initSession()
     * @return string
     */
    public function getSessionId()
    {
        return $this->_sessionId;
    }


    /*##########################################################################
    # Modifiers
    ##########################################################################*/

    /**
     * Set the uri and parse it for useful info
     *
     * @param   string  $uri
     */
    public function setUri($uri)
    {
        $this->_uri = trim($uri, '/');
    }

    /**
     * Set the session array.
     *
     * @param   string  $name
     * @param   mixed   $value
     */
    public function setSession($name, $value=null)
    {
        if (is_array($name)) {
            $this->_session = $name;
        } else {
            $this->_session[$name] = $value;
        }
    }


    /*##########################################################################
    # Private Methods
    ##########################################################################*/

    /**
     * Start up default session storage, and get stored data.
     *
     * @todo    further investigate session_cache_limiter() on ie6 (see below)
     * @todo    implement active record session store
     */
    protected function _initSessionData()
    {
        $this->_sessionId = session_id();

        if (! strlen($this->_sessionId)) {
            // internet explorer 6 will ignore the filename/content-type during
            // sendfile over ssl unless session_cache_limiter('public') is set
            // http://joseph.randomnetworks.com/archives/2004/10/01/making-ie-accept-file-downloads/
            $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            if (strpos($agent, 'MSIE') !== false) {
                session_cache_limiter("public");
            }

            session_start();
            $this->_sessionId = session_id();
        }

        // Important: Setting "$this->_session = $_SESSION" does NOT work.
        $this->_session = array();
        if (is_array($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                $this->_session[$key] = $value;
            }
        }
    }

    /**
     * Initialize the File upload information
     */
    protected function _setFilesSuperglobals()
    {
        if (empty($_FILES)) {
            $this->_files = array();
            return;
        }
        $_FILES = array_map(array($this, '_fixNestedFiles'), $_FILES);

        // create FileUpload object of the file options
        foreach ((array)$_FILES as $name => $options) {
            if (isset($options['tmp_name'])) {
                $this->_files[$name] = new Horde_Controller_FileUpload($options);
            } else {
                foreach ($options as $attr => $data) {
                    $this->_files[$name][$attr] = new Horde_Controller_FileUpload($data);
                }
            }
        }
    }

    /**
     * fix $_FILES superglobal array. (PHP mungles data when we use brackets)
     *
     * @link http://www.shauninman.com/archive/2006/11/30/fixing_the_files_superglobal
     * @param   array   $group
     */
    protected function _fixNestedFiles($group)
    {
        // only rearrange nested files
        if (!is_array($group['tmp_name'])) { return $group; }

        foreach ($group as $property => $arr) {
            foreach ($arr as $item => $value) {
                $result[$item][$property] = $value;
            }
        }
        return $result;
    }

    /**
     * Gets the value of header.
     *
     * Returns the value of the specified request header.
     *
     * @param    string  $name   the name of the header
     * @return   string          the value of the specified header
     * @access   public
     */
    function getHeader($name)
    {
        if ($this->_headers == null) {
            $this->_headers = $this->_getAllHeaders();
        }

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
     * @access   public
     */
    function getHeaderNames()
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
     * @access   public
     */
    function getHeaders()
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
     * @access   private
     */
    function _getAllHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $result = array();
        reset($_SERVER);
        foreach ($_SERVER as $key => $value) {
            $header_name = substr($key, 0, 5);
            if ($header_name == 'HTTP_') {
                $result[$key] = $value;
            }
        }

        // map so that the variables gotten from the environment when
        // running as CGI have the same names as when PHP is an apache
        // module
        $map = array (
            'HTTP_ACCEPT'           =>  'Accept',
            'HTTP_ACCEPT_CHARSET'   =>  'Accept-Charset',
            'HTTP_ACCEPT_ENCODING'  =>  'Accept-Encoding',
            'HTTP_ACCEPT_LANGUAGE'  =>  'Accept-Language',
            'HTTP_CONNECTION'       =>  'Connection',
            'HTTP_HOST'             =>  'Host',
            'HTTP_KEEP_ALIVE'       =>  'Keep-Alive',
            'HTTP_USER_AGENT'       =>  'User-Agent' );

        $mapped_result = array();
        foreach ($result as $k => $v) {
            if (!empty($map[$k])) {
                $mapped_result[$map[$k]] = $v;
            } elseif (substr($k, 0, 5) == 'HTTP_') {
                // Try to work with what we have...
                $hdr_key = substr($k, 5);
                $tokens = explode('_', $hdr_key);
                if (count($tokens) > 0) {
                    foreach($tokens as $key => $value) {
                        $tokens[$key] = Horde_String::ucfirst(Horde_String::lower($value));
                    }
                    $hdr_key = implode('-', $tokens);
                    $mapped_result[$hdr_key] = $v;
                }
            }
        }

        return $mapped_result;
    }

}
