<?php
/**
 * A test callback function for login redirects.
 * @param $url
 * @return unknown_type
 */
function testFBCallback($url)
{
    echo '<script type="text/javascript">alert("testing callback:' . $url . '");</script>';
    exit;
}

define('HORDE_BASE', '/private/var/www/html/horde');
require_once HORDE_BASE . '/lib/base.php';

$appapikey = 'xxx';    //CHANGE THIS
$appsecret = 'xxx';    //CHANGE THIS

// Horde_Service_Facebook *requires* an http_client, http_request objects
// it can optionally use a callback function to handle login redirects
$context = array('http_client' => new Horde_Http_Client(),
                 'http_request' => new Horde_Controller_Request_Http(),);
                 //'login_redirect_callback' => 'testFBCallback');

// Create the facebook object and make sure we have an active, authenticated
// session.
$facebook = new Horde_Service_Facebook($appapikey, $appsecret, $context);
$user_id = $facebook->require_login();


/** Use a FQL query to get some friend info **/
$result = $facebook->fql_query('SELECT name, status FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = ' . $user_id . ')');
var_dump($result);

/** Similar can be done as so using individual api calls...but takes a *long* time **/
//$friends = $facebook->friends_get();
//  foreach ($friends as $friend) {
//    $personArray = $facebook->users_getInfo($friend, 'name');
//    $person[] = $personArray[0];
//  }
//
//  foreach ($person as $f) {
//    echo ' ' . $f['name'] . '<br />';
//  }


/** Calling code that requires extended permissions **/
try {
    $facebook->users_setStatus('is testing my facebook code...again.');
} catch (Horde_Service_Facebook_Exception $e) {
  // Check that we failed because of insufficient app permissions.
  // then redirect if needed...
}

/** Batch mode. **/
// When calling in batch mode, you must assign the results of the method calls
// as a reference so when run() is called, you still have a handle to the
// results.
$facebook->batchBegin();
$notifications = &$facebook->notifications_get();
$friends = &$facebook->friends_get();
$facebook->batchEnd();
var_dump($friends);
var_dump($notifications);