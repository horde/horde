<?php
/**
 * @package Horde_RPC
 */

die("Please configure the URL, username, and password, and then remove this line.\n");

require_once 'Horde/RPC.php';

// SOAP endpoint
$rpc_endpoint = 'http://example.com/horde/rpc.php';

// SOAP method to call
$rpc_method = 'calendar.listCalendars';

// SOAP options, usually username and password
$rpc_options = array(
    'user' => '',
    'pass' => '',
    'namespace' => 'urn:horde',
);

$result = Horde_RPC::request(
    'soap',
    $GLOBALS['rpc_endpoint'],
    $GLOBALS['rpc_method'],
    array(),
    $GLOBALS['rpc_options']);

var_dump($result);
