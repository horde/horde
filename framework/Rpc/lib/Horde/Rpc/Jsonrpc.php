<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Joey Hewitt <joey@joeyhewitt.com>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Rpc
 */

/**
 * The Horde_RPC_json-rpc class provides a JSON-RPC 1.1 implementation of the
 * Horde RPC system.
 *
 * - Only POST requests are supported.
 * - Named and positional parameters are accepted but the Horde registry only
 *   works with positional parameters.
 * - Service Descriptions are not supported yet.
 *
 * @link     http://json-rpc.org
 * @category Horde
 */
class Horde_Rpc_Jsonrpc extends Horde_Rpc
{
    /**
     * Returns the Content-Type of the response.
     *
     * @return string  The MIME Content-Type of the RPC response.
     */
    function getResponseContentType()
    {
        return 'application/json';
    }

    /**
     * Sends an RPC request to the server and returns the result.
     *
     * @param string  The raw request string.
     *
     * @return string  The JSON encoded response from the server.
     */
    function getResponse($request)
    {
        $request = Horde_Serialize::unserialize($request, Horde_Serialize::JSON);

        if (!is_object($request)) {
            return $this->_raiseError('Request must be a JSON object', $request);
        }

        // Validate the request.
        if (empty($request->method)) {
            return $this->_raiseError('Request didn\'t specify a method name.', $request);
        }

        // Convert objects to associative arrays.
        if (empty($request->params)) {
            $params = array();
        } else {
            $params = $this->_objectsToArrays($request->params);
            if (!is_array($params)) {
                return $this->_raiseError('Parameters must be JSON objects or arrays.', $request);
            }
        }

        // Check the method name.
        $method = str_replace('.', '/', $request->method);
        if (!$GLOBALS['registry']->hasMethod($method)) {
            return $this->_raiseError('Method "' . $request->method . '" is not defined', $request);
        }

        // Call the method.
        try {
            $result = $GLOBALS['registry']->call($method, $params);
        } catch (Horde_Exception $e) {
            return $this->_raiseError($e, $request);
        }

        // Return result.
        $response = array('version' => '1.1', 'result' => $result);
        if (isset($request->id)) {
            $response['id'] = $request->id;
        }

        return Horde_Serialize::serialize($response, Horde_Serialize::JSON);
    }

    /**
     * Returns a specially crafted PEAR_Error object containing a JSON-RPC
     * response in the error message.
     *
     * @param string|PEAR_Error $error  The error message or object.
     * @param stdClass $request         The original request object.
     *
     * @return PEAR_Error  An error object suitable for a JSON-RPC 1.1
     *                     conform error result.
     */
    function _raiseError($error, $request)
    {
        $code = $userinfo = null;
        if (is_a($error, 'PEAR_Error')) {
            $code = $error->getCode();
            $userinfo = $error->getUserInfo();
            $error = $error->getMessage();
        }
        $error = array('name' => 'JSONRPCError',
                       'code' => $code ? $code : 999,
                       'message' => $error);
        if ($userinfo) {
            $error['error'] = $userinfo;
        }
        $response = array('version' => '1.1', 'error' => $error);
        if (isset($request->id)) {
            $response['id'] = $request->id;
        }

        return PEAR::raiseError(Horde_Serialize::serialize($response, Horde_Serialize::JSON));
    }

    /**
     * Builds an JSON-RPC request and sends it to the server.
     *
     * This statically called method is actually the JSON-RPC client.
     *
     * @param string|Horde_Url $url  The path to the JSON-RPC server on the
     *                               called host.
     * @param string $method         The method to call.
     * @param Horde_Http_Client $client
     * @param array $params          A hash containing any necessary parameters
     *                               for the method call.
     *
     * @return mixed  The returned result from the method.
     * @throws Horde_Rpc_Exception
     */
    public static function request($url, $method, $client, $params = null)
    {
        $headers = array(
            'User-Agent' => 'Horde RPC client',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json');

        $data = array('version' => '1.1', 'method' => $method);
        if (!empty($params)) {
            $data['params'] = $params;
        }
        $data = Horde_Serialize::serialize($data, Horde_Serialize::JSON);
        try {
            $result = $client->post($url, $data, $headers);
        } catch (Horde_Http_Client_Exception $e) {
            throw new Horde_Rpc_Exception($e->getMessage());
        }
        if ($result->code == 500) {
            $response = Horde_Serialize::unserialize($result->getBody(), Horde_Serialize::JSON);
            if (is_a($response, 'stdClass') &&
                isset($response->error) &&
                is_a($response->error, 'stdClass') &&
                isset($response->error->name) &&
                $response->error->name == 'JSONRPCError') {
                throw new Horde_Rpc_Exception($response->error->message);
                /* @todo: Include more information if we have an Exception that can handle this.
                return PEAR::raiseError($response->error->message,
                                        $response->error->code,
                                        null, null,
                                        isset($response->error->error) ? $response->error->error : null);
                */
            }
            throw new Horde_Rpc_Exception($http->getResponseBody());
        } elseif ($result->code != 200) {
            throw new Horde_Rpc_Exception('Request couldn\'t be answered. Returned errorcode: "' . $result->code);
        }

        return Horde_Serialize::unserialize($result->getBody(), Horde_Serialize::JSON);
    }

    /**
     * Converts stdClass object to associative arrays.
     *
     * @param $data mixed  Any stdClass object, array, or scalar.
     *
     * @return mixed  stdClass objects are returned as asscociative arrays,
     *                scalars as-is, and arrays with their elements converted.
     */
    function _objectsToArrays($data)
    {
        if (is_a($data, 'stdClass')) {
            $data = get_object_vars($data);
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->_objectsToArrays($value);
            }
        }
        return $data;
    }

}
