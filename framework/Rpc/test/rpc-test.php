#!/usr/bin/env php
<?php
/**
 * @package Rpc
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/horde/lib/Application.php';
Horde_Registry::appInit('horde', array('cli' => true));

$conf['server']['name'] = 'localhost';
$conf['server']['port'] = 80;

if (!isset($argv) || count($argv) < 2) {
    die("Can't read arguments.\n");
}

array_shift($argv);
$testno = array_shift($argv);
$rpc_params = array(
    'request.username' => @array_shift($argv),
    'request.password' => @array_shift($argv)
);
$language = isset($GLOBALS['language']) ?
    $GLOBALS['language'] :
    (isset($_SERVER['LANG']) ? $_SERVER['LANG'] : '');
if (!empty($language)) {
    $rpc_params['request.headers'] = array('Accept-Language' => $language);
}
$http = $GLOBALS['injector']->
    getInstance('Horde_Core_Facotory_HttpClient')->
    create($rpc_params);
try {
    switch ($testno) {

    case 0:
        $response = Horde_Rpc_Xmlrpc::request(Horde::url('rpc.php', true, -1),
                                              'system.listMethods', $http);
        break;

    case 1:
        $response = Horde_Rpc_Xmlrpc::request(Horde::url('rpc.php', true, -1),
                                              'system.describeMethods', $http,
                                              array('tasks.list'));
        break;

    case 2:
        $response = Horde_Rpc_Xmlrpc::request(Horde::url('rpc.php', true, -1),
                                              'tasks.listTasks', $http, array(0));
        break;

    case 3:
        $response = Horde_Rpc_Xmlrpc::request('http://dev.horde.org/horde/rpc.php',
                                              'system.listMethods', $http);
        break;

    case 4:
        // @TODO: Need to instantiate a soap client.
        $rpc_options = array(
            'login' => $rpc_params['username'],
            'password' => $rpc_params['password'],
            'namespace' => 'urn:horde',
            'timeout' => 5,
            'allowRedirects' => true,
            'maxRedirects' => 3,
            'location' => Horde::url(rpc.php, true, -1),
            'uri' => 'urn:horde',
            'exceptions' => true,
            'trace' => true,
        );
        $soap = new SOAP_Client(null, $rpc_options);
        $response = Horde_Rpc_Soap::request(Horde::url('rpc.php', true, -1),
                                            'tasks.listTasks', $soap, array());
        break;

    case 5:

        $rpc_options = array(
            'login' => $rpc_params['username'],
            'password' => $rpc_params['password'],
            'namespace' => 'urn:horde',
            'timeout' => 5,
            'allowRedirects' => true,
            'maxRedirects' => 3,
            'location' => Horde::url(rpc.php, true, -1),
            'uri' => 'urn:horde',
            'exceptions' => true,
            'trace' => true,
        );
        $soap = new SOAP_Client(null, $rpc_options);
        $response = Horde_Rpc_Soap::request(Horde::url('rpc.php', true, -1),
                                            array_shift($argv), $soap, $argv);

        break;

    case 6:
        $response = Horde_Rpc_Xmlrpc::request(Horde::url('rpc.php', true, -1),
                                              array_shift($argv), $http, $argv);
        break;

    case 7:
        $response = Horde_Rpc_Jsonrpc::request(Horde::url('rpc.php', true, -1),
                                               array_shift($argv), $http, $argv);
        break;

    }
    echo "===value======\n";
    var_dump($response);
    echo "==============\n";
} catch (Horde_Rpc_Exception $e) {
    echo "===error======\n";
    echo $e->getMessage();
    echo "\n";
    $info = $e->getTraceAsString();
    if (is_string($info)) {
        echo strtr($info, array_flip(get_html_translation_table(HTML_ENTITIES)));
    } else {
        var_dump($info);
    }
    echo "\n==============\n";
}
