<?php
/**
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @category Horde
 * @package  Horde_Rpc
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
     * ActiveSync's backend target (the datastore it syncs the PDA with)
     *
     * @var Horde_ActiveSync_Driver
     */
    private $_backend;

    /**
     * Constructor.
     * Parameters in addition to Horde_Rpc's:
     *   (required) 'backend'      = Horde_ActiveSync_Driver
     *   (required) 'server'       = Horde_ActiveSync
     *   (optional) 'provisioning' = Require device provisioning?
     *
     * @param Horde_Controller_Request_Http  The request object.
     * @param array $config  A hash containing any additional configuration or
     *                       connection parameters this class might need.
     */
    public function __construct(Horde_Controller_Request_Http $request, $params = array())
    {
        parent::__construct($request, $params);

        /* Check for requirements */
        $this->_get = $request->getGetVars();
        if ($request->getMethod() == 'POST' &&
            (empty($this->_get['Cmd']) ||  empty($this->_get['User']) || empty($this->_get['DeviceId']) || empty($this->_get['DeviceType']))) {

            $this->_logger->err('Missing required parameters.');

            throw new Horde_Rpc_Exception('Your device requested the ActiveSync URL wihtout required parameters.');
        }

        /* Set our server and backend objects */
        $this->_backend = $params['backend'];
        $this->_server = $params['server'];
        /* provisioning can be false, true, or 'loose' */
        $this->_server->setProvisioning(empty($params['provisioning']) ? false : $params['provisioning']);
    }

    /**
     * Returns the Content-Type of the response.
     *
     * @return string  The MIME Content-Type of the RPC response.
     */
    public function getResponseContentType()
    {
        return 'application/vnd.ms-sync.wbxml';
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
     *
     * @return void
     */
    public function getResponse($request)
    {
        /* Not sure about this, but it's what zpush did so... */
        ob_start(null, 1048576);

        switch ($this->_request->getServer('REQUEST_METHOD')) {
        case 'OPTIONS':
            $this->_logger->debug('Horde_Rpc_ActiveSync::getResponse() starting for OPTIONS');
            $this->_server->handleRequest('Options', null, null);
            break;

        case 'POST':
            Horde_ActiveSync::activeSyncHeader();

            // Do the actual request
            $this->_logger->debug('Horde_Rpc_ActiveSync::getResponse() starting for ' . $this->_get['Cmd']);
            $this->_server->handleRequest($this->_get['Cmd'], $this->_get['DeviceId']);

            break;

        case 'GET':
            /* Someone trying to access the activesync url from a browser */
            throw new Horde_Rpc_Exception('Trying to access the ActiveSync endpoint from a browser. Not Supported.');
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

        // TODO: Figure this out...Z-Push had two possible paths for outputting
        // to the client, 1) if the ob reached it's capacity, and here...but
        // it didn't originally output the Content-Type header
        header('Content-Type: application/vnd.ms-sync.wbxml');
        header('Content-Length: ' . $len);
        echo $data;
    }

    /**
     * Check authentication. Different backends may handle
     * authentication in different ways. The base class implementation
     * checks for HTTP Authentication against the Horde auth setup.
     *
     * @TODO should the realm be configurable - since Horde is only one of the
     * possible backends?
     *
     * @return boolean  Returns true if authentication is successful.
     *                  Should send appropriate "not authorized" headers
     *                  or other response codes/body if auth fails,
     *                  and take care of exiting.
     */
    public function authorize()
    {
        $this->_logger->debug('Horde_Rpc_ActiveSync::authorize() starting');
        if (!$this->_requireAuthorization) {
            return true;
        }

        /* Get user and possibly domain */
        $user = $this->_request->getServer('PHP_AUTH_USER');
        $pos = strrpos($user, '\\');
        if ($pos !== false) {
            $domain = substr($user, 0, $pos);
            $user = substr($user, $pos + 1);
        } else {
            $domain = null;
        }

        /* Get passwd */
        $pass = $this->_request->getServer('PHP_AUTH_PW');

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

}
