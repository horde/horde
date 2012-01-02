<?php
/**
 * Basic example for fetching a page with Horde_Http_Client
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */

require 'Horde/Autoloader/Default.php';

$client = new Horde_Http_Client();
$response = $client->get('http://www.example.com/');
var_dump($response);
echo $response->getBody();
