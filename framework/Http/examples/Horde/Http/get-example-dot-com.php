<?php
/**
 * Basic example for fetching a page with Horde_Http_Client
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */

require 'Horde/Autoloader.php';

$client = new Horde_Http_Client();
$response = $client->get('http://www.example.com/');
var_dump($response);
echo $response->getBody();
