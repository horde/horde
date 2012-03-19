<?php
/**
 * @package Yaml
 */

require_once dirname(dirname(__DIR__)) . '/Yaml/__autoload.php';

$file = __DIR__ . '/example.yaml';
echo "$file loaded into PHP:\n";
var_dump(Horde_Yaml::loadFile($file));
