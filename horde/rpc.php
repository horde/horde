<?php
/**
 * RPC processing script.
 *
 * Possible GET values:
 * <pre>
 * 'requestMissingAuthorization' - Whether or not to request authentication
 *                                 credentials if they are not already
 *                                 present.
 * 'wsdl' - TODO
 * </pre>
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
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
     (strpos($_SERVER['CONTENT_TYPE'], 'application/vnd.ms-sync.wbxml') !== false)) ||
   (strpos($_SERVER['REQUEST_URI'], 'Microsoft-Server-ActiveSync') !== false)) {
    /* ActiveSync Request */
    $serverType = 'ActiveSync';
    $nocompress = true;
    $session_control = 'none';
} elseif (!empty($_SERVER['PATH_INFO']) ||
          in_array($_SERVER['REQUEST_METHOD'], array('DELETE', 'PROPFIND', 'PUT', 'OPTIONS'))) {
    $serverType = 'Webdav';
} elseif (!empty($_SERVER['CONTENT_TYPE'])) {
    if (strpos($_SERVER['CONTENT_TYPE'], 'application/vnd.syncml+xml') !== false) {
        $serverType = 'Syncml';
        $nocompress = true;
        $session_control = 'none';
    } elseif (strpos($_SERVER['CONTENT_TYPE'], 'application/vnd.syncml+wbxml') !== false) {
        $serverType = 'Syncml_Wbxml';
        $nocompress = true;
        $session_control = 'none';
    } elseif (strpos($_SERVER['CONTENT_TYPE'], 'text/xml') !== false) {
        $input = Horde_Rpc::getInput();
        /* Check for SOAP namespace URI. */
        $serverType = (strpos($input, 'http://schemas.xmlsoap.org/soap/envelope/') !== false)
            ? 'Soap'
            : 'Xmlrpc';
    } elseif (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $serverType = 'Jsonrpc';
    } else {
        header('HTTP/1.0 501 Not Implemented');
        exit;
    }
} elseif ($_SERVER['QUERY_STRING'] && $_SERVER['QUERY_STRING'] == 'phpgw') {
    $serverType = 'Phpgw';
} else {
    $serverType = 'Soap';
}

/* Initialize Horde environment. */
Horde_Registry::appInit('horde', array(
    'authentication' => 'none',
    'nocompress' => $nocompress,
    'session_control' => $session_control
));

$request = $GLOBALS['injector']->getInstance('Horde_Controller_Request');

$params['logger'] = $injector->getInstance('Horde_Log_Logger');

/* Check to see if we want to exit if required credentials are not
 * present. */
if (($ra = Horde_Util::getGet('requestMissingAuthorization')) !== null) {
    $params['requestMissingAuthorization'] = $ra;
}

/* Driver specific tasks that require Horde environment. */
switch ($serverType) {
case 'ActiveSync':
    /* Check if we are even enabled for AS */
    if (empty($conf['activesync']['enabled'])) {
        exit;
    }

    if ($conf['activesync']['logging']['type'] == 'custom') {
        $params['logger'] = new Horde_Log_Logger(new Horde_Log_Handler_Stream(fopen($conf['activesync']['logging']['path'], 'a')));
    }

    $state_params = $conf['activesync']['state']['params'];
    $state_params['db'] = $injector->getInstance('Horde_Db_Adapter');
    $stateMachine = new Horde_ActiveSync_State_History($state_params);
    $params['registry'] = $registry;
    $driver_params = array(
        'connector' => new Horde_ActiveSync_Driver_Horde_Connector_Registry($params),
        'ping' => $conf['activesync']['ping'],
        'state_basic' => $stateMachine,
        'auth' => $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
    );

    if ($params['provisioning'] = $conf['activesync']['securitypolicies']['provisioning']) {
        $driver_params['policies'] = $conf['activesync']['securitypolicies'];
    }
    $params['backend'] = new Horde_ActiveSync_Driver_Horde($driver_params);
    $params['server'] = new Horde_ActiveSync(
        $params['backend'],
        new Horde_ActiveSync_Wbxml_Decoder(fopen('php://input', 'r')),
        new Horde_ActiveSync_Wbxml_Encoder(fopen('php://output', 'w+')),
        $request
    );
    $params['server']->setLogger($params['logger']);
    break;

case 'Soap':
    $serverVars = $request->getServerVars();
    if (!$serverVars['REQUEST_METHOD'] ||
        ($serverVars['REQUEST_METHOD'] != 'POST')) {
        $params['requireAuthorization'] = false;
        $input = (Horde_Util::getGet('wsdl') === null)
            ? 'disco'
            : 'wsdl';
    }
    break;
}

/* Load the RPC backend based on $serverType. */
$server = Horde_Rpc::factory($serverType, $request, $params);

/* Let the backend check authentication. By default, we look for HTTP
 * basic authentication against Horde, but backends can override this
 * as needed. */
$server->authorize();

/* Get the server's response. We call $server->getInput() to allow
 * backends to handle input processing differently. */
if (is_null($input)) {
    $input = $server->getInput();
}

$out = $server->getResponse($input);
if ($out instanceof PEAR_Error) {
    header('HTTP/1.0 500 Internal Server Error');
    echo $out->getMessage();
    exit;
}

// Allow backends to determine how and when to send output.
$server->sendOutput($out);
