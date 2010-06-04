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

// We have a chicken-and-egg problem here regarding app initialization. Since
// different RPC servers have different session requirements, we can't call
// appInit() until we know which server we are requesting. The Request object
// also needs to know that we don't want a session since it tries to access/
// initialize the session state.  We therfore don't create the Request object
// or initialize the application until after we know the rpc server we want.
$input = $session_control = null;
$nocompress = false;
$params = array();

/* Look at the Content-type of the request, if it is available, to try
 * and determine what kind of request this is. */
if ((!empty($_SERVER['CONTENT_TYPE']) &&
     strpos($_SERVER['CONTENT_TYPE'], 'application/vnd.ms-sync.wbxml') !== false) ||
    strpos($_SERVER['REQUEST_URI'], 'Microsoft-Server-ActiveSync') !== false) {

    /* ActiveSync Request */
    $serverType = 'ActiveSync';
    $session_control = 'none';
    $nocompress = true;
    Horde_Registry::appInit('horde', array('authentication' => 'none', 'nocompress' => $nocompress, 'session_control' => $session_control));

    /* Check if we are even enabled for AS */
    if (!empty($conf['activesync']['enabled'])) {
        $request = new Horde_Controller_Request_Http(array('session_control' => $session_control));
        if ($conf['activesync']['logging']['type'] == 'custom') {
            $params['logger'] = new Horde_Log_Logger(new Horde_Log_Handler_Stream(fopen($conf['activesync']['logging']['path'], 'a')));
        } else {
            $params['logger'] = $injector->getInstance('Horde_Log_Logger');
        }
        $mailer = $injector->getInstance('Horde_Mail');

        /* TODO: Probably want to bind a factory to injector for this? */
        $params['registry'] = $registry;
        $connector = new Horde_ActiveSync_Driver_Horde_Connector_Registry($params);
        switch ($conf['activesync']['state']['driver']) {
        case 'file':
            $stateMachine = new Horde_ActiveSync_State_File($conf['activesync']['state']['params']);
            break;
        case 'history':
            $state_params = $conf['activesync']['state']['params'];
            $state_params['db'] = $injector->getInstance('Horde_Db_Adapter_Base')->getDb();
            $stateMachine = new Horde_ActiveSync_State_History($state_params);
        }

        $driver_params = array('connector' => $connector,
                               'state_basic' => $stateMachine,
                               'mail' => $mailer,
                               'ping' => $conf['activesync']['ping']);

        if ($params['provisioning'] = $conf['activesync']['securitypolicies']['provisioning']) {
            $driver_params['policies'] = $conf['activesync']['securitypolicies'];
        }
        $params['backend'] = new Horde_ActiveSync_Driver_Horde($driver_params);
        $params['server'] = new Horde_ActiveSync($params['backend'],
                                                 new Horde_ActiveSync_Wbxml_Decoder(fopen('php://input', 'r')),
                                                 new Horde_ActiveSync_Wbxml_Encoder(fopen('php://output', 'w+')),
                                                 $request);
        $params['server']->setLogger($params['logger']);
    }
} elseif (!empty($_SERVER['PATH_INFO']) ||
          in_array($_SERVER['REQUEST_METHOD'], array('DELETE', 'PROPFIND', 'PUT', 'OPTIONS'))) {
    $serverType = 'Webdav';
    Horde_Registry::appInit('horde', array('authentication' => 'none', 'nocompress' => $nocompress, 'session_control' => $session_control));
    $request = new Horde_Controller_Request_Http(array('session_control' => $session_control));
} elseif ($_SERVER['CONTENT_TYPE']) {
    if (strpos($_SERVER['CONTENT_TYPE'], 'application/vnd.syncml+xml') !== false) {
        $serverType = 'Syncml';
        $session_control = 'none';
        $nocompress = true;
    } elseif (strpos($_SERVER['CONTENT_TYPE'], 'application/vnd.syncml+wbxml') !== false) {
        $serverType = 'Syncml_Wbxml';
        $session_control = 'none';
        $nocompress = true;
    } elseif (strpos($_SERVER['CONTENT_TYPE'], 'text/xml') !== false) {
        $input = Horde_Rpc::getInput();
        /* Check for SOAP namespace URI. */
        if (strpos($input, 'http://schemas.xmlsoap.org/soap/envelope/') !== false) {
            $serverType = 'Soap';
        } else {
            $serverType = 'Xmlrpc';
        }
    } elseif (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $serverType = 'Jsonrpc';
    } else {
        header('HTTP/1.0 501 Not Implemented');
        exit;
    }
    Horde_Registry::appInit('horde', array('authentication' => 'none', 'nocompress' => $nocompress, 'session_control' => $session_control));
    $request = new Horde_Controller_Request_Http(array('session_control' => $session_control));
} elseif ($_SERVER['QUERY_STRING'] && $_SERVER['QUERY_STRING'] == 'phpgw') {
    $serverType = 'Phpgw';
    Horde_Registry::appInit('horde', array('authentication' => 'none', 'nocompress' => $nocompress, 'session_control' => $session_control));
    $request = new Horde_Controller_Request_Http(array('session_control' => $session_control));
} else {
    $serverType = 'Soap';
    Horde_Registry::appInit('horde', array('authentication' => 'none', 'nocompress' => $nocompress, 'session_control' => $session_control));
    $request = new Horde_Controller_Request_Http(array('session_control' => $session_control));
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

/* Make sure we have a logger */
if (empty($params['logger'])) {
    $params['logger'] = $injector->getInstance('Horde_Log_Logger');
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

