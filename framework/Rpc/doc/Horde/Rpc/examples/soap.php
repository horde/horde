<?php
/**
 * @package Rpc
 */

die("Please configure the URL, username, and password, and then remove this line.\n");

// SOAP endpoint
$rpc_endpoint = 'http://example.com/horde/rpc.php';

// SOAP method to call
$rpc_method = 'calendar.listCalendars';

// SOAP options, usually username and password
$rpc_options = array(
    'login' => '',
    'password' => '',
    'namespace' => 'urn:horde',
    'timeout' => 5,
    'allowRedirects' => true,
    'maxRedirects' => 3,
    'location' => $rpc_endpoint,
    'uri' => 'urn:horde',
    'exceptions' => true,
    'trace' => true,
);

$soap = new SoapClient(null, $rpc_options);
$result = Horde_Rpc::request(
    'soap',
    $GLOBALS['rpc_endpoint'],
    $GLOBALS['rpc_method'],
    $soap,
    array());
var_dump($result);
