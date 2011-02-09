<?php
/**
 * The Horde_Rpc_Phpgw class provides an XMLRPC implementation of the
 * Horde RPC system compatible with phpgw. It is based on the
 * xmlrpc.php implementation by Jan Schneider.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Braun <mi.braun@onlinehome.de>
 * @category Horde
 * @package  Horde_Rpc
 */
class Horde_Rpc_Phpgw extends Horde_Rpc
{
    /**
     * Resource handler for the XML-RPC server.
     *
     * @var resource
     */
    var $_server;

    /**
     * XMLRPC server constructor.
     */
    function __construct($request, $params = array())
    {
        parent::__construct($request, $params);

        $this->_server = xmlrpc_server_create();

        // Register only phpgw services.
        foreach ($GLOBALS['registry']->listMethods('phpgw') as $method) {
            $methods = explode('/', $method);
            array_shift($methods);
            $method = implode('.', $methods);
            xmlrpc_server_register_method($this->_server, $method, array('Horde_Rpc_Phpgw', '_dispatcher'));
        }
    }

    /**
     * Authorization is done by xmlrpc method system.login.
     */
    function authorize()
    {
        return true;
    }

    /**
     * Sends an RPC request to the server and returns the result.
     *
     * @param string  The raw request string.
     *
     * @return string  The XML encoded response from the server.
     */
    function getResponse($request)
    {
        $response = null;
        return xmlrpc_server_call_method($this->_server, $request, $response);
    }

    /**
     * Will be registered as the handler for all available methods
     * and will call the appropriate function through the registry.
     *
     * @access private
     *
     * @param string $method  The name of the method called by the RPC request.
     * @param array $params   The passed parameters.
     * @param mixed $data     Unknown.
     *
     * @return mixed  The result of the called registry method.
     */
    function _dispatcher($method, $params, $data)
    {
        global $registry;
        $method = str_replace('.', '/', 'phpgw.' . $method);

        if (!$registry->hasMethod($method)) {
            Horde::logMessage(sprintf(Horde_Rpc_Translation::t("Method \"%s\" is not defined"), $method), 'NOTICE');
            return sprintf(Horde_Rpc_Translation::t("Method \"%s\" is not defined"), $method);
        }

        // Try to resume a session
        if (isset($params[0]['kp3']) && $params[0]["kp3"] == session_name() && session_id() != $params[0]["sessionid"]) {
            Horde::logMessage("manually reload session ".$params[0]["sessionid"], 'NOTICE');
            session_regenerate_id();
            session_unset();
            session_id($params[0]["sessionid"]);
        }

        // Be authenticated or call system.login.
        $authenticated = $registry->isAuthenticated() || $method== "phpgw/system/login";

        if ($authenticated) {
            Horde::logMessage("rpc call $method allowed", 'NOTICE');
            return $registry->call($method, $params);
        } else {
            return PEAR::raiseError(Horde_Rpc_Translation::t("You did not authenticate."), 'horde.error');
            // return parent::authorize();
            // error 9 "access denied"
        }
    }

    /**
     * Builds an XMLRPC request and sends it to the XMLRPC server.
     *
     * This statically called method is actually the XMLRPC client.
     *
     * @param string|Horde_Url $url     The path to the XMLRPC server on the
     *                                  called host.
     * @param string $method             The method to call.
     * @param Horde_Http_Client $client  The transport client
     * @param array $params              A hash containing any necessary
     *                                   parameters for the method call.
     *
     * @return mixed  The returned result from the method.
     * @throws Horde_Rpc_Exception
     */
    public static function request($url, $method, $client, $params = null)
    {
        $options['method'] = 'POST';
        $headers = array(
            'User-Agent' => 'Horde RPC client',
            'Content-Type', 'text/xml');
        try {
            $result = $client->post($url, xmlrpc_encode_request($method, $params), $headers);
        } catch (Horde_Http_Client_Exception $e) {
            throw new Horde_Rpc_Exception($result);
        }
        if ($result->code != 200) {
            throw new Horde_Rpc_Exception(Horde_Rpc_Translation::t("Request couldn't be answered. Returned errorcode: ") . $result->code);
        } elseif (strpos($result->getBody(), '<?xml') === false) {
            throw new Horde_Rpc_Exception(Horde_Rpc_Translation::t("No valid XML data returned:") . "\n" . $result->getBody());
        } else {
            $response = @xmlrpc_decode(substr($result->getBody(), strpos($result->getBody(), '<?xml')));
            if (is_array($response) && isset($response['faultString'])) {
                throw new Horde_Rpc_Exception($response['faultString']);
            } elseif (is_array($response) && isset($response[0]) &&
                      is_array($response[0]) && isset($response[0]['faultString'])) {
                throw new Horde_Rpc_Exception($response[0]['faultString']);
            }
            return $response;
        }
    }

}
