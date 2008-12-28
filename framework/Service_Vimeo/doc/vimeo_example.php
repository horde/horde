<?php
require_once 'Horde/Autoloader.php';
$v = Horde_Service_Vimeo::factory('Simple');

// Get the list of all user videos
$results = unserialize($v->user('user1015172')->clips()->run());

// Get the list of all clips in a group
$results = unserialize($v->group('bestof08')->clips()->run());

// List of all clips in a channel
$results = unserialize($v->channel('theedit')->clips()->run());

// List of clips in an album
$results = unserialize($v->album('52803')->clips()->run());

// Get first video to embed
$embed = $v->getEmbedJSON($latest['url']);

// Decode the data and print out the HTML
$results = Horde_Serialize::unserialize($embed, SERIALIZE_JSON);
echo $results->html;
