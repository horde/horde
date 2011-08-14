<?php
/**
 * @package Yaml
 */

require_once dirname(dirname(dirname(__FILE__))) . '/Yaml/__autoload.php';

$file = dirname(__FILE__) . '/example.yaml';
echo "$file loaded into PHP:\n";
var_dump(Horde_Yaml::loadFile($file));
