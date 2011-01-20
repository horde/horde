#!/usr/bin/env php
<?php
/**
 * @package Horde_Rpc
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
$user   = @array_shift($argv);
$pass   = @array_shift($argv);

switch ($testno) {
case 0:
    $response = Horde_Rpc_Xmlrpc::request(Horde::url('rpc.php', true, -1),
                                          'system.listMethods', null,
                                          array('user' => $user, 'pass' => $pass));
    break;

case 1:
    $response = Horde_Rpc_Xmlrpc::request(Horde::url('rpc.php', true, -1),
                                          'system.describeMethods', array('tasks.list'),
                                          array('user' => $user, 'pass' => $pass));
    break;

case 2:
    $response = Horde_Rpc_Xmlrpc::request(Horde::url('rpc.php', true, -1),
                                          'tasks.listTasks', array(0),
                                          array('user' => $user, 'pass' => $pass));
    break;

case 3:
    $response = Horde_Rpc_Xmlrpc::request('http://dev.horde.org/horde/rpc.php',
                                          'system.listMethods', null,
                                          array('user' => $user, 'pass' => $pass));
    break;

case 4:
    $response = Horde_Rpc_Soap::request(Horde::url('rpc.php', true, -1),
                                        'tasks.listTasks', array(),
                                        array('namespace' => 'urn:horde',
                                              'user' => $user,
                                              'pass' => $pass));
    break;

case 5:
    $response = Horde_Rpc_Soap::request(Horde::url('rpc.php', true, -1),
                                        array_shift($argv), $argv,
                                        array('namespace' => 'urn:horde',
                                              'user' => $user,
                                              'pass' => $pass));
    break;

case 6:
    $response = Horde_Rpc_Xmlrpc::request(Horde::url('rpc.php', true, -1),
                                          array_shift($argv), $argv,
                                          array('user' => $user, 'pass' => $pass));
    break;

case 7:
    $response = Horde_Rpc_Jsonrpc::request(Horde::url('rpc.php', true, -1),
                                           array_shift($argv), $argv,
                                           array('user' => $user, 'pass' => $pass));
    break;

}

if (is_a($response, 'PEAR_Error')) {
    echo "===error======\n";
    echo $response->getMessage();
    echo "\n";
    $info = $response->getUserInfo();
    if (is_string($info)) {
        echo strtr($info, array_flip(get_html_translation_table(HTML_ENTITIES)));
    } else {
        var_dump($info);
    }
    echo "\n==============\n";
} else {
    echo "===value======\n";
    var_dump($response);
    echo "==============\n";
}
