<?php
/**
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 *
 * @category Horde
 * @package  Rpc
 */
class Horde_Rpc_ActiveSync extends Horde_Rpc
{
    /**
     * Holds the request's GET variables
     *
     * @var array
     */
    private $_get;

    /**
     * The ActiveSync server object
     *
     * @var Horde_ActiveSync
     */
    private $_server;

    /**
     * The backend data driver.
     *
     * @var Horde_ActiveSync_Driver_Base
     */
    private $_backend;

    /**
     * Content type header to send in response.
     *
     * @var string
     */
    private $_contentType = 'application/vnd.ms-sync.wbxml';

    /**
     * Constructor.
     *
     * @param Horde_Controller_Request_Http  The request object.
     *
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters this class might need:
     *   'backend'      = Horde_ActiveSync_Driver_Base [REQUIRED]
     *   'server'       = Horde_ActiveSync [REQUIRED]
     *   'provisioning' = Require device provisioning? [OPTIONAL]
     */
    public function __construct(Horde_Controller_Request_Http $request, array $params = array())
    {
        parent::__construct($request, $params);

        // Check for requirements
        $this->_get = $request->getGetVars();

        if ($request->getMethod() == 'POST' &&
            (empty($this->_get['Cmd']) || empty($this->_get['DeviceId']) || empty($this->_get['DeviceType']))) {

            $this->_logger->err('Missing required parameters.');
            throw new Horde_Rpc_Exception('Your device requested the ActiveSync URL wihtout required parameters.');
        }

        // Some devices (incorrectly) only send the username in the httpauth
        if ($request->getMethod() == 'POST' &&  empty($this->_get['User'])) {
            $serverVars = $this->_request->getServerVars();
            if ($serverVars['PHP_AUTH_USER']) {
                $this->_get['User'] = $serverVars['PHP_AUTH_USER'];
            } elseif ($serverVars['Authorization']) {
                $hash = str_replace('Basic ', '', $serverVars['Authorization']);
                $hash = base64_decode($hash);
                if (strpos($hash, ':') !== false) {
                    list($this->_get['User'], $pass) = explode(':', $hash, 2);
                }
            }
            if (empty($this->_get['User'])) {
                $this->_logger->err('Missing required parameters.');
                throw new Horde_Rpc_Exception('Your device requested the ActiveSync URL wihtout required parameters.');
            }
        }

        $this->_backend = $params['backend'];
        $this->_server = $params['server'];
        $this->_server->setProvisioning(empty($params['provisioning']) ? false : $params['provisioning']);
    }

    /**
     * Returns the Content-Type of the response.
     *
     * @return string  The MIME Content-Type of the RPC response.
     */
    public function getResponseContentType()
    {
        return $this->_contentType;
    }

    /**
     * Horde_ActiveSync will read the input stream directly, do not access
     * it here.
     *
     * @see framework/Rpc/lib/Horde/Horde_Rpc#getInput()
     */
    public function getInput()
    {
        return null;
    }

    /**
     * Sends an RPC request to the server and returns the result.
     *
     * @param string $request  PHP input stream (ignored).
     */
    public function getResponse($request)
    {
        ob_start(null, 1048576);
        $serverVars = $this->_request->getServerVars();
        switch ($serverVars['REQUEST_METHOD']) {
        case 'OPTIONS':
        case 'GET':
            if ($serverVars['REQUEST_METHOD'] == 'GET' &&
                $this->_get['Cmd'] != 'OPTIONS') {
                throw new Horde_Rpc_Exception(
                    Horde_Rpc_Translation::t('Trying to access the ActiveSync endpoint from a browser. Not Supported.'));
            }
            $this->_logger->debug('Horde_Rpc_ActiveSync::getResponse() starting for OPTIONS');
            try {
                $this->_server->handleRequest('Options', null, null);
            } catch (Horde_Exception $e) {
                $this->_handleError($e);
            }
            break;

        case 'POST':
            $this->_logger->debug('Horde_Rpc_ActiveSync::getResponse() starting for ' . $this->_get['Cmd']);
            try {
                $ret = $this->_server->handleRequest($this->_get['Cmd'], $this->_get['DeviceId']);
                if ($ret === false) {
                    throw new Horde_ActiveSync_Exception('Unknown Error');
                } elseif ($ret !== true) {
                    $this->_contentType = $ret;
                }
            } catch (Horde_ActiveSync_Exception_InvalidRequest $e) {
               $this->_logger->err('Returning HTTP 400');
               $this->_handleError($e);
               header('HTTP/1.1 400 Invalid Request ' . $e->getMessage());
               exit;
            } catch (Horde_Exception $e) {
                $this->_logger->err('Returning HTTP 500');
                $this->_handleError($e);
                header('HTTP/1.1 500 ' . $e->getMessage());
                exit;
            }
            break;
        }
    }

    /**
     *
     * @see framework/Rpc/lib/Horde/Horde_Rpc#sendOutput($output)
     */
    public function sendOutput($output)
    {
        // Unfortunately, even though zpush can stream the data to the client
        // with a chunked encoding, using chunked encoding also breaks the
        // progress bar on the PDA. So we de-chunk here and just output a
        // content-length header and send it as a 'normal' packet. If the output
        // packet exceeds 1MB (see ob_start) then it will be sent as a chunked
        // packet anyway because PHP will have to flush the buffer.
        $len = ob_get_length();
        $data = ob_get_contents();
        ob_end_clean();

        header('Content-Type: ' . $this->_contentType);
        header('Content-Length: ' . $len);
        echo $data;
    }

    /**
     * Check authentication. Different backends may handle
     * authentication in different ways. The base class implementation
     * checks for HTTP Authentication against the Horde auth setup.
     *
     * @return boolean  Returns true if authentication is successful.
     */
    public function authorize()
    {
        $this->_logger->debug('Horde_Rpc_ActiveSync::authorize() starting');
        if (!$this->_requireAuthorization) {
            return true;
        }

        /* Get user and possibly domain */
        $serverVars = $this->_request->getServerVars();
        $user = !empty($serverVars['PHP_AUTH_USER']) ? $serverVars['PHP_AUTH_USER'] : '';
        $pos = strrpos($user, '\\');
        if ($pos !== false) {
            $domain = substr($user, 0, $pos);
            $user = substr($user, $pos + 1);
        } else {
            $domain = null;
        }

        /* Get passwd */
        $pass = !empty($serverVars['PHP_AUTH_PW']) ? $serverVars['PHP_AUTH_PW'] : '';

        /* Attempt to auth to backend */
        $results = $this->_backend->logon($user, $pass, $domain);
        if (!$results && empty($this->_policykey)) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="Horde RPC"');
            $this->_logger->info('Access denied for user: ' . $user . '. Username or password incorrect.');
        }

        /* Successfully authenticated to backend, try to setup the backend */
        if (empty($this->_get['User'])) {
            return false;
        }
        $results = $this->_backend->setup($this->_get['User']);
        if (!$results) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="Horde RPC"');
            echo 'Access denied or user ' . $this->_get['User'] . ' unknown.';
        }

        $this->_logger->debug('Horde_Rpc_ActiveSync::authorize() exiting');

        return true;
    }

    /**
     * Output exception information to the logger.
     *
     * @param Exception $e  The exception
     *
     * @throws Horde_Rpc_Exception $e
     */
    protected function _handleError($e)
    {
        $trace = $e->getTraceAsString();
        $m = $e->getMessage();
        $buffer = ob_get_clean();

        $this->_logger->err('Error in communicating with ActiveSync server: ' . $m);
        $this->_logger->err($trace);
        $this->_logger->err('Buffer contents: ' . $buffer);
    }

}
