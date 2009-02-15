<?php
define('HORDE_BASE', '/private/var/www/html/horde');
require_once HORDE_BASE . '/lib/base.php';

$appapikey = 'xxx';    //CHANGE THIS
$appsecret = 'xxx'; //CHANGE THIS

$facebook = new Horde_Service_Facebook($appapikey, $appsecret, null, false);
$user_id = $facebook->require_login();

// Use a fql query to get some friend info
$result = $facebook->fql_query('SELECT name, status FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = ' . $user_id . ')');
var_dump($result);

// Similar can be done as so using individual api calls...
//  $friends = $facebook->friends_get();
//  foreach ($friends as $friend) {
//    $personArray = $facebook->users_getInfo($friend, 'name');
//    $person[] = $personArray[0];
//  }
//
//  foreach ($person as $f) {
//    echo ' ' . $f['name'] . '<br />';
//  }


// Get a list of new notifications:
var_dump($facebook->notifications_get());