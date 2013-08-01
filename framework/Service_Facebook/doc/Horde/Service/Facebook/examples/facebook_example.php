<?php
define('HORDE_BASE', '/private/var/www/html/horde');
require_once HORDE_BASE . '/lib/base.php';

// To call Facebook API methods, you will need to set up the application in
// Facebook, and obtain both the api_key and the app_secret.
// See:
//  http://developers.facebook.com/get_started.php?tab=tutorial

$apikey = 'xxx';    //CHANGE THIS
$secret = 'xxx';    //CHANGE THIS

/**
 * Horde_Service_Facebook *requires* an http_client, http_request objects
 * and a Horde_Log_Logger object
 */
$context = array('http_client' => new Horde_Http_Client(),
                 'http_request' => new Horde_Controller_Request_Http());

/** Create the facebook object **/
$facebook = new Horde_Service_Facebook($apikey, $secret, $context);

/**
 * Authenticating and logging into a Facebook app from an external site is
 * a complicated and multi-stage process.  For these examples, we are assuming
 * that we have authenticated the application and are either already logged into
 * Facebook or we have authorized 'offline_access'.
 */

/**
 * If we have a valid cookie, this will know about it. This method should also
 * be called both after the user has authorized the application and again after
 * the user has (optionally) authorized infinite sessions (offline_access). Then
 * you would obtain the infinite session_key by calling auth->getSessionKey() and
 * storing the results as you will not be able to retrieve it from FB again.
 * This is the *only* way to obtain the session key.
 */
//$facebook->auth->validateSession();

// Current uid can be obtained with:
//$uid = $facebook->auth->getUser();

/** session_key, if you need it, can be obtained via: **/
//$sid = $facebook->auth->getSessionKey();

/**
 * Otherwise, you would use uid and session_key from prefs or other local
 * storage and set up the session by calling setUser(). This is how you would
 * need to do this when utilizing an infinite session_key, since FB will only
 * send the infinite session_key to you one time only - it's up to client code
 * to store it.
 */
 $fbp = unserialize($prefs->getValue('facebook'));
 $uid = $fbp['uid'];
 $sid = $fbp['sid'];
 $facebook->auth->setUser($uid, $sid, 0);


/** Use a FQL query to get some friend info **/
$result = $facebook->fql->run('SELECT name, status FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = ' . $uid . ')');
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
//try {
//    // Set your Facebook status (requires 'status_update' extended perm)
//    $facebook->users->setStatus('is testing my Horde_Service_Facebook client library code...again.');
//} catch (Horde_Service_Facebook_Exception $e) {
//    // Check that we failed because of insufficient app permissions.
//    // then redirect if needed...
//    if ($e->getCode() == Horde_Service_Facebook_ErrorCodes::API_EC_PERMISSION_STATUS_UPDATE) {
//      // Don't have status_update...tell user/provide link to authorize page etc...
        // You can get the link to the authorize page like this:
//        $facebook->auth->getExtendedPermUrl(
//            Horde_Service_Facebook_Auth::EXTEND_PERMS_STATUSUPDATE,
//            'http://yourcallbackurl.com');
//    } else {
//      // Something else
//      echo $e->getMessage();
//    }
//}

/**
 * Alternatively, you could check for the necessary perms first, but IMO, it's
 * more effecient to get the error since checking the perms and then performing
 * the action require two round trips to the server.
 */
//$hasPerm = $facebook->users->hasAppPermissions('status_update');
//if ($hasPerm) {
//    //.....
//}


/**
 * Batch mode.
 * When calling in batch mode, you must assign the results of the method calls
 * as a reference so when run() is called, you still have a handle to the
 * results.
 */
//$facebook->batchBegin();
//$notifications = &$facebook->notifications->get();
//$friends = &$facebook->friends->get();
//$facebook->batchEnd();
//var_dump($friends);
//var_dump($notifications);

/**
 * View a user's pictures. $uid should be the user id whose albums you want to
 * retrieve. (Permissions permitting, of course)
 */
//$albums = $facebook->photos->getAlbums($uid);
//var_dump($albums);
//$images = $facebook->photos->get('', $albums[0]['aid']);
//var_dump($images);


/**
 * Request the raw JSON (or XML) data
 */
//$facebook->dataFormat = Horde_Service_Facebook::DATA_FORMAT_JSON;
//$results = $facebook->photos->getAlbums($uid);
//var_dump($results);

/**
 * Upload a photo
 */
$path = "/Users/mrubinsk/Desktop/horde_fb.jpg";
$results = $facebook->photos->upload($path);
var_dump($results);