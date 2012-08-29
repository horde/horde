<?php
/**
 * Basic example for searching with ElasticSearch
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
$tweet = new Horde_ElasticSearch_Type('tweet', $twitter);
var_dump($tweet->search('elasticsearch'));
