<?php
/**
 * Example of posting a new Atom entry with Horde_Feed.
 *
 * @package Feed
 */

/* Parameters */
$blogUri = 'http://www.blogger.com/feeds/YOURBLOGID/posts/default';
$username = 'GOOGLEUSERNAMEWITHDOMAIN';
$password = 'PASSWORD';

/* Get a Horde framework include_path set up. */
require 'Horde/Autoloader/Default.php';

$httpClient = new Horde_Http_Client();
/* Authenticate. */
try {
    $response = $httpClient->post(
        'https://www.google.com/accounts/ClientLogin',
        'accountType=GOOGLE&service=blogger&source=horde-feed-blogger-example-1&Email=' . $username . '&Passwd=' . $password,
        array('Content-type', 'application/x-www-form-urlencoded')
    );
    if ($response->code !== 200) {
        throw new Horde_Feed_Exception('Expected response code 200, got ' . $response->code);
    }
} catch (Horde_Feed_Exception $e) {
    die('An error occurred authenticating: ' . $e->getMessage() . "\n");
}
$auth = null;
foreach (explode("\n", $response->getBody()) as $line) {
    $param = explode('=', $line);
    if ($param[0] == 'Auth') {
        $auth = $param[1];
    }
}
if (empty($auth)) {
    throw new Horde_Feed_Exception('Missing authentication token in the response!');
}

/* The base feed URI is the same as the POST URI, so just supply the
 * Horde_Feed_Entry_Atom object with that. */
$entry = new Horde_Feed_Entry_Atom();

/* Give the entry its initial values. */
$entry->{'atom:title'} = 'Entry 1';
$entry->{'atom:title'}['type'] = 'text';
$entry->{'atom:content'} = '1.1';
$entry->{'atom:content'}['type'] = 'text';

/* Do the initial post. */
try {
    $entry->save($blogUri, array('Authorization' => 'GoogleLogin auth=' . $auth));
} catch (Horde_Feed_Exception $e) {
    die('An error occurred posting: ' . $e->getMessage() . "\n");
}

/* $entry will be filled in with any elements returned by the
 * server (id, updated, link rel="edit", etc). */
echo "new id is: {$entry->id()}\n";
echo "entry last updated at: {$entry->updated()}\n";
echo "edit the entry at: {$entry->edit()}\n";
