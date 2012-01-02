#!/usr/bin/env php
<?php
/**
 * Simple Twitter client.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Twitter
 */

/* Keys - these are obtained when registering for the service */
$keys = array(
    'consumer_key'        => '*****',
    'consumer_secret'     => '*****',
    'access_token'        => '*****-*****',
    'access_token_secret' => '*****'
);

/* Enable autoloading. */
require 'Horde/Autoloader/Default.php';

/* Create the Twitter client */
$twitter = Horde_Service_Twitter::create(array('oauth' => $keys));

/* Do something cool.... */
try {
    $result = $twitter->statuses->update('Testing Horde/Twitter integration 2');
    print_r(Horde_Serialize::unserialize($result, Horde_Serialize::JSON));
} catch (Horde_Service_Twitter_Exception $e) {
    $error = Horde_Serialize::unserialize($e->getMessage(), Horde_Serialize::JSON);
    echo "$error->error\n";
}
