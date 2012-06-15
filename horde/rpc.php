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
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */

require_once __DIR__ . '/lib/Application.php';

// Since different RPC servers have different session requirements, we can't
// call appInit() until we know which server we are requesting. We  don't
// initialize the application until after we know the rpc server we want.
$input = $session_control = null;
$nocompress = false;
$params = array();

/* Look at the Content-type of the request, if it is available, to try
 * and determine what kind of request this is. */
if ((!empty($_SERVER['CONTENT_TYPE']) &&
     (strpos($_SERVER['CONTENT_TYPE'], 'application/vnd.ms-sync.wbxml') !== false)) ||
   (strpos($_SERVER['REQUEST_URI'], 'Microsoft-Server-ActiveSync') !== false) ||
   (strpos($_SERVER['REQUEST_URI'], 'autodiscover/autodiscover.xml') !== false)) {
    /* ActiveSync Request */
    $conf['cookie']['path'] = '/Microsoft-Server-ActiveSync';
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
    // Check if AS is enabled. Note that we can't check the user perms for it
    // here since the user is not yet logged into horde at this point.
    if (empty($conf['activesync']['enabled'])) {
        exit;
    }
    $params['server'] = $injector->getInstance('Horde_ActiveSyncServer');
    $params['provisioning'] = $conf['activesync']['securitypolicies']['provisioning'];
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
try {
    $server = Horde_Rpc::factory($serverType, $request, $params);
} catch (Horde_Rpc_Exception $e) {
    Horde::logMessage($e, 'ERR');
    header('HTTP/1.1 501 Not Implemented');
    exit;
}

// Let the backend check authentication. By default, we look for HTTP
// basic authentication against Horde, but backends can override this
// as needed. Must reset the authentication argument since we delegate
// auth to the RPC server.
$GLOBALS['registry']->setAuthenticationSetting(
    (array_key_exists($params, 'requireAuthorization') && $params['requireAuthorization'] === false)
     ? 'none'
     : 'Authenticate');

try {
    $server->authorize();
} catch (Horde_Rpc_Exception $e) {
    Horde::logMessage($e, 'ERR');
    header('HTTP/1.0 500 Internal Server Error');
    echo $e->getMessage();
    exit;
}


/* Get the server's response. We call $server->getInput() to allow
 * backends to handle input processing differently. */
if (is_null($input)) {
    try {
        $input = $server->getInput();
    } catch (Horde_Rpc_Exception $e) {
        Horde::logMessage($e, 'ERR');
        header('HTTP/1.0 500 Internal Server Error');
        echo $e->getMessage();
        exit;
    }
}

try {
    $out = $server->getResponse($input);
} catch (Horde_Rpc_Exception $e) {
    Horde::logMessage($e, 'ERR');
    header('HTTP/1.0 500 Internal Server Error');
    echo $e->getMessage();
    exit;
}


if ($out instanceof PEAR_Error) {
    header('HTTP/1.0 500 Internal Server Error');
    echo $out->getMessage();
    exit;
}

// Allow backends to determine how and when to send output.
$server->sendOutput($out);
