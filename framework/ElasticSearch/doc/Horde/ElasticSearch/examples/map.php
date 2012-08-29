<?php
/**
 * Basic example for adding a mapping to ElasticSearch
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  ElasticSearch
 */

require 'Horde/Autoloader/Default.php';

$client = new Horde_ElasticSearch_Client('http://localhost:9200/', new Horde_Http_Client());
$twitter = new Horde_ElasticSearch_Index('twitter', $client);
$user = new Horde_ElasticSearch_Type('user', $twitter);
var_dump($user->map('{
    "properties" : {
        "name" : { "type" : "string" }
    }
}'));
