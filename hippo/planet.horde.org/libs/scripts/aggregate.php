<?php
include_once('../../inc/config.inc.php');
include_once('aggregator.php');

$agg = new aggregator();
$agg->aggregateAllBlogs(isset($argv[1]) ? $argv[1] : null);
