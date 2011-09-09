<?php
require_once 'Horde/Autoloader.php';

// Create a Horde_Service_Vimeo_Simple object
// 'http_client' is required, a cache and cache_lifetime are optional
$params = array('http_client' => new Horde_Http_Client(),
                'cache' => $GLOBALS['cache'],
                'cache_lifetime' => $GLOBALS['conf']['cache']['default_lifetime']);

$v = Horde_Service_Vimeo::factory('Simple', $params);


// Get the list of all user videos
$results = unserialize($v->user('user1015172')->clips()->run());

// Get the list of all clips in a group
$results = unserialize($v->group('bestof08')->clips()->run());

// List of all clips in a channel
$results = unserialize($v->channel('theedit')->clips()->run());

// List of clips in an album
$results = unserialize($v->album('52803')->clips()->run());

// Get first video to embed - this returns a json encoded array
$embed = $v->getEmbedJson($latest['url']);

// Decode the data and print out the HTML. You could also just output
// the json within your page's javascript for use later etc...
$results = Horde_Serialize::unserialize($embed, Horde_Serialize::JSON);
echo $results->html;
