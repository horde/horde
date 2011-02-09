<?php
/**
 * @package Horde_RPC
 */

die("Please configure the URL, username, and password, and then remove this line.\n");

require_once 'Horde/RPC.php';

// XML-RPC endpoint
$rpc_endpoint = 'http://example.com/horde/rpc.php';

// XML-RPC method to call
$rpc_method = 'calendar.listCalendars';

// XML-RPC options, usually username and password
$rpc_options = array(
    'user' => '',
    'pass' => '',
);

$http_client = new Horde_Http_Client($rpc_options);
$result = Horde_RPC::request(
    'xmlrpc',
    $GLOBALS['rpc_endpoint'],
    $GLOBALS['rpc_method'],
    $GLOBALS['http_client'],
    array());

var_dump($result);
