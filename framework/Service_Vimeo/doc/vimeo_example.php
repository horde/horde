<?php

// require_once /var/www/html/horde/horde-hatchery/service/lib/VimeoSimple.php';

require_once 'Horde/Autoloader.php';
$v = new Service_VimeoSimple();

// Get the list of all user videos
$results = unserialize($v->getClips(array('userClips' => 'user1015172')));

// Get first video to embed
$latest = $results[0]; 

// Get the code to embed it
$embed = $v->getEmbedJSON($latest['url']);

// Decode the data and print out the HTML
$results = Horde_Serialize::unserialize($embed, SERIALIZE_JSON);
echo $results->html;
