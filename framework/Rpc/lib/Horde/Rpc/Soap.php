<?php
/**
 * The Horde_Rpc_Soap class provides a PHP 5 Soap implementation
 * of the Horde RPC system.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Rpc
 */
class Horde_Rpc_Soap extends Horde_Rpc
{
    /**
     * Resource handler for the SOAP server.
     *
     * @var object
     */
    var $_server;

    /**
     * List of types to emit in the WSDL.
     *
     * @var array
     */
    var $_allowedTypes = array();

    /**
     * List of method names to allow.
     *
     * @var array
     */
    var $_allowedMethods = array();

    /**
     * Name of the SOAP service to use in the WSDL.
     *
     * @var string
     */
    var $_serviceName = null;

    /**
     * SOAP server constructor
     *
     * @access private
     */
    public function __construct($request, $params = array())
    {
        parent::__construct($request, $params);

        if (!empty($params['allowedTypes'])) {
            $this->_allowedTypes = $params['allowedTypes'];
        }
        if (!empty($params['allowedMethods'])) {
            $this->_allowedMethods = $params['allowedMethods'];
        }
        if (!empty($params['serviceName'])) {
            $this->_serviceName = $params['serviceName'];
        }

        $this->_server = new SoapServer(null, array('uri' => (string)Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/rpc.php', true, -1)));
        $this->_server->addFunction(SOAP_FUNCTIONS_ALL);
        $this->_server->setClass('Horde_Rpc_Soap_Caller', $params);
    }

    /**
     * Takes a SOAP request and returns the result.
     *
     * @param string  The raw request string.
     *
     * @return string  The XML encoded response from the server.
     */
    function getResponse($request)
    {
        if ($request == 'disco' || $request == 'wsdl') {
            /*@TODO Replace with subcalls for disco and wsdl generation from the old SOAP driver. */
            //$handler = new Horde_Rpc_Soap($this->_params);
            //return $handler->getResponse($request);
        }

        /* We can't use Horde_Util::bufferOutput() here for some reason. */
        $beginTime = time();
        ob_start();
        $this->_server->handle($request);
        Horde::logMessage(
            sprintf('SOAP call: %s(%s) by %s serviced in %d seconds, sent %d bytes in response',
                    $GLOBALS['__horde_rpc_PhpSoap']['lastMethodCalled'],
                    implode(', ', array_map(create_function('$a', 'return is_array($a) ? "Array" : $a;'),
                                            $GLOBALS['__horde_rpc_PhpSoap']['lastMethodParams'])),
                    $GLOBALS['registry']->getAuth(),
                    time() - $beginTime,
                    ob_get_length()),
            'INFO');
        return ob_get_clean();
    }

    /**
     * Builds a SOAP request and sends it to the SOAP server.
     *
     * This statically called method is actually the SOAP client.
     *
     * @param string|Horde_Url $url  The path to the SOAP server on the called
     *                               host.
     * @param string $method         The method to call.
     * @param array $params          A hash containing any necessary parameters
     *                               for the method call.
     * @param $options  Optional associative array of parameters which can be:
     *                  - user:              Basic Auth username
     *                  - pass:              Basic Auth password
     *                  - proxy_host:        Proxy server host
     *                  - proxy_port:        Proxy server port
     *                  - proxy_user:        Proxy auth username
     *                  - proxy_pass:        Proxy auth password
     *                  - timeout:           Connection timeout in seconds.
     *                  - allowRedirects:    Whether to follow redirects or not
     *                  - maxRedirects:      Max number of redirects to follow
     *                  - namespace:
     *                  - soapaction:
     *                  - from:              SMTP, from address
     *                  - transfer-encoding: SMTP, sets the
     *                                       Content-Transfer-Encoding header
     *                  - subject:           SMTP, subject header
     *                  - headers:           SMTP, array-hash of extra smtp
     *                                       headers
     *
     * @return mixed  The returned result from the method
     * @throws Horde_Rpc_Exception
     */
    public static function request($url, $method, $params = null, $options = array())
    {
        if (!isset($options['timeout'])) {
            $options['timeout'] = 5;
        }
        if (!isset($options['allowRedirects'])) {
            $options['allowRedirects'] = true;
            $options['maxRedirects']   = 3;
        }
        if (isset($options['user'])) {
            $options['login'] = $options['user'];
            unset($options['user']);
        }
        if (isset($options['pass'])) {
            $options['password'] = $options['pass'];
            unset($options['pass']);
        }
        $options['location'] = (string)$url;
        $options['uri'] = $options['namespace'];
        $options['exceptions'] = true;

        $options['trace'] = true;
        try {
            $soap = new SoapClient(null, $options);
            return $soap->__soapCall($method, $params);
        } catch (Exception $e) {
            throw new Horde_Rpc_Exception($e);
        }
    }

}

class Horde_Rpc_Soap_Caller {

    /**
     * List of method names to allow.
     *
     * @var array
     */
    protected $_allowedMethods = array();

    /**
     */
    public function __construct($params = array())
    {
        if (!empty($params['allowedMethods'])) {
            $this->_allowedMethods = $params['allowedMethods'];
        }
    }

    /**
     * Will be registered as the handler for all methods called in the
     * SOAP server and will call the appropriate function through the registry.
     *
     * @todo  PEAR SOAP operates on a copy of this object at some unknown
     *        point and therefore doesn't have access to instance
     *        variables if they're set here. Instead, globals are used
     *        to track the method name and args for the logging code.
     *        Once this is PHP 5-only, the globals can go in favor of
     *        instance variables.
     *
     * @access private
     *
     * @param string $method    The name of the method called by the RPC request.
     * @param array $params     The passed parameters.
     * @param mixed $data       Unknown.
     *
     * @return mixed            The result of the called registry method.
     */
    public function __call($method, $params)
    {
        $method = str_replace('.', '/', $method);

        if (!empty($this->_params['allowedMethods']) &&
            !in_array($method, $this->_params['allowedMethods'])) {
            return sprintf($this->_dict->t("Method \"%s\" is not defined"), $method);
        }

        $GLOBALS['__horde_rpc_PhpSoap']['lastMethodCalled'] = $method;
        $GLOBALS['__horde_rpc_PhpSoap']['lastMethodParams'] =
            !empty($params) ? $params : array();

        if (!$GLOBALS['registry']->hasMethod($method)) {
            return sprintf($this->_dict->t("Method \"%s\" is not defined"), $method);
        }

        return $GLOBALS['registry']->call($method, $params);
    }

}
