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

require_once dirname(__FILE__) . '/lib/core.php';

$input = $session_control = null;
$nocompress = false;
$params = array();

/* Look at the Content-type of the request, if it is available, to try
 * and determine what kind of request this is. */
if (!empty($_SERVER['PATH_INFO']) ||
    in_array($_SERVER['REQUEST_METHOD'], array('DELETE', 'PROPFIND', 'PUT', 'OPTIONS'))) {
    $serverType = 'Webdav';
} elseif (!empty($_SERVER['CONTENT_TYPE'])) {
    if (strpos($_SERVER['CONTENT_TYPE'], 'application/vnd.syncml+xml') !== false) {
        $serverType = 'Syncml';
        /* Syncml does its own session handling. */
        $session_control = 'none';
        $nocompress = true;
    } elseif (strpos($_SERVER['CONTENT_TYPE'], 'application/vnd.syncml+wbxml') !== false) {
        $serverType = 'Syncml_Wbxml';
        /* Syncml does its own session handling. */
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
} elseif (!empty($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] == 'phpgw') {
    $serverType = 'Phpgw';
} else {
    $serverType = 'Soap';
}

if ($serverType == 'Soap' &&
    (!isset($_SERVER['REQUEST_METHOD']) ||
     $_SERVER['REQUEST_METHOD'] != 'POST')) {
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

/* Load base libraries. */
new Horde_Application(array('authentication' => 'none', 'nocompress' => $nocompress, 'session_control' => $session_control));

/* Load the RPC backend based on $serverType. */
$server = Horde_Rpc::factory($serverType, $params);

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

/* Return the response to the client. */
header('Content-Type: ' . $server->getResponseContentType());
header('Content-length: ' . strlen($out));
header('Accept-Charset: UTF-8');
echo $out;
