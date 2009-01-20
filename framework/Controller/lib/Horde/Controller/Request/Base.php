<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Controller_Request_Base
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
     * @return  string
     */
    public function getBody()
    {
        if (!isset($this->_body)) {
            $this->_body = file_get_contents("php://input");
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
     * Get rid of register_globals variables.
     *
     * @author Richard Heyes
     * @author Stefan Esser
     * @url http://www.phpguru.org/article.php?ne_id=60
     */
    public function reverseRegisterGlobals()
    {
        if (ini_get('register_globals')) {
            // Variables that shouldn't be unset
            $noUnset = array(
                'GLOBALS',
                '_GET',
                '_POST',
                '_COOKIE',
                '_REQUEST',
                '_SERVER',
                '_ENV',
                '_FILES',
            );

            $input = array_merge(
                $_GET,
                $_POST,
                $_COOKIE,
                $_SERVER,
                $_ENV,
                $_FILES,
                isset($_SESSION) ? $_SESSION : array()
            );

            foreach ($input as $k => $v) {
                if (!in_array($k, $noUnset) && isset($GLOBALS[$k])) {
                    unset($GLOBALS[$k]);
                }
            }
        }
    }

    /**
     * @author Ilia Alshanetsky <ilia@php.net>
     */
    public function reverseMagicQuotes()
    {
        set_magic_quotes_runtime(0);
        if (get_magic_quotes_gpc()) {
            $input = array(&$_GET, &$_POST, &$_REQUEST, &$_COOKIE, &$_ENV, &$_SERVER);

            while (list($k, $v) = each($input)) {
                foreach ($v as $key => $val) {
                    if (!is_array($val)) {
                        $key = stripslashes($key);
                        $input[$k][$key] = stripslashes($val);
                        continue;
                    }
                    $input[] =& $input[$k][$key];
                }
            }

            unset($input);
        }
    }

    /**
     * Turn this request into a URL-encoded query string.
     */
    public function __toString()
    {
        return http_build_query($this);
    }

    public function getPath()
    {
    }

    /**
     * Uniquely identify each request from others. This aids in threading
     *  related log requests during troubleshooting on a busy server
     */
    private function _initRequestId()
    {
        $this->_requestId = (string)new Horde_Support_Uuid;
    }

    /**
     * The default locale (eg. en-us) the application uses.
     *
     * @var      string
     * @access   private
     */
    var $_defaultLocale = 'en-us';

    /**
     * The locales (eg. en-us, fi_fi, se_se etc) the application
     * supports.
     *
     * @var      array
     * @access   private
     */
    var $_supportedLocales = NULL;

    /**
     * Gets the used character encoding.
     *
     * Returns the name of the character encoding used in the body of
     * this request.
     *
     * @todo     implement this method
     * @return   string  the used character encoding
     * @access   public
     */
    function getCharacterEncoding()
    {
        // XXX: what to do with this?
    }

    /**
     * Gets the default locale for the application.
     *
     * @return   string  the default locale
     * @access   public
     */
    function getDefaultLocale()
    {
        return $this->_defaultLocale;
    }

    /**
     * Gets the supported locales for the application.
     *
     * @return   array      the supported locales
     * @access   public
     */
    function getSupportedLocales()
    {
        return $this->_supportedLocales;
    }

    /**
     * Deduces the clients preferred locale.
     *
     * You might want to override this method if you want to do more
     * sophisticated decisions. It gets the supported locales and the
     * default locale from the class attributes file and tries to find a
     * match. If no match is found it uses the default locale. The
     * locale is always changed into lowercase.
     *
     * @return   string  the locale
     * @access   public
     */
    function getLocale()
    {
        require_once('HTTP.php');

        if ($this->_supportedLocales == NULL) {
            return $this->_defaultLocale;
        } else {
            return strtolower(HTTP::negotiateLanguage(  $this->_supportedLocales,
                                                        $this->_defaultLocale   ));
        }
    }

    /**
     * Sets the default locale for the application.
     *
     * Create an instance of <code>Ismo_Core_Request</code> manually and
     * set the default locale with this method. Then add it as the
     * application's request class with
     * <code>Ismo_Core_Application::setRequest()</code>.
     *
     * @param   string  $locale     the default locale
     * @access  public
     */
    function setDefaultLocale($locale)
    {
        $this->_defaultLocale = str_replace('_', '-', $locale);
    }

    /**
     * Sets the locales supported by the application.
     *
     * Create an instance of <code>Ismo_Core_Request</code> manually and
     * set the supported locales with this method. Then add it as the
     * application's request class with
     * <code>Ismo_Core_Application::setRequest()</code>.
     *
     * @param   array   $locales    the locales
     * @access  public
     */
    function setSupportedLocales($locales)
    {
        if (is_array($locales)) {
            foreach ($locales as $n => $locale) {
                 $this->_supportedLocales[ str_replace('_', '-', $locale) ] = true;
            }
        }
    }

}
