<?php
/**
 * Callback page for Twitter integration.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Twitter
 */

require_once dirname(__FILE__) . '/../lib/base.php';

/* Keys - these are obtained when registering for the service */
$consumer_key = '********';
$consumer_secret = '*********';

/* Used to obtain an unprivileged request token */
$token_url = 'http://twitter.com/oauth/request_token';

/* Used for allowing the user to allow/deny access to the application */
// (User is redirected to this URL if needed).
$auth_url = 'http://twitter.com/oauth/authorize';

// Used to obtain an access token after user authorizes the application
$accessToken_url = 'http://twitter.com/oauth/access_token';

/* Parameters required for the Horde_Oauth_Consumer */
$params = array('key' => $consumer_key,
                'secret' => $consumer_secret,
                'requestTokenUrl' => $token_url,
                'authorizeTokenUrl' => $auth_url,
                'accessTokenUrl' => $accessToken_url,
                'signatureMethod' => new Horde_Oauth_SignatureMethod_HmacSha1());

/* Create the Consumer */
$oauth = new Horde_Oauth_Consumer($params);

/* Create the Twitter client */
$twitter = new Horde_Service_Twitter(array('oauth' => $oauth,
                                           'request' => new Horde_Controller_Request_Http()));
/* At this point we would check for an existing, valid authorization token */
// $auth_token should be a Horde_Oauth_Token object
// $auth_token = getTokenFromStorage();

// Do we have a good auth token? Keep in mind this is example code, and in a true
// callback page we probably wouldn't be doing anything if we already have a token,
// but for testing purposes....
if (!empty($auth_token)) {
    /* Have a token, tell the Twitter client about it */
    $twitter->auth->setToken($auth_token);

    // Do something cool....
    // $twitter->statuses->update('Testing Horde/Twitter integration');

} elseif (!empty($_SESSION['twitter_request_secret'])) {
    /* No existing auth token, maybe we are in the process of getting it? */
    $a_token = $twitter->auth->getAccessToken(new Horde_Controller_Request_Http(),
                                              $_SESSION['twitter_request_secret']);

    // Clear the request secret from the session now that we're done with it,
    // again, using _SESSION for simplicity for this example
    $_SESSION['twitter_request_secret'] = '';

    if ($a_token === false || empty($a_token)) {
        // We had a request secret, but something went wrong. maybe navigated
        // back here between requests?
        echo 'error';
        die;
    } else {
        // We have a good token, save it to DB etc....
        var_dump($a_token);
        die;
    }
}

// No auth token, not in the process of getting one...ask user to verify
$results = $twitter->auth->getRequestToken();
$_SESSION['twitter_request_secret'] = $results->secret;

// Redirect to auth url
Horde::externalUrl($twitter->auth->getUserAuthorizationUrl($results), false)->redirect();
