<?php
/**
 * The Horde_Rpc_xmlrpc class provides an XMLRPC implementation of the
 * Horde RPC system.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Rpc
 */
class Horde_Rpc_Xmlrpc extends Horde_Rpc
{
    /**
     * Resource handler for the XMLRPC server.
     *
     * @var resource
     */
    var $_server;

    /**
     * XMLRPC server constructor
     *
     * @access private
     */
    public function __construct($request, $params = array())
    {
        parent::__construct($request, $params);

        $this->_server = xmlrpc_server_create();

        foreach ($GLOBALS['registry']->listMethods() as $method) {
            xmlrpc_server_register_method($this->_server, str_replace('/', '.', $method), array('Horde_Rpc_Xmlrpc', '_dispatcher'));
        }
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

        $method = str_replace('.', '/', $method);
        if (!$registry->hasMethod($method)) {
            return 'Method "' . $method . '" is not defined';
        }

        try {
            $result = $registry->call($method, $params);
        } catch (Horde_Exception $e) {
            $result = array('faultCode' => (int)$e->getCode(),
                            'faultString' => $e->getMessage());
        }

        return $result;
    }

    /**
     * Builds an XMLRPC request and sends it to the XMLRPC server.
     *
     * This statically called method is actually the XMLRPC client.
     *
     * @param string|Horde_Url $url      The path to the XMLRPC server on the
     *                                   called host.
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
        $headers = array(
            'User-Agent' => 'Horde RPC client',
            'Content-Type' => 'text/xml');
        try {
            $result = $client->post($url, xmlrpc_encode_request($method, $params), $headers);
        } catch (Horde_Http_Client_Exception $e) {
            throw new Horde_Rpc_Exception($result);
        }
        if ($result->code != 200) {
            throw new Horde_Rpc_Exception('Request couldn\'t be answered. Returned errorcode: "' . $result->code);
        } elseif (strpos($result->getBody(), '<?xml') === false) {
            throw new Horde_Rpc_Exception("No valid XML data returned:\n" . $result->getBody());
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
