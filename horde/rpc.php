<?php
/**
 * RPC processing script.
 *
 * Possible GET values:
 *
 *   'requestMissingAuthorization' -- Whether or not to request
 *   authentication credentials if they are not already present.
 *
 *   'wsdl' -- TODO
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';

$input = $session_control = null;
$nocompress = false;
$params = array();

/* Load base libraries. */
Horde_Registry::appInit('horde', array('authentication' => 'none', 'nocompress' => $nocompress, 'session_control' => $session_control));

/* Get a request object. */
$request = new Horde_Controller_Request_Http();

/* TODO: This is for debugging, replace with logger from injector before merge */
//$params['logger'] = new Horde_Log_Logger(new Horde_Log_Handler_Stream(fopen('/tmp/activesync.txt', 'a')));
$params['logger'] = $GLOBALS['injector']->getInstance('Horde_Log_Logger');

/* Look at the Content-type of the request, if it is available, to try
 * and determine what kind of request this is. */
if (!empty($GLOBALS['conf']['activesync']['enabled']) &&
    ((strpos($request->getServer('CONTENT_TYPE'), 'application/vnd.ms-sync.wbxml') !== false) ||
    (strpos($request->getUri(), 'Microsoft-Server-ActiveSync') !== false))) {
    /* ActiveSync Request */
    $serverType = 'ActiveSync';
    $horde_session_control = 'none';
    $horde_no_compress = true;

    /* TODO: Probably want to bind a factory to injector for this? */
    $params['registry'] = $GLOBALS['registry'];
    $connector = new Horde_ActiveSync_Driver_Horde_Connector_Registry($params);
    $stateMachine = new Horde_ActiveSync_State_File(array('stateDir' => $GLOBALS['conf']['activesync']['state']['directory']));
    $driver_params = array('connector' => $connector, 'state_basic' => $stateMachine);
    if ($params['provisioning'] = $GLOBALS['conf']['activesync']['securitypolicies']['provisioning']) {
        $driver_params['policies'] = $GLOBALS['conf']['activesync']['securitypolicies'];
    }
    $params['backend'] = new Horde_ActiveSync_Driver_Horde($driver_params);
    $params['server'] = new Horde_ActiveSync($params['backend'],
                                             new Horde_ActiveSync_Wbxml_Decoder(fopen('php://input', 'r')),
                                             new Horde_ActiveSync_Wbxml_Encoder(fopen('php://output', 'w+')),
                                             $request);
    $params['server']->setLogger($params['logger']);
} elseif ($request->getServer('PATH_INFO') ||
    in_array($request->getServer('REQUEST_METHOD'), array('DELETE', 'PROPFIND', 'PUT', 'OPTIONS'))) {
    $serverType = 'Webdav';
} elseif ($request->getServer('CONTENT_TYPE')) {
    if (strpos($request->getServer('CONTENT_TYPE'), 'application/vnd.syncml+xml') !== false) {
        $serverType = 'Syncml';
        /* Syncml does its own session handling. */
        $session_control = 'none';
        $nocompress = true;
    } elseif (strpos($request->getServer('CONTENT_TYPE'), 'application/vnd.syncml+wbxml') !== false) {
        $serverType = 'Syncml_Wbxml';
        /* Syncml does its own session handling. */
        $horde_session_control = 'none';
        $horde_no_compress = true;
    } elseif (strpos($request->getServer('CONTENT_TYPE'), 'text/xml') !== false) {
        $input = Horde_Rpc::getInput();
        /* Check for SOAP namespace URI. */
        if (strpos($input, 'http://schemas.xmlsoap.org/soap/envelope/') !== false) {
            $serverType = 'Soap';
        } else {
            $serverType = 'Xmlrpc';
        }
    } elseif (strpos($request->getServer('CONTENT_TYPE'), 'application/json') !== false) {
        $serverType = 'Jsonrpc';
    } else {
        header('HTTP/1.0 501 Not Implemented');
        exit;
    }
} elseif ($request->getServer('QUERY_STRING') && $request->getServer('QUERY_STRING') == 'phpgw') {
    $serverType = 'Phpgw';
} else {
    $serverType = 'Soap';
}

if ($serverType == 'Soap' &&
    (!$request->getServer('REQUEST_METHOD') ||
     $request->getServer('REQUEST_METHOD') != 'POST')) {
    $session_control = 'none';
    $params['requireAuthorization'] = false;
    if (Horde_Util::getGet('wsdl') !== null) {
        $input = 'wsdl';
    } else {
        $input = 'disco';
    }
}

/* Check to see if we want to exit if required credentials are not
 * present. */
if (($ra = Horde_Util::getGet('requestMissingAuthorization')) !== null) {
    $params['requestMissingAuthorization'] = $ra;
}

/* Load the RPC backend based on $serverType. */
$server = Horde_Rpc::factory($serverType, $request, $params);

/* Let the backend check authentication. By default, we look for HTTP
 * basic authentication against Horde, but backends can override this
 * as needed. */
$server->authorize();

/* Get the server's response. We call $server->getInput() to allow
 * backends to handle input processing differently. */
if ($input === null) {
    $input = $server->getInput();
}

$out = $server->getResponse($input);
if (is_a($out, 'PEAR_Error')) {
    header('HTTP/1.0 500 Internal Server Error');
    echo $out->getMessage();
    exit;
}

// Allow backends to determine how and when to send output.
$server->sendOutput($out);

