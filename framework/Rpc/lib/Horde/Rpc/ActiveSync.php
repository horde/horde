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
    protected $_get = array();

    /**
     * The ActiveSync server object
     *
     * @var Horde_ActiveSync
     */
    protected $_server;

    /**
     * Content type header to send in response.
     *
     * @var string
     */
    protected $_contentType = 'application/vnd.ms-sync.wbxml';

    /**
     * Do we need an authenticated user?
     *
     * ActiveSync handles the authentication directly.
     *
     * @var boolean
     */
    protected $_requireAuthorization = false;

    /**
     * Constructor.
     *
     * @param Horde_Controller_Request_Http  The request object.
     *
     * @param array $params  A hash containing configuration parameters:
     *   - server: (Horde_ActiveSync) The ActiveSync server object.
     *             DEFAULT: none, REQUIRED
     *   - provisioning: (mixed) Require device provisioning? Takes a
     *                   Horde_ActiveSync::PROVISIONING_* constant.
     *                   DEFAULT: Horde_ActiveSync::PROVISIONING_NONE
     *                   (do not require provisioning).
     */
    public function __construct(Horde_Controller_Request_Http $request, array $params = array())
    {
        parent::__construct($request, $params);
        // Use the server's getGetVars() method since they might be transmitted
        // as base64 encoded binary data.
        $serverVars = $request->getServerVars();
        $this->_get = $params['server']->getGetVars();
        if ($request->getMethod() == 'POST' &&
            (((empty($this->_get['Cmd']) || empty($this->_get['DeviceId']) ||
              empty($this->_get['DeviceType'])) && empty($serverVars['QUERY_STRING'])) &&
             $serverVars['REQUEST_URI'] != '/autodiscover/autodiscover.xml')) {

            $this->_logger->err('Missing required parameters.');
            throw new Horde_Rpc_Exception('Your device requested the ActiveSync URL wihtout required parameters.');
        }
        $this->_server = $params['server'];
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

            if ($serverVars['REQUEST_URI'] == '/autodiscover/autodiscover.xml') {
                try {
                    if (!$this->_server->handleRequest('Autodiscover', null)) {
                        throw new Horde_Exception('Unknown Error');
                    }
                } catch (Horde_Exception_AuthenticationFailure $e) {
                    $this->_sendAuthenticationFailedHeaders();
                    exit;
                } catch (Horde_Exception $e) {
                    $this->_handleError($e);
                }
                break;
            }

            $this->_logger->debug('Horde_Rpc_ActiveSync::getResponse() starting for OPTIONS');
            try {
                if (!$this->_server->handleRequest('Options', null)) {
                    throw new Horde_Exception('Unknown Error');
                }
            } catch (Horde_Exception_AuthenticationFailure $e) {
                $this->_sendAuthenticationFailedHeaders();
                exit;
            } catch (Horde_Exception $e) {
                $this->_handleError($e);
            }
            break;

        case 'POST':
            $this->_logger->debug('Horde_Rpc_ActiveSync::getResponse() starting for ' . $this->_get['Cmd']);
            // Autodiscover Request
            if ($serverVars['REQUEST_URI'] == '/autodiscover/autodiscover.xml') {
                $this->_get['Cmd'] = 'Autodiscover';
                $this->_get['DeviceId'] = null;
            }
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
            } catch (Horde_Exception_AuthenticationFailure $e) {
                $this->_sendAuthenticationFailedHeaders();
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

        if (!headers_sent()) {
            header('Content-Length: ' . $len);
            header('Content-Type: ' . $this->_contentType);
            flush();
            echo $data;
        } else {
            flush();
            sleep(2);
            $this->_logger->debug('Output ' . $len . ' Bytes of data found in content buffer since output started');
            echo $data;
        }
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

    /**
     * Send 401 Unauthorized headers.
     */
    protected function _sendAuthenticationFailedHeaders()
    {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="Horde ActiveSync"');
    }
}
